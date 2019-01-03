<?php

namespace App\Controller\ApiV1;

use App\Model\ApiAccessToken;
use App\Model\Storage\ApiAccessTokenStorage;
use App\Model\Storage\UserStorage;
use App\Model\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * @Route("/auth")
 */
class AuthController extends AbstractController
{
    /**
     * @Route("/login", name="api_v1_auth_login", methods={"POST"})
     *
     * @param Request $request
     * @param UserStorage $userStorage
     * @param ApiAccessTokenStorage $apiAccessTokenStorage
     * @param EncoderFactoryInterface $encoderFactory
     * @return Response JSON containing token
     * @throws \Exception
     */
    public function login(Request $request, UserStorage $userStorage, ApiAccessTokenStorage $apiAccessTokenStorage, EncoderFactoryInterface $encoderFactory): Response
    {
        if (!$request->request->has('login')) {
            throw new BadRequestHttpException('Mandatory parameter "login" is missing');
        }

        if (!$request->request->has('password')) {
            throw new BadRequestHttpException('Mandatory parameter "password" is missing');
        }

        $login = $request->request->get('login');

        /** @var User $user */
        $user = $userStorage->read($login);
        if ($user) {
            $encodedRequestPassword = $encoderFactory->getEncoder($user)->encodePassword($request->request->get('password'), $user->getSalt());
            if ($user->getPassword() === $encodedRequestPassword) {
                $token = bin2hex(random_bytes(64));
                $apiAccessToken = new ApiAccessToken();
                $apiAccessToken->setToken($token);
                $apiAccessToken->setUser($user);

                $apiAccessTokenStorage->insert($apiAccessTokenStorage->getKey($apiAccessToken), $apiAccessToken);

                return $this->json([
                    'access-token' => $token,
                ]);
            }
        }
        throw new BadRequestHttpException('Invalid credentials');
    }

    /**
     * @Route("/logout", name="api_v1_auth_logout", methods={"POST"})
     *
     * @param Request $request
     * @param ApiAccessTokenStorage $apiAccessTokenStorage
     * @return Response
     */
    public function logout(Request $request, ApiAccessTokenStorage $apiAccessTokenStorage): Response
    {
        $token = $request->headers->get('X-AUTH-TOKEN');
        $apiAccessTokenStorage->delete($token);

        return new Response();
    }
}

<?php

namespace App\Controller\ApiV1;

use App\Entity\User;
use App\Storage\Storage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/auth")
 */
class AuthController extends AbstractController
{
    /**
     * @Route("/login", name="api_v1_auth_login", methods={"POST"})
     *
     * @param Request $request
     * @param Storage $storage
     * @return Response JSON containing token
     * @throws \Exception
     */
    public function login(Request $request, Storage $storage): Response
    {
        if (!$request->request->has('login')) {
            throw new BadRequestHttpException('Mandatory parameter "login" is missing');
        }

        if (!$request->request->has('password')) {
            throw new BadRequestHttpException('Mandatory parameter "password" is missing');
        }

        $login = $request->request->get('login');

        $user = null;
        $userData = $storage->read('users', ['login' => $login]);
        if ($userData) {
            $user = new User($userData);
            if ($user->getData()['password'] !== $request->request->get('password')) {
                $user = null;
            }
        }

        $token = bin2hex(random_bytes(64));

        $storage->insert('api_access_tokens', ['token' => $token], ['user_login' => $login]);

        if ($user === null) {
            throw new BadRequestHttpException('Invalid credentials');
        }

        return $this->json([
            'access-token' => $token,
        ]);
    }

    /**
     * @Route("/logout", name="api_v1_auth_logout", methods={"POST"})
     *
     * @param Request $request
     * @param Storage $storage
     * @return Response
     */
    public function logout(Request $request, Storage $storage): Response
    {
        $token = $request->headers->get('X-AUTH-TOKEN');
        $storage->delete('api_access_tokens', ['token' => $token]);

        return new Response();
    }
}

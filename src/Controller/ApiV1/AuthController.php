<?php declare(strict_types=1);

namespace App\Controller\ApiV1;

use App\Model\User;
use App\ModelManager\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
     * @param UserManager $userManager
     * @param EncoderFactoryInterface $encoderFactory
     * @param SessionInterface $session
     * @return Response JSON containing token
     * @throws \Exception
     */
    public function login(Request $request, UserManager $userManager, SessionInterface $session, EncoderFactoryInterface $encoderFactory): Response
    {
        if ($this->getUser()) {
            throw new BadRequestHttpException('Already authorized');
        }
        if (!$request->request->has('login')) {
            throw new BadRequestHttpException('Mandatory parameter "login" is missing');
        }
        if (!$request->request->has('password')) {
            throw new BadRequestHttpException('Mandatory parameter "password" is missing');
        }

        $login = $request->request->get('login');

        /** @var User $user */
        $user = $userManager->read($login);
        if ($user) {
            $encodedRequestPassword = $encoderFactory->getEncoder($user)->encodePassword($request->request->get('password'), $user->getSalt());
            if ($user->getPassword() === $encodedRequestPassword) {
                $session->start();
                $session->set('login', $user->getLogin());
                $session->set('password', $user->getPassword());
                $session->save();

                return new Response('OK');
            }
        }
        return new Response('Invalid credentials', Response::HTTP_FORBIDDEN);
    }

    /**
     * @Route("/logout", name="api_v1_auth_logout", methods={"POST"})
     *
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     *
     * @param SessionInterface $session
     * @return Response
     */
    public function logout(SessionInterface $session): Response
    {
        $session->clear();
        $session->save();

        return new Response();
    }
}

<?php declare(strict_types=1);

namespace App\Controller\ApiV1;

use App\Model\User;
use App\ModelManager\UserManager;
use App\Security\LoginManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
     * @param UserManager $userManager
     * @param LoginManagerInterface $loginManager
     * @return Response
     */
    public function login(Request $request, UserManager $userManager, LoginManagerInterface $loginManager): Response
    {
        if ($this->getUser()) {
            return new Response('Already authorized', Response::HTTP_BAD_REQUEST);
        }
        if (!$request->request->has('login')) {
            return new Response('Mandatory parameter "login" is missing', Response::HTTP_BAD_REQUEST);
        }
        if (!$request->request->has('password')) {
            return new Response('Mandatory parameter "password" is missing', Response::HTTP_BAD_REQUEST);
        }

        $login = $request->request->get('login');

        /** @var User $user */
        $user = $userManager->read($login);
        if ($user) {
            $result = $loginManager->logInUser($request, 'main', $user);
            if ($result) {
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
     * @param Request $request
     * @param LoginManagerInterface $loginManager
     * @return Response
     */
    public function logout(Request $request, LoginManagerInterface $loginManager): Response
    {
        $loginManager->logOutUser($request);
        return new Response();
    }
}

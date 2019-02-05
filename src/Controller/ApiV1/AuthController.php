<?php declare(strict_types=1);

namespace App\Controller\ApiV1;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/auth")
 */
class AuthController extends AbstractController
{
    /**
     * @Route("/login", name="api_v1_auth_login", methods={"POST"})
     *
     * Note: no need to implement the login mechanism,
     * the security system intercepts the request and initiates the authentication process
     */
    public function login(): JsonResponse
    {
        return $this->json([], 204);
    }

    /**
     * @Route("/logout", name="api_v1_auth_logout", methods={"POST"})
     *
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function logout(): JsonResponse
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ($request->hasPreviousSession()) {
            $request->getSession()->invalidate();
        }

        $this->container->get('security.token_storage')->setToken();

        return $this->json([], 204);
    }
}

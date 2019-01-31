<?php declare(strict_types=1);

namespace App\Controller\ApiV1;

use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/auth")
 */
class AuthController
{
    /**
     * @Route("/login", name="api_v1_auth_login", methods={"POST"})
     */
    public function login()
    {
        //TODO
    }

    /**
     * @Route("/logout", name="api_v1_auth_logout", methods={"POST"})
     */
    public function logout()
    {
        //TODO
    }
}

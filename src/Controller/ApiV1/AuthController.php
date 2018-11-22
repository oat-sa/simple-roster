<?php

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
     * @Route("/token", name="api_v1_auth_token", methods={"POST"})
     */
    public function token()
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

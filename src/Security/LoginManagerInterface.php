<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

interface LoginManagerInterface
{
    /**
     * Logs in a user being currently authenticated anonimously
     *
     * @param Request $request
     * @param $firewallName
     * @param UserInterface $user
     * @return bool
     */
    public function logInUser(Request $request, $firewallName, UserInterface $user): bool;

    /**
     * Logs out a user being currently authenticated fully
     *
     * @param Request $request
     */
    public function logOutUser(Request $request): void;
}
<?php

namespace App\Entity;

class User extends Entity
{
    protected $requiredProperties = ['login', 'password'];

    public function getTable(): string
    {
        return 'users';
    }

    public function getKey()
    {
        return 'login';
    }

    public function validate()
    {
        // @todo
    }
}

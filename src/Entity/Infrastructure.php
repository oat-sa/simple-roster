<?php

namespace App\Entity;

class Infrastructure extends Entity
{
    protected $requiredProperties = ['id', 'lti_director_link', 'key', 'secret'];

    public function getTable(): string
    {
        return 'infrastructures';
    }

    public function getKey()
    {
        return 'id';
    }
}

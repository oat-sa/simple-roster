<?php

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

class User extends Entity implements UserInterface
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

    /**
     * @param String[] $uris
     */
    public function addAssignments(array $uris): void
    {
        if (!array_key_exists('assignments', $this->data)) {
            $this->data['assignments'] = [];
        }

        foreach ($uris as $uri) {
            if (!array_key_exists($uri, $this->data['assignments'])) {
                $this->data['assignments'][] = [
                    'line_item_tao_uri' => $uri,
                ];
            }
        }
    }

    public function getRoles()
    {
        return [];
    }

    public function getPassword()
    {
        $this->getData()['password'];
    }

    public function getSalt()
    {
        return $this->getData()['salt'];
    }

    public function getUsername()
    {
        return $this->getData()['login'];
    }

    public function eraseCredentials()
    {
        $this->data['password'] = '***';
        $this->data['salt'] = '***';
    }
}

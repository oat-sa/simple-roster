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
}

<?php declare(strict_types=1);

namespace App\Ingester\Ingester;

use App\Entity\EntityInterface;
use App\Entity\User;

class UserIngester extends AbstractIngester
{
    public function getName(): string
    {
        return 'user';
    }

    protected function createEntity(array $data): EntityInterface
    {
        $user = new User();

        return $user
            ->setUsername($data[0] ?? '')
            ->setPassword($data[1] ?? '')
            ->setPlainPassword($data[1] ?? '');
    }
}

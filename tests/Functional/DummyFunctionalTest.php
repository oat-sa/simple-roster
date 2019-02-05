<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DummyFunctionalTest extends WebTestCase
{
    use ReloadDatabaseTrait;

    public function test()
    {
        static::bootKernel();

        $users = static::$container->get(EntityManagerInterface::class)
            ->getRepository(User::class)
            ->findAll();

        $this->assertCount(10, $users);
    }
}
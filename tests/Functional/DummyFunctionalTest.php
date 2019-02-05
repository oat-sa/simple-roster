<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Tests\Traits\DatabaseFixturesTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DummyFunctionalTest extends WebTestCase
{
    use DatabaseFixturesTrait;

    public function test()
    {
        static::bootKernel();

        $users = static::$container->get(EntityManagerInterface::class)
            ->getRepository(User::class)
            ->findAll();

        $this->assertCount(1, $users);
    }
}

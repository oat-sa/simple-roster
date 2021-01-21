<?php

namespace OAT\SimpleRoster\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    /**
     * @codeCoverageIgnore Not in use yet
     */
    public function load(ObjectManager $manager): void
    {
        $manager->flush();
    }
}

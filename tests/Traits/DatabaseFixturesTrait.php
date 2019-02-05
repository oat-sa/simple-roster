<?php

namespace App\Tests\Traits;

trait DatabaseFixturesTrait
{
    use DatabaseTrait;

    protected function setUp()
    {
        $this->setUpDatabase();
        $this->setUpFixtures();
    }

    protected function setUpFixtures()
    {
        static::populateDatabase();
    }
}

<?php declare(strict_types=1);

namespace App\Tests\Traits;

trait DatabaseFixturesTrait
{
    use DatabaseTrait;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->setUpFixtures();
    }

    protected function setUpFixtures(): void
    {
        static::populateDatabase();
    }
}

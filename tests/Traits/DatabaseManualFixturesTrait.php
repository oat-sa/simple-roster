<?php declare(strict_types=1);

namespace App\Tests\Traits;

trait DatabaseManualFixturesTrait
{
    use DatabaseTrait;

    protected function loadFixtures(array $files): void
    {
        $loader = static::$container->get('fidry_alice_data_fixtures.loader.doctrine');
        $loader->load($files);
    }
}

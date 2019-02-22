<?php declare(strict_types=1);

namespace App\Tests\Traits;

use Fidry\AliceDataFixtures\Loader\PurgerLoader;

trait DatabaseManualFixturesTrait
{
    use DatabaseTrait;

    protected function loadFixtures(array $files): void
    {
        /** @var PurgerLoader $loader */
        $loader = static::$container->get('fidry_alice_data_fixtures.loader.doctrine');
        $loader->load($files);
    }
}

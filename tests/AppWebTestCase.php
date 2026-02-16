<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AppWebTestCase extends WebTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        while (true) {
            $previousHandler = set_exception_handler(static fn () => null);

            restore_exception_handler();

            if ($previousHandler === null) {
                break;
            }

            restore_exception_handler();
        }
    }
}

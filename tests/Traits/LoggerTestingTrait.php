<?php

declare(strict_types=1);

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

namespace App\Tests\Traits;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

trait LoggerTestingTrait
{
    /** @var TestHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->setUpTestLogHandler();
    }

    protected function setUpTestLogHandler(): void
    {
        static::ensureKernelTestCase();

        /** @var Logger $logger */
        $logger = static::$container->get(LoggerInterface::class);
        $this->handler = new TestHandler();

        $logger->pushHandler($this->handler);
    }

    public function getLogRecords(): array
    {
        return $this->handler->getRecords();
    }

    public function assertHasLogRecord(array $record, int $level): void
    {
        $this->assertTrue(
            $this->handler->hasRecord($record, $level),
            sprintf(
                'Failed asserting that Logger contains record: [%s] %s',
                Logger::getLevelName($level),
                json_encode($record)
            )
        );
    }

    public function assertHasLogRecordWithMessage(string $message, int $level): void
    {
        $this->assertTrue(
            $this->handler->hasRecordThatContains($message, $level),
            sprintf(
                'Failed asserting that Logger contains record: [%s] %s',
                Logger::getLevelName($level),
                $message
            )
        );
    }

    public function assertHasRecordThatPasses(callable $callable, int $level): void
    {
        $this->assertTrue($this->handler->hasRecordThatPasses($callable, $level));
    }
}

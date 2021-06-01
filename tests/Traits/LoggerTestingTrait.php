<?php

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

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Traits;

use LogicException;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

trait LoggerTestingTrait
{
    private TestHandler $handler;

    protected function setUp(): void
    {
        $this->setUpTestLogHandler();
    }

    protected function setUpTestLogHandler(string ...$channels): void
    {
        /** @var Logger $logger */
        $logger = self::$container->get(LoggerInterface::class);
        $this->handler = new TestHandler();

        $logger->pushHandler($this->handler);

        foreach ($channels as $channel) {
            $logger = self::$container->get(sprintf('monolog.logger.%s', $channel));

            if (!$logger instanceof Logger) {
                throw new LogicException(sprintf("Logger 'monolog.logger.%s' is not defined.", $channel));
            }

            $logger->pushHandler($this->handler);
        }
    }

    public function getLogRecords(): array
    {
        return $this->handler->getRecords();
    }

    public function assertHasLogRecord(array $record, int $level): void
    {
        self::assertTrue(
            $this->handler->hasRecord($record, $level),
            sprintf(
                'Failed asserting that Logger contains record: [%s] %s',
                Logger::getLevelName($level),
                json_encode($record, JSON_THROW_ON_ERROR, 512)
            )
        );
    }

    public function assertHasLogRecordWithMessage(string $message, int $level): void
    {
        self::assertTrue(
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
        self::assertTrue($this->handler->hasRecordThatPasses($callable, $level));
    }
}

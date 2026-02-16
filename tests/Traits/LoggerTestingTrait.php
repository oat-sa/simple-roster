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
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\After;

trait LoggerTestingTrait
{
    private TestHandler $handler;
    private array $registeredLoggers = [];

    protected function setUpTestLogHandler(string ...$channels): void
    {
        if (!self::getContainer()) {
            self::bootKernel();
        }

        /** @var Logger $mainLogger */
        $mainLogger = self::getContainer()->get(LoggerInterface::class);
        $this->handler = new TestHandler();

        if ($mainLogger instanceof Logger) {
            $mainLogger->pushHandler($this->handler);
            $this->registeredLoggers[] = $mainLogger;
        }

        foreach ($channels as $channel) {
            $logger = self::getContainer()->get(sprintf('monolog.logger.%s', $channel));

            if (!$logger instanceof Logger) {
                throw new LogicException(sprintf("Logger 'monolog.logger.%s' is not defined.", $channel));
            }

            $logger->pushHandler($this->handler);
            $this->registeredLoggers[] = $logger;
        }
    }

    public function getLogRecords(): array
    {
        return $this->handler->getRecords();
    }

    public function assertHasLogRecord(array $record, Level $level): void
    {
        $record = [
            'message' => (string)$record['message'],
            'context' => $record['context'] ?? [],
        ];

        self::assertTrue(
            $this->handler->hasRecord($record, $level),
            sprintf(
                'Failed asserting that Logger contains record: [%s] %s',
                $level->name,
                json_encode($record, JSON_THROW_ON_ERROR, 512)
            )
        );
    }

    public function assertHasLogRecordWithMessage(string $message, Level $level): void
    {
        self::assertTrue(
            $this->handler->hasRecordThatContains($message, $level),
            sprintf(
                'Failed asserting that Logger contains record: [%s] %s',
                $level->name,
                $message
            )
        );
    }

    public function assertHasRecordThatPasses(callable $callable, Level $level): void
    {
        self::assertTrue($this->handler->hasRecordThatPasses($callable, $level));
    }

    #[After]
    protected function tearDownLoggerTrait(): void
    {
        foreach ($this->registeredLoggers as $logger) {
            $logger->popHandler();
        }
        $this->registeredLoggers = [];
    }
}

<?php

/*
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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Cache;

use InvalidArgumentException;
use OAT\SimpleRoster\Message\WarmUpGroupedUserCacheMessage;
use OAT\SimpleRoster\Model\UsernameCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

class UserCacheWarmerService
{
    private MessageBusInterface $messageBus;
    private LoggerInterface $messengerLogger;
    private LoggerInterface $cacheWarmupLogger;
    private int $messagePayloadSize;

    public function __construct(
        MessageBusInterface $messageBus,
        LoggerInterface $messengerLogger,
        LoggerInterface $cacheWarmupLogger,
        int $userCacheWarmupMessagePayloadBatchSize
    ) {
        $this->messageBus = $messageBus;
        $this->messengerLogger = $messengerLogger;
        $this->cacheWarmupLogger = $cacheWarmupLogger;

        if ($userCacheWarmupMessagePayloadBatchSize < 1) {
            throw new InvalidArgumentException('Message payload size must be greater or equal to 1.');
        }

        $this->messagePayloadSize = $userCacheWarmupMessagePayloadBatchSize;
    }

    /**
     * @throws Throwable
     */
    public function process(UsernameCollection $usernames): void
    {
        $dispatchedUsernames = [];
        foreach ($usernames as $username) {
            $dispatchedUsernames[] = $username;

            if (count($dispatchedUsernames) === $this->messagePayloadSize) {
                $this->dispatchEvents($dispatchedUsernames);

                $dispatchedUsernames = [];
            }
        }

        if ($dispatchedUsernames) {
            $this->dispatchEvents($dispatchedUsernames);
        }
    }

    /**
     * @param string[] $usernames
     *
     * @throws Throwable
     */
    private function dispatchEvents(array $usernames): void
    {
        try {
            $message = new WarmUpGroupedUserCacheMessage($usernames);
            $this->messageBus->dispatch($message);

            $log = sprintf("Cache warmup event was successfully dispatched for users '%s'", implode(', ', $usernames));

            $this->messengerLogger->info($log);
            $this->cacheWarmupLogger->info($log);
        } catch (Throwable $exception) {
            $errorLog = sprintf(
                "Unsuccessful cache warmup for user '%s'. Error: %s",
                implode(', ', $usernames),
                $exception->getMessage()
            );

            $this->messengerLogger->error($errorLog);
            $this->cacheWarmupLogger->error($errorLog);

            throw $exception;
        }
    }
}

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
use OAT\SimpleRoster\Exception\CacheWarmupException;
use OAT\SimpleRoster\Message\WarmUpGroupedUserCacheMessage;
use OAT\SimpleRoster\Model\UsernameCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

class UserCacheWarmerService
{
    private const MAX_RETRY_COUNT = 5;

    private MessageBusInterface $messageBus;
    private LoggerInterface $messengerLogger;
    private LoggerInterface $cacheWarmupLogger;
    private int $messagePayloadSize;
    /**@var positive-int $retryWaitInterval */
    private int $retryWaitInterval;

    public function __construct(
        MessageBusInterface $messageBus,
        LoggerInterface $messengerLogger,
        LoggerInterface $cacheWarmupLogger,
        int $cacheWarmupPayloadBatchSize,
        int $userCacheWarmupRetryWaitInterval
    ) {
        $this->messageBus = $messageBus;
        $this->messengerLogger = $messengerLogger;
        $this->cacheWarmupLogger = $cacheWarmupLogger;

        if ($cacheWarmupPayloadBatchSize < 1) {
            throw new InvalidArgumentException('Message payload size must be greater or equal to 1.');
        }

        if ($userCacheWarmupRetryWaitInterval < 1000) {
            throw new InvalidArgumentException(
                'Retry wait time interval must be greater than or equal to 1000 microseconds.'
            );
        }

        $this->messagePayloadSize = $cacheWarmupPayloadBatchSize;
        $this->retryWaitInterval = $userCacheWarmupRetryWaitInterval;
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
                $this->dispatchEventsWithRetry($dispatchedUsernames);

                $dispatchedUsernames = [];
            }
        }

        if ($dispatchedUsernames) {
            $this->dispatchEventsWithRetry($dispatchedUsernames);
        }
    }

    /**
     * @param string[] $usernames
     *
     * @throws CacheWarmupException
     */
    private function dispatchEventsWithRetry(array $usernames): void
    {
        $attemptCount = 0;
        do {
            $isSuccessfulDispatch = false;

            try {
                $this->dispatchEvents($usernames);

                $isSuccessfulDispatch = true;
            } catch (Throwable $previousException) {
                if ($attemptCount === self::MAX_RETRY_COUNT) {
                    throw new CacheWarmupException(
                        sprintf(
                            'Unsuccessful cache warmup after %d retry attempts. Last error message: %s',
                            self::MAX_RETRY_COUNT,
                            $previousException->getMessage()
                        ),
                        $previousException->getCode(),
                        $previousException
                    );
                }

                $attemptCount++;

                $logMessage = sprintf(
                    "Unsuccessful cache warmup attempt. Retrying after %d microseconds... [%d/%d]",
                    $this->retryWaitInterval,
                    $attemptCount,
                    self::MAX_RETRY_COUNT
                );

                $this->messengerLogger->warning($logMessage);
                $this->cacheWarmupLogger->warning($logMessage);

                if ($this->retryWaitInterval > 0) {
                    usleep($this->retryWaitInterval);
                }
            }
        } while (!$isSuccessfulDispatch);
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

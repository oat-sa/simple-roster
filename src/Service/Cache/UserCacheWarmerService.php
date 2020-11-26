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

use OAT\SimpleRoster\Message\WarmUpGroupedUserCacheMessage;
use OAT\SimpleRoster\Model\UsernameCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

// TODO Rename?
class UserCacheWarmerService
{
    private const MESSAGE_SIZE = 100;

    /** @var MessageBusInterface */
    private $messageBus;

    /** @var LoggerInterface */
    private $messengerLogger;

    /** @var LoggerInterface */
    private $cacheWarmupLogger;

    public function __construct(
        MessageBusInterface $messageBus,
        LoggerInterface $messengerLogger,
        LoggerInterface $cacheWarmupLogger
    ) {
        $this->messageBus = $messageBus;
        $this->messengerLogger = $messengerLogger;
        $this->cacheWarmupLogger = $cacheWarmupLogger;
    }

    /**
     * @throws Throwable
     */
    public function process(UsernameCollection $usernames): void
    {
        $dispatchedUsernames = [];
        foreach ($usernames as $username) {
            $dispatchedUsernames[] = $username;

            if (count($dispatchedUsernames) === self::MESSAGE_SIZE) {
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

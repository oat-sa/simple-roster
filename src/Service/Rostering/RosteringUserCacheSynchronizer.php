<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

use OAT\SimpleRoster\Model\UsernameCollection;
use OAT\SimpleRoster\Service\Cache\UserCacheWarmerService;
use Psr\Log\LoggerInterface;
use Throwable;

final class RosteringUserCacheSynchronizer
{
    /** @var array<string, bool> username => needsWarmup */
    private array $users = [];

    public function __construct(
        private readonly UserCacheInvalidator $userCacheInvalidator,
        private readonly UserCacheWarmerService $userCacheWarmerService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function reset(): void
    {
        $this->users = [];
    }

    public function markForWarmup(string $username): void
    {
        $this->users[$username] = true;
    }

    public function markForInvalidationOnly(string $username): void
    {
        if (isset($this->users[$username])) {
            return;
        }

        $this->users[$username] = false;
    }

    public function synchronize(): void
    {
        $usernamesForInvalidation = array_keys($this->users);
        $usernamesForWarmup = array_keys(array_filter($this->users));

        foreach ($usernamesForInvalidation as $username) {
            try {
                $this->userCacheInvalidator->invalidate($username);
            } catch (Throwable $exception) {
                $this->logger->warning(
                    sprintf("Unable to invalidate cache for user '%s' after rostering import.", $username),
                    ['exception' => $exception]
                );
            }
        }

        if ($usernamesForWarmup === []) {
            return;
        }

        try {
            $this->userCacheWarmerService->process(new UsernameCollection(...$usernamesForWarmup));
        } catch (Throwable $exception) {
            $this->logger->warning(
                sprintf('Unable to warm up cache for %d users after rostering import.', count($usernamesForWarmup)),
                [
                    'usernames' => $usernamesForWarmup,
                    'exception' => $exception,
                ]
            );
        }
    }
}

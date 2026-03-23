<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Generator\UserCacheIdGenerator;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException as CacheInvalidArgumentException;
use Psr\Log\LoggerInterface;

final class UserCacheInvalidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserCacheIdGenerator $cacheIdGenerator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function invalidateAfterUserChange(string $username): void
    {
        $this->invalidate($username, 'user');
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function invalidateAfterAssignmentChange(string $username): void
    {
        $this->invalidate($username, 'assignment');
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    private function invalidate(string $username, string $trigger): void
    {
        if ($username === '') {
            throw new InvalidArgumentException('Username cannot be empty when invalidating user cache.');
        }

        $resultCache = $this->entityManager->getConfiguration()->getResultCache();
        if (!$resultCache instanceof CacheItemPoolInterface) {
            throw new DoctrineResultCacheImplementationNotFoundException(
                'Doctrine result cache implementation is not configured.'
            );
        }

        $cacheKey = $this->cacheIdGenerator->generate($username);
        $resultCache->deleteItem($cacheKey);

        $this->logger->info(
            sprintf(
                "Rostering cache for user '%s' was successfully invalidated after %s change.",
                $username,
                $trigger
            ),
            [
                'cacheKey' => $cacheKey,
                'trigger' => $trigger,
            ]
        );
    }
}


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
    public function invalidate(string $username): void
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
                "User cache for user '%s' was successfully invalidated.",
                $username
            ),
            [
                'cacheKey' => $cacheKey,
            ]
        );
    }
}

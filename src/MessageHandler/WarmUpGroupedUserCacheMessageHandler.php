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

namespace OAT\SimpleRoster\MessageHandler;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use OAT\SimpleRoster\Exception\CacheWarmupException;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Generator\UserCacheIdGenerator;
use OAT\SimpleRoster\Message\WarmUpGroupedUserCacheMessage;
use OAT\SimpleRoster\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Throwable;

class WarmUpGroupedUserCacheMessageHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UserCacheIdGenerator $cacheIdGenerator;
    private CacheProvider $resultCacheImplementation;
    private LoggerInterface $messengerLogger;
    private LoggerInterface $cacheWarmupLogger;
    private int $cacheTtl;

    /**
     * @throws DoctrineResultCacheImplementationNotFoundException
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserCacheIdGenerator $cacheIdGenerator,
        LoggerInterface $messengerLogger,
        LoggerInterface $cacheWarmupLogger,
        int $userWithAssignmentsCacheTtl
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->cacheIdGenerator = $cacheIdGenerator;
        $this->messengerLogger = $messengerLogger;
        $this->cacheWarmupLogger = $cacheWarmupLogger;
        $this->cacheTtl = $userWithAssignmentsCacheTtl;

        $resultCacheImplementation = $entityManager->getConfiguration()->getResultCacheImpl();

        if (!$resultCacheImplementation instanceof CacheProvider) {
            throw new DoctrineResultCacheImplementationNotFoundException(
                'Doctrine result cache implementation is not configured.'
            );
        }

        $this->resultCacheImplementation = $resultCacheImplementation;
    }

    /**
     * @throws Throwable
     */
    public function __invoke(WarmUpGroupedUserCacheMessage $message): void
    {
        foreach ($message->getUsernames() as $username) {
            try {
                $this->refreshCacheForUsername($username);
            } catch (Throwable $exception) {
                $errorLog = sprintf(
                    "Unsuccessful cache warmup for user '%s'. Error: %s",
                    $username,
                    $exception->getMessage()
                );

                $this->messengerLogger->error($errorLog);
                $this->cacheWarmupLogger->error($errorLog);

                throw $exception;
            } finally {
                $this->entityManager->clear();
            }
        }
    }

    /**
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     * @throws CacheWarmupException
     */
    private function refreshCacheForUsername(string $username): void
    {
        $resultCacheId = $this->cacheIdGenerator->generate($username);
        $this->resultCacheImplementation->delete($resultCacheId);

        // Refresh by query
        $this->userRepository->findByUsernameWithAssignments($username);

        if (!$this->resultCacheImplementation->contains($resultCacheId)) {
            throw new CacheWarmupException(
                sprintf(
                    "Result cache does not contain key '%s' after warmup.",
                    $resultCacheId
                )
            );
        }

        $logMessage = sprintf("Result cache for user '%s' was successfully warmed up", $username);
        $logContext = [
            'cacheKey' => $resultCacheId,
            'cacheTtl' => $this->cacheTtl,
        ];

        $this->messengerLogger->info($logMessage, $logContext);
        $this->cacheWarmupLogger->info($logMessage, $logContext);
    }
}

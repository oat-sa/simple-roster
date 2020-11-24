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
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Generator\UserCacheIdGenerator;
use OAT\SimpleRoster\Message\RefreshUserCacheMessage;
use OAT\SimpleRoster\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RefreshUserCacheMessageHandler implements MessageHandlerInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var UserRepository */
    private $userRepository;

    /** @var UserCacheIdGenerator */
    private $cacheIdGenerator;

    /** @var CacheProvider */
    private $resultCacheImplementation;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @throws DoctrineResultCacheImplementationNotFoundException
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserCacheIdGenerator $cacheIdGenerator,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->cacheIdGenerator = $cacheIdGenerator;
        $this->logger = $logger;

        $resultCacheImplementation = $entityManager->getConfiguration()->getResultCacheImpl();

        if (!$resultCacheImplementation instanceof CacheProvider) {
            throw new DoctrineResultCacheImplementationNotFoundException(
                'Doctrine result cache implementation is not configured.'
            );
        }

        $this->resultCacheImplementation = $resultCacheImplementation;
    }

    public function __invoke(RefreshUserCacheMessage $message): void
    {
        $username = $message->getUsername();
        $resultCacheId = $this->cacheIdGenerator->generate($message->getUsername());
        $this->resultCacheImplementation->delete($resultCacheId);

        // Refresh by query
        $this->userRepository->findByUsernameWithAssignments($username);
        $this->entityManager->clear();

        if (!$this->resultCacheImplementation->contains($resultCacheId)) {
            $this->logger->error( // TODO: log channel!
                sprintf(
                    "Unsuccessful cache warmup for user '%s' (cache id: '%s')",
                    $username,
                    $resultCacheId
                )
            );
        }
    }
}

<?php

declare(strict_types=1);

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

namespace App\Command\Cache;

use App\Command\CommandProgressBarFormatterTrait;
use App\Entity\User;
use App\Exception\DoctrineResultCacheImplementationNotFoundException;
use App\Generator\UserCacheIdGenerator;
use App\Repository\UserRepository;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DoctrineResultCacheWarmerCommand extends Command
{
    use CommandProgressBarFormatterTrait;

    public const NAME = 'roster:doctrine-result-cache:warmup';

    private const OPTION_USER_IDS = 'user-ids';
    private const OPTION_LINE_ITEM_IDS = 'line-item-ids';
    private const OPTION_BATCH_SIZE = 'batch-size';

    private const DEFAULT_BATCH_SIZE = 1000;

    /** @var Cache */
    private $resultCacheImplementation;

    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    /** @var UserRepository */
    private $userRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var SymfonyStyle */
    private $symfonyStyle;

    /** @var int */
    private $batchSize;

    /** @var array int[] */
    private $userIds = [];

    /** @var array */
    private $lineItemIds = [];

    /**
     * @throws DoctrineResultCacheImplementationNotFoundException
     */
    public function __construct(
        UserCacheIdGenerator $userCacheIdGenerator,
        Configuration $doctrineConfiguration,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct(self::NAME);

        $this->userCacheIdGenerator = $userCacheIdGenerator;
        $this->entityManager = $entityManager;

        $resultCacheImplementation = $doctrineConfiguration->getResultCacheImpl();

        if ($resultCacheImplementation === null) {
            throw new DoctrineResultCacheImplementationNotFoundException(
                'Doctrine result cache implementation is not configured.'
            );
        }

        $this->resultCacheImplementation = $resultCacheImplementation;

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $this->userRepository = $userRepository;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Warms up doctrine result cache.');

        $this->addOption(
            self::OPTION_BATCH_SIZE,
            'b',
            InputOption::VALUE_REQUIRED,
            'Number of assignments to process per batch',
            self::DEFAULT_BATCH_SIZE
        );

        $this->addOption(
            self::OPTION_USER_IDS,
            'u',
            InputOption::VALUE_REQUIRED,
            'User id list filter.'
        );

        $this->addOption(
            self::OPTION_LINE_ITEM_IDS,
            'l',
            InputOption::VALUE_REQUIRED,
            'Line item id list filter.'
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);
        $this->symfonyStyle->title('Simple Roster - Doctrine Result Cache Warmer');

        $this->initializeBatchSizeOption($input);

        if ($input->getOption(self::OPTION_USER_IDS)) {
            $this->initializeUserIdsOption($input);
        }

        if ($input->getOption(self::OPTION_LINE_ITEM_IDS)) {
            $this->initializeLineItemIdsOption($input);
        }

        if (!empty($this->userIds) && !empty($this->lineItemIds)) {
            throw new InvalidArgumentException(
                sprintf(
                    "'%s' and '%s' are exclusive options, please specify only one of them",
                    self::OPTION_USER_IDS,
                    self::OPTION_LINE_ITEM_IDS
                )
            );
        }
    }

    /**
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $offset = 0;
        $numberOfWarmedUpCacheEntries = 0;

        $this->symfonyStyle->note('Calculating total number of entries to warm up...');

        $numberOfTotalUsers = $this->getNumberOfTotalUsers();

        if ($numberOfTotalUsers === 0) {
            $this->symfonyStyle->success('No matching cache entries, exiting.');

            return 0;
        }

        $this->symfonyStyle->note('Warming up doctrine result cache...');

        $progressBar = $this->createNewFormattedProgressBar($output);

        $progressBar->setMaxSteps($numberOfTotalUsers);
        $progressBar->start();

        do {
            $iterateResult = $this
                ->getFindAllUsernameQuery($offset)
                ->iterate();

            foreach ($iterateResult as $row) {
                $this->warmUpResultCacheForUserName(current($row)['username']);

                $numberOfWarmedUpCacheEntries++;

                unset($row);
            }

            if ($numberOfWarmedUpCacheEntries % $this->batchSize === 0) {
                $progressBar->advance($this->batchSize);
            }

            $offset += $this->batchSize;
        } while ($offset <= $numberOfTotalUsers + $this->batchSize);

        $progressBar->finish();

        $this->symfonyStyle->success(
            sprintf(
                '%s result cache entries have been successfully warmed up.',
                $numberOfWarmedUpCacheEntries
            )
        );

        return 0;
    }

    private function getFindAllUsernameQuery(int $offset): Query
    {
        $queryBuilder = $this->entityManager
            ->createQueryBuilder()
            ->select('u.username')
            ->from(User::class, 'u');

        if (!empty($this->userIds)) {
            $queryBuilder
                ->where('u.id IN (:userIds)')
                ->setParameter('userIds', $this->userIds);
        }

        if ($this->lineItemIds) {
            $queryBuilder
                ->distinct()
                ->leftJoin('u.assignments', 'a')
                ->leftJoin('a.lineItem', 'l')
                ->leftJoin('l.infrastructure', 'i')
                ->where('l.id IN (:lineItemIds)')
                ->setParameter('lineItemIds', $this->lineItemIds);
        }

        return $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($this->batchSize)
            ->getQuery()
            ->setHydrationMode(Query::HYDRATE_SINGLE_SCALAR);
    }

    private function getNumberOfTotalUsers(): int
    {
        $queryBuilder =
            $this->entityManager
                ->createQueryBuilder()
                ->select('COUNT(u.id) AS number_of_users')
                ->from(User::class, 'u');

        if ($this->userIds) {
            $queryBuilder
                ->where('u.id IN (:userIds)')
                ->setParameter('userIds', $this->userIds);
        }

        if ($this->lineItemIds) {
            $queryBuilder
                ->leftJoin('u.assignments', 'a')
                ->leftJoin('a.lineItem', 'l')
                ->leftJoin('l.infrastructure', 'i')
                ->where('l.id IN (:lineItemIds)')
                ->setParameter('lineItemIds', $this->lineItemIds);
        }

        $result = $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();

        return null === $result ? 0 : (int)$result['number_of_users'];
    }

    private function warmUpResultCacheForUserName(string $username): void
    {
        $resultCacheId = $this->userCacheIdGenerator->generate($username);
        $this->resultCacheImplementation->delete($resultCacheId);

        // Refresh by query
        $user = $this->userRepository->getByUsernameWithAssignments($username);
        $this->entityManager->clear();

        unset($user);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function initializeBatchSizeOption(InputInterface $input): void
    {
        $this->batchSize = (int)$input->getOption(self::OPTION_BATCH_SIZE);
        if ($this->batchSize < 1) {
            throw new InvalidArgumentException(
                sprintf("Invalid '%s' option received.", self::OPTION_BATCH_SIZE)
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function initializeLineItemIdsOption(InputInterface $input): void
    {
        $this->lineItemIds = array_filter(
            explode(',', (string)$input->getOption(self::OPTION_LINE_ITEM_IDS)),
            'is_numeric'
        );

        if (empty($this->lineItemIds)) {
            throw new InvalidArgumentException(
                sprintf("Invalid '%s' option received.", self::OPTION_LINE_ITEM_IDS)
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function initializeUserIdsOption(InputInterface $input): void
    {
        $this->userIds = array_filter(
            explode(',', (string)$input->getOption(self::OPTION_USER_IDS)),
            'is_numeric'
        );

        if (empty($this->userIds)) {
            throw new InvalidArgumentException(
                sprintf("Invalid '%s' option received.", self::OPTION_USER_IDS)
            );
        }
    }
}

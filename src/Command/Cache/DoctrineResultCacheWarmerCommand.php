<?php declare(strict_types=1);
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

use App\Command\CommandWatcherTrait;
use App\Entity\User;
use App\Generator\UserCacheIdGenerator;
use App\Repository\UserRepository;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DoctrineResultCacheWarmerCommand extends Command
{
    use CommandWatcherTrait;

    public const NAME = 'roster:doctrine-result-cache:warmup';

    private const OPTION_USER_IDS = 'user-ids';
    private const OPTION_LINE_ITEM_IDS = 'line-item-ids';
    private const OPTION_BATCH_SIZE = 'batch-size';

    private const DEFAULT_BATCH_SIZE = 1000;

    /** @var Cache|null */
    private $resultCacheImplementation;

    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    /** @var UserRepository */
    private $userRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var SymfonyStyle */
    private $symfonyStyle;

    /** @var ConsoleSectionOutput */
    private $consoleSectionOutput;

    /** @var int */
    private $batchSize;

    /** @var array int[] */
    private $userIds = [];

    /** @var array */
    private $lineItemIds = [];

    public function __construct(
        UserCacheIdGenerator $userCacheIdGenerator,
        Configuration $doctrineConfiguration,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct(self::NAME);

        $this->userCacheIdGenerator = $userCacheIdGenerator;
        $this->resultCacheImplementation = $doctrineConfiguration->getResultCacheImpl();
        $this->entityManager = $entityManager;

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $this->userRepository = $userRepository;
    }

    protected function configure()
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
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $consoleOutput = $this->ensureConsoleOutput($output);

        $this->symfonyStyle = new SymfonyStyle($input, $consoleOutput);
        $this->consoleSectionOutput = $consoleOutput->section();

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->startWatch(self::NAME, __FUNCTION__);

        $offset = 0;
        $numberOfWarmedUpCacheEntries = 0;

        $this->symfonyStyle->note('Warming up doctrine result cache...');
        $this->consoleSectionOutput->writeln('Number of warmed up cache entries: 0');
        $numberOfTotalUsers = $this->getNumberOfTotalUsers();

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
                $this->consoleSectionOutput->overwrite(
                    sprintf('Number of warmed up cache entries: %s', $numberOfWarmedUpCacheEntries)
                );
            }

            $offset += $this->batchSize;
        } while ($offset <= $numberOfTotalUsers + $this->batchSize);

        $this->symfonyStyle->success(
            sprintf(
                '%s result cache entries have been successfully warmed up.',
                $numberOfWarmedUpCacheEntries
            )
        );

        $this->symfonyStyle->note(sprintf('Took: %s', $this->stopWatch(self::NAME)));

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

        return (int)$result['number_of_users'];
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
     * @throws LogicException
     */
    private function ensureConsoleOutput(OutputInterface $output): ConsoleOutputInterface
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new LogicException(
                sprintf(
                    "Output must be instance of '%s' because of section usage.",
                    ConsoleOutputInterface::class
                )
            );
        }

        return $output;
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

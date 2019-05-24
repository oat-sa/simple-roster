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
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class DoctrineResultCacheWarmerCommand extends Command
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

    /** @var int */
    private $numberOfWarmedUpCacheEntries = 0;

    /** @var int */
    private $batchSize = 0;

    /** @var ConsoleSectionOutput */
    private $progressOutputSection;

    /** @var int[] */
    private $userIds;

    /** @var int[] */
    private $lineItemIds;

    /** @var SymfonyStyle */
    private $consoleOutput;

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
            InputOption::VALUE_OPTIONAL,
            'User ids. Comma separated'
        );

        $this->addOption(
            self::OPTION_LINE_ITEM_IDS,
            'l',
            InputOption::VALUE_OPTIONAL,
            'Line item ids. Comma separated'
        );
    }

    /**
     * @param int $numberOfWarmedUpCacheEntries
     */
    public function addNumberOfWarmedUpCacheEntries(int $numberOfWarmedUpCacheEntries): void
    {
        $this->numberOfWarmedUpCacheEntries += $numberOfWarmedUpCacheEntries;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (($userIds = $input->getOption(self::OPTION_USER_IDS)) !== null) {
            $this->userIds = array_filter(filter_var_array(explode(',', $userIds), FILTER_VALIDATE_INT), 'is_int');

            if (empty($this->userIds)) {
                throw new RuntimeException(
                    sprintf('Option %s is empty. Should contain at least one value', self::OPTION_USER_IDS)
                );
            }
        }

        if (($lineItemIds = $input->getOption(self::OPTION_LINE_ITEM_IDS)) !== null) {
            $this->lineItemIds = array_filter(filter_var_array(explode(',', $lineItemIds), FILTER_VALIDATE_INT), 'is_int');

            if (empty($this->lineItemIds)) {
                throw new RuntimeException(
                    sprintf('Option %s is empty. Should contain at least one value', self::OPTION_LINE_ITEM_IDS)
                );
            }
        }

        $this->batchSize = (int)$input->getOption(self::OPTION_BATCH_SIZE);
        if ($this->batchSize < 1) {
            throw new InvalidArgumentException("Invalid 'batch-size' argument received.");
        }

        $consoleOutput = $this->ensureConsoleOutput($output);
        $this->consoleOutput = new SymfonyStyle($input, $consoleOutput);
        $this->progressOutputSection = $consoleOutput->section();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     *
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->startWatch(self::NAME, __FUNCTION__);

        $this->consoleOutput->note('Warming up doctrine result cache...');
        $this->progressOutputSection->writeln('Number of warmed up cache entries: 0');

        if (!($this->hasSpecificLineItems() || $this->hasSpecificUsers())) {
            $this->warmUpAll();
        }

        if ($this->hasSpecificUsers()) {
            $this->warmUpSpecificUsers();
        }

        if ($this->hasSpecificLineItems()) {
            $this->warmUpByLineItemIds();
        }

        $this->echoAffectedEntries(true);

        $this->consoleOutput->success(
            sprintf(
                '%s result cache entries have been successfully warmed up.',
                $this->getNumberOfWarmedUpCacheEntries()
            )
        );

        $this->consoleOutput->note(sprintf('Took: %s', $this->stopWatch(self::NAME)));

        return 0;
    }

    /**
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    private function warmUpByLineItemIds(): void
    {
        $paginator = $this->findUserNamesByLineItemIds($this->lineItemIds, 0);
        $countOfUsersToBeUpdated = $paginator->count();
        $updated = 0;

        while ($countOfUsersToBeUpdated - $updated > 0) {
            $paginator = $this->findUserNamesByLineItemIds($this->lineItemIds, $updated);
            /** @var User $user */
            foreach ($paginator as $user) {
                $this->warmUpResultCacheForUserName($user->getUsername());
                $this->echoAffectedEntries();
                $updated++;
            }
        }
    }

    private function hasSpecificUsers(): bool
    {
        return $this->userIds !== null;
    }

    private function hasSpecificLineItems(): bool
    {
        return $this->lineItemIds !== null;
    }

    /**
     * @param IterableResult $iterateResult
     *
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    private function warmUp(IterableResult $iterateResult): void
    {
        foreach ($iterateResult as $row) {
            $this->warmUpResultCacheForUserName(current($row)['username']);
            $this->echoAffectedEntries();
        }
    }

    private function echoAffectedEntries(bool $force = false): void
    {
        if ($force || $this->numberOfWarmedUpCacheEntries % $this->batchSize === 0) {
            $this->progressOutputSection->overwrite(
                sprintf('Number of warmed up cache entries: %s', $this->numberOfWarmedUpCacheEntries)
            );
        }
    }

    /**
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    private function warmUpSpecificUsers(): void
    {
        $offset = 0;
        $numberOfTotalUsers = count($this->userIds);

        do {
            $userIds = array_slice($this->userIds, $offset, $this->batchSize);

            $this->warmUp($this->findUsersNameById($userIds)->iterate());

            $offset += $this->batchSize;
        } while ($offset < $numberOfTotalUsers);
    }

    /**
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    private function warmUpAll(): void
    {
        $offset = 0;
        $numberOfTotalUsers = $this->getTotalNumberOfUsers();

        do {
            $this->warmUp($this->findAllUsersNameQuery($offset)->iterate());

            $offset += $this->batchSize;
        } while ($offset <= $numberOfTotalUsers + $this->batchSize);
    }

    private function findAllUsersNameQuery(int $offset): Query
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('u.username')
            ->from(User::class, 'u')
            ->setFirstResult($offset)
            ->setMaxResults($this->batchSize)
            ->getQuery()
            ->setHydrationMode(Query::HYDRATE_SINGLE_SCALAR);
    }

    private function findUsersNameById(array $userIds): Query
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('u.username')
            ->from(User::class, 'u')
            ->where('u.id IN (:users)')
            ->setParameter('users', $userIds)
            ->getQuery()
            ->setHydrationMode(Query::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * @param array $lineItemIds
     * @param int   $offset
     *
     * @return Paginator
     */
    private function findUserNamesByLineItemIds(array $lineItemIds, int $offset): Paginator
    {
        $query = $this->entityManager
            ->createQueryBuilder()
            ->select('u')
            ->setFirstResult($offset)
            ->setMaxResults($this->batchSize)
            ->from(User::class, 'u')
            ->innerJoin('u.assignments', 'a')
            ->innerJoin('a.lineItem', 'l')
            ->where('l.id IN (:lineItemIds)')
            ->setParameter('lineItemIds', $lineItemIds)
            ->getQuery();

        return new Paginator($query, false);
    }

    /**
     * @return int
     *
     * @throws NonUniqueResultException
     */
    private function getTotalNumberOfUsers(): int
    {
        return (int)$this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param string $username
     *
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    private function warmUpResultCacheForUserName(string $username): void
    {
        $resultCacheId = $this->userCacheIdGenerator->generate($username);
        $this->resultCacheImplementation->delete($resultCacheId);

        // Refresh by query
        $user = $this->userRepository->getByUsernameWithAssignments($username);
        $this->entityManager->clear();
        unset($user);

        $this->addNumberOfWarmedUpCacheEntries(1);
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
}

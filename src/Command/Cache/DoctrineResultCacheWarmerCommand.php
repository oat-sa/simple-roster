<?php

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

declare(strict_types=1);

namespace App\Command\Cache;

use App\Command\CommandProgressBarFormatterTrait;
use App\Exception\DoctrineResultCacheImplementationNotFoundException;
use App\Generator\UserCacheIdGenerator;
use App\Repository\Criteria\EuclideanDivisionCriterion;
use App\Repository\Criteria\FindUserCriteria;
use App\Repository\UserRepository;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DoctrineResultCacheWarmerCommand extends Command
{
    use CommandProgressBarFormatterTrait;

    public const NAME = 'roster:doctrine-result-cache:warmup';

    private const OPTION_USERNAMES = 'usernames';
    private const OPTION_LINE_ITEM_SLUGS = 'line-item-slugs';
    private const OPTION_BATCH_SIZE = 'batch-size';
    private const OPTION_MODULO = 'modulo';
    private const OPTION_REMAINDER = 'remainder';

    private const DEFAULT_BATCH_SIZE = 1000;

    /** @var CacheProvider */
    private $resultCacheImplementation;

    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    /** @var UserRepository */
    private $userRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var SymfonyStyle */
    private $symfonyStyle;

    /** @var int */
    private $batchSize;

    /** @var array string[] */
    private $usernames = [];

    /** @var array */
    private $lineItemSlugs = [];

    /** @var int|null */
    private $modulo;

    /** @var int|null */
    private $remainder;

    /**
     * @throws DoctrineResultCacheImplementationNotFoundException
     */
    public function __construct(
        UserRepository $userRepository,
        UserCacheIdGenerator $userCacheIdGenerator,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        parent::__construct(self::NAME);

        $this->userCacheIdGenerator = $userCacheIdGenerator;
        $this->entityManager = $entityManager;
        $resultCacheImplementation = $this->entityManager->getConfiguration()->getResultCacheImpl();

        if (!$resultCacheImplementation instanceof CacheProvider) {
            throw new DoctrineResultCacheImplementationNotFoundException(
                'Doctrine result cache implementation is not configured.'
            );
        }

        $this->resultCacheImplementation = $resultCacheImplementation;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
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
            self::OPTION_USERNAMES,
            'u',
            InputOption::VALUE_REQUIRED,
            'Username filter.'
        );

        $this->addOption(
            self::OPTION_LINE_ITEM_SLUGS,
            'l',
            InputOption::VALUE_REQUIRED,
            'Line item slug filter.'
        );

        $this->addOption(
            self::OPTION_MODULO,
            'm',
            InputOption::VALUE_REQUIRED,
            "Modulo (M) of Euclidean division A = M*Q + R (0 ≤ R < |M|), where A = user id, Q = quotient, " .
            "R = 'remainder' option"
        );

        $this->addOption(
            self::OPTION_REMAINDER,
            'r',
            InputOption::VALUE_REQUIRED,
            "Remainder (R) of Euclidean division A = M*Q + R (0 ≤ R < |M|), where A = user id, Q = quotient, " .
            "M = 'modulo' option"
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

        if ($input->getOption(self::OPTION_USERNAMES)) {
            $this->initializeUsernamesOption($input);
        }

        if ($input->getOption(self::OPTION_LINE_ITEM_SLUGS)) {
            $this->initializeLineItemIdsOption($input);
        }

        if (!empty($this->usernames) && !empty($this->lineItemSlugs)) {
            throw new InvalidArgumentException(
                sprintf(
                    "'%s' and '%s' are exclusive options, please specify only one of them",
                    self::OPTION_USERNAMES,
                    self::OPTION_LINE_ITEM_SLUGS
                )
            );
        }

        if ($input->getOption(self::OPTION_MODULO)) {
            $this->initializeModuloOption($input);
        }

        if ($input->getOption(self::OPTION_REMAINDER) !== null) {
            $this->initializeRemainderOption($input);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $numberOfWarmedUpCacheEntries = 0;

        $this->symfonyStyle->note('Calculating total number of entries to warm up...');

        $criteria = $this->getFindUserCriteria();
        $numberOfTotalUsers = $this->userRepository->countByCriteria($criteria);

        if ($numberOfTotalUsers === 0) {
            $this->symfonyStyle->success('No matching cache entries, exiting.');

            return 0;
        }

        $this->symfonyStyle->note('Warming up doctrine result cache...');

        $progressBar = $this->createNewFormattedProgressBar($output);

        $progressBar->setMaxSteps($numberOfTotalUsers);
        $progressBar->start();

        $lastUserId = null;
        do {
            $resultSet = $this->userRepository->findAllUsernamePaged($this->batchSize, $lastUserId, $criteria);

            foreach ($resultSet as $username) {
                $this->warmUpResultCacheForUserName($username);

                $numberOfWarmedUpCacheEntries++;
            }

            if ($numberOfWarmedUpCacheEntries % $this->batchSize === 0) {
                $progressBar->advance($this->batchSize);
            }

            $lastUserId = $resultSet->getLastUserId();
        } while ($resultSet->hasMore());

        $progressBar->finish();

        $this->symfonyStyle->success(
            sprintf(
                '%s result cache entries have been successfully warmed up.',
                $numberOfWarmedUpCacheEntries
            )
        );

        return 0;
    }

    private function getFindUserCriteria(): FindUserCriteria
    {
        $criteria = new FindUserCriteria();

        if (!empty($this->usernames)) {
            $criteria->addUsernameCriterion(...$this->usernames);
        }

        if (!empty($this->lineItemSlugs)) {
            $criteria->addLineItemSlugCriterion(...$this->lineItemSlugs);
        }

        if ($this->modulo && $this->remainder !== null) {
            $criteria->addEuclideanDivisionCriterion(
                new EuclideanDivisionCriterion($this->modulo, $this->remainder)
            );
        }

        return $criteria;
    }

    private function warmUpResultCacheForUserName(string $username): void
    {
        $resultCacheId = $this->userCacheIdGenerator->generate($username);
        $this->resultCacheImplementation->delete($resultCacheId);

        // Refresh by query
        $user = $this->userRepository->findByUsernameWithAssignments($username);
        $this->entityManager->clear();

        if (!$this->resultCacheImplementation->contains($resultCacheId)) {
            $this->logger->error(
                sprintf(
                    "Unsuccessful cache warmup for user '%s' (cache id: '%s')",
                    $username,
                    $resultCacheId
                )
            );
        }

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
        $this->lineItemSlugs = array_filter(
            explode(',', (string)$input->getOption(self::OPTION_LINE_ITEM_SLUGS)),
            static function ($value) {
                return !empty($value) && is_string($value);
            }
        );

        if (empty($this->lineItemSlugs)) {
            throw new InvalidArgumentException(
                sprintf("Invalid '%s' option received.", self::OPTION_LINE_ITEM_SLUGS)
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function initializeUsernamesOption(InputInterface $input): void
    {
        $this->usernames = array_filter(
            explode(',', (string)$input->getOption(self::OPTION_USERNAMES)),
            static function ($value) {
                return !empty($value) && is_string($value);
            }
        );

        if (empty($this->usernames)) {
            throw new InvalidArgumentException(
                sprintf("Invalid '%s' option received.", self::OPTION_USERNAMES)
            );
        }
    }

    private function initializeModuloOption(InputInterface $input): void
    {
        if ($input->getOption(self::OPTION_REMAINDER) === null) {
            throw new InvalidArgumentException(
                sprintf("Command option '%s' is expected to be specified.", self::OPTION_REMAINDER)
            );
        }

        if (!is_numeric($input->getOption(self::OPTION_MODULO))) {
            throw new InvalidArgumentException(
                sprintf(
                    "Command option '%s' is expected to be numeric.",
                    self::OPTION_MODULO
                )
            );
        }

        $modulo = (int)$input->getOption(self::OPTION_MODULO);

        if ($modulo < 2 || $modulo > 100) {
            throw new InvalidArgumentException(
                sprintf(
                    "Invalid '%s' option received: %d, expected value: 2 <= m <= 100",
                    self::OPTION_MODULO,
                    $modulo
                )
            );
        }

        $this->modulo = $modulo;
    }

    private function initializeRemainderOption(InputInterface $input): void
    {
        if (!$input->getOption(self::OPTION_MODULO)) {
            throw new InvalidArgumentException(
                sprintf("Command option '%s' is expected to be specified.", self::OPTION_MODULO)
            );
        }

        if (!is_numeric($input->getOption(self::OPTION_REMAINDER))) {
            throw new InvalidArgumentException(
                sprintf(
                    "Command option '%s' is expected to be numeric.",
                    self::OPTION_REMAINDER
                )
            );
        }

        $remainder = (int)$input->getOption(self::OPTION_REMAINDER);

        if ($remainder < 0 || $remainder >= $this->modulo) {
            throw new InvalidArgumentException(
                sprintf(
                    "Invalid '%s' option received: %d, expected value: 0 <= r <= %d",
                    self::OPTION_REMAINDER,
                    $remainder,
                    $this->modulo - 1
                )
            );
        }

        $this->remainder = $remainder;
    }
}

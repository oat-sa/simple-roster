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
use App\Repository\Criteria\FindUserCriteria;
use App\Repository\UserRepository;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
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

    private const OPTION_USERNAMES = 'usernames';
    private const OPTION_LINE_ITEM_SLUGS = 'line-item-slugs';
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

    /** @var array string[] */
    private $usernames = [];

    /** @var array */
    private $lineItemSlugs = [];

    /**
     * @throws DoctrineResultCacheImplementationNotFoundException
     */
    public function __construct(
        UserRepository $userRepository,
        UserCacheIdGenerator $userCacheIdGenerator,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct(self::NAME);

        $this->userCacheIdGenerator = $userCacheIdGenerator;
        $this->entityManager = $entityManager;
        $resultCacheImplementation = $this->entityManager->getConfiguration()->getResultCacheImpl();

        if ($resultCacheImplementation === null) {
            throw new DoctrineResultCacheImplementationNotFoundException(
                'Doctrine result cache implementation is not configured.'
            );
        }

        $this->resultCacheImplementation = $resultCacheImplementation;
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
    }

    /**
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

        return $criteria;
    }

    private function warmUpResultCacheForUserName(string $username): void
    {
        $resultCacheId = $this->userCacheIdGenerator->generate($username);
        $this->resultCacheImplementation->delete($resultCacheId);

        // Refresh by query
        $user = $this->userRepository->findByUsernameWithAssignments($username);
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
}

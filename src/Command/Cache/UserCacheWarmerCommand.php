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

namespace OAT\SimpleRoster\Command\Cache;

use Doctrine\ORM\ORMException;
use InvalidArgumentException;
use OAT\SimpleRoster\Command\BlackfireProfilerTrait;
use OAT\SimpleRoster\Command\CommandProgressBarFormatterTrait;
use OAT\SimpleRoster\Repository\Criteria\FindUserCriteria;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Service\Cache\UserCacheWarmerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class UserCacheWarmerCommand extends Command
{
    use CommandProgressBarFormatterTrait;
    use BlackfireProfilerTrait;

    public const NAME = 'roster:cache-warmup:user';

    private const OPTION_USERNAMES = 'usernames';
    private const OPTION_LINE_ITEM_SLUGS = 'line-item-slugs';
    private const OPTION_BATCH = 'batch';

    private const DEFAULT_BATCH_SIZE = '1000';

    private UserCacheWarmerService $userCacheWarmerService;
    private UserRepository $userRepository;
    private SymfonyStyle $symfonyStyle;
    private int $batchSize;

    /** @var string[] */
    private array $usernames = [];

    /** @var string[] */
    private array $lineItemSlugs = [];

    public function __construct(UserCacheWarmerService $userCacheWarmerService, UserRepository $userRepository)
    {
        $this->userCacheWarmerService = $userCacheWarmerService;
        $this->userRepository = $userRepository;

        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('User cache warmup');
        // @codingStandardsIgnoreStart
        $this->setHelp(<<<'EOF'
The <info>%command.name%</info> command warms up the cache for users.

    <info>php %command.full_name%</info>

Use the --batch option to warm up the cache in batches:

    <info>php %command.full_name% --batch=10000</info>

Use the --usernames option to warm up the cache for specific users:

    <info>php %command.full_name% --usernames=username_1,username_2,username_3</info>

Use the --line-item-slugs option to warm up the cache for users having assignments for specific line items:

    <info>php %command.full_name% --line-item-slugs=slug_1,slug_2,slug_3</info>
EOF
        );
        // @codingStandardsIgnoreEnd

        $this->addBlackfireProfilingOption();

        $this->addOption(
            self::OPTION_BATCH,
            'b',
            InputOption::VALUE_REQUIRED,
            'Number of cache entries to process per batch',
            self::DEFAULT_BATCH_SIZE
        );

        $this->addOption(
            self::OPTION_USERNAMES,
            'u',
            InputOption::VALUE_REQUIRED,
            'Comma separated list of usernames to scope the cache warmup'
        );

        $this->addOption(
            self::OPTION_LINE_ITEM_SLUGS,
            'l',
            InputOption::VALUE_REQUIRED,
            'Comma separated list of line item slugs to scope the cache warmup'
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);
        $this->symfonyStyle->title('Simple Roster - User Cache Warmer');

        $this->initializeBatchSizeOption($input);

        if ($input->getOption(self::OPTION_USERNAMES)) {
            $this->initializeUsernamesOption($input);
        }

        if ($input->getOption(self::OPTION_LINE_ITEM_SLUGS)) {
            $this->initializeLineItemSlugsOption($input);
        }

        if (!empty($this->usernames) && !empty($this->lineItemSlugs)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Option '%s' and '%s' are exclusive options.",
                    self::OPTION_USERNAMES,
                    self::OPTION_LINE_ITEM_SLUGS
                )
            );
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->symfonyStyle->note('Calculating total number of entries to warm up...');

        $criteria = $this->getFindUserCriteria();
        $numberOfTotalUsers = $this->userRepository->countByCriteria($criteria);

        if ($numberOfTotalUsers === 0) {
            $this->symfonyStyle->warning('There are no users found in the database.');

            return 0;
        }

        $this->symfonyStyle->note('Warming up doctrine result cache...');

        $progressBar = $this->createFormattedProgressBar($output);

        $progressBar->setMaxSteps($numberOfTotalUsers);
        $progressBar->start();

        $lastUserId = null;
        $numberOfWarmedUpCacheEntries = 0;

        try {
            do {
                $resultSet = $this->userRepository->findAllUsernamesByCriteriaPaged(
                    $this->batchSize,
                    $lastUserId,
                    $criteria
                );

                if (!$resultSet->isEmpty()) {
                    $this->userCacheWarmerService->process($resultSet->getUsernameCollection());
                }

                $progressBar->advance($this->batchSize);
                $numberOfWarmedUpCacheEntries += $resultSet->count();

                $lastUserId = $resultSet->getLastUserId();
            } while ($resultSet->hasMore());

            $progressBar->finish();
            $this->symfonyStyle->newLine(2);

            $this->symfonyStyle->success(
                sprintf(
                    'Cache warmup for %d users was successfully initiated.',
                    $numberOfWarmedUpCacheEntries,
                )
            );
        } catch (Throwable $exception) {
            $this->symfonyStyle->error(sprintf('An unexpected error occurred: %s', $exception->getMessage()));

            return 1;
        }

        return 0;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function initializeBatchSizeOption(InputInterface $input): void
    {
        $this->batchSize = (int)$input->getOption(self::OPTION_BATCH);
        if ($this->batchSize < 1) {
            throw new InvalidArgumentException(
                sprintf("Invalid '%s' option received.", self::OPTION_BATCH)
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function initializeLineItemSlugsOption(InputInterface $input): void
    {
        $this->lineItemSlugs = array_filter(
            explode(',', (string)$input->getOption(self::OPTION_LINE_ITEM_SLUGS)),
            static function ($value): bool {
                return !empty($value) && mb_strlen($value) > 1;
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
            static function ($value): bool {
                return !empty($value) && mb_strlen($value) > 1;
            }
        );

        if (empty($this->usernames)) {
            throw new InvalidArgumentException(
                sprintf("Invalid '%s' option received.", self::OPTION_USERNAMES)
            );
        }
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
}

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
use OAT\SimpleRoster\Repository\Criteria\EuclideanDivisionCriterion;
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
    private const OPTION_MODULO = 'modulo';
    private const OPTION_REMAINDER = 'remainder';

    private const DEFAULT_BATCH_SIZE = 1000;

    /** @var UserCacheWarmerService */
    private $userCacheWarmerService;

    /** @var UserRepository */
    private $userRepository;

    /** @var SymfonyStyle */
    private $symfonyStyle;

    /** @var int */
    private $batchSize;

    /** @var string[] */
    private $usernames = [];

    /** @var array */
    private $lineItemSlugs = [];

    /** @var int|null */
    private $modulo;

    /** @var int|null */
    private $remainder;

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
        $this->setHelp(<<<'EOF'
The <info>%command.name%</info> command warms up the cache for users.

    <info>php %command.full_name%</info>

Use the --batch option to warm up the cache in batches:

    <info>php %command.full_name% --batch=10000</info>

Use the --usernames option to warm up the cache for specific users:

    <info>php %command.full_name% --usernames=username_1,username_2,username_3</info>

Use the --line-item-slugs option to warm up the cache for users having assignments for specific line items:

    <info>php %command.full_name% --line-item-slugs=slug_1,slug_2,slug_3</info>

Use the --modulo and --remainder options for parallelized cache warmup:

    <info>php %command.full_name% --modulo=4 --remainder=1</info>
    <comment>(Documentation: https://github.com/oat-sa/simple-roster/blob/develop/docs/cli/user-cache-warmer-command.md#synchronous-cache-warmup-parallelization)</comment>
EOF
        );

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
                $resultSet = $this->userRepository->findAllUsernamesPaged($this->batchSize, $lastUserId, $criteria);
                if (!$resultSet->isEmpty()) {
                    $this->userCacheWarmerService->process($resultSet->getUsernameCollection());
                }

                $progressBar->advance($this->batchSize);
                $numberOfWarmedUpCacheEntries += $resultSet->count();

                $lastUserId = $resultSet->getLastUserId();
            } while ($resultSet->hasMore());

            $progressBar->finish();

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
                return !empty($value) && is_string($value) && mb_strlen($value) > 1;
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
                return !empty($value) && is_string($value) && mb_strlen($value) > 1;
            }
        );

        if (empty($this->usernames)) {
            throw new InvalidArgumentException(
                sprintf("Invalid '%s' option received.", self::OPTION_USERNAMES)
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function initializeModuloOption(InputInterface $input): void
    {
        if ($input->getOption(self::OPTION_REMAINDER) === null) {
            throw new InvalidArgumentException(
                sprintf("Option '%s' is expected to be specified.", self::OPTION_REMAINDER)
            );
        }

        if (!is_numeric($input->getOption(self::OPTION_MODULO))) {
            throw new InvalidArgumentException(
                sprintf(
                    "Option '%s' is expected to be numeric.",
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

    /**
     * @throws InvalidArgumentException
     */
    private function initializeRemainderOption(InputInterface $input): void
    {
        if (!$input->getOption(self::OPTION_MODULO)) {
            throw new InvalidArgumentException(
                sprintf("Option '%s' is expected to be specified.", self::OPTION_MODULO)
            );
        }

        if (!is_numeric($input->getOption(self::OPTION_REMAINDER))) {
            throw new InvalidArgumentException(
                sprintf(
                    "Option '%s' is expected to be numeric.",
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
}

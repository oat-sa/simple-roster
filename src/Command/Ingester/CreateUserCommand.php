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

namespace OAT\SimpleRoster\Command\Ingester;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use OAT\SimpleRoster\Service\Bulk\BulkUserCreationService;
use Throwable;

class CreateUserCommand extends Command
{
    public const NAME = 'roster:create:user';

    protected ProgressBar $progressBar;
    private BulkUserCreationService $bulkUserCreationService;

    private const DEFAULT_BATCH_SIZE = 10;
    private const OPTION_LINE_ITEM_IDS = 'line-item-ids';
    private const OPTION_LINE_ITEM_SLUGS = 'line-item-slugs';
    private const OPTION_GROUP_PREFIX = 'group-prefix';
    private const OPTION_BATCH_SIZE = 'batch-size';

    private SymfonyStyle $symfonyStyle;
    /** @var string[] */
    private array $lineItemSlugs = [];
    private array $userPrefix;

    /** @var int[] */
    private array $lineItemIds = [];

    /** @var int */
    private int $batchSize;
    public function __construct(
        BulkUserCreationService $bulkUserCreationService
    ) {
        parent::__construct(self::NAME);
        $this->bulkUserCreationService = $bulkUserCreationService;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Automate a user generate list');

        $this->addOption(
            self::OPTION_LINE_ITEM_IDS,
            'i',
            InputOption::VALUE_REQUIRED,
            'Comma separated list of line item IDs',
        );

        $this->addOption(
            self::OPTION_LINE_ITEM_SLUGS,
            's',
            InputOption::VALUE_REQUIRED,
            'Comma separated list of line item slugs',
        );

        $this->addOption(
            self::OPTION_BATCH_SIZE,
            'b',
            InputOption::VALUE_REQUIRED,
            'User Create Batch size',
            self::DEFAULT_BATCH_SIZE
        );

        $this->addArgument(
            'user-prefix',
            InputArgument::REQUIRED,
            'user prefix list'
        );

        $this->addOption(
            self::OPTION_GROUP_PREFIX,
            'p',
            InputOption::VALUE_REQUIRED,
            'Group Prefix',
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);
        $this->symfonyStyle->title('Simple Roster - Automate the user-generation');

        $inputLineItemIds = $input->getOption(self::OPTION_LINE_ITEM_IDS) ?? '';
        $inputLineItemSlugs = $input->getOption(self::OPTION_LINE_ITEM_SLUGS) ?? '';
        $inputBatchSize = $input->getOption(self::OPTION_BATCH_SIZE);
        $inputUserPrefix = $input->getArgument('user-prefix');
        if ($inputLineItemIds) {
            $this->initializeLineItemIdsOption($inputLineItemIds);
        }
        if ($inputLineItemSlugs) {
            $this->initializeLineItemSlugsOption($inputLineItemSlugs);
        }
        if (
            !empty($this->lineItemIds) && !empty($this->lineItemSlugs)
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Option %s and %s are exclusive options.',
                    self::OPTION_LINE_ITEM_IDS,
                    self::OPTION_LINE_ITEM_SLUGS
                )
            );
        }
        if ($inputUserPrefix) {
            $this->initializeUserPrefixOption($inputUserPrefix);
        }
        if ($inputBatchSize) {
            $this->initializeBatchOption($inputBatchSize);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->symfonyStyle->comment('Executing Automation...');

        try {
            $processDataResult = $this->bulkUserCreationService->createUsers(
                $this->lineItemIds,
                $this->lineItemSlugs,
                $this->userPrefix,
                $this->batchSize,
                $input->getOption(self::OPTION_GROUP_PREFIX)
            );
            if ($processDataResult['status'] === 1 && !empty($processDataResult['notExistLineItemsArray'])) {
                $this->symfonyStyle->note(
                    sprintf(
                        '%s %s',
                        implode(',', $processDataResult['notExistLineItemsArray']),
                        'Line Items not exist in the system'
                    )
                );
            }
            $this->symfonyStyle->success(
                sprintf('%s', $processDataResult['message'])
            );
        } catch (Throwable $exception) {
            $this->symfonyStyle->error($exception->getMessage());

            return 1;
        }

        return 0;
    }

    private function initializeLineItemIdsOption(string $inputLineItemIds): void
    {
        $lineItemIds = array_filter(
            explode(',', $inputLineItemIds),
            static function (string $value): bool {
                return !empty($value) && (int)$value > 0;
            }
        );
        if (empty($lineItemIds)) {
            throw new InvalidArgumentException(
                sprintf('Invalid %s option received.', self::OPTION_LINE_ITEM_IDS)
            );
        }

        $this->lineItemIds = array_map('intval', $lineItemIds);
    }

    private function initializeLineItemSlugsOption(string $inputLineItemSlugs): void
    {
        if (!empty($inputLineItemSlugs)) {
            $this->lineItemSlugs = array_filter(
                explode(',', $inputLineItemSlugs),
                static function (string $value): bool {
                    return !empty($value);
                }
            );
        }
        if (empty($this->lineItemSlugs)) {
            throw new InvalidArgumentException(
                sprintf('Invalid %s option received.', self::OPTION_LINE_ITEM_SLUGS)
            );
        }
    }

    private function initializeUserPrefixOption(string $inputUserPrefix): void
    {
        $this->userPrefix = array_filter(
            explode(',', (string)$inputUserPrefix),
            static function (string $value): bool {
                return !empty($value);
            }
        );

        if (empty($this->userPrefix)) {
            throw new InvalidArgumentException(
                sprintf('User Prefix is a required argument')
            );
        }
    }

    private function initializeBatchOption(string $inputBatchSize): void
    {
        if (is_numeric($inputBatchSize)) {
            $this->batchSize = (int)$inputBatchSize;
        }
        if (empty($this->batchSize)) {
            throw new InvalidArgumentException(
                sprintf('Batch Size should be a valid number')
            );
        }
    }
}

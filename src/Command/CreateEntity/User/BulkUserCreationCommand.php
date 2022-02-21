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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Command\CreateEntity\User;

use InvalidArgumentException;
use OAT\SimpleRoster\Service\Bulk\BulkCreateUsersServiceConsoleProxy;
use League\Flysystem\FileExistsException;
use OAT\SimpleRoster\Service\AwsS3\FolderSyncService;
use OAT\SimpleRoster\Service\Bulk\BulkCreateUsersService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class BulkUserCreationCommand extends Command
{
    public const NAME = 'roster:create-entity:user';

    private BulkCreateUsersServiceConsoleProxy $bulkCreateUsersService;

    private const DEFAULT_BATCH_SIZE = 100;
    private const OPTION_LINE_ITEM_IDS = 'line-item-ids';
    private const OPTION_LINE_ITEM_SLUGS = 'line-item-slugs';
    private const OPTION_GROUP_PREFIX = 'group-prefix';
    private const OPTION_BATCH_SIZE = 'batch-size';

    private SymfonyStyle $symfonyStyle;
    private FolderSyncService $userFolderSync;

    private array $lineItemSlugs = [];
    private array $userPrefix;
    private array $lineItemIds = [];

    private int $batchSize;

    public function __construct(
        BulkCreateUsersServiceConsoleProxy $bulkCreateUsersService,
        FolderSyncService $userFolderSync
    ) {
        parent::__construct(self::NAME);
        $this->bulkCreateUsersService = $bulkCreateUsersService;
        $this->userFolderSync = $userFolderSync;
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
            (string)self::DEFAULT_BATCH_SIZE
        );

        $this->addArgument(
            'user-prefix',
            InputArgument::REQUIRED,
            'user prefix list'
        );

        $this->addOption(
            self::OPTION_GROUP_PREFIX,
            'g',
            InputOption::VALUE_REQUIRED,
            'Group Prefix',
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);
        $this->symfonyStyle->title('Simple Roster - Bulk User Creation');

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
        $this->symfonyStyle->comment('Executing Bulk user creation');

        try {
            $folderName = date('Y-m-d');

            $processDataResult = $this->bulkCreateUsersService->createUsers(
                $this->lineItemIds,
                $this->lineItemSlugs,
                $this->userPrefix,
                $this->batchSize,
                $input->getOption(self::OPTION_GROUP_PREFIX),
                date('Y-m-d')
            );
            if (!empty($processDataResult->getNonExistingLineItems())) {
                $this->symfonyStyle->note(
                    sprintf(
                        'Line Items with slugs/ids \'%s\' were not found in the system.',
                        implode(',', $processDataResult->getNonExistingLineItems()),
                    )
                );
            }

            try {
                $this->userFolderSync->sync($folderName);
            } catch (FileExistsException | FileExistsException $exception) {
                $this->symfonyStyle->note($exception->getMessage());
            }

            $this->symfonyStyle->success(
                sprintf('%s', $processDataResult->getMessage())
            );
        } catch (Throwable $exception) {
            $this->symfonyStyle->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function initializeLineItemIdsOption(string $inputLineItemIds): void
    {
        $lineItemIds = array_filter(
            explode(',', $inputLineItemIds),
            static function (string $value): bool {
                return !empty($value) && filter_var($value, FILTER_VALIDATE_INT);
            }
        );

        if (empty($lineItemIds)) {
            throw new InvalidArgumentException(
                sprintf('Invalid %s option value received.', self::OPTION_LINE_ITEM_IDS)
            );
        }
        $this->lineItemIds = array_map('intval', $lineItemIds);
    }

    private function initializeLineItemSlugsOption(string $inputLineItemSlugs): void
    {
        $this->lineItemSlugs = array_filter(
            explode(',', $inputLineItemSlugs)
        );

        if (empty($this->lineItemSlugs)) {
            throw new InvalidArgumentException(
                sprintf('Invalid %s option value received.', self::OPTION_LINE_ITEM_SLUGS)
            );
        }
    }

    private function initializeUserPrefixOption(string $inputUserPrefix): void
    {
        $this->userPrefix = array_filter(
            explode(',', $inputUserPrefix)
        );

        if (empty($this->userPrefix)) {
            throw new InvalidArgumentException(
                sprintf('User Prefix is a required argument')
            );
        }
    }

    private function initializeBatchOption(string $inputBatchSize): void
    {
        $batchSize = filter_var($inputBatchSize, FILTER_VALIDATE_INT);

        if ($batchSize === false) {
            throw new InvalidArgumentException(
                sprintf('Batch Size should be a valid number')
            );
        }

        $this->batchSize = $batchSize;
    }
}

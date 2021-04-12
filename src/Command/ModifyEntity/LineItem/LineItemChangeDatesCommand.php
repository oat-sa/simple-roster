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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Command\ModifyEntity\LineItem;

use Carbon\Carbon;
use DateTime;
use InvalidArgumentException;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use OAT\SimpleRoster\Repository\LineItemRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\UuidV6;
use Throwable;

class LineItemChangeDatesCommand extends Command
{
    public const NAME = 'roster:modify-entity:line-item:change-dates';

    private const OPTION_LINE_ITEM_IDS = 'line-item-ids';
    private const OPTION_LINE_ITEM_SLUGS = 'line-item-slugs';
    private const OPTION_LINE_ITEM_GROUP_IDS = 'line-item-group-ids';
    private const OPTION_START_DATE = 'start-date';
    private const OPTION_END_DATE = 'end-date';
    private const OPTION_FORCE = 'force';

    /** @var SymfonyStyle */
    private $symfonyStyle;

    /** @var bool */
    private $isDryRun;

    /** @var string[] */
    private $lineItemSlugs;

    /** @var UuidV6[] */
    private $lineItemIds;

    /** @var string[] */
    private $lineItemGroupIds;

    /** @var DateTime|null */
    private $startDate;

    /** @var DateTime|null */
    private $endDate;

    /** @var LineItemRepository */
    private $lineItemRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(LineItemRepository $lineItemRepository, LoggerInterface $logger)
    {
        parent::__construct(self::NAME);

        $this->lineItemRepository = $lineItemRepository;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Updates the start and end dates of line item(s).');

        // @codingStandardsIgnoreStart
        $this->setHelp(<<<EOF
The <info>%command.name%</info> command changes the dates for specific line items.

<comment>Not specifying a start-date or end-date option will nullify the value for the column.</comment>
<comment>Dates are expected to be in the format: 2020-01-01T00:00:00+0000.</comment>
<comment>You can adjust the timezone: 2020-01-01T00:00:00+0100 will be stored as 2019-12-31 23:00:00 GMT.</comment>

To change both start and end date of a line item using IDs:
    <info>php %command.full_name% -i 00000001-0000-6000-0000-000000000000,00000002-0000-6000-0000-000000000000 --start-date <date> --end-date <date></info>
    <info>php %command.full_name% --line-item-ids 00000001-0000-6000-0000-000000000000,00000002-0000-6000-0000-000000000000 --start-date <date> --end-date <date></info>

To change both start and end date of a line item using slugs:
    <info>php %command.full_name% -s slug1,slug2,slug3 --start-date <date> --end-date <date></info>
    <info>php %command.full_name% --line-item-slugs slug1,slug2,slug3 --start-date <date> --end-date <date></info>

To change both start and end date of a line item using group ids:
    <info>php %command.full_name% -g group1,group2,group3 --start-date <date> --end-date <date></info>
    <info>php %command.full_name% --line-item-group-ids group1,group2,group3 --start-date <date> --end-date <date></info>
EOF
        );
        // @codingStandardsIgnoreEnd

        $this->addOption(
            self::OPTION_LINE_ITEM_IDS,
            'i',
            InputOption::VALUE_REQUIRED,
            'Comma separated list of line item IDs to be updated',
        );

        $this->addOption(
            self::OPTION_LINE_ITEM_SLUGS,
            's',
            InputOption::VALUE_REQUIRED,
            'Comma separated list of line item slugs to be updated',
        );

        $this->addOption(
            self::OPTION_LINE_ITEM_GROUP_IDS,
            'g',
            InputOption::VALUE_REQUIRED,
            'Comma separated list of line item group ids to be updated',
        );

        $this->addOption(
            self::OPTION_START_DATE,
            null,
            InputOption::VALUE_OPTIONAL,
            'Start date for the line item(s). Expected format: 2020-01-01T00:00:00+0000',
        );

        $this->addOption(
            self::OPTION_END_DATE,
            null,
            InputOption::VALUE_OPTIONAL,
            'End date for the line item(s). Expected format: 2020-01-01T00:00:00+0000',
        );

        $this->addOption(
            self::OPTION_FORCE,
            'f',
            InputOption::VALUE_NONE,
            'To involve actual database modifications or not'
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);
        $this->symfonyStyle->title('Simple Roster - Start and End dates update');

        if ($input->getOption(self::OPTION_LINE_ITEM_IDS)) {
            $this->initializeLineItemIdsOption($input);
        }

        if ($input->getOption(self::OPTION_LINE_ITEM_SLUGS)) {
            $this->initializeLineItemSlugsOption($input);
        }

        if ($input->getOption(self::OPTION_LINE_ITEM_GROUP_IDS)) {
            $this->initializeLineItemGroupIdsOption($input);
        }

        if (empty($this->lineItemIds) && empty($this->lineItemSlugs) && empty($this->lineItemGroupIds)) {
            throw new InvalidArgumentException(
                sprintf(
                    "You need to specify '%s', '%s' or '%s' option.",
                    self::OPTION_LINE_ITEM_IDS,
                    self::OPTION_LINE_ITEM_SLUGS,
                    self::OPTION_LINE_ITEM_GROUP_IDS,
                )
            );
        }

        if (!empty($this->lineItemIds) && !empty($this->lineItemSlugs) && !empty($this->lineItemGroupIds)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Option '%s', '%s' and '%s' are exclusive options.",
                    self::OPTION_LINE_ITEM_IDS,
                    self::OPTION_LINE_ITEM_SLUGS,
                    self::OPTION_LINE_ITEM_GROUP_IDS
                )
            );
        }

        $this->validateAndSetDates($input);

        $this->isDryRun = !$input->getOption(self::OPTION_FORCE);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->symfonyStyle->note('Checking line items to be updated...');

            $criteria = $this->getFindLineItemCriteria();
            $lineItemsCollection = $this->lineItemRepository->findLineItemsByCriteria($criteria);

            if ($lineItemsCollection->isEmpty()) {
                $this->symfonyStyle->warning('No line items found with specified criteria.');

                return 0;
            }

            /** @var LineItem $lineItem */
            foreach ($lineItemsCollection as $lineItem) {
                $lineItem->setAvailabilityDates($this->startDate, $this->endDate);

                $this->lineItemRepository->persist($lineItem);

                $this->logger->info(
                    sprintf('New dates were set for line item with: "%s"', $lineItem->getId()),
                    $lineItem->jsonSerialize()
                );
            }

            if ($this->isDryRun) {
                $this->symfonyStyle->warning(
                    sprintf(
                        '[DRY RUN] %d line item(s) have been updated.',
                        $lineItemsCollection->count()
                    )
                );

                return 0;
            }

            $this->symfonyStyle->success(
                sprintf(
                    '%d line item(s) have been updated.',
                    $lineItemsCollection->count()
                )
            );

            $this->lineItemRepository->flush();

            return 0;
        } catch (Throwable $exception) {
            $this->symfonyStyle->error(sprintf('An unexpected error occurred: %s', $exception->getMessage()));

            return 1;
        }
    }

    private function initializeLineItemIdsOption(InputInterface $input): void
    {
        try {
            $this->lineItemIds = array_map(
                static function (string $lineItemId): UuidV6 {
                    return new UuidV6($lineItemId);
                },
                explode(',', (string)$input->getOption(self::OPTION_LINE_ITEM_IDS)),
            );
        } catch (Throwable $exception) {
            $this->lineItemIds = [];
        }

        if (empty($this->lineItemIds)) {
            throw new InvalidArgumentException(
                sprintf("Invalid '%s' option received.", self::OPTION_LINE_ITEM_IDS)
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
            static function (string $value): bool {
                return !empty($value);
            }
        );

        if (empty($this->lineItemSlugs)) {
            throw new InvalidArgumentException(
                sprintf("Invalid '%s' option received.", self::OPTION_LINE_ITEM_SLUGS)
            );
        }
    }

    private function initializeLineItemGroupIdsOption(InputInterface $input): void
    {
        $this->lineItemGroupIds = array_filter(
            explode(',', (string)$input->getOption(self::OPTION_LINE_ITEM_GROUP_IDS)),
            static function (string $value): bool {
                return !empty($value);
            }
        );

        if (empty($this->lineItemGroupIds)) {
            throw new InvalidArgumentException(
                sprintf("Invalid '%s' option received.", self::OPTION_LINE_ITEM_GROUP_IDS)
            );
        }
    }

    private function getFindLineItemCriteria(): FindLineItemCriteria
    {
        $criteria = new FindLineItemCriteria();

        if (!empty($this->lineItemIds)) {
            $criteria->addLineItemIds(...$this->lineItemIds);
        }

        if (!empty($this->lineItemSlugs)) {
            $criteria->addLineItemSlugs(...$this->lineItemSlugs);
        }

        if (!empty($this->lineItemGroupIds)) {
            $criteria->addLineItemGroupIds(...$this->lineItemGroupIds);
        }

        return $criteria;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateAndSetDates(InputInterface $input): void
    {
        $inputStartDate = (string)$input->getOption(self::OPTION_START_DATE);
        $inputEndDate = (string)$input->getOption(self::OPTION_END_DATE);

        $this->startDate = $this->initializeDate($inputStartDate);
        $this->endDate = $this->initializeDate($inputEndDate);

        if (
            $this->endDate !== null && $this->endDate < $this->startDate
        ) {
            $message = sprintf(
                'End date should be later than start date. Start Date: %s End Date: %s.',
                $inputStartDate,
                $inputEndDate
            );

            throw new InvalidArgumentException($message);
        }
    }

    private function initializeDate(string $dateString): ?DateTime
    {
        $dateObj = null;
        if (!empty($dateString)) {
            try {
                $date = Carbon::createFromFormat(Carbon::ATOM, $dateString);
                $dateObj = $date ? $date->setTimezone('UTC')->toDateTime() : null;
            } catch (Throwable $e) {
                $message = sprintf(
                    '%s is an invalid date. Expected format: 2020-01-01T00:00:00+0000',
                    $dateString
                );

                throw new InvalidArgumentException($message);
            }
        }

        return $dateObj;
    }
}

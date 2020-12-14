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

namespace OAT\SimpleRoster\Command\Update;

use DateTime;
use Exception;
use InvalidArgumentException;
use OAT\SimpleRoster\Command\BlackfireProfilerTrait;
use OAT\SimpleRoster\Command\Cache\LineItemCacheWarmerCommand;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use OAT\SimpleRoster\Repository\LineItemRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class UpdateLineItemDatesCommand extends Command
{
    use BlackfireProfilerTrait;

    public const NAME = 'roster:update:line-item-dates';

    private const OPTION_LINE_ITEM_IDS = 'line-item-ids';
    private const OPTION_LINE_ITEM_SLUGS = 'line-item-slugs';
    private const OPTION_START_DATE = 'start-date';
    private const OPTION_END_DATE = 'end-date';
    private const OPTION_FORCE = 'force';

    /** @var SymfonyStyle */
    private $symfonyStyle;

    /** @var bool */
    private $isDryRun;

    /** @var string[] */
    private $lineItemSlugs;

    /** @var int[] */
    private $lineItemIds;

    /** @var DateTime|null */
    private $startDate;

    /** @var DateTime|null */
    private $endDate;

    /** @var LineItemRepository */
    private $lineItemRepository;

    public function __construct(LineItemRepository $lineItemRepository)
    {
        parent::__construct(self::NAME);

        $this->lineItemRepository = $lineItemRepository;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addBlackfireProfilingOption();

        $this->setDescription('Updates the start and end dates of line item(s).');

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
            self::OPTION_START_DATE,
            '',
            InputOption::VALUE_OPTIONAL,
            'Start date for the line item(s)',
        );

        $this->addOption(
            self::OPTION_END_DATE,
            '',
            InputOption::VALUE_OPTIONAL,
            'End date for the line item(s)',
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

        if (!empty($this->lineItemIds) && !empty($this->lineItemSlugs)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Option '%s' and '%s' are exclusive options.",
                    self::OPTION_LINE_ITEM_IDS,
                    self::OPTION_LINE_ITEM_SLUGS
                )
            );
        }

        $this->initializeDates($input);

        $this->isDryRun = !(bool)$input->getOption(self::OPTION_FORCE);
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

            $command = $this->getApplication()->find(LineItemCacheWarmerCommand::NAME);
            $command->run(new ArrayInput([]), $output);

            return 0;
        } catch (Throwable $exception) {
            $this->symfonyStyle->error(sprintf('An unexpected error occurred: %s', $exception->getMessage()));

            return 1;
        }
    }

    private function initializeLineItemIdsOption(InputInterface $input): void
    {
        $this->lineItemIds = array_filter(
            explode(',', (string)$input->getOption(self::OPTION_LINE_ITEM_IDS)),
            static function ($value): bool {
                return !empty($value) && (int)$value > 0;
            }
        );

        if (empty($this->lineItemIds)) {
            throw new InvalidArgumentException(
                sprintf("Invalid '%s' option received.", self::OPTION_LINE_ITEM_IDS)
            );
        }

        $this->lineItemIds = array_map('intval', $this->lineItemIds);
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
    private function initializeDates(InputInterface $input): void
    {
        $inputStartDate = $input->getOption(self::OPTION_START_DATE);
        $inputEndDate = $input->getOption(self::OPTION_END_DATE);

        if ($inputStartDate !== null) {
            try {
                $this->startDate = new DateTime($inputStartDate);
            } catch (Exception $e) {
                $expectedFormat = (new DateTime())->format(DateTime::ATOM);
                $message = sprintf(
                    '%s is an invalid start date. Expected format: %s',
                    $inputStartDate,
                    $expectedFormat
                );

                throw new InvalidArgumentException($message);
            }
        }

        if ($inputEndDate !== null) {
            try {
                $this->endDate = new DateTime($inputEndDate);
            } catch (Exception $e) {
                $expectedFormat = (new DateTime())->format(DateTime::ATOM);
                $message = sprintf(
                    '%s is an invalid end date. Expected format: %s',
                    $inputEndDate,
                    $expectedFormat
                );

                throw new InvalidArgumentException($message);
            }
        }

        if ($this->startDate !== null && $this->endDate !== null && $this->startDate > $this->endDate) {
            $message = sprintf(
                'End date should be later than start date. Start Date: %s End Date: %s.',
                $inputStartDate,
                $inputEndDate
            );

            throw new InvalidArgumentException($message);
        }
    }

    private function getFindLineItemCriteria(): FindLineItemCriteria
    {
        $criteria = new FindLineItemCriteria();

        if (!empty($this->lineItemIds)) {
            $criteria->addLineItemIdsCriteria(...$this->lineItemIds);
        }

        if (!empty($this->lineItemSlugs)) {
            $criteria->addLineItemSlugsCriteria(...$this->lineItemSlugs);
        }

        return $criteria;
    }
}

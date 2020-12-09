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

use InvalidArgumentException;
use OAT\SimpleRoster\Command\BlackfireProfilerTrait;
use OAT\SimpleRoster\Command\Cache\LineItemCacheWarmerCommand;
use Symfony\Component\Console\Command\Command;
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

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        parent::configure();

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
            InputOption::VALUE_REQUIRED,
            'Start date for the line item(s)',
        );

        $this->addOption(
            self::OPTION_END_DATE,
            '',
            InputOption::VALUE_REQUIRED,
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

        $this->isDryRun = !(bool)$input->getOption(self::OPTION_FORCE);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->symfonyStyle->note('Checking line items to be updated...');

            dd($this->lineItemIds, $this->isDryRun);

            //$command = $this->getApplication()->find(LineItemCacheWarmerCommand::NAME);
            //$command->run($input, $output);

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
}

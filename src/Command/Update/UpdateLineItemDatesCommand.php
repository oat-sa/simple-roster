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

    private const OPTION_IDS = 'ids';
    private const OPTION_SLUGS = 'slugs';
    private const OPTION_START_DATE = 'start-date';
    private const OPTION_END_DATE = 'end-date';
    private const OPTION_FORCE = 'force';

    /** @var SymfonyStyle */
    private $symfonyStyle;

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Updates the start and end dates of line item(s).');

        $this->addOption(
            self::OPTION_IDS,
            'i',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Comma separated list of line item IDs to be updated.',
        );

        $this->addOption(
            self::OPTION_SLUGS,
            's',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Comma separated list of line item slugs to be updated.',
        );

        $this->addOption(
            self::OPTION_START_DATE,
            '',
            InputOption::VALUE_REQUIRED,
            'Start date for the line item(s).',
        );

        $this->addOption(
            self::OPTION_END_DATE,
            '',
            InputOption::VALUE_REQUIRED,
            'End date for the line item(s).',
        );

        $this->addOption(
            self::OPTION_FORCE,
            'f',
            InputOption::VALUE_OPTIONAL,
            'To involve actual database modifications or not.'
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);
        $this->symfonyStyle->title('Simple Roster - Start and End dates update');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->symfonyStyle->note('Checking line items to be updated...');

            $command = $this->getApplication()->find(LineItemCacheWarmerCommand::NAME);
            $command->run($input, $output);

            return 0;
        } catch (Throwable $exception) {
            $this->symfonyStyle->error(sprintf('An unexpected error occurred: %s', $exception->getMessage()));

            return 1;
        }
    }
}

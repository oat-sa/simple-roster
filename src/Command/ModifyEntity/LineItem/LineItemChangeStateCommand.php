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

namespace OAT\SimpleRoster\Command\ModifyEntity\LineItem;

use InvalidArgumentException;
use OAT\SimpleRoster\Repository\LineItemRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\UuidV6;
use Throwable;

class LineItemChangeStateCommand extends Command
{
    public const NAME = 'roster:modify-entity:line-item:change-state';

    private const FIELD_ID = 'id';
    private const FIELD_SLUG = 'slug';
    private const FIELD_URI = 'uri';

    private const AVAILABLE_QUERY_FIELDS = [
        self::FIELD_ID,
        self::FIELD_SLUG,
        self::FIELD_URI,
    ];

    private const TOGGLE_ACTIVATE = 'activate';
    private const TOGGLE_DEACTIVATE = 'deactivate';

    private const TOGGLE_OPERATIONS = [
        self::TOGGLE_ACTIVATE,
        self::TOGGLE_DEACTIVATE,
    ];

    /** @var LineItemRepository */
    private $lineItemRepository;

    /** @var SymfonyStyle */
    private $symfonyStyle;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        LineItemRepository $lineItemRepository,
        LoggerInterface $logger
    ) {
        parent::__construct(self::NAME);

        $this->lineItemRepository = $lineItemRepository;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Activate/Deactivate line items into the application');
        $this->setHelp(
            <<<EOF
The <info>%command.name%</info> command Activate/Deactivate line items into the application.

    <info>php %command.full_name% <path></info>

To Activate a line item by slug:

    <info>php %command.full_name% activate slug {line-item-slug}</info>
    
To Activate a line item by multiple slugs:

    <info>php %command.full_name% activate slug {line-item-slug1} {line-item-slug2}</info>

To Activate a line item by id:

    <info>php %command.full_name% activate id {line-item-id}</info>

To Activate a line item by uri:

    <info>php %command.full_name% activate uri {line-item-id}</info>
To Deactivate a line item by slug:

    <info>php %command.full_name% deactivate slug {line-item-slug}</info>
    
To Deactivate a line item by id:

    <info>php %command.full_name% deactivate id {line-item-id}</info>

To Deactivate a line item by uri:

    <info>php %command.full_name% deactivate uri {line-item-id}</info>
EOF
        );

        $this->addArgument(
            'toggle',
            InputArgument::REQUIRED,
            'Accepted two values "activate" to activate a line item. "deactivate" to deactivate a line item.'
        );

        $fieldQueryDescription = 'How do you want to query the line items that you want to activate/deactivate. '
            . 'Accepted parameters are: %s';
        $this->addArgument(
            'query-field',
            InputArgument::REQUIRED,
            sprintf($fieldQueryDescription, implode(', ', self::AVAILABLE_QUERY_FIELDS))
        );

        $fieldValueDescription = 'The value that should match based on the query field. it can be one value or a list'
            . ' of values split by space. Example: given that the query'
            . ' field is "slug" and the query value is "test1 test2" then all the line items '
            . 'with slug equals to test1 or test2 will be updated';
        $this->addArgument(
            'query-value',
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            $fieldValueDescription
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->symfonyStyle = new SymfonyStyle($input, $output);

        $this->symfonyStyle->title('Simple Roster - Line Item Activator');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $toggle = (string)$input->getArgument('toggle');
        $queryField = (string)$input->getArgument('query-field');
        $queryValue = $input->getArgument('query-value');

        $this->validateToggleArgument($toggle);
        $this->validateQueryFieldArgument($queryField);

        try {
            $this->symfonyStyle->comment(sprintf('Executing %s...', ucfirst($toggle)));

            if ($queryField === self::FIELD_ID) {
                $queryValue = array_map(
                    static function (string $id): string {
                        return (new UuidV6($id))->toBinary();
                    },
                    (array)$queryValue
                );
            }

            $lineItems = $this->lineItemRepository->findBy([$queryField => $queryValue]);

            foreach ($lineItems as $lineItem) {
                $toggle === self::TOGGLE_ACTIVATE ? $lineItem->enable() : $lineItem->disable();

                $this->logger->info(
                    sprintf(
                        "The operation: '%s' was executed for Line Item with id: '%s'",
                        $toggle,
                        (string)$lineItem->getId()
                    )
                );
            }

            $this->lineItemRepository->flush();

            $this->symfonyStyle->success(
                sprintf('The operation: "%s" was executed for "%d" Line Item(s).', $toggle, count($lineItems))
            );
        } catch (Throwable $exception) {
            $this->symfonyStyle->error($exception->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateToggleArgument(string $toggle): void
    {
        if (in_array($toggle, self::TOGGLE_OPERATIONS)) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf('Invalid toggle argument. Please use: %s', implode(', ', self::TOGGLE_OPERATIONS))
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateQueryFieldArgument(string $queryField): void
    {
        if (in_array($queryField, self::AVAILABLE_QUERY_FIELDS)) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                'Invalid query-field argument. Please use: %s',
                implode(', ', self::AVAILABLE_QUERY_FIELDS)
            )
        );
    }
}

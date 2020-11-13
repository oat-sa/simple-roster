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

namespace OAT\SimpleRoster\Command\Ingester\Native;

use InvalidArgumentException;
use OAT\SimpleRoster\Command\CommandProgressBarFormatterTrait;
use OAT\SimpleRoster\DataTransferObject\AssignmentDto;
use OAT\SimpleRoster\DataTransferObject\UserDto;
use OAT\SimpleRoster\DataTransferObject\UserDtoCollection;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Exception\LineItemNotFoundException;
use OAT\SimpleRoster\Ingester\Ingester\NativeUserIngester;
use OAT\SimpleRoster\Ingester\Registry\IngesterSourceRegistry;
use OAT\SimpleRoster\Ingester\Source\IngesterSourceInterface;
use OAT\SimpleRoster\Model\LineItemCollection;
use OAT\SimpleRoster\Repository\LineItemRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Throwable;

class NativeUserIngesterCommand extends Command
{
    use CommandProgressBarFormatterTrait;

    public const NAME = 'roster:native-ingest:user';

    private const DEFAULT_BATCH_SIZE = 1000;

    /** @var IngesterSourceRegistry */
    private $ingesterSourceRegistry;

    /** @var NativeUserIngester */
    private $nativeUserIngester;

    /** @var LineItemRepository */
    private $lineItemRepository;

    /** @var UserPasswordEncoderInterface */
    private $passwordEncoder;

    /** @var SymfonyStyle */
    private $symfonyStyle;

    /** @var bool */
    private $isDryRun;

    /** @var int */
    private $batchSize;

    public function __construct(
        IngesterSourceRegistry $ingesterSourceRegistry,
        NativeUserIngester $nativeUserIngester,
        LineItemRepository $lineItemRepository,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->ingesterSourceRegistry = $ingesterSourceRegistry;
        $this->nativeUserIngester = $nativeUserIngester;
        $this->lineItemRepository = $lineItemRepository;
        $this->passwordEncoder = $passwordEncoder;

        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('Responsible for native user ingesting from various sources (Local file, S3 bucket)');

        $this->addArgument(
            'path',
            InputArgument::REQUIRED,
            'Source path to ingest from'
        );

        $this->addArgument(
            'source',
            InputArgument::OPTIONAL,
            sprintf(
                'Source type to ingest from, possible values: ["%s"]',
                implode('", "', array_keys($this->ingesterSourceRegistry->all()))
            ),
            'local'
        );

        $this->addOption(
            'delimiter',
            'd',
            InputOption::VALUE_REQUIRED,
            'CSV delimiter',
            IngesterSourceInterface::DEFAULT_CSV_DELIMITER
        );

        $this->addOption(
            'charset',
            'c',
            InputOption::VALUE_REQUIRED,
            'CSV source charset',
            IngesterSourceInterface::DEFAULT_CSV_CHARSET
        );

        $this->addOption(
            'batch',
            'b',
            InputOption::VALUE_REQUIRED,
            'Batch size',
            self::DEFAULT_BATCH_SIZE
        );

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'To apply actual database modifications or not');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);
        $this->symfonyStyle->title('Simple Roster - Native User Ingester');

        $this->isDryRun = !(bool)$input->getOption('force');
        $this->batchSize = (int)$input->getOption('batch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->symfonyStyle->note('Starting user ingestion...');

        $csvFilePath = (string)$input->getArgument('path');
        $progressBar = $this->createNewFormattedProgressBar($output);

        try {
            $source = $this->ingesterSourceRegistry
                ->get((string)$input->getArgument('source'))
                ->setPath($csvFilePath)
                ->setDelimiter((string)$input->getOption('delimiter'))
                ->setCharset((string)$input->getOption('charset'));

            $process = new Process(['wc', '-l', $csvFilePath]);
            $process->run();

            $progressBar->setMaxSteps((int)$process->getOutput() - 1);
            $progressBar->start();

            $lineItems = $this->lineItemRepository->findAllAsCollection();

            if ($lineItems->isEmpty()) {
                throw new LineItemNotFoundException("No line items were found in database.");
            }

            $userDtoCollection = new UserDtoCollection();
            $numberOfProcessedRows = 1;
            foreach ($source->getContent() as $rawUser) {
                $this->validateRawUser($rawUser);

                $username = $rawUser['username'];

                $userDto = $userDtoCollection->containsUsername($username)
                    ? $userDtoCollection->getByUsername($username)
                    : $this->createUserDto($rawUser);

                $assignmentDto = $this->createAssignmentDto($rawUser['slug'], $lineItems);

                $userDto->addAssignment($assignmentDto);
                $userDtoCollection->add($userDto);

                if ($numberOfProcessedRows % $this->batchSize === 0) {
                    if (!$this->isDryRun) {
                        $this->nativeUserIngester->ingest($userDtoCollection);
                    }

                    $userDtoCollection->clear();
                    $progressBar->advance($this->batchSize);
                }

                $numberOfProcessedRows++;
            }

            if (!$this->isDryRun && !$userDtoCollection->isEmpty()) {
                $this->nativeUserIngester->ingest($userDtoCollection);
            }

            $progressBar->finish();
        } catch (Throwable $exception) {
            $this->symfonyStyle->error($exception->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateRawUser(array $rawUser): void
    {
        if (!isset($rawUser['username'])) {
            throw new InvalidArgumentException("Column 'username' is not set in source file.");
        }

        if (!isset($rawUser['password'])) {
            throw new InvalidArgumentException("Column 'password' is not set in source file.");
        }

        if (!isset($rawUser['slug'])) {
            throw new InvalidArgumentException("Column 'slug' is not set in source file.");
        }
    }

    /**
     *
     * @throws InvalidArgumentException
     */
    private function createUserDto(array $rawUser): UserDto
    {
        return new UserDto(
            $rawUser['username'],
            $this->passwordEncoder->encodePassword(new User(), $rawUser['password']),
            $rawUser['groupId'] ?? null
        );
    }

    /**
     * @throws LineItemNotFoundException
     */
    private function createAssignmentDto(string $lineItemSlug, LineItemCollection $lineItems): AssignmentDto
    {
        $lineItem = $lineItems->getBySlug($lineItemSlug);

        return new AssignmentDto(Assignment::STATE_READY, (int)$lineItem->getId(), 0);
    }
}

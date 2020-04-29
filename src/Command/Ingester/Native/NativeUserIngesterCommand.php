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

namespace App\Command\Ingester\Native;

use App\Command\CommandProgressBarFormatterTrait;
use App\DataTransferObject\AssignmentDto;
use App\DataTransferObject\UserDto;
use App\DataTransferObject\UserDtoCollection;
use App\Entity\Assignment;
use App\Entity\User;
use App\Exception\LineItemNotFoundException;
use App\Ingester\Ingester\NativeUserIngester;
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Ingester\Source\IngesterSourceInterface;
use App\Model\LineItemCollection;
use App\Repository\LineItemRepository;
use App\Repository\NativeUserRepository;
use Exception;
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

    /** @var NativeUserRepository */
    private $nativeUserRepository;

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
        NativeUserRepository $nativeUserRepository,
        LineItemRepository $lineItemRepository,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->ingesterSourceRegistry = $ingesterSourceRegistry;
        $this->nativeUserIngester = $nativeUserIngester;
        $this->nativeUserRepository = $nativeUserRepository;
        $this->lineItemRepository = $lineItemRepository;
        $this->passwordEncoder = $passwordEncoder;

        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('Responsible for native user ingesting from various sources (Local file, S3 bucket)');

        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            sprintf(
                'Source type to ingest from, possible values: ["%s"]',
                implode('", "', array_keys($this->ingesterSourceRegistry->all()))
            )
        );

        $this->addArgument(
            'path',
            InputArgument::REQUIRED,
            'Source path to ingest from'
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
            $numberOfUsers = (int)$process->getOutput() - 1;

            $progressBar->setMaxSteps($numberOfUsers);
            $progressBar->start();

            $index = $this->nativeUserRepository->findNextAvailableUserIndex();
            $lineItemCollection = $this->lineItemRepository->findAll();

            if ($lineItemCollection->isEmpty()) {
                throw new Exception("Cannot native ingest 'user' since line-item table is empty.");
            }

            $userDtoCollection = new UserDtoCollection();
            foreach ($source->getContent() as $row) {
                $userDtoCollection->add($this->createUserDto($lineItemCollection, $row, $index));

                if ($index % $this->batchSize === 0) {
                    if (!$this->isDryRun) {
                        $this->nativeUserIngester->ingest($userDtoCollection);
                    }

                    $userDtoCollection->clear();
                    $progressBar->advance($this->batchSize);
                }

                $index++;
            }

            if (!$this->isDryRun && !$userDtoCollection->isEmpty()) {
                $this->nativeUserIngester->ingest($userDtoCollection);
            }

//            foreach ($this->errors as $error) {
//                $this->symfonyStyle->error($error);
//            }

            $progressBar->finish();
        } catch (Throwable $exception) {
            $this->symfonyStyle->error($exception->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     *
     * @throws LineItemNotFoundException
     */
    private function createUserDto(LineItemCollection $lineItemCollection, array $row, int $index): UserDto
    {
        $lineItem = $lineItemCollection->getBySlug($row['slug']);
        $newAssignment = new AssignmentDto($index, Assignment::STATE_READY, $index, $lineItem->getId());

        return new UserDto(
            $index,
            $row['username'],
            $this->passwordEncoder->encodePassword(new User(), $row['password']),
            $newAssignment,
            [],
            $row['groupId'] ?? null
        );
    }
}

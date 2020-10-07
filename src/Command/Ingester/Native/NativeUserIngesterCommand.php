<?php

declare(strict_types=1);

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

namespace App\Command\Ingester\Native;

use App\Command\CommandProgressBarFormatterTrait;
use App\Entity\Assignment;
use App\Entity\LineItem;
use App\Entity\User;
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Ingester\Source\IngesterSourceInterface;
use App\Repository\AssignmentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\ResultSetMapping;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Throwable;

class NativeUserIngesterCommand extends Command
{
    use CommandProgressBarFormatterTrait;

    public const NAME = 'roster:native-ingest:user';

    private const DEFAULT_BATCH_SIZE = 1000;

    /** @var IngesterSourceRegistry */
    private $ingesterSourceRegistry;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var UserRepository */
    private $userRepository;

    /** @var AssignmentRepository */
    private $assignmentRepository;

    /** @var UserPasswordEncoderInterface */
    private $passwordEncoder;

    /** @var array */
    private $userQueryParts = [];

    /** @var array */
    private $assignmentQueryParts = [];

    /** @var array */
    private $errors = [];

    /** @var string */
    private $kernelEnvironment;

    /** @var SymfonyStyle */
    private $symfonyStyle;

    /** @var bool */
    private $isDryRun;

    /** @var int */
    private $batchSize;

    public function __construct(
        IngesterSourceRegistry $ingesterSourceRegistry,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        AssignmentRepository $assignmentRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        string $kernelEnvironment
    ) {
        $this->ingesterSourceRegistry = $ingesterSourceRegistry;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->assignmentRepository = $assignmentRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->kernelEnvironment = $kernelEnvironment;

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

        $resultSetMapping = new ResultSetMapping();

        $progressBar = $this->createNewFormattedProgressBar($output);

        try {
            $source = $this->ingesterSourceRegistry
                ->get($input->getArgument('source'))
                ->setPath($input->getArgument('path'))
                ->setDelimiter($input->getOption('delimiter'))
                ->setCharset($input->getOption('charset'));

            $progressBar->setMaxSteps($source->count());
            $progressBar->start();

            $lineItemCollection = $this->fetchLineItems();

            $batchedRawUsers = [];
            $numberOfProcessedRows = 1;
            foreach ($source->getContent() as $rawUser) {
                $batchedRawUsers[] = $rawUser;
                if ($numberOfProcessedRows % $this->batchSize === 0) {
                    $this->processBatchedRawUsers($batchedRawUsers, $lineItemCollection);
                    $batchedRawUsers = [];
                    $progressBar->advance($this->batchSize);
                }

                $numberOfProcessedRows++;
            }

            if ($batchedRawUsers) {
                $this->processBatchedRawUsers($batchedRawUsers, $lineItemCollection);
            }
        } catch (Throwable $exception) {
            $this->errors[] = $exception->getMessage();
            $this->userQueryParts = [];
            $this->assignmentQueryParts = [];
        } finally {
            $this->refreshSequences($resultSetMapping);
            $progressBar->finish();
        }

        foreach ($this->errors as $error) {
            $this->symfonyStyle->error($error);
        }

        return (int)!empty($this->errors);
    }

    /**
     * @param LineItem[] $lineItemCollection
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function processBatchedRawUsers(array $rawUsers, array $lineItemCollection): void
    {
        $usernamesToCheck = array_map(
            static function (array $rawUser) {
                return $rawUser['username'];
            },
            $rawUsers
        );

        $existingUsernames = [];
        foreach ($this->userRepository->findBy(['username' => array_unique($usernamesToCheck)]) as $user) {
            $existingUsernames[$user->getId()] = $user->getUsername();
        }

        $userIndex = $this->getAvailableUserStartIndex();
        $assignmentIndex = $this->getAvailableAssignmentStartIndex();

        $resultSetMapping = new ResultSetMapping();
        $user = new User();
        foreach ($rawUsers as $rawUser) {
            if (in_array($rawUser['username'], $existingUsernames, true)) {
                $userId = array_search($rawUser['username'], $existingUsernames, true);

                $this->assignmentQueryParts[] = sprintf(
                    "(%s, %s, %s, '%s', %d)",
                    $assignmentIndex,
                    $userId,
                    $lineItemCollection[$rawUser['slug']]->getId(),
                    Assignment::STATE_READY,
                    0
                );
            } else {
                $this->userQueryParts[] = sprintf(
                    "(%s, '%s', '%s', '[]', '%s')",
                    $userIndex,
                    $rawUser['username'],
                    $this->encodeUserPassword($user, $rawUser['password']),
                    $rawUser['groupId'] ?? null
                );

                $this->assignmentQueryParts[] = sprintf(
                    "(%s, %s, %s, '%s', %d)",
                    $assignmentIndex,
                    $userIndex,
                    $lineItemCollection[$rawUser['slug']]->getId(),
                    Assignment::STATE_READY,
                    0
                );

                // Make sure we don't create the same user twice
                $existingUsernames[$userIndex] = $rawUser['username'];

                $userIndex++;
            }

            $assignmentIndex++;
        }

        $this->executeNativeInsertions($resultSetMapping);
        $this->refreshSequences($resultSetMapping);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function getAvailableUserStartIndex(): int
    {
        $index = $this->userRepository
            ->createQueryBuilder('u')
            ->select('MAX(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $index + 1;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function getAvailableAssignmentStartIndex(): int
    {
        $index = $this->assignmentRepository
            ->createQueryBuilder('a')
            ->select('MAX(a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $index + 1;
    }

    private function executeNativeInsertions(ResultSetMapping $mapping): void
    {
        if (!empty($this->userQueryParts) && !$this->isDryRun) {
            $userQuery = sprintf(
                'INSERT INTO users (id, username, password, roles, group_id) VALUES %s',
                implode(',', $this->userQueryParts)
            );

            $this->entityManager->createNativeQuery($userQuery, $mapping)->execute();
        }

        if (!empty($this->assignmentQueryParts) && !$this->isDryRun) {
            $assignmentQuery = sprintf(
                'INSERT INTO assignments (id, user_id, line_item_id, state, attempts_count) VALUES %s',
                implode(',', $this->assignmentQueryParts)
            );

            $this->entityManager->createNativeQuery($assignmentQuery, $mapping)->execute();
        }

        $this->userQueryParts = [];
        $this->assignmentQueryParts = [];
    }

    /**
     * @codeCoverageIgnore Cannot be tested with SQLite database
     */
    private function refreshSequences(ResultSetMapping $mapping): void
    {
        if ($this->kernelEnvironment !== 'test' && !$this->isDryRun) {
            $this->entityManager
                ->createNativeQuery(
                    "SELECT SETVAL('assignments_id_seq', COALESCE(MAX(id), 1)) FROM assignments",
                    $mapping
                )
                ->execute();

            $this->entityManager
                ->createNativeQuery(
                    "SELECT SETVAL('users_id_seq', COALESCE(MAX(id), 1)) FROM users",
                    $mapping
                )
                ->execute();
        }
    }

    private function encodeUserPassword(User $user, string $value): string
    {
        return $this->passwordEncoder->encodePassword($user, $value);
    }

    /**
     * @return LineItem[]
     * @throws Exception
     */
    private function fetchLineItems(): array
    {
        /** @var LineItem[] $lineItems */
        $lineItems = $this->entityManager->getRepository(LineItem::class)->findAll();

        if (empty($lineItems)) {
            throw new Exception("Cannot native ingest 'user' since line-item table is empty.");
        }

        $lineItemCollection = [];
        foreach ($lineItems as $lineItem) {
            $lineItemCollection[$lineItem->getSlug()] = $lineItem;
        }

        return $lineItemCollection;
    }
}

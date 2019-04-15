<?php declare(strict_types=1);

namespace App\Command\Ingester\Native;

use App\Command\CommandWatcherTrait;
use App\Entity\Assignment;
use App\Entity\LineItem;
use App\Entity\User;
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Ingester\Source\IngesterSourceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Exception;
use Throwable;

class NativeUserIngesterCommand extends Command
{
    use CommandWatcherTrait;

    public const NAME = 'roster:native-ingest:user';
    private const DEFAULT_BATCH_SIZE = 1000;

    /** @var IngesterSourceRegistry */
    private $ingesterSourceRegistry;

    /** @var EntityManagerInterface */
    private $entityManager;

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

    public function __construct(
        IngesterSourceRegistry $ingesterSourceRegistry,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder,
        string $kernelEnvironment
    ) {
        $this->ingesterSourceRegistry = $ingesterSourceRegistry;
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->kernelEnvironment = $kernelEnvironment;

        parent::__construct(self::NAME);
    }

    protected function configure()
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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->startWatch(self::NAME, __FUNCTION__);
        $consoleOutput = $this->ensureConsoleOutput($output);
        $style = new SymfonyStyle($input, $consoleOutput);
        $section = $consoleOutput->section();
        $section->writeln('Starting user ingestion...');
        $batchSize = $input->getOption('batch');

        $resultSetMapping = new ResultSetMapping();
        $user = new User();

        try {
            $source = $this->ingesterSourceRegistry
                ->get($input->getArgument('source'))
                ->setPath($input->getArgument('path'))
                ->setDelimiter($input->getOption('delimiter'))
                ->setCharset($input->getOption('charset'));

            $index = $this->getAvailableStartIndex();
            $lineItemCollection = $this->fetchLineItems();

            foreach ($source->getContent() as $row) {
                $this->userQueryParts[] = sprintf(
                    "(%s, '%s', '%s', '[]')",
                    $index,
                    $row['username'],
                    $this->encodeUserPassword($user, $row['password'])
                );

                $this->assignmentQueryParts[] = sprintf(
                    "(%s, %s, %s, '%s')",
                    $index,
                    $index,
                    $lineItemCollection[$row['slug']]->getId(),
                    Assignment::STATE_READY
                );

                if ($index % $batchSize === 0) {
                    $this->executeNativeInsertions($resultSetMapping);
                    $section->overwrite(sprintf('Success: %s, batched errors: %s', $index, count($this->errors)));
                }

                $index++;
            }

            $this->executeNativeInsertions($resultSetMapping);
            $section->overwrite(sprintf(
                'Total of users imported: %s, batched errors: %s',
                $this->getRealUserCount(),
                count($this->errors)
            ));

            if (!empty($this->errors)) {
                foreach ($this->errors as $error) {
                    $style->error($error);
                }
            }
        } catch (Throwable $exception) {
            $style->error($exception->getMessage());

            return 1;
        } finally {
            $this->refreshSequences($resultSetMapping);
            $style->note(sprintf('Took: %s', $this->stopWatch(self::NAME)));
        }

        return 0;
    }

    private function getAvailableStartIndex(): int
    {
        $index = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('MAX(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $index + 1;
    }

    private function getRealUserCount(): int
    {
        $count = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$count;
    }

    private function executeNativeInsertions(ResultSetMapping $mapping): void
    {
        try {
            if (!empty($this->userQueryParts) && !empty($this->assignmentQueryParts)) {
                $userQuery = sprintf(
                    'INSERT INTO users (id, username, password, roles) VALUES %s',
                    implode(',', $this->userQueryParts)
                );

                $this->entityManager->createNativeQuery($userQuery, $mapping)->execute();

                $assignmentQuery = sprintf(
                    'INSERT INTO assignments (id, user_id, line_item_id, state) VALUES %s',
                    implode(',', $this->assignmentQueryParts)
                );

                $this->entityManager->createNativeQuery($assignmentQuery, $mapping)->execute();
            }
        } catch (Throwable $exception) {
            $this->errors[] = $exception->getMessage();
        }

        $this->userQueryParts = [];
        $this->assignmentQueryParts = [];
    }

    private function refreshSequences(ResultSetMapping $mapping): void
    {
        if ($this->kernelEnvironment !== 'test') {
            $this->entityManager
                ->createNativeQuery(
                    "SELECT SETVAL('assignments_id_seq', COALESCE(MAX(id), 1) ) FROM assignments",
                    $mapping
                )
                ->execute();

            $this->entityManager
                ->createNativeQuery(
                    "SELECT SETVAL('users_id_seq', COALESCE(MAX(id), 1) ) FROM users",
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

    /**
     * @throws LogicException
     */
    private function ensureConsoleOutput(OutputInterface $output): ConsoleOutputInterface
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new LogicException(
                sprintf(
                    "Output must be instance of '%s' because of section usage.",
                    ConsoleOutputInterface::class
                )
            );
        }

        return $output;
    }
}

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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Exception;
use Throwable;

class NativeUserIngesterCommand extends Command
{
    use CommandWatcherTrait;

    public const NAME = 'roster:native-ingest:user';
    private const BATCH_SIZE = 1000;

    /** @var IngesterSourceRegistry */
    private $sourceRegistry;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var UserPasswordEncoderInterface */
    private $passwordEncoder;

    public function __construct(
        IngesterSourceRegistry $sourceRegistry,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->sourceRegistry = $sourceRegistry;
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;

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
                implode('", "', array_keys($this->sourceRegistry->all()))
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
            'memory',
            'm',
            InputOption::VALUE_REQUIRED,
            'PHP memory limit',
            '1G'
        );
    }

    /**
     * @param InputInterface $input
     * @param ConsoleOutput|OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->startWatch(self::NAME, __FUNCTION__);
        $style = new SymfonyStyle($input, $output);

        $section = $output->section();
        $section->writeln('Starting user ingestion...');

        try {
            ini_set('memory_limit', $input->getOption('memory'));

            $source = $this->sourceRegistry
                ->get($input->getArgument('source'))
                ->setPath($input->getArgument('path'))
                ->setDelimiter($input->getOption('delimiter'));

            $index = 1;
            $lineItemCollection = $this->fetchLineItems();
            $resultSetMapping = new ResultSetMapping();
            $user = new User();
            $userQueryParts = [];
            $assignmentQueryParts = [];

            foreach ($source->getContent() as $row) {

                $userQueryParts[] = sprintf(
                    "(%s, '%s', '%s', '[]')",
                    $index,
                    $row[0],
                    $this->encodeUserPassword($user, $row[1])
                );

                $assignmentQueryParts[] = sprintf(
                    "(%s, %s, %s, '%s')",
                    $index,
                    $index,
                    $lineItemCollection[$row[2]]->getId(),
                    Assignment::STATE_READY
                );

                if ($index % self::BATCH_SIZE === 0) {

                    $this->executeNativeInsertions($resultSetMapping, $userQueryParts, $assignmentQueryParts);

                    $userQueryParts = [];
                    $assignmentQueryParts = [];

                    $section->overwrite(sprintf('Number of users imported so far: %s', $index));
                }

                $index++;
            }

            $this->executeNativeInsertions($resultSetMapping, $userQueryParts, $assignmentQueryParts);
            $section->overwrite(sprintf('Total of users imported: %s', $index));

        } catch (Throwable $exception) {
            $style->error($exception->getMessage());

            return 1;
        } finally {
            $style->note(sprintf('Took: %s', $this->stopWatch(self::NAME)));
        }

        return 0;
    }

    private function executeNativeInsertions(ResultSetMapping $mapping, array $userQueryParts, array $assignmentQueryParts): void
    {
        $userQuery = sprintf(
            "INSERT INTO users (id, username, password, roles) VALUES %s",
            implode(',', $userQueryParts)
        );

        $this->entityManager->createNativeQuery($userQuery, $mapping)->execute();

        $assignmentQuery = sprintf(
            "INSERT INTO assignments (id, user_id, line_item_id, state) VALUES %s",
            implode(',', $assignmentQueryParts)
        );

        $this->entityManager->createNativeQuery($assignmentQuery, $mapping)->execute();
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

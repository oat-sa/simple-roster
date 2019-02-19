<?php declare(strict_types=1);

namespace App\Command\Ingester\User;

use App\Entity\Assignment;
use App\Entity\LineItem;
use App\Entity\User;
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Ingester\Source\IngesterSourceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Throwable;

class NativeUserIngesterCommand extends Command
{
    public const NAME = 'roster:native-ingest:user';

    /** @var IngesterSourceRegistry */
    private $sourceRegistry;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Stopwatch */
    private $stopwatch;

    /** UserPasswordEncoderInterface */
    private $userPasswordEncoderInterface;

    /** User */
    private $user;

    /** LineItem[] */
    private $lineItemCollection = [];

    public function __construct(
        IngesterSourceRegistry $sourceRegistry,
        EntityManagerInterface $entityManager,
        Stopwatch $stopwatch,
        UserPasswordEncoderInterface $userPasswordEncoderInterface
    ) {
        $this->sourceRegistry = $sourceRegistry;
        $this->entityManager = $entityManager;
        $this->stopwatch = $stopwatch;
        $this->userPasswordEncoderInterface = $userPasswordEncoderInterface;
        $this->user = new User();

        parent::__construct(static::NAME);
    }

    protected function configure()
    {
        $this->setDescription('Responsible for user ingesting from various sources (Local file, S3 bucket)');

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
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Causes data ingestion to be applied into storage'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '1G');

        $this->stopwatch->start(__CLASS__, __FUNCTION__);
        $style = new SymfonyStyle($input, $output);

        try {
            $source = $this->sourceRegistry
                ->get($input->getArgument('source'))
                ->setPath($input->getArgument('path'))
                ->setDelimiter($input->getOption('delimiter'));

            $iterator = 1;
            $resultSetMapping = new ResultSetMapping();
            $this->fetchLineItems();

            $userQueryParts = [];
            $assignmentQueryParts = [];

            foreach ($source->getContent() as $row) {

                $userQueryParts[] = sprintf(
                    "(%s, '%s', '%s', '[]')",
                    $iterator,
                    $row[0],
                    $this->encodePassword($row[1])
                );

                $assignmentQueryParts[] = sprintf(
                    "(%s, %s, %s, '%s')",
                    $iterator,
                    $iterator,
                    $this->lineItemCollection[$row[2]]->getId(),
                    Assignment::STATE_READY
                );

                if ($iterator % 1000 === 0) {

                    $userQuery = sprintf(
                        "INSERT INTO users (id, username, password, roles) VALUES %s",
                        implode(', ', $userQueryParts)
                    );

                    $this->entityManager->createNativeQuery($userQuery, $resultSetMapping)->execute();

                    $assignmentQuery = sprintf(
                        "INSERT INTO assignments (id, user_id, line_item_id, state) VALUES %s",
                        implode(', ', $assignmentQueryParts)
                    );

                    $this->entityManager->createNativeQuery($assignmentQuery, $resultSetMapping)->execute();

                    $userQueryParts = [];
                    $assignmentQueryParts = [];
                }

                $iterator++;
            }

        } catch (Throwable $exception) {
            $style->error($exception->getMessage());

            return 1;
        } finally {
            $event = $this->stopwatch->stop(__CLASS__);
            $style->note(sprintf('Took: %s', $event));
        }

        return 0;
    }

    private function encodePassword(string $value): string
    {
        return $this->userPasswordEncoderInterface->encodePassword($this->user, $value);
    }

    /**
     * @throws Exception
     */
    private function fetchLineItems(): void
    {
        /** @var LineItem[] $lineItems */
        $lineItems = $this->entityManager->getRepository(LineItem::class)->findAll();

        if (empty($lineItems)) {
            throw new Exception(
                sprintf("Cannot native ingest 'user' since line-item table is empty.", $this->getRegistryItemName())
            );
        }

        foreach ($lineItems as $lineItem) {
            $this->lineItemCollection[$lineItem->getSlug()] = $lineItem;
        }
    }
}

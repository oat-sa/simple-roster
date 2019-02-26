<?php declare(strict_types=1);

namespace App\Command\Cache;

use App\Command\CommandWatcherTrait;
use App\Entity\User;
use App\Generator\UserCacheIdGenerator;
use App\Repository\UserRepository;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DoctrineResultCacheWarmerCommand extends Command
{
    use CommandWatcherTrait;

    public const NAME = 'roster:doctrine-result-cache:warmup';
    private const DEFAULT_BATCH_SIZE = 1000;

    /** @var Cache|null */
    private $resultCacheImplementation;

    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    /** @var UserRepository */
    private $userRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        UserCacheIdGenerator $userCacheIdGenerator,
        Configuration $doctrineConfiguration,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct(self::NAME);

        $this->userCacheIdGenerator = $userCacheIdGenerator;
        $this->resultCacheImplementation = $doctrineConfiguration->getResultCacheImpl();
        $this->entityManager = $entityManager;

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $this->userRepository = $userRepository;
    }

    protected function configure()
    {
        parent::configure();

        $this->setDescription('Warms up doctrine result cache.');

        $this->addOption(
            'batch-size',
            'b',
            InputOption::VALUE_REQUIRED,
            'Number of assignments to process per batch',
            self::DEFAULT_BATCH_SIZE
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->startWatch(self::NAME, __FUNCTION__);
        $consoleOutput = $this->ensureConsoleOutput($output);
        $style = new SymfonyStyle($input, $consoleOutput);

        $section = $consoleOutput->section();

        $batchSize = (int)$input->getOption('batch-size');
        if ($batchSize < 1) {
            throw new InvalidArgumentException("Invalid 'batch-size' argument received.");
        }

        $offset = 0;
        $numberOfWarmedUpCacheEntries = 0;

        $style->note('Warming up doctrine result cache...');
        $section->writeln('Number of warmed up cache entries: 0');
        $numberOfTotalUsers = $this->getTotalNumberOfUsers();

        do {
            $iterateResult = $this
                ->getFindAllUsersNameQuery($offset, $batchSize)
                ->iterate();

            foreach ($iterateResult as $row) {
                $this->warmUpResultCacheForUserName(current($row)['username']);

                $numberOfWarmedUpCacheEntries++;

                unset($row);
            }

            if ($numberOfWarmedUpCacheEntries % $batchSize === 0) {
                $section->overwrite(
                    sprintf('Number of warmed up cache entries: %s', $numberOfWarmedUpCacheEntries)
                );
            }

            $offset += $batchSize;
        } while ($offset <= $numberOfTotalUsers + $batchSize);

        $style->success(
            sprintf(
                '%s result cache entries have been successfully warmed up.',
                $numberOfWarmedUpCacheEntries
            )
        );

        $style->note(sprintf('Took: %s', $this->stopWatch(self::NAME)));

        return 0;
    }

    private function getFindAllUsersNameQuery(int $offset, int $batchSize): Query
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('u.username')
            ->from(User::class, 'u')
            ->setFirstResult($offset)
            ->setMaxResults($batchSize)
            ->getQuery()
            ->setHydrationMode(Query::HYDRATE_SINGLE_SCALAR);
    }

    private function getTotalNumberOfUsers(): int
    {
        return (int)$this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function warmUpResultCacheForUserName(string $username): void
    {
        $resultCacheId = $this->userCacheIdGenerator->generate($username);
        $this->resultCacheImplementation->delete($resultCacheId);

        // Refresh by query
        $user = $this->userRepository->getByUsernameWithAssignments($username);
        $this->entityManager->clear();
        unset($user);
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

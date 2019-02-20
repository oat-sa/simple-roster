<?php declare(strict_types=1);

namespace App\Command\Cache;

use App\Generator\UserCacheIdGenerator;
use App\Repository\UserRepository;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Configuration;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class DoctrineResultCacheWarmerCommand extends Command
{
    public const NAME = 'roster:doctrine-result-cache:warmup';

    private const DEFAULT_BATCH_SIZE = 1000;

    /** @var Cache|null */
    private $resultCacheImplementation;

    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    /** @var UserRepository */
    private $userRepository;

    /** @var Stopwatch */
    private $stopwatch;

    public function __construct(
        UserCacheIdGenerator $userCacheIdGenerator,
        UserRepository $userRepository,
        Configuration $doctrineConfiguration,
        Stopwatch $stopwatch
    ) {
        parent::__construct(self::NAME);

        $this->userCacheIdGenerator = $userCacheIdGenerator;
        $this->userRepository = $userRepository;
        $this->resultCacheImplementation = $doctrineConfiguration->getResultCacheImpl();
        $this->stopwatch = $stopwatch;
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
        $this->stopwatch->start(self::NAME, __FUNCTION__);
        $style = new SymfonyStyle($input, $output);

        $batchSize = (int)$input->getOption('batch-size');
        if ($batchSize < 1) {
            throw new InvalidArgumentException("Invalid 'batch-size' argument received.");
        }

        $offset = 0;
        $numberOfWarmedUpCacheEntries = 0;

        $style->note('Warming up doctrine result cache...');
        do {
            $users = $this->userRepository->findAllPaginated($batchSize, $offset);
            foreach ($users as $user) {
                $resultCacheId = $this->userCacheIdGenerator->generate($user->getUsername());
                $this->resultCacheImplementation->delete($resultCacheId);

                // Refresh by query
                $this->userRepository->getByUsernameWithAssignments($user->getUsername());
                $numberOfWarmedUpCacheEntries++;
            }

            $offset += $batchSize;
        } while ($users->getIterator()->count() === $batchSize);

        $style->success(
            sprintf(
                '%s result cache entries have been successfully warmed up.',
                $numberOfWarmedUpCacheEntries
            )
        );
        $style->note(sprintf('Took to %s:', $this->stopwatch->stop(self::NAME)));

        return 0;
    }
}

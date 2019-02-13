<?php declare(strict_types=1);

namespace App\Command;

use App\Generator\UserCacheIdGenerator;
use App\Repository\UserRepository;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrineResultCacheWarmerCommand extends Command
{
    public const NAME = 'roster:doctrine:result-cache:warm-up';

    /** @var Cache|null */
    private $resultCacheImplementation;

    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    /** @var UserRepository */
    private $userRepository;

    public function __construct(
        UserCacheIdGenerator $userCacheIdGenerator,
        UserRepository $userRepository,
        Configuration $doctrineConfiguration
    ) {
        parent::__construct(self::NAME);

        $this->userCacheIdGenerator = $userCacheIdGenerator;
        $this->userRepository = $userRepository;
        $this->resultCacheImplementation = $doctrineConfiguration->getResultCacheImpl();
    }

    protected function configure()
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return 0;
    }
}

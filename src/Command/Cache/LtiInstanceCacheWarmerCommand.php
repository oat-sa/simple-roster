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

namespace OAT\SimpleRoster\Command\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use OAT\SimpleRoster\Command\BlackfireProfilerTrait;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class LtiInstanceCacheWarmerCommand extends Command
{
    use BlackfireProfilerTrait;

    public const NAME = 'roster:cache-warmup:lti-instance';

    /** @var LtiInstanceRepository */
    private LtiInstanceRepository $ltiInstanceRepository;

    /** @var SymfonyStyle */
    private SymfonyStyle $symfonyStyle;

    /** @var CacheProvider */
    private $resultCacheImplementation;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var int */
    private int $ltiInstancesCacheTtl;

    public function __construct(
        LtiInstanceRepository $ltiInstanceRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $cacheWarmupCacheWarmupLogger,
        int $ltiInstancesCacheTtl
    ) {
        $this->ltiInstanceRepository = $ltiInstanceRepository;
        $this->logger = $cacheWarmupCacheWarmupLogger;
        $this->ltiInstancesCacheTtl = $ltiInstancesCacheTtl;

        $resultCacheImplementation = $entityManager->getConfiguration()->getResultCacheImpl();

        if (!$resultCacheImplementation instanceof CacheProvider) {
            throw new DoctrineResultCacheImplementationNotFoundException(
                'Doctrine result cache implementation is not configured.'
            );
        }

        $this->resultCacheImplementation = $resultCacheImplementation;

        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addBlackfireProfilingOption();

        $this->setDescription('LTI instance cache warmup');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);
        $this->symfonyStyle->title('Simple Roster - LTI Instance Cache Warmer');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->symfonyStyle->comment('Executing cache warmup...');

            $this->resultCacheImplementation->delete(LtiInstanceRepository::CACHE_ID_ALL_LTI_INSTANCES);

            // Refresh by query
            $ltiInstances = $this->ltiInstanceRepository->findAllAsCollection();

            if ($ltiInstances->isEmpty()) {
                $this->symfonyStyle->warning('There are no LTI instances found in the database.');

                return 0;
            }

            $this->logger->info(
                sprintf('Result cache for %d LTI instances have been successfully warmed up.', count($ltiInstances)),
                [
                    'cacheKey' => LtiInstanceRepository::CACHE_ID_ALL_LTI_INSTANCES,
                    'cacheTtl' => number_format($this->ltiInstancesCacheTtl),
                ]
            );

            $this->symfonyStyle->success(
                sprintf(
                    'Result cache for %d LTI instances have been successfully warmed up. [TTL: %s seconds]',
                    count($ltiInstances),
                    number_format($this->ltiInstancesCacheTtl)
                )
            );
        } catch (Throwable $exception) {
            $this->symfonyStyle->error(sprintf('An unexpected error occurred: %s', $exception->getMessage()));

            return 1;
        }

        return 0;
    }
}

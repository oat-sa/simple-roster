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
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Generator\LineItemCacheIdGenerator;
use OAT\SimpleRoster\Repository\LineItemRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class LineItemCacheWarmerCommand extends Command
{
    use BlackfireProfilerTrait;

    public const NAME = 'roster:cache-warmup:line-item';

    /** @var SymfonyStyle */
    private $symfonyStyle;

    /** @var CacheProvider */
    private $resultCacheImplementation;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $lineItemCacheTtl;

    /** @var LineItemRepository */
    private $lineItemRepository;

    /** @var LineItemCacheIdGenerator */
    private $lineItemCacheIdGenerator;

    public function __construct(
        LineItemRepository $lineItemRepository,
        LineItemCacheIdGenerator $lineItemCacheIdGenerator,
        EntityManagerInterface $entityManager,
        LoggerInterface $cacheWarmupLogger,
        int $lineItemCacheTtl
    ) {
        parent::__construct(self::NAME);

        $this->logger = $cacheWarmupLogger;
        $this->lineItemCacheTtl = $lineItemCacheTtl;

        $resultCacheImplementation = $entityManager->getConfiguration()->getResultCacheImpl();

        if (!$resultCacheImplementation instanceof CacheProvider) {
            throw new DoctrineResultCacheImplementationNotFoundException(
                'Doctrine result cache implementation is not configured.'
            );
        }

        $this->resultCacheImplementation = $resultCacheImplementation;
        $this->lineItemRepository = $lineItemRepository;
        $this->lineItemCacheIdGenerator = $lineItemCacheIdGenerator;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addBlackfireProfilingOption();

        $this->setDescription('Line Item cache warmup');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);
        $this->symfonyStyle->title('Simple Roster - Line Item Cache Warmer');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->symfonyStyle->comment('Executing cache warmup...');

            $lineItemCollection = $this->lineItemRepository->findAllAsCollection();

            if ($lineItemCollection->isEmpty()) {
                $this->symfonyStyle->warning('There are no Line Items found in the database.');

                return 0;
            }
            /** @var LineItem $lineItem */
            foreach ($lineItemCollection as $lineItem) {
                $id = (int)$lineItem->getId();
                $lineItemCacheKey = $this->lineItemCacheIdGenerator->generate($id);

                $this->resultCacheImplementation->delete($lineItemCacheKey);
                $this->lineItemRepository->findById($id);

                $this->logger->info(
                    sprintf('Result cache for Line Item Id %d have been successfully warmed up.', $id),
                    [
                        'cacheKey' => $lineItemCacheKey,
                        'cacheTtl' => number_format($this->lineItemCacheTtl),
                    ]
                );
            }

            $this->symfonyStyle->success(
                sprintf(
                    'Result cache for %d Line Items have been successfully warmed up. [TTL: %s seconds]',
                    count($lineItemCollection),
                    number_format($this->lineItemCacheTtl)
                )
            );
        } catch (Throwable $exception) {
            $this->symfonyStyle->error(sprintf('An unexpected error occurred: %s', $exception->getMessage()));

            return 1;
        }

        return 0;
    }
}

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

namespace OAT\SimpleRoster\Tests\Unit\EventListener\Doctrine;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\EventListener\Doctrine\EntityListenerInterface;
use OAT\SimpleRoster\EventListener\Doctrine\WarmUpLineItemCacheListener;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Generator\LineItemCacheIdGenerator;
use OAT\SimpleRoster\Repository\LineItemRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

class WarmUpLineItemCacheListenerTest extends TestCase
{
    /** @var Configuration|MockObject */
    private $doctrineConfiguration;

    /** @var EntityManagerInterface|MockObject */
    private $entityManager;

    /** @var LineItemRepository|MockObject */
    private $lineItemRepository;

    /** @var LineItemCacheIdGenerator|MockObject */
    private $lineItemCacheIdGenerator;

    protected function setUp(): void
    {
        $this->doctrineConfiguration = $this->createMock(Configuration::class);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->method('getConfiguration')
            ->willReturn($this->doctrineConfiguration);

        $this->lineItemCacheIdGenerator = $this->createMock(LineItemCacheIdGenerator::class);
        $this->lineItemRepository = $this->createMock(LineItemRepository::class);
    }

    public function testItThrowsExceptionIfDoctrineResultCacheImplementationIsNotConfigured(): void
    {
        $this->expectException(DoctrineResultCacheImplementationNotFoundException::class);
        $this->expectExceptionMessage('Doctrine result cache implementation is not configured.');

        new WarmUpLineItemCacheListener(
            $this->lineItemRepository,
            $this->entityManager,
            $this->lineItemCacheIdGenerator
        );
    }

    public function testItIsDoctrineEntityListener(): void
    {
        $this->doctrineConfiguration->expects(self::once())
            ->method('getResultCache')
            ->willReturn($this->createMock(CacheItemPoolInterface::class));

        $subject = new WarmUpLineItemCacheListener(
            $this->lineItemRepository,
            $this->entityManager,
            $this->lineItemCacheIdGenerator
        );

        self::assertInstanceOf(EntityListenerInterface::class, $subject);
    }

    public function testItWarmsUpCacheDuringPostUpdate(): void
    {
        $this->lineItemRepository->expects(self::once())
            ->method('findOneById')
            ->with(1);

        $cacheProvider = $this->createMock(CacheItemPoolInterface::class);
        $cacheProvider->expects(self::once())
            ->method('delete')
            ->with('line_item_1');

        $this->doctrineConfiguration->expects(self::once())
            ->method('getResultCache')
            ->willReturn($cacheProvider);

        $this->lineItemCacheIdGenerator->expects(self::once())
            ->method('generate')
            ->with(1)
            ->willReturn('line_item_1');

        $subject = new WarmUpLineItemCacheListener(
            $this->lineItemRepository,
            $this->entityManager,
            $this->lineItemCacheIdGenerator
        );

        $lineItem = $this->createMock(LineItem::class);
        $lineItem->expects(self::once())
            ->method('getId')
            ->willReturn(1);

        $subject->postUpdate($lineItem);
    }
}

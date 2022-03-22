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

namespace OAT\SimpleRoster\Tests\Integration\Repository;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Generator\LineItemCacheIdGenerator;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LineItemRepositoryTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var LineItemRepository */
    private $subject;

    /** @var CacheItemPoolInterface */
    private $doctrineResultCacheImplementation;

    /** @var LineItemCacheIdGenerator */
    private $cacheIdGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();
        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        $this->cacheIdGenerator = self::getContainer()->get(LineItemCacheIdGenerator::class);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $this->doctrineResultCacheImplementation = $entityManager->getConfiguration()->getResultCache();
        $this->subject = self::getContainer()->get(LineItemRepository::class);
    }

    public function testItCanFindAllAsCollection(): void
    {
        $collection = $this->subject->findAllAsCollection();

        self::assertCount(3, $collection);
        self::assertSame(1, $collection->getBySlug('lineItemSlug1')->getId());
        self::assertSame(2, $collection->getBySlug('lineItemSlug2')->getId());
        self::assertSame(3, $collection->getBySlug('lineItemSlug3')->getId());
    }

    public function testItCanFindOneLineItemById(): void
    {
        $lineItem = $this->subject->findOneById(1);

        self::assertSame(1, $lineItem->getId());
    }

    public function testItCanFindLineItemsByIdsUsingCriteria(): void
    {
        $criteria = new FindLineItemCriteria();
        $criteria->addLineItemIds(1, 2, 3);

        $resultSet = $this->subject->findLineItemsByCriteria($criteria);

        self::assertCount(3, $resultSet);
        self::assertSame(1, $resultSet->getLineItemCollection()->getBySlug('lineItemSlug1')->getId());
        self::assertSame(2, $resultSet->getLineItemCollection()->getBySlug('lineItemSlug2')->getId());
        self::assertSame(3, $resultSet->getLineItemCollection()->getBySlug('lineItemSlug3')->getId());
    }

    public function testItCanFindLineItemsBySlugUsingCriteria(): void
    {
        $criteria = new FindLineItemCriteria();
        $criteria->addLineItemSlugs('lineItemSlug1', 'lineItemSlug2', 'lineItemSlug3');

        $resultSet = $this->subject->findLineItemsByCriteria($criteria);

        self::assertCount(3, $resultSet);
        self::assertSame(1, $resultSet->getLineItemCollection()->getBySlug('lineItemSlug1')->getId());
        self::assertSame(2, $resultSet->getLineItemCollection()->getBySlug('lineItemSlug2')->getId());
        self::assertSame(3, $resultSet->getLineItemCollection()->getBySlug('lineItemSlug3')->getId());
    }

    public function testItShouldReturnEmptyCollectionIfNoLineItemWasFoundUsingIdsCriteria(): void
    {
        $criteria = new FindLineItemCriteria();
        $criteria->addLineItemIds(1000, 1001);

        $collection = $this->subject->findLineItemsByCriteria($criteria);

        self::assertTrue($collection->isEmpty());
    }

    public function testItShouldReturnEmptyCollectionIfNoLineItemWasFoundUsingSlugsCriteria(): void
    {
        $criteria = new FindLineItemCriteria();
        $criteria->addLineItemSlugs('wrongSlug1', 'wrongSlug2');

        $collection = $this->subject->findLineItemsByCriteria($criteria);

        self::assertTrue($collection->isEmpty());
    }

    /**
     * @throws InvalidArgumentException
     * @throws EntityNotFoundException
     */
    public function testItUsesResultCacheImplementationForFindingLineItemById(): void
    {
        $id = 1;

        $expectedResultCacheId = $this->cacheIdGenerator->generate($id);

        self::assertFalse($this->doctrineResultCacheImplementation->hasItem($expectedResultCacheId));

        $this->subject->findOneById($id);

        self::assertTrue($this->doctrineResultCacheImplementation->hasItem($expectedResultCacheId));
    }

    public function testItThrowsExceptionIfLineItemCannotBeFound(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $this->subject->findOneById(10);
    }

    public function testItCreatesNewLineItem(): void
    {
        $lineItem = (new LineItem())
            ->setSlug('my-slug')
            ->setLabel('my-label')
            ->setUri('my-uri');

        $result = $this->subject->createOrUpdate($lineItem);

        self::assertSame($lineItem, $result);
    }

    public function testUpdatesExistingLineItem(): void
    {
        $lineItem = (new LineItem())
            ->setSlug('lineItemSlug1')
            ->setLabel('The first line item')
            ->setUri('http://lineitemuri.com');

        $result = $this->subject->createOrUpdate($lineItem);

        self::assertSame($lineItem->getSlug(), $result->getSlug());
        self::assertSame($lineItem->getLabel(), $result->getLabel());
        self::assertSame($lineItem->getUri(), $result->getUri());
    }
}

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
use Doctrine\ORM\EntityNotFoundException;
use OAT\SimpleRoster\Generator\LineItemCacheIdGenerator;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LineItemRepositoryTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var LineItemRepository */
    private $subject;

    /** @var Cache */
    private $doctrineResultCacheImplementation;

    /** @var LineItemCacheIdGenerator */
    private $cacheIdGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();
        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        $this->cacheIdGenerator = self::$container->get(LineItemCacheIdGenerator::class);
        $this->doctrineResultCacheImplementation = self::$container->get('doctrine.orm.default_result_cache');
        $this->subject = self::$container->get(LineItemRepository::class);
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

        $collection = $this->subject->findLineItemsByCriteria($criteria);

        self::assertCount(3, $collection);
        self::assertSame(1, $collection->getBySlug('lineItemSlug1')->getId());
        self::assertSame(2, $collection->getBySlug('lineItemSlug2')->getId());
        self::assertSame(3, $collection->getBySlug('lineItemSlug3')->getId());
    }

    public function testItCanFindLineItemsBySlugUsingCriteria(): void
    {
        $criteria = new FindLineItemCriteria();
        $criteria->addLineItemSlugs('lineItemSlug1', 'lineItemSlug2', 'lineItemSlug3');

        $collection = $this->subject->findLineItemsByCriteria($criteria);

        self::assertCount(3, $collection);
        self::assertSame(1, $collection->getBySlug('lineItemSlug1')->getId());
        self::assertSame(2, $collection->getBySlug('lineItemSlug2')->getId());
        self::assertSame(3, $collection->getBySlug('lineItemSlug3')->getId());
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

    public function testItUsesResultCacheImplementationForFindingLineItemById(): void
    {
        $id = 1;

        $expectedResultCacheId = $this->cacheIdGenerator->generate($id);

        self::assertFalse($this->doctrineResultCacheImplementation->contains($expectedResultCacheId));

        $this->subject->findOneById($id);

        self::assertTrue($this->doctrineResultCacheImplementation->contains($expectedResultCacheId));
    }

    public function testItThrowsExceptionIfLineItemCannotBeFound(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $this->subject->findOneById(10);
    }
}

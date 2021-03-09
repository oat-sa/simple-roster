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
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LtiInstanceRepositoryTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var LtiInstanceRepository */
    private $subject;

    /** @var Cache */
    private $doctrineResultCache;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();
        $this->loadFixtureByFilename('5ltiInstances.yml');

        $this->subject = self::$container->get(LtiInstanceRepository::class);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $doctrineResultCache = $entityManager->getConfiguration()->getResultCacheImpl();

        if (!$doctrineResultCache instanceof Cache) {
            throw new LogicException('Doctrine result cache is not configured.');
        }
        $this->doctrineResultCache = $doctrineResultCache;
    }

    public function testItCanFindAllOrderedByCreatedAt(): void
    {
        $collection = $this->subject->findAllAsCollection();

        self::assertCount(5, $collection);

        self::assertSame('cluster_qqy', $collection->getByIndex(0)->getLabel());
        self::assertSame('cluster_zoi', $collection->getByIndex(1)->getLabel());
        self::assertSame('cluster_w2n', $collection->getByIndex(2)->getLabel());
        self::assertSame('cluster_awr', $collection->getByIndex(3)->getLabel());
        self::assertSame('cluster_4eb', $collection->getByIndex(4)->getLabel());
    }

    public function testItCachesAllLtiInstances(): void
    {
        self::assertFalse($this->doctrineResultCache->contains(LtiInstanceRepository::CACHE_ID_ALL_LTI_INSTANCES));

        $this->subject->findAllAsCollection();

        self::assertTrue($this->doctrineResultCache->contains(LtiInstanceRepository::CACHE_ID_ALL_LTI_INSTANCES));

        $cacheValue = $this->doctrineResultCache->fetch(LtiInstanceRepository::CACHE_ID_ALL_LTI_INSTANCES);
        self::assertCount(1, $cacheValue);
        self::assertCount(5, array_values($cacheValue)[0]);
    }
}

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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

namespace OAT\SimpleRoster\Tests\Unit\Service\Bulk;

use OAT\SimpleRoster\DataTransferObject\UserCreationResultMessage;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Exception\LineItemNotFoundException;
use OAT\SimpleRoster\Lti\Collection\UniqueLtiInstanceCollection;
use OAT\SimpleRoster\Lti\Service\GenerateGroupIdsService;
use OAT\SimpleRoster\Model\LineItemCollection;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\ResultSet\LineItemResultSet;
use OAT\SimpleRoster\Service\Bulk\BulkCreateUsersService;
use OAT\SimpleRoster\Service\Bulk\BulkCreateUsersServiceConsoleProxy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class BulkCreateUsersServiceConsoleProxyTest extends TestCase
{
    public function testCreateUsers(): void
    {
        $createServiceMock = self::createMock(BulkCreateUsersService::class);;
        $createServiceMock->expects(self::once())->method('generate');

        $service = new BulkCreateUsersServiceConsoleProxy(
            $this->makeLineItemRepositoryMock($this->getDefaultLineItems()),
            $this->makeGenerateGroupIdsServiceMock(),
            $this->makeLtiInstanceRepositoryMock(),
            new UserCreationResultMessage(),
            $createServiceMock
        );

        $service->createUsers(
            [1, 2], [], ['QA', 'LQA'], 20, 'testGroup', date('Y-m-d')
        );
    }

    public function testLineItemNotFound(): void
    {
        self::expectException(LineItemNotFoundException::class);

        $createServiceMock = self::createMock(BulkCreateUsersService::class);;

        $service = new BulkCreateUsersServiceConsoleProxy(
            $this->makeLineItemRepositoryMock(),
            $this->makeGenerateGroupIdsServiceMock(),
            $this->makeLtiInstanceRepositoryMock(),
            new UserCreationResultMessage(),
            $createServiceMock
        );

        $service->createUsers(
            [777], [], ['QA', 'LQA'], 20, 'testGroup', date('Y-m-d')
        );
    }

    public function testThereIsNoLineItems(): void
    {
        self::expectException(LineItemNotFoundException::class);

        $createServiceMock = self::createMock(BulkCreateUsersService::class);;

        $service = new BulkCreateUsersServiceConsoleProxy(
            $this->makeLineItemRepositoryMock(),
            $this->makeGenerateGroupIdsServiceMock(),
            $this->makeLtiInstanceRepositoryMock(),
            new UserCreationResultMessage(),
            $createServiceMock
        );

        $service->createUsers(
            [], [], ['QA', 'LQA'], 20, 'testGroup', date('Y-m-d')
        );
    }

    /**
     * @return MockObject|LtiInstanceRepository
     */
    protected function makeLtiInstanceRepositoryMock(): MockObject
    {
        $mock = self::createMock(LtiInstanceRepository::class);
        $mock->method('findAllAsCollection')->willReturn(new UniqueLtiInstanceCollection());
        return $mock;
    }

    /**
     * @return MockObject|GenerateGroupIdsService
     */
    protected function makeGenerateGroupIdsServiceMock(): MockObject
    {
        $mock = self::createMock(GenerateGroupIdsService::class);
        $mock->method('generateGroupIds')->willReturn(['test_test1']);
        return $mock;
    }

    /**
     * @param LineItem[] $items
     * @return MockObject|LineItemRepository
     */
    protected function makeLineItemRepositoryMock(array $items = []): MockObject
    {
        $mock = self::createMock(LineItemRepository::class);

        $collection = new LineItemCollection();
        foreach ($items as $item) {
            $collection->add($item);
        }
        $mock->method('findAllAsCollection')->willReturn($collection);
        $mock->method('findLineItemsByCriteria')->willReturn(new LineItemResultSet($collection, false, 777));

        return $mock;
    }

    protected function getDefaultLineItems(): array
    {
        return [
            $this->setLineItemId((new LineItem())->setSlug('test1'), 1),
            $this->setLineItemId((new LineItem())->setSlug('test2'), 2),
        ];
    }

    public function setLineItemId(LineItem $object, $value): LineItem
    {
        $reflection = new ReflectionClass($object);
        $reflection_property = $reflection->getProperty('id');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($object, $value);

        return $object;
    }
}

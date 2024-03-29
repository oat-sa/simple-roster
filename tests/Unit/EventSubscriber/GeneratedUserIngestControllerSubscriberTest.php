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

namespace OAT\SimpleRoster\Tests\Unit\EventSubscriber;

use Carbon\Carbon;
use DateTimeInterface;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Events\LineItemUpdated;
use OAT\SimpleRoster\EventSubscriber\GeneratedUserIngestControllerSubscriber;
use OAT\SimpleRoster\Lti\Service\GenerateGroupIdsService;
use OAT\SimpleRoster\Model\LineItemCollection;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\ResultSet\LineItemResultSet;
use OAT\SimpleRoster\Service\AwsS3\FolderSyncService;
use OAT\SimpleRoster\Service\Bulk\BulkCreateUsersService;
use OAT\SimpleRoster\Service\Bulk\CreateUserServiceContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GeneratedUserIngestControllerSubscriberTest extends TestCase
{
    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals([
            LineItemUpdated::NAME => ['onLineItemUpdated', 10],
        ], GeneratedUserIngestControllerSubscriber::getSubscribedEvents());
    }

    public function testOnLineItemUpdatedIgnoreOnDisabled(): void
    {
        $logerMock = $this->createMock(LoggerInterface::class);
        $createServiceMock = $this->createMock(BulkCreateUsersService::class);
        $createServiceMock->expects(self::never())->method('generate');

        $generateGroupIdsServiceMock = $this->createMock(GenerateGroupIdsService::class);
        $ltiInstanceRepositoryMock = $this->createMock(LtiInstanceRepository::class);
        $lineItemRepositoryMock = $this->createMock(LineItemRepository::class);
        $userFolderSyncMock = $this->createMock(FolderSyncService::class);

        $service = new GeneratedUserIngestControllerSubscriber(
            $logerMock,
            $createServiceMock,
            $generateGroupIdsServiceMock,
            $ltiInstanceRepositoryMock,
            $lineItemRepositoryMock,
            $userFolderSyncMock,
            new CreateUserServiceContext(['test1'], ['tao1'], 10, true),
            'test',
            false,
            false
        );

        $service->onLineItemUpdated(new LineItemUpdated(['test1']));
    }

    public function testOnLineItemUpdatedIgnoreFolderSyncOnDisabled(): void
    {
        $logerMock = $this->createMock(LoggerInterface::class);
        $createServiceMock = $this->createMock(BulkCreateUsersService::class);
        $createServiceMock
            ->expects(self::once())
            ->method('generate')
            ->with($this->makeLineItemCollection($this->getDefaultLineItems())->jsonSerialize());

        $generateGroupIdsServiceMock = $this->createMock(GenerateGroupIdsService::class);
        $ltiInstanceRepositoryMock = $this->createMock(LtiInstanceRepository::class);

        $lineItemRepositoryMock = $this->makeLineItemRepositoryMock(
            $this->makeLineItemCollection($this->getDefaultLineItems())
        );
        $userFolderSyncMock = $this->createMock(FolderSyncService::class);

        $service = new GeneratedUserIngestControllerSubscriber(
            $logerMock,
            $createServiceMock,
            $generateGroupIdsServiceMock,
            $ltiInstanceRepositoryMock,
            $lineItemRepositoryMock,
            $userFolderSyncMock,
            new CreateUserServiceContext(['test1'], ['tao1'], 10, true),
            'test',
            true,
            false
        );

        $service->onLineItemUpdated(new LineItemUpdated(['test1']));
    }

    public function testOnLineItemUpdatedBasicPipeline(): void
    {
        $logerMock = $this->createMock(LoggerInterface::class);
        $createServiceMock = $this->createMock(BulkCreateUsersService::class);
        $createServiceMock
            ->expects(self::once())
            ->method('generate')
            ->with($this->makeLineItemCollection($this->getDefaultLineItems())->jsonSerialize());

        $generateGroupIdsServiceMock = $this->createMock(GenerateGroupIdsService::class);
        $ltiInstanceRepositoryMock = $this->createMock(LtiInstanceRepository::class);

        $lineItemRepositoryMock = $this->makeLineItemRepositoryMock(
            $this->makeLineItemCollection($this->getDefaultLineItems())
        );
        $userFolderSyncMock = $this->createMock(FolderSyncService::class);

        $service = new GeneratedUserIngestControllerSubscriber(
            $logerMock,
            $createServiceMock,
            $generateGroupIdsServiceMock,
            $ltiInstanceRepositoryMock,
            $lineItemRepositoryMock,
            $userFolderSyncMock,
            new CreateUserServiceContext(['test1'], ['tao1'], 10, true),
            'test',
            true,
            true
        );

        $service->onLineItemUpdated(new LineItemUpdated(['test1']));
    }

    /**
     * @return MockObject|LineItemRepository
     */
    protected function makeLineItemRepositoryMock(LineItemCollection $collection): MockObject
    {
        $lineItemRepositoryMock = $this->createMock(LineItemRepository::class);

        $lineItemRepositoryMock->method('findAllAsCollection')->willReturn($collection);
        $lineItemRepositoryMock
            ->method('findLineItemsByCriteria')
            ->willReturn(new LineItemResultSet($collection, false, 777));

        return $lineItemRepositoryMock;
    }

    /**
     * @param LineItem[] $items
     */
    protected function makeLineItemCollection(array $items = []): LineItemCollection
    {
        $collection = new LineItemCollection();
        foreach ($items as $item) {
            $collection->add($item);
        }
        return $collection;
    }

    protected function getDefaultLineItems(): array
    {
        return [
            $this->setLineItemId($this->buildDefaultLineItem(1), 1),
            $this->setLineItemId($this->buildDefaultLineItem(2), 2),
        ];
    }

    protected function buildDefaultLineItem(int $index): LineItem
    {
        $lineItem = (new LineItem())
            ->setUri("test_uri{$index}")
            ->setLabel("test_label{$index}")
            ->setSlug("test_slug{$index}");

        return $this->setLineItemUpdatedAt($lineItem, Carbon::today());
    }

    public function setLineItemId(LineItem $object, int $value): LineItem
    {
        $reflection = new ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);

        return $object;
    }

    public function setLineItemUpdatedAt(LineItem $object, DateTimeInterface $value): LineItem
    {
        $reflection = new ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty('updatedAt');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);

        return $object;
    }
}

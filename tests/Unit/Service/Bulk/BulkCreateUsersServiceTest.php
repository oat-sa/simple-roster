<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Bulk;

use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Lti\Service\AssigmentFactoryInterface;
use OAT\SimpleRoster\Lti\Service\AssignmentCollectionMapper\AssignmentCollectionMapperInterface;
use OAT\SimpleRoster\Lti\Service\UserGenerator\UserGeneratorStateStorageInterface;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Service\Bulk\BulkCreateUsersService;
use OAT\SimpleRoster\Service\Bulk\CreateUserServiceContext;
use OAT\SimpleRoster\Service\LineItem\LineItemAssignedIndexResolver;
use OAT\SimpleRoster\Storage\UserGenerator\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BulkCreateUsersServiceTest extends TestCase
{
    /** @var UserGeneratorStateStorageInterface|MockObject */
    private $stateStorage;
    /** @var AssignmentCollectionMapperInterface|MockObject */
    private $assignmentMapper;
    /** @var LineItemAssignedIndexResolver|MockObject */
    private $lineItemAssignedIndexResolver;
    /** @var AssigmentFactoryInterface|MockObject */
    private $assigmentFactory;
    /** @var StorageInterface|MockObject */
    private $storage;
    private CreateUserServiceContext $createUserServiceContext;
    /** @var LineItemRepository|MockObject */
    private $lineItemRepository;
    private BulkCreateUsersService $bulkCreateUsersService;

    protected function setUp(): void
    {
        $this->stateStorage = $this->createMock(UserGeneratorStateStorageInterface::class);
        $this->assignmentMapper = $this->createMock(AssignmentCollectionMapperInterface::class);
        $this->lineItemAssignedIndexResolver = $this->createMock(LineItemAssignedIndexResolver::class);
        $this->assigmentFactory = $this->createMock(AssigmentFactoryInterface::class);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->createUserServiceContext = new CreateUserServiceContext(['prefix'], ['groupPrefix'], 1, false);
        $this->lineItemRepository = $this->createMock(LineItemRepository::class);

        $this->bulkCreateUsersService = new BulkCreateUsersService(
            $this->stateStorage,
            $this->assignmentMapper,
            $this->lineItemAssignedIndexResolver,
            $this->assigmentFactory,
            $this->storage,
            $this->createUserServiceContext,
            $this->lineItemRepository
        );
    }

    public function testGenerate(): void
    {
        $this->storage->expects(self::atLeastOnce())->method('persistUsers');
        $this->storage->expects(self::atLeastOnce())->method('persistAssignments');

        $lineItem1 = new LineItem();
        $lineItem1->setSlug('slug1');

        $lineItems = [
            $lineItem1
        ];
        $this->lineItemAssignedIndexResolver->method('getLastUserAssignedToLineItems')->willReturn(
            ['slug1' => 0]
        );

        $this->bulkCreateUsersService->generate($lineItems, 'path');
    }

    public function testDoNotGenerateQAUsersWhenNotNeeded(): void
    {
        $this->storage->expects(self::never())->method('persistUsers');
        $this->storage->expects(self::never())->method('persistAssignments');

        $lineItem1 = new LineItem();
        $lineItem1->setSlug('slug1');

        $lineItems = [
            $lineItem1
        ];

        $this->lineItemRepository->method('hasLineItemQAUsers')->willReturn(true);

        $createUserContext = $this->createUserServiceContext->withRecreateUsers(false);

        $this->bulkCreateUsersService->generate($lineItems, 'path', $createUserContext);
    }
}

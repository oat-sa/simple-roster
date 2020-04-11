<?php

/**
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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace App\Tests\Unit\Service\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Entity\Assignment;
use App\Entity\LineItem;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Bulk\BulkUpdateUsersAssignmentsStateService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BulkUpdateUsersAssignmentsStateServiceTest extends TestCase
{
    /** @var BulkUpdateUsersAssignmentsStateService */
    private $subject;

    /** @var EntityManagerInterface|MockObject */
    private $entityManager;

    /** @var UserRepository|MockObject */
    private $userRepository;

    /** @var LoggerInterface|MockObject */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->userRepository = $this->createMock(UserRepository::class);

        $this->entityManager
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        $this->subject = new BulkUpdateUsersAssignmentsStateService($this->entityManager, $this->logger);
    }

    public function testItAddsBulkOperationFailureIfWrongOperationTypeReceived(): void
    {
        $expectedFailingOperation = new BulkOperation('expectedFailure', BulkOperation::TYPE_CREATE);
        $successfulOperation = new BulkOperation(
            'test',
            BulkOperation::TYPE_UPDATE,
            ['state' => Assignment::STATE_CANCELLED]
        );

        $expectedAssignment = (new Assignment())
            ->setLineItem(new LineItem())
            ->setState(Assignment::STATE_STARTED);

        $expectedUser = (new User())
            ->setUsername('expectedUser')
            ->addAssignment($expectedAssignment);

        $this->userRepository
            ->method('findByUsernameWithAssignments')
            ->willReturn($expectedUser);

        $bulkOperationCollection = (new BulkOperationCollection())
            ->add($expectedFailingOperation)
            ->add($successfulOperation);

        $this->assertEquals([
            'data' => [
                'applied' => false,
                'results' => [
                    'expectedFailure' => false,
                    'test' => true,
                ],
            ],
        ], $this->subject->process($bulkOperationCollection)->jsonSerialize());
    }

    public function testIfEntityManagerIsFlushedOnlyOnceDuringTheProcessToOptimizeMemoryConsumption(): void
    {
        $this->userRepository
            ->method('findByUsernameWithAssignments')
            ->willReturn(new User());

        $bulkOperationCollection = (new BulkOperationCollection())
            ->add(new BulkOperation('test', BulkOperation::TYPE_UPDATE, ['state' => Assignment::STATE_CANCELLED]))
            ->add(new BulkOperation('test1', BulkOperation::TYPE_UPDATE, ['state' => Assignment::STATE_CANCELLED]))
            ->add(new BulkOperation('test2', BulkOperation::TYPE_UPDATE, ['state' => Assignment::STATE_CANCELLED]));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->subject->process($bulkOperationCollection);
    }

    /**
     * @dataProvider provideUnsupportedAssignmentState
     */
    public function testItThrowsExceptionIfInvalidStateIsReceivedAsOperationAttribute(string $invalidState): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                "Not allowed state attribute received while bulk updating: '%s', '%s' expected.",
                $invalidState,
                Assignment::STATE_CANCELLED
            )
        );

        $this->userRepository
            ->method('findByUsernameWithAssignments')
            ->willReturn(new User());

        $bulkOperationCollection = (new BulkOperationCollection())
            ->add(new BulkOperation('test', BulkOperation::TYPE_UPDATE, ['state' => $invalidState]));

        $this->subject->process($bulkOperationCollection);
    }

    public function testItIgnoresCompletedAssignments(): void
    {
        $operation = new BulkOperation('test', BulkOperation::TYPE_UPDATE, ['state' => Assignment::STATE_CANCELLED]);

        $completedAssignment = (new Assignment())
            ->setLineItem(new LineItem())
            ->setState(Assignment::STATE_COMPLETED);

        $user = (new User())
            ->addAssignment($completedAssignment);

        $this->userRepository
            ->method('findByUsernameWithAssignments')
            ->willReturn($user);

        $bulkOperationCollection = (new BulkOperationCollection())->add($operation);

        $this->subject->process($bulkOperationCollection)->jsonSerialize();

        $this->assertEquals(Assignment::STATE_COMPLETED, $completedAssignment->getState());
    }

    public function provideUnsupportedAssignmentState(): array
    {
        return [
            Assignment::STATE_STARTED => [Assignment::STATE_STARTED],
            Assignment::STATE_READY => [Assignment::STATE_READY],
            Assignment::STATE_COMPLETED => [Assignment::STATE_COMPLETED],
        ];
    }
}

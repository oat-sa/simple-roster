<?php

declare(strict_types=1);

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

namespace App\Tests\Unit\Service\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Entity\Assignment;
use App\Entity\LineItem;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Bulk\BulkCreateUsersAssignmentsService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BulkCreateUsersAssignmentsServiceTest extends TestCase
{
    /** @var BulkCreateUsersAssignmentsService */
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
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->entityManager
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        $this->subject = new BulkCreateUsersAssignmentsService($this->entityManager, $this->logger);
    }

    public function testItAddsBulkOperationFailureIfWrongOperationTypeReceived(): void
    {
        $user = (new User())->addAssignment((new Assignment())
            ->setState(Assignment::STATE_READY)
            ->setLineItem(new LineItem()));

        $this->userRepository
            ->method('getByUsernameWithAssignments')
            ->willReturnCallback(static function (string $username) use ($user) {
                return $user->setUsername($username);
            });

        $expectedFailingOperation = new BulkOperation('expectedFailure', BulkOperation::TYPE_UPDATE);
        $successfulOperation = new BulkOperation('test', BulkOperation::TYPE_CREATE);

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
            ->method('getByUsernameWithAssignments')
            ->willReturnCallback(static function (string $username) {
                return (new User())
                    ->setUsername($username)
                    ->addAssignment((new Assignment())->setLineItem(new LineItem()));
            });

        $bulkOperationCollection = (new BulkOperationCollection())
            ->add(new BulkOperation('test', BulkOperation::TYPE_CREATE))
            ->add(new BulkOperation('test1', BulkOperation::TYPE_CREATE))
            ->add(new BulkOperation('test2', BulkOperation::TYPE_CREATE));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->subject->process($bulkOperationCollection);
    }
}

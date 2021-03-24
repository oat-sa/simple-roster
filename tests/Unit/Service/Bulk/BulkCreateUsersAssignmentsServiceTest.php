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

namespace OAT\SimpleRoster\Tests\Unit\Service\Bulk;

use Doctrine\ORM\EntityManagerInterface;
use OAT\SimpleRoster\Bulk\Operation\BulkOperation;
use OAT\SimpleRoster\Bulk\Operation\BulkOperationCollection;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Model\UsernameCollection;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Service\Bulk\BulkCreateUsersAssignmentsService;
use OAT\SimpleRoster\Service\Cache\UserCacheWarmerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Uid\UuidV6;

class BulkCreateUsersAssignmentsServiceTest extends TestCase
{
    /** @var BulkCreateUsersAssignmentsService */
    private $subject;

    /** @var EntityManagerInterface|MockObject */
    private $entityManager;

    /** @var UserCacheWarmerService|MockObject */
    private $userCacheWarmerService;

    /** @var UserRepository|MockObject */
    private $userRepository;

    /** @var LoggerInterface|MockObject */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userCacheWarmerService = $this->createMock(UserCacheWarmerService::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->entityManager
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        $this->subject = new BulkCreateUsersAssignmentsService(
            $this->entityManager,
            $this->userCacheWarmerService,
            $this->logger
        );
    }

    public function testItAddsBulkOperationFailureIfWrongOperationTypeReceived(): void
    {
        $lineItem = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'testLabel',
            'testUri',
            'testSlug',
            LineItem::STATUS_ENABLED
        );

        $user = (new User())
            ->addAssignment((new Assignment())
                ->setState(Assignment::STATE_READY)
                ->setLineItem($lineItem));

        $this->userRepository
            ->method('findByUsernameWithAssignments')
            ->willReturnCallback(static function (string $username) use ($user) {
                return $user->setUsername($username);
            });

        $expectedFailingOperation = new BulkOperation('expectedFailure', BulkOperation::TYPE_UPDATE);
        $successfulOperation = new BulkOperation('test', BulkOperation::TYPE_CREATE);

        $bulkOperationCollection = (new BulkOperationCollection())
            ->add($expectedFailingOperation)
            ->add($successfulOperation);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                'Bulk assignments create error: wrong type.',
                ['operation' => $expectedFailingOperation],
            );

        self::assertSame([
            'data' => [
                'applied' => false,
                'results' => [
                    'expectedFailure' => false,
                    'test' => true,
                ],
            ],
        ], $this->subject->process($bulkOperationCollection)->jsonSerialize());
    }

    public function testItLogsUnexpectedException(): void
    {
        $this->userRepository
            ->method('findByUsernameWithAssignments')
            ->willThrowException(new RuntimeException('Something unexpected happened'));

        $expectedOperation = new BulkOperation('testUser', BulkOperation::TYPE_CREATE);
        $bulkOperationCollection = (new BulkOperationCollection())->add($expectedOperation);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                'Bulk assignments create error: Something unexpected happened',
                ['operation' => $expectedOperation]
            );

        self::assertSame([
            'data' => [
                'applied' => false,
                'results' => [
                    'testUser' => false,
                ],
            ],
        ], $this->subject->process($bulkOperationCollection)->jsonSerialize());
    }

    public function testIfEntityManagerIsFlushedOnlyOnceDuringTheProcessToOptimizeMemoryConsumption(): void
    {
        $expectedLineItem = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'testLabel',
            'testUri',
            'testSlug',
            LineItem::STATUS_ENABLED
        );

        $this->userRepository
            ->method('findByUsernameWithAssignments')
            ->willReturnCallback(static function (string $username) use ($expectedLineItem): User {
                return (new User())
                    ->setUsername($username)
                    ->addAssignment((new Assignment())->setLineItem($expectedLineItem));
            });

        $bulkOperationCollection = (new BulkOperationCollection())
            ->add(new BulkOperation('test', BulkOperation::TYPE_CREATE))
            ->add(new BulkOperation('test1', BulkOperation::TYPE_CREATE))
            ->add(new BulkOperation('test2', BulkOperation::TYPE_CREATE));

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $this->userCacheWarmerService
            ->expects(self::once())
            ->method('process')
            ->with(
                self::callback(static function (UsernameCollection $collection): bool {
                    return $collection->count() === 3
                        && $collection->getIterator()->getArrayCopy() === ['test', 'test1', 'test2'];
                })
            );

        $this->logger
            ->expects(self::exactly(3))
            ->method('info')
            ->withConsecutive(
                [
                    "Successful assignment creation (username = 'test').",
                    ['lineItem' => $expectedLineItem],
                ],
                [
                    "Successful assignment creation (username = 'test1').",
                    ['lineItem' => $expectedLineItem],
                ],
                [
                    "Successful assignment creation (username = 'test2').",
                    ['lineItem' => $expectedLineItem],
                ],
            );

        $this->subject->process($bulkOperationCollection);
    }
}

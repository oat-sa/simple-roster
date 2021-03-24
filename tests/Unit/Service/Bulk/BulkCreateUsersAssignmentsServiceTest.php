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
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Service\Bulk\BulkCreateUsersAssignmentsService;
use OAT\SimpleRoster\Service\Cache\UserCacheWarmerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class BulkCreateUsersAssignmentsServiceTest extends TestCase
{
    /** @var BulkCreateUsersAssignmentsService */
    private $subject;

    /** @var UserRepository|MockObject */
    private $userRepository;

    /** @var LoggerInterface|MockObject */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new BulkCreateUsersAssignmentsService(
            $entityManager,
            $this->userRepository,
            $this->createMock(UserCacheWarmerService::class),
            $this->logger
        );
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
}

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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Integration\Service\Bulk;

use Monolog\Logger;
use OAT\SimpleRoster\Bulk\Operation\BulkOperation;
use OAT\SimpleRoster\Bulk\Operation\BulkOperationCollection;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Service\Bulk\BulkCreateUsersAssignmentsService;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BulkCreateUsersAssignmentsServiceTest extends KernelTestCase
{
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    /** @var BulkCreateUsersAssignmentsService */
    private $subject;

    /** @var UserRepository */
    private $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();
        $this->setUpTestLogHandler('cache_warmup');
        $this->loadFixtureByFilename('3usersWithAssignments.yml');

        $this->subject = self::$container->get(BulkCreateUsersAssignmentsService::class);
        $this->userRepository = self::$container->get(UserRepository::class);
    }

    public function testIfSuccessfulOperationCreatesNewAssignmentsAndCancelsAlreadyExistingOnes(): void
    {
        $bulkOperationCollection = (new BulkOperationCollection())
            ->add(new BulkOperation('user_1', BulkOperation::TYPE_CREATE))
            ->add(new BulkOperation('user_2', BulkOperation::TYPE_CREATE))
            ->add(new BulkOperation('user_3', BulkOperation::TYPE_CREATE));

        $bulkResult = $this->subject->process($bulkOperationCollection);

        self::assertSame(
            [
                'data' => [
                    'applied' => true,
                    'results' => [
                        'user_1' => true,
                        'user_2' => true,
                        'user_3' => true,
                    ],
                ],
            ],
            $bulkResult->jsonSerialize()
        );

        foreach (['user_1', 'user_2', 'user_3'] as $username) {
            $user = $this->userRepository->findByUsernameWithAssignments($username);

            $userAssignments = $user->getAssignments();

            self::assertCount(2, $userAssignments);
            self::assertSame(Assignment::STATE_CANCELLED, $userAssignments[0]->getState());
            self::assertSame(Assignment::STATE_READY, $userAssignments[1]->getState());
        }
    }

    public function testIfSuccessfulOperationProducesLogs(): void
    {
        $bulkOperationCollection = (new BulkOperationCollection())
            ->add(new BulkOperation('user_1', BulkOperation::TYPE_CREATE))
            ->add(new BulkOperation('user_2', BulkOperation::TYPE_CREATE))
            ->add(new BulkOperation('user_3', BulkOperation::TYPE_CREATE));

        $this->subject->process($bulkOperationCollection);

        foreach (['user_1', 'user_2', 'user_3'] as $username) {
            $this->assertHasLogRecordWithMessage(
                sprintf("Successful assignment creation (username = '%s').", $username),
                Logger::INFO
            );
        }
    }

    public function testIfSuccessfulOperationTriggersCacheWarmup(): void
    {
        $bulkOperationCollection = (new BulkOperationCollection())
            ->add(new BulkOperation('user_1', BulkOperation::TYPE_CREATE))
            ->add(new BulkOperation('user_2', BulkOperation::TYPE_CREATE))
            ->add(new BulkOperation('user_3', BulkOperation::TYPE_CREATE));

        $this->subject->process($bulkOperationCollection);

        foreach (['user_1', 'user_2', 'user_3'] as $username) {
            $this->assertHasLogRecordWithMessage(
                sprintf("Cache warmup event was successfully dispatched for users '%s'", $username),
                Logger::INFO
            );
        }
    }

    public function testItHandlesAndLogsInvalidOperationType(): void
    {
        $unexpectedBulkOperation = new BulkOperation('user_2', BulkOperation::TYPE_UPDATE);

        $bulkOperationCollection = (new BulkOperationCollection())
            ->add(new BulkOperation('user_1', BulkOperation::TYPE_CREATE))
            ->add($unexpectedBulkOperation)
            ->add(new BulkOperation('user_3', BulkOperation::TYPE_CREATE));

        $bulkResult = $this->subject->process($bulkOperationCollection);

        self::assertSame(
            [
                'data' => [
                    'applied' => false,
                    'results' => [
                        'user_1' => true,
                        'user_2' => false,
                        'user_3' => true,
                    ],
                ],
            ],
            $bulkResult->jsonSerialize()
        );

        self::assertTrue($bulkResult->hasFailures());

        $this->assertHasLogRecord(
            [
                'message' => 'Bulk assignments create error: wrong type.',
                'context' => [
                    'operation' => $unexpectedBulkOperation,
                ],
            ],
            Logger::ERROR
        );
    }
}

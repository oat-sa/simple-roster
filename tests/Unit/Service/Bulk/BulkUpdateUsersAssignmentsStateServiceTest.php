<?php declare(strict_types=1);

namespace App\Tests\Unit\Service\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Entity\Assignment;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Bulk\BulkUpdateUsersAssignmentsStateService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;

class BulkUpdateUsersAssignmentsStateServiceTest extends TestCase
{
    /** @var BulkUpdateUsersAssignmentsStateService */
    private $subject;

    /** @var EntityManagerInterface|PHPUnit_Framework_MockObject_MockObject */
    private $entityManager;

    /** @var UserRepository|PHPUnit_Framework_MockObject_MockObject */
    private $userRepository;

    /** @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    protected function setUp()
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
}

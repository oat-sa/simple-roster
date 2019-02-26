<?php declare(strict_types=1);

namespace App\Tests\Unit\Service\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
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

    /** @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->entityManager
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->createMock(UserRepository::class));

        $this->subject = new BulkUpdateUsersAssignmentsStateService($this->entityManager, $this->logger);
    }

    public function testItAddsBulkOperationFailureIfWrongOperationTypeReceived(): void
    {
        $expectedFailingOperation = new BulkOperation('expectedFailure', BulkOperation::TYPE_CREATE);
        $successfulOperation = new BulkOperation('test', BulkOperation::TYPE_UPDATE);

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
}

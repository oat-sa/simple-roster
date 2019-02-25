<?php declare(strict_types=1);

namespace App\Tests\Unit\Service\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Service\Bulk\BulkCreateUsersAssignmentService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;

class BulkCreateUsersAssignmentServiceTest extends TestCase
{
    /** @var BulkCreateUsersAssignmentService */
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

        $this->subject = new BulkCreateUsersAssignmentService($this->entityManager, $this->logger);
    }

    public function testItAddsBulkOperationFailureIfWrongOperationTypeReceived(): void
    {
        $bulkOperation = new BulkOperation('expectedFailure', BulkOperation::TYPE_UPDATE);
        $bulkOperationCollection = (new BulkOperationCollection())->add($bulkOperation);

        $this->assertEquals([
            'data' => [
                'applied' => false,
                'results' => [
                    'expectedFailure' => false,
                ],
            ],
        ], $this->subject->process($bulkOperationCollection)->jsonSerialize());
    }
}

<?php declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Assignment;
use App\Entity\User;
use App\Service\CreateUserAssignmentService;
use App\Service\CreateUsersAssignmentsService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateUsersAssignmentsServiceTest extends TestCase
{
    /** @var CreateUsersAssignmentsService */
    private $subject;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var CreateUserAssignmentService */
    private $createUserAssignmentService;

    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->createUserAssignmentService = $this->createMock(CreateUserAssignmentService::class);
        $this->subject = new CreateUsersAssignmentsService($this->createUserAssignmentService, $this->entityManager);
    }

    public function testItWrapsGenerationInTransaction(): void
    {
        $expectedAssignment = (new Assignment())->setState(Assignment::STATE_READY);

        $this->createUserAssignmentService
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturn($expectedAssignment);

        $this->entityManager
            ->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->entityManager
            ->expects($this->once())
            ->method('commit');

        foreach ($this->subject->create(new User(), new User()) as $createdAssignment) {
            $this->assertEquals($expectedAssignment, $createdAssignment);
        }
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Ooops...
     */
    public function testItRollsBackTransactionUponException(): void
    {
        $this->createUserAssignmentService
            ->method('create')
            ->willThrowException(new \Exception('Ooops...'));

        $this->entityManager
            ->expects($this->once())
            ->method('rollback');

        /** @noinspection LoopWhichDoesNotLoopInspection */
        /** @noinspection MissingOrEmptyGroupStatementInspection */
        /** @noinspection PhpStatementHasEmptyBodyInspection */
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($this->subject->create(new User()) as $createdAssignment) {
            // Looping over generator
        }
    }
}

<?php declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Assignment;
use App\Repository\AssignmentRepository;
use App\Tests\Traits\DatabaseManualFixturesTrait;
use DateInterval;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AssignmentRepositoryTest extends KernelTestCase
{
    use DatabaseManualFixturesTrait;

    /** @var AssignmentRepository */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        static::bootKernel();
        $this->setUpDatabase();

        $this->loadFixtures([
            __DIR__ . '/../../../fixtures/usersWithStartedButStuckAssignments.yml',
        ]);

        $this->subject = self::$container->get(AssignmentRepository::class);
    }

    public function testItCanReturnAssignmentsByStateAndUpdatedAt(): void
    {
        $dateTime = (new DateTime())->add(new DateInterval('P1D'));
        $assignments = $this->subject->findAllByStateAndUpdatedAtPaged(Assignment::STATE_STARTED, $dateTime);

        $this->assertCount(10, $assignments->getIterator());
        $this->assertCount(10, $assignments);
    }

    public function testItCanReturnAssignmentsByStateAndUpdatedAtPaged(): void
    {
        $dateTime = (new DateTime())->add(new DateInterval('P1D'));
        $assignments = $this->subject->findAllByStateAndUpdatedAtPaged(Assignment::STATE_STARTED, $dateTime, 2, 3);

        $this->assertCount(3, $assignments->getIterator());
        $this->assertCount(10, $assignments);
    }
}

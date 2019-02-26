<?php declare(strict_types=1);

namespace App\Tests\Integration\Entity;

use App\Entity\Assignment;
use App\Entity\User;
use App\Tests\Traits\DatabaseFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserTest extends KernelTestCase
{
    use DatabaseFixturesTrait;

    public function testItCanRetrieveAndRemoveAssignments(): void
    {
        /** @var User $subject */
        $subject = $this->getRepository(User::class)->find(1);

        /** @var Assignment $assignment */
        $assignment = $this->getRepository(Assignment::class)->find(1);

        $this->assertCount(1, $subject->getAssignments());
        $this->assertCount(1, $subject->getAvailableAssignments());

        $this->assertSame($assignment, current($subject->getAvailableAssignments()));

        $subject->removeAssignment($assignment);

        $this->assertEmpty($subject->getAssignments());
        $this->assertEmpty($subject->getAvailableAssignments());
    }
}

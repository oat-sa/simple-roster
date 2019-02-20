<?php declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseManualFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    use DatabaseManualFixturesTrait;

    /** @var UserRepository */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();

        $this->loadFixtures([
            __DIR__ . '/../../../fixtures/100usersWithAssignments.yml',
        ]);

        $this->subject = self::$container->get(UserRepository::class);
    }

    public function testItCanGiveTheTotalNumberOfUsers(): void
    {
        $this->assertEquals(100, $this->subject->getTotalNumberOfUsers());
    }
}

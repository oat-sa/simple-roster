<?php declare(strict_types=1);

namespace App\Tests\Integration\Ingester\Ingester;

use App\Entity\User;
use App\Ingester\Ingester\UserIngester;
use App\Ingester\Result\IngesterResult;
use App\Ingester\Source\IngesterSourceInterface;
use App\Ingester\Source\LocalCsvIngesterSource;
use App\Tests\Traits\DatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserIngesterTest extends KernelTestCase
{
    use DatabaseTrait;

    /** @var UserIngester */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();

        $this->subject = new UserIngester($this->getManagerRegistry());
    }

    public function testDryRunIngest()
    {
        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/users.csv');

        $output = $this->subject->ingest($source);

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertEquals('user', $output->getIngesterType());
        $this->assertTrue($output->isDryRun());
        $this->assertEquals(3, $output->getSuccessCount());
        $this->assertFalse($output->hasFailures());

        $this->assertEmpty($this->getRepository(User::class)->findAll());
    }

    public function testIngestWithValidSource()
    {
        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/users.csv');

        $output = $this->subject->ingest($source, false);

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertEquals('user', $output->getIngesterType());
        $this->assertFalse($output->isDryRun());
        $this->assertEquals(3, $output->getSuccessCount());
        $this->assertFalse($output->hasFailures());

        $this->assertCount(3, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        $this->assertEquals('user_1', $user1->getUsername());

        $user2 = $this->getRepository(User::class)->find(2);
        $this->assertEquals('user_2', $user2->getUsername());

        $user3 = $this->getRepository(User::class)->find(3);
        $this->assertEquals('user_3', $user3->getUsername());
    }

    public function testIngestWithInvalidSource()
    {
        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Invalid/users.csv');

        $output = $this->subject->ingest($source, false);

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertEquals('user', $output->getIngesterType());
        $this->assertFalse($output->isDryRun());
        $this->assertEquals(1, $output->getSuccessCount());
        $this->assertTrue($output->hasFailures());
        $this->assertCount(1, $output->getFailures());

        $this->assertCount(1, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        $this->assertEquals('user_1', $user1->getUsername());

        $failure = current($output->getFailures());
        $this->assertEquals(2, $failure->getLineNumber());
        $this->assertEquals(
            ['user_1', 'password', 'http://taoplatform.loc/delivery_2.rdf'],
            $failure->getData()
        );
        $this->assertContains('UNIQUE constraint failed: users.username', $failure->getReason());
    }

    private function createIngesterSource(string $path): IngesterSourceInterface
    {
        return (new LocalCsvIngesterSource())->setPath($path);
    }
}
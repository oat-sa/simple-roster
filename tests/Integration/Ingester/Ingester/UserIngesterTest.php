<?php declare(strict_types=1);

namespace App\Tests\Integration\Ingester\Ingester;

use App\Entity\User;
use App\Ingester\Ingester\InfrastructureIngester;
use App\Ingester\Ingester\LineItemIngester;
use App\Ingester\Ingester\UserIngester;
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

    public function testDryRunIngest(): void
    {
        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/users.csv');

        $output = $this->subject->ingest($source);

        $this->assertEquals('user', $output->getIngesterType());
        $this->assertTrue($output->isDryRun());
        $this->assertEquals(12, $output->getSuccessCount());
        $this->assertFalse($output->hasFailures());

        $this->assertEmpty($this->getRepository(User::class)->findAll());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Cannot ingest 'user' since line-item table is empty.
     */
    public function testIngestWithEmptyLineItems(): void
    {
        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/users.csv');

        $this->subject->ingest($source, false);
    }

    public function testIngestWithInvalidSource(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Invalid/users.csv');

        $output = $this->subject->ingest($source, false);

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
            [
                'username' => 'user_1',
                'password' => 'password1',
                'slug' => 'gra13_ita_1'
            ],
            $failure->getData()
        );
        $this->assertContains('UNIQUE constraint failed: users.username', $failure->getReason());
    }

    public function testIngestWithValidSource(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/users.csv');

        $output = $this->subject->ingest($source, false);

        $this->assertEquals('user', $output->getIngesterType());
        $this->assertFalse($output->isDryRun());
        $this->assertEquals(12, $output->getSuccessCount());
        $this->assertFalse($output->hasFailures());

        $this->assertCount(12, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        $this->assertEquals('user_1', $user1->getUsername());

        $user12 = $this->getRepository(User::class)->find(12);
        $this->assertEquals('user_12', $user12->getUsername());
    }

    private function createIngesterSource(string $path): IngesterSourceInterface
    {
        return (new LocalCsvIngesterSource())->setPath($path);
    }

    private function prepareIngestionContext(): void
    {
        static::$container->get(InfrastructureIngester::class)->ingest(
            $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv'),
            false
        );

        static::$container->get(LineItemIngester::class)->ingest(
            $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/line-items.csv'),
            false
        );
    }
}

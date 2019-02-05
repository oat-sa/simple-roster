<?php declare(strict_types=1);

namespace App\Tests\Integration\Ingester\Ingester;

use App\Entity\User;
use App\Ingester\Ingester\UserIngester;
use App\Ingester\Result\IngesterResult;
use App\Ingester\Source\IngesterSourceInterface;
use App\Ingester\Source\LocalCsvIngesterSource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserIngesterTest extends KernelTestCase
{
    /** @var UserIngester */
    private $subject;

    /** @var EntityManagerInterface */
    private $entityManager;

    protected function setUp()
    {
        parent::setUp();

        static::bootKernel();

        $this->entityManager = static::$container->get(EntityManagerInterface::class);
        $this->subject = new UserIngester($this->entityManager);
    }

    public function testDryRunIngest()
    {
        $source = $this->createLocalIngesterSource(__DIR__ . '/../../../Resources/Ingester/users.csv');

        $output = $this->subject->ingest($source);

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertTrue($output->isDryRun());
        $this->assertCount(3, $output->getSuccesses());
        $this->assertCount(0, $output->getFailures());

        $this->assertEmpty($this->entityManager->getRepository(User::class)->findAll());
    }

    private function createLocalIngesterSource(string $path): IngesterSourceInterface
    {
        return (new LocalCsvIngesterSource())->setPath($path);
    }
}
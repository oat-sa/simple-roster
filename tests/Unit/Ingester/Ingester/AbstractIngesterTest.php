<?php declare(strict_types=1);

namespace App\Tests\Unit\Ingester\Ingester;

use App\Entity\EntityInterface;
use App\Ingester\Ingester\AbstractIngester;
use App\Ingester\Ingester\IngesterInterface;
use App\Ingester\Result\IngesterResult;
use App\Ingester\Source\AbstractIngesterSource;
use App\Ingester\Source\IngesterSourceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use ArrayIterator;
use Iterator;

class AbstractIngesterTest extends TestCase
{
    /** @var EntityManagerInterface */
    private $entityManager;

    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    public function testInternalEntityManagerIsNotInvolvedOnDryRun()
    {
        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $output = $this->createIngesterInstance()->ingest($this->createSourceInstance());

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertEquals(1, $output->getRowCount());
        $this->assertEquals('anonymousIngester', $output->getType());
        $this->assertEquals(
            '[DRY_RUN] 1 elements of type anonymousIngester have been ingested.',
            $output->__toString()
        );
    }

    public function testInternalEntityManagerPersistsAndFlushesEntityOnRealRun()
    {
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(EntityInterface::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $output = $this->createIngesterInstance()->ingest($this->createSourceInstance(), false);

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertEquals(1, $output->getRowCount());
        $this->assertEquals('anonymousIngester', $output->getType());
        $this->assertEquals(
            '1 elements of type anonymousIngester have been ingested.',
            $output->__toString()
        );
    }

    private function createIngesterInstance(): IngesterInterface
    {
        return new class ($this->entityManager) extends AbstractIngester
        {
            public function getRegistryItemName(): string
            {
                return 'anonymousIngester';
            }

            protected function createEntity(array $data): EntityInterface
            {
                return new class () implements EntityInterface
                {
                    public function getId(): ?int
                    {
                        return 1;
                    }
                };
            }
        };
    }

    private function createSourceInstance(): IngesterSourceInterface
    {
        return new class () extends AbstractIngesterSource
        {
            public function getRegistryItemName(): string
            {
                return 'anonymousSource';
            }

            public function read(): Iterator
            {
                return new ArrayIterator([[]]);
            }
        };
    }
}
<?php declare(strict_types=1);

namespace App\Tests\Traits;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\SchemaTool;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Common\Persistence\ManagerRegistry;

trait DatabaseTrait
{
    use BaseDatabaseTrait;

    protected function setUp(): void
    {
        $this->setUpDatabase();
    }

    protected function setUpDatabase(): KernelInterface
    {
        static::ensureKernelTestCase();

        $kernel = parent::bootKernel();

        $entityManager = $this->getEntityManager();

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropDatabase();
        $schemaTool->updateSchema($metadata);

        return $kernel;
    }

    protected function getManagerRegistry(): ManagerRegistry
    {
        return self::$kernel->getContainer()->get('doctrine');
    }

    /**
     * @return ObjectManager|EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getManagerRegistry()->getManager();
    }

    protected function getRepository(string $class): EntityRepository
    {
        return $this->getEntityManager()->getRepository($class);
    }
}

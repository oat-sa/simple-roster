<?php

namespace App\Tests\Traits;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\SchemaTool;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;
use Symfony\Component\HttpKernel\KernelInterface;

trait DatabaseTrait
{
    use BaseDatabaseTrait;

    protected function setUp()
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

    protected function getEntityManager(): EntityManager
    {
        return self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function getRepository(string $class): EntityRepository
    {
        return $this->getEntityManager()->getRepository($class);
    }

}

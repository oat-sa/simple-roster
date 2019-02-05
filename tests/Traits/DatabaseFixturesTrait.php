<?php

namespace App\Tests\Traits;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\SchemaTool;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;

trait DatabaseFixturesTrait
{
    use BaseDatabaseTrait;

    protected function setUp()
    {
        $this->setUpDatabaseAndFixture();
    }

    protected function setUpDatabaseAndFixture()
    {
        static::ensureKernelTestCase();

        parent::bootKernel();

        $entityManager = $this->getEntityManager();

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropDatabase();
        $schemaTool->updateSchema($metadata);

        static::populateDatabase();
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
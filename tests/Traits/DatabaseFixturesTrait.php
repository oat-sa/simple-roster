<?php

namespace App\Tests\Traits;

use Doctrine\ORM\Tools\SchemaTool;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;
use Symfony\Component\HttpKernel\Kernel;

trait DatabaseFixturesTrait
{
    use BaseDatabaseTrait;

    protected function setUp()
    {
        static::ensureKernelTestCase();

        /** @var Kernel $kernel */
        $kernel = parent::bootKernel();

        $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropDatabase();
        $schemaTool->updateSchema($metadata);

        static::populateDatabase();

        return $kernel;
    }
}
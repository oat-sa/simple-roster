<?php declare(strict_types=1);

namespace App\Tests\Traits;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\SchemaTool;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;
use LogicException;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Common\Persistence\ManagerRegistry;

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

    protected function getManagerRegistry(): ManagerRegistry
    {
        return self::$kernel->getContainer()->get('doctrine');
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        $manager = $this->getManagerRegistry()->getManager();

        if (!$manager instanceof EntityManagerInterface) {
            throw new LogicException(
                sprintf(
                    "ManagerRegistry returned '%s', '%s' expected",
                    get_class($manager),
                    EntityManagerInterface::class
                )
            );
        }

        return $manager;
    }

    protected function getRepository(string $class): EntityRepository
    {
        return $this->getEntityManager()->getRepository($class);
    }
}

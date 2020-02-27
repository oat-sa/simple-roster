<?php

declare(strict_types=1);

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

namespace App\Tests\Traits;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;

trait DatabaseTestingTrait
{
    use BaseDatabaseTrait;

    protected function setUpDatabase(): void
    {
        static::ensureKernelTestCase();

        $entityManager = $this->getEntityManager();

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropDatabase();
        $schemaTool->updateSchema($metadata);
    }

    /**
     * @param string $filename Relative filename to tests/Fixtures directory.
     */
    protected function loadFixtureByFilename(string $filename): void
    {
        /** @var PurgerLoader $loader */
        $loader = static::$container->get('fidry_alice_data_fixtures.loader.doctrine');

        $loader->load([sprintf('%s/../../tests/Fixtures/%s', __DIR__, $filename)]);
    }

    protected function getManagerRegistry(): ManagerRegistry
    {
        return self::$kernel->getContainer()->get('doctrine');
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->getManagerRegistry()->getManager();
    }

    protected function getRepository(string $class): ObjectRepository
    {
        return $this->getEntityManager()->getRepository($class);
    }
}

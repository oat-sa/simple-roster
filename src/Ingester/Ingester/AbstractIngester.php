<?php declare(strict_types=1);
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

namespace App\Ingester\Ingester;

use App\Entity\EntityInterface;
use App\Ingester\Result\IngesterResult;
use App\Ingester\Result\IngesterResultFailure;
use App\Ingester\Source\IngesterSourceInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Throwable;

abstract class AbstractIngester implements IngesterInterface
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function ingest(IngesterSourceInterface $source, bool $dryRun = true): IngesterResult
    {
        $result = new IngesterResult(
            $this->getRegistryItemName(),
            $source->getRegistryItemName(),
            $dryRun
        );

        $this->prepare();

        $lineNumber = 1;
        foreach ($source->getContent() as $data) {
            try {
                $entity = $this->createEntity($data);

                if (!$dryRun) {
                    $this->managerRegistry->getManager()->persist($entity);
                    $this->managerRegistry->getManager()->flush();
                }

                $result->addSuccess();
            } catch (Throwable $exception) {
                if (!$dryRun) {
                    $this->managerRegistry->resetManager();
                }

                $result->addFailure(
                    new IngesterResultFailure($lineNumber, $data, $exception->getMessage())
                );
            }

            $lineNumber++;
        }

        return $result;
    }

    protected function prepare(): void
    {
    }

    abstract protected function createEntity(array $data): EntityInterface;
}

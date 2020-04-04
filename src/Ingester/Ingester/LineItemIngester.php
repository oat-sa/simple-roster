<?php

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

declare(strict_types=1);

namespace App\Ingester\Ingester;

use App\Entity\EntityInterface;
use App\Entity\Infrastructure;
use App\Entity\LineItem;
use DateTime;
use Exception;

class LineItemIngester extends AbstractIngester
{
    /** @var Infrastructure[] */
    private $infrastructureCollection;

    public function getRegistryItemName(): string
    {
        return 'line-item';
    }

    /**
     * @throws Exception
     */
    protected function prepare(): void
    {
        /** @var Infrastructure[] $infrastructures */
        $infrastructures = $this->managerRegistry->getRepository(Infrastructure::class)->findAll();

        if (empty($infrastructures)) {
            throw new Exception(
                sprintf("Cannot ingest '%s' since infrastructure table is empty.", $this->getRegistryItemName())
            );
        }

        foreach ($infrastructures as $infrastructure) {
            $this->infrastructureCollection[$infrastructure->getLabel()] = $infrastructure;
        }
    }

    /**
     * @throws Exception
     */
    protected function createEntity(array $data): EntityInterface
    {
        $lineItem = new LineItem();

        $lineItem
            ->setUri($data['uri'])
            ->setLabel($data['label'])
            ->setSlug($data['slug'])
            ->setInfrastructure($this->infrastructureCollection[$data['infrastructure']]);

        if (isset($data['startTimestamp']) && $data['endTimestamp']) {
            $lineItem
                ->setStartAt($this->createDateTime($data['startTimestamp']))
                ->setEndAt($this->createDateTime($data['endTimestamp']));
        }

        return $lineItem;
    }

    /**
     * @throws Exception
     */
    private function createDateTime(string $value): DateTime
    {
        return (new DateTime())->setTimestamp((int)$value);
    }
}

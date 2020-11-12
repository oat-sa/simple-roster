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

namespace OAT\SimpleRoster\Ingester\Ingester;

use DateTime;
use Exception;
use OAT\SimpleRoster\Entity\EntityInterface;
use OAT\SimpleRoster\Entity\LineItem;

class LineItemIngester extends AbstractIngester
{
    public function getRegistryItemName(): string
    {
        return 'line-item';
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
            ->setMaxAttempts((int)$data['maxAttempts']);

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

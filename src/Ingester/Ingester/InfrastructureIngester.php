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
use App\Entity\Infrastructure;

class InfrastructureIngester extends AbstractIngester
{
    public function getRegistryItemName(): string
    {
        return 'infrastructure';
    }

    protected function createEntity(array $data): EntityInterface
    {
        return (new Infrastructure())
            ->setLabel($data['label'])
            ->setLtiDirectorLink($data['ltiDirectorLink'])
            ->setLtiKey($data['ltiKey'])
            ->setLtiSecret($data['ltiSecret']);
    }
}

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

use App\Entity\Assignment;
use App\Entity\EntityInterface;
use App\Entity\LineItem;
use App\Entity\User;
use Exception;

class UserIngester extends AbstractIngester
{
    /** @var LineItem[] */
    private $lineItemCollection;

    public function getRegistryItemName(): string
    {
        return 'user';
    }

    /**
     * @throws Exception
     */
    protected function prepare(): void
    {
        /** @var LineItem[] $lineItems */
        $lineItems = $this->managerRegistry->getRepository(LineItem::class)->findAll();

        if (empty($lineItems)) {
            throw new Exception(
                sprintf("Cannot ingest '%s' since line-item table is empty.", $this->getRegistryItemName())
            );
        }

        foreach ($lineItems as $lineItem) {
            $this->lineItemCollection[$lineItem->getSlug()] = $lineItem;
        }
    }

    protected function createEntity(array $data): EntityInterface
    {
        $assignment = new Assignment();
        $assignment
            ->setLineItem($this->lineItemCollection[$data['slug']])
            ->setState(Assignment::STATE_READY);

        $user = (new User())
            ->setUsername($data['username'])
            ->setPassword($data['password'])
            ->setPlainPassword($data['password'])
            ->addAssignment($assignment);

        if (isset($data['groupId'])) {
            $user->setGroupId($data['groupId']);
        }

        return $user;
    }
}

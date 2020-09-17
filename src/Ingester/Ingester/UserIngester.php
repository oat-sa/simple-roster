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

use App\Entity\Assignment;
use App\Entity\EntityInterface;
use App\Entity\LineItem;
use App\Entity\User;
use App\Exception\LineItemNotFoundException;
use App\Model\LineItemCollection;
use App\Repository\LineItemRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Exception;

class UserIngester extends AbstractIngester
{
    /** @var LineItemCollection */
    private $lineItemCollection;

    /** @var LineItemRepository */
    private $lineItemRepository;

    public function __construct(LineItemRepository $lineItemRepository, ManagerRegistry $managerRegistry)
    {
        $this->lineItemRepository = $lineItemRepository;

        parent::__construct($managerRegistry);
    }

    public function getRegistryItemName(): string
    {
        return 'user';
    }

    /**
     * @throws Exception
     */
    protected function prepare(): void
    {
        $this->lineItemCollection = $this->lineItemRepository->findAllAsCollection();

        if ($this->lineItemCollection->isEmpty()) {
            throw new Exception(
                sprintf("Cannot ingest '%s' since line-item table is empty.", $this->getRegistryItemName())
            );
        }
    }

    /**
     * @throws LineItemNotFoundException
     */
    protected function createEntity(array $data): EntityInterface
    {
        $assignment = new Assignment();
        $assignment
            ->setLineItem($this->lineItemCollection->getBySlug($data['slug']))
            ->setState(Assignment::STATE_READY)
            ->setAttemptsCount(0);

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

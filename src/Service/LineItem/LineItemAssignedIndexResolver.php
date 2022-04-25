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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\LineItem;

use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Repository\AssignmentRepository;

class LineItemAssignedIndexResolver
{
    private const DEFAULT_USERNAME_INCREMENT_VALUE = 0;

    private AssignmentRepository $assignmentRepository;

    public function __construct(AssignmentRepository $assignmentRepository)
    {
        $this->assignmentRepository = $assignmentRepository;
    }

    /**
     * @param LineItem[] $lineItems
     */
    public function getLastUserAssignedToLineItems(array $lineItems): array
    {
        /*
         * TODO: replace with uuid or something similar? current solution is not consistency safe
         * */

        $userNameIncrementArray = [];

        foreach ($lineItems as $lineItem) {
            $assignment = $this->assignmentRepository->findByLineItemId((int)$lineItem->getId());
            $userNameIncrementArray[$lineItem->getSlug()] = self::DEFAULT_USERNAME_INCREMENT_VALUE;

            if (!empty($assignment)) {
                $userData = $assignment->getUser()->getUsername();
                $userNameArray = explode('_', (string)$userData);
                $index = end($userNameArray);
                if (is_numeric($index)) {
                    $userNameIncrementArray[$lineItem->getSlug()] = (int)$index;
                }
            }
        }

        return $userNameIncrementArray;
    }
}

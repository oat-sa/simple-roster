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

namespace OAT\SimpleRoster\Lti\Service\AssignmentGenerator;

use Doctrine\ORM\OptimisticLockException;
use OAT\SimpleRoster\DataTransferObject\AssignmentDto;
use OAT\SimpleRoster\DataTransferObject\AssignmentDtoCollection;
use OAT\SimpleRoster\Model\AssignmentCollection;
use OAT\SimpleRoster\Repository\NativeAssignmentRepository;

class AssignmentGeneratorStateStorage implements AssignmentGeneratorStateStorageInterface
{
    private NativeAssignmentRepository $nativeAssignmentRepository;

    public function __construct(
        NativeAssignmentRepository $nativeAssignmentRepository
    ) {
        $this->nativeAssignmentRepository = $nativeAssignmentRepository;
    }

    /**
     *
     * @throws OptimisticLockException
     */
    public function insertAssignment(AssignmentCollection $assignments): void
    {
        $assignmentDtoCollection = new AssignmentDtoCollection();
        foreach ($assignments as $assignment) {
            $assignmentDtoCollection->add(
                new AssignmentDto(
                    $assignment->getState(),
                    $assignment->getLineItem()->getId(),
                    $assignment->getUser()->getUsername(),
                    $assignment->getUser()->getId()
                )
            );
        }

        $this->nativeAssignmentRepository->insertMultiple($assignmentDtoCollection);
    }
}

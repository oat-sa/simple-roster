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

namespace App\Service;

use App\Entity\Assignment;
use App\Exception\AssignmentNotFoundException;
use App\Repository\AssignmentRepository;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;

class CompleteUserAssignmentService
{
    /** @var AssignmentRepository */
    private $assignmentRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(AssignmentRepository $assignmentRepository, LoggerInterface $logger)
    {
        $this->assignmentRepository = $assignmentRepository;
        $this->logger = $logger;
    }

    /**
     * @throws AssignmentNotFoundException
     * @throws ORMException
     */
    public function markAssignmentAsCompleted(int $assignmentId): void
    {
        $assignment = $this->assignmentRepository->find($assignmentId);

        if (!$assignment) {
            throw new AssignmentNotFoundException(sprintf("Assignment with id '%s' not found.", $assignmentId));
        }

        $assignment->setState(Assignment::STATE_COMPLETED);

        $this->assignmentRepository->persist($assignment);
        $this->assignmentRepository->flush();

        $this->logger->info(
            sprintf(
                "Assignment with id='%s' of user with username='%s' has been marked as completed.",
                $assignmentId,
                $assignment->getUser()->getUsername()
            ),
            ['lineItem' => $assignment->getLineItem()]
        );
    }
}

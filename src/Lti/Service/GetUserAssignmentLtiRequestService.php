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

namespace OAT\SimpleRoster\Lti\Service;

use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Exception\AssignmentNotProcessableException;
use OAT\SimpleRoster\Lti\Factory\LtiRequestFactoryInterface;
use OAT\SimpleRoster\Lti\Request\LtiRequest;

class GetUserAssignmentLtiRequestService
{
    /** @var LtiRequestFactoryInterface */
    private $ltiRequestFactory;

    public function __construct(LtiRequestFactoryInterface $ltiRequestFactory)
    {
        $this->ltiRequestFactory = $ltiRequestFactory;
    }

    /**
     * @throws AssignmentNotProcessableException
     */
    public function getAssignmentLtiRequest(Assignment $assignment): LtiRequest
    {
        $this->checkIfAssignmentCanBeProcessed($assignment);

        return $this->ltiRequestFactory->create($assignment);
    }

    /**
     * @throws AssignmentNotProcessableException
     */
    private function checkIfAssignmentCanBeProcessed(Assignment $assignment): void
    {
        $lineItem = $assignment->getLineItem();
        $maxAttempts = $lineItem->getMaxAttempts();
        $attemptsCount = $assignment->getAttemptsCount();

        if (
            $lineItem->hasMaxAttempts() &&
            (
                $attemptsCount > $maxAttempts ||
                ($maxAttempts === $attemptsCount && $assignment->getState() === Assignment::STATE_COMPLETED)
            )
        ) {
            throw new AssignmentNotProcessableException(
                sprintf("Assignment with id '%s' has reached the maximum attempts.", $assignment->getId())
            );
        }

        if (!in_array($assignment->getState(), [Assignment::STATE_READY, Assignment::STATE_STARTED], true)) {
            throw new AssignmentNotProcessableException(
                sprintf("Assignment with id '%s' does not have a suitable state.", $assignment->getId())
            );
        }
    }
}

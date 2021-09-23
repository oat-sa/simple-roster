<?php

/*
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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Lti\BasicOutcome;

use LogicException;
use OAT\Library\Lti1p3BasicOutcome\Service\Server\Processor\BasicOutcomeServiceServerProcessorInterface;
use OAT\Library\Lti1p3BasicOutcome\Service\Server\Processor\Result\BasicOutcomeServiceServerProcessorResult;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\SimpleRoster\Service\CompleteUserAssignmentService;
use Psr\Log\LoggerInterface;
use Throwable;

class Lti1p3BasicOutcomeProcessor implements BasicOutcomeServiceServerProcessorInterface
{
    private CompleteUserAssignmentService $completeUserAssignmentService;
    private LoggerInterface $logger;

    public function __construct(
        CompleteUserAssignmentService $completeUserAssignmentService,
        LoggerInterface $logger
    ) {
        $this->completeUserAssignmentService = $completeUserAssignmentService;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function processReadResult(
        RegistrationInterface $registration,
        string $sourcedId
    ): BasicOutcomeServiceServerProcessorResult {
        throw new LogicException('Basic outcome read result operation is not supported');
    }

    /**
     * @inheritDoc
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function processReplaceResult(
        RegistrationInterface $registration,
        string $sourcedId,
        float $score,
        string $language = 'en'
    ): BasicOutcomeServiceServerProcessorResult {
        try {
            $this->completeUserAssignmentService->markAssignmentAsCompleted((int)$sourcedId);
            $isSuccess = true;
        } catch (Throwable $throwable) {
            $this->logger->error(
                sprintf(
                    'Unsuccessful basic outcome replace result operation: %s',
                    $throwable->getMessage()
                )
            );
            $isSuccess = false;
        }

        return new BasicOutcomeServiceServerProcessorResult($isSuccess);
    }

    /**
     * @inheritDoc
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function processDeleteResult(
        RegistrationInterface $registration,
        string $sourcedId
    ): BasicOutcomeServiceServerProcessorResult {
        throw new LogicException('Basic outcome delete result operation is not supported');
    }
}

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

namespace OAT\SimpleRoster\Action\Lti;

use OAT\SimpleRoster\Exception\AssignmentNotFoundException;
use OAT\SimpleRoster\Exception\InvalidLtiReplaceResultBodyException;
use OAT\SimpleRoster\Lti\Extractor\ReplaceResultSourceIdExtractor;
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Security\OAuth\OAuthSignatureValidatedActionInterface;
use OAT\SimpleRoster\Service\CompleteUserAssignmentService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateLtiOutcomeAction implements OAuthSignatureValidatedActionInterface
{
    /** @var ReplaceResultSourceIdExtractor */
    private $replaceResultSourceIdExtractor;

    /** @var CompleteUserAssignmentService */
    private $completeUserAssignmentService;

    /** @var SerializerResponder */
    private $serializerResponder;

    public function __construct(
        ReplaceResultSourceIdExtractor $replaceResultSourceIdExtractor,
        CompleteUserAssignmentService $completeUserAssignmentService,
        SerializerResponder $serializerResponder
    ) {
        $this->replaceResultSourceIdExtractor = $replaceResultSourceIdExtractor;
        $this->completeUserAssignmentService = $completeUserAssignmentService;
        $this->serializerResponder = $serializerResponder;
    }

    public function __invoke(Request $request): Response
    {
        try {
            $assignmentId = $this->replaceResultSourceIdExtractor->extractSourceId($request->getContent());

            $this->completeUserAssignmentService->markAssignmentAsCompleted($assignmentId);
        } catch (AssignmentNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        } catch (InvalidLtiReplaceResultBodyException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        return $this->serializerResponder->createJsonResponse(null);
    }
}

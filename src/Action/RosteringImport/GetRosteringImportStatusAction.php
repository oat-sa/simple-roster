<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Action\RosteringImport;

use OAT\SimpleRoster\Request\Validator\RosteringImport\RosteringImportReferenceIdValidator;
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Service\Rostering\RosteringImportStatusService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetRosteringImportStatusAction
{
    public function __construct(
        private readonly RosteringImportStatusService $statusService,
        private readonly RosteringImportReferenceIdValidator $referenceIdValidator,
        private readonly SerializerResponder $responder
    ) {
    }

    public function __invoke(string $referenceId): JsonResponse
    {
        $validatedReferenceId = $this->referenceIdValidator->validate($referenceId);

        $status = $this->statusService->getStatus($validatedReferenceId);
        if ($status === null) {
            throw new NotFoundHttpException(
                sprintf('Status for referenceId "%s" was not found.', $validatedReferenceId)
            );
        }

        return $this->responder->createJsonResponse(['result' => $status->toArray()], Response::HTTP_OK);
    }
}

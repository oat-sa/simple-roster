<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Action\RosteringImport;

use OAT\SimpleRoster\Request\Validator\RosteringImport\RosteringImportReferenceIdValidator;
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Service\Rostering\Exception\RosteringStatusException;
use OAT\SimpleRoster\Service\Rostering\RosteringImportStatusService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetRosteringImportDownloadAction
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

        try {
            $signedUrl = $this->statusService->getDownloadUrl($validatedReferenceId);
        } catch (RosteringStatusException $exception) {
            throw new BadRequestHttpException('Unable to resolve rostering import download URL.', $exception);
        }

        if ($signedUrl === null) {
            throw new NotFoundHttpException(
                sprintf('Download URL for referenceId "%s" is not available.', $validatedReferenceId)
            );
        }

        return $this->responder->createJsonResponse(['signedUrl' => $signedUrl], Response::HTTP_OK);
    }
}

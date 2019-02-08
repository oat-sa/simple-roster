<?php declare(strict_types=1);

namespace App\Action;

use App\Exception\AssignmentNotFoundException;
use App\Exception\InvalidLtiReplaceResultBodyException;
use App\Responder\SerializerResponder;
use App\Service\CompleteUserAssignmentService;
use App\Service\LTI\ReplaceResultSourceIdExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateLtiOutcomeAction implements OAuthSignatureValidatedAction
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

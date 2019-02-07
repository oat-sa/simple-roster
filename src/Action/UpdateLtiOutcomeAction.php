<?php declare(strict_types=1);

namespace App\Action;

use App\Exception\AssignmentNotFoundException;
use App\Exception\InvalidLtiReplaceResultBodyException;
use App\Responder\SerializerResponder;
use App\Service\CompleteAssignmentService;
use App\Service\LTI\ReplaceResultSourceIdExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateLtiOutcomeAction implements OAuthSignatureValidatedAction
{
    /** @var ReplaceResultSourceIdExtractor */
    private $replaceResultSourceIdExtractor;

    /** @var CompleteAssignmentService */
    private $completeAssignmentService;

    /** @var SerializerResponder */
    private $serializerResponder;

    public function __construct(
        ReplaceResultSourceIdExtractor $replaceResultSourceIdExtractor,
        CompleteAssignmentService $completeAssignmentService,
        SerializerResponder $serializerResponder
    )
    {
        $this->replaceResultSourceIdExtractor = $replaceResultSourceIdExtractor;
        $this->completeAssignmentService = $completeAssignmentService;
        $this->serializerResponder = $serializerResponder;
    }

    public function __invoke(Request $request): Response
    {
        try {
            $assignmentId = $this->replaceResultSourceIdExtractor->extractSourceId($request->getContent());

            $this->completeAssignmentService->markAssignmentAsCompleted($assignmentId);
        } catch (AssignmentNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        } catch (InvalidLtiReplaceResultBodyException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        return $this->serializerResponder->createJsonResponse(null);
    }
}

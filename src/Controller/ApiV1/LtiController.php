<?php declare(strict_types=1);

namespace App\Controller\ApiV1;

use App\Exception\AssignmentNotFoundException;
use App\Exception\InvalidLtiReplaceResultBodyException;
use App\Service\CompleteAssignmentService;
use App\Service\LTI\ReplaceResultSourceIdExtractor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/lti")
 */
class LtiController implements OAuthSignatureValidatedController
{
    /**
     * @Route("/outcome", name="api_v1_lti_outcome", methods={"POST"})
     */
    public function outcome(
        Request $request,
        ReplaceResultSourceIdExtractor $replaceResultSourceIdExtractor,
        CompleteAssignmentService $completeAssignmentService
    ): Response
    {
        $assignmentId = $replaceResultSourceIdExtractor->extractSourceId($request->getContent());

        try {
            $completeAssignmentService->markAssignmentAsCompleted($assignmentId);
        } catch (AssignmentNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        } catch (InvalidLtiReplaceResultBodyException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}

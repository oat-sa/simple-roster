<?php declare(strict_types=1);

namespace App\Controller\ApiV1;

use App\Lti\LaunchRequestBuilder;
use App\Model\Assignment;
use App\Model\Infrastructure;
use App\Model\LineItem;
use App\Model\User;
use App\ModelManager\InfrastructureManager;
use App\ModelManager\LineItemManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/assignments")
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class AssignmentController extends AbstractController
{
    /**
     * @Route("/", name="api_v1_get_assignments", methods={"GET"})
     */
    public function getAssignments(LineItemManager $lineItemManager): Response
    {
        $assignmentsToOutput = [];

        /** @var User $user */
        $user = $this->getUser();

        foreach ($user->getAssignments() as $assignment) {
            if ($assignment->getState() !== Assignment::STATE_CANCELLED) {
                /** @var LineItem $lineItem */
                $lineItem = $lineItemManager->read($assignment->getLineItemTaoUri());

                if ($lineItem === null) {
                    throw new \Exception('Line item has disappeared.');
                }

                $assignmentsToOutput[] = [
                    'id' => $assignment->getId(),
                    'username' => $user->getUsername(),
                    'lineItem' => [
                        'uri' => $lineItem->getTaoUri(),
                        'login' => $user->getUsername(),
                        'name' => $lineItem->getTitle(),
                        'startDateTime' => $lineItem->getStartDateTime()->getTimestamp(),
                        'endDateTime' => $lineItem->getEndDateTime()->getTimestamp(),
                        'infrastructure' => $lineItem->getInfrastructureId(),
                    ]
                ];
            }
        }

        return new JsonResponse(['assignments' => $assignmentsToOutput]);
    }

    /**
     * @Route("/", name="api_v1_add_assignment", methods={"POST"})
     */
    public function addAssignment()
    {
        //TODO
    }

    /**
     * @Route("/{id}/lti-link", name="api_v1_get_assignment_lti_link", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function getAssignmentLtiLink(int $id, InfrastructureManager $infrastructureManager, LineItemManager $lineItemManager, LaunchRequestBuilder $launchRequestBuilder)
    {
        if ($id === null) {
            throw new BadRequestHttpException('Mandatory parameter "id" is missing');
        }

        /** @var User $user */
        $user = $this->getUser();
        $foundAssignment = null;
        foreach ($user->getAssignments() as $assignment) {
            if ($assignment->getId() === $id) {
                $foundAssignment = $assignment;
                continue;
            }
        }

        if (!$foundAssignment) {
            throw $this->createNotFoundException(sprintf('Assignment with ID %d has not been found', $id));
        }

        /** @var LineItem $lineItem */
        $lineItem = $lineItemManager->read($foundAssignment->getLineItemTaoUri());

        if ($lineItem === null) {
            throw new \Exception(sprintf('Line item "%s" has disappeared.', $lineItem->getTaoUri()));
        }

        /** @var Infrastructure $infrastructure */
        $infrastructure = $infrastructureManager->read($lineItem->getInfrastructureId());
        if ($infrastructure === null) {
            throw new \Exception(sprintf('Infrastructure "%s" has disappeared.', $infrastructure->getId()));
        }

        $request = $launchRequestBuilder->build($user, $lineItem, $infrastructure);

        $requestParameters = $request->getAllParameters();

        $response = $requestParameters;
        $response['ltiLink'] = $request->getUrl();

        return new JsonResponse($response);
    }
}

<?php declare(strict_types=1);

namespace App\Controller\ApiV1;

use App\Model\Assignment;
use App\Model\LineItem;
use App\Model\User;
use App\ModelManager\LineItemManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
        $assignmentId = 0;
        foreach ($user->getAssignments() as $assignment) {
            $assignmentId++;
            if ($assignment->getState() !== Assignment::STATE_CANCELLED) {
                /** @var LineItem $lineItem */
                $lineItem = $lineItemManager->read($assignment->getLineItemTaoUri());

                $assignmentsToOutput[] = [
                    'id' => $assignmentId,
                    'username' => $user->getUsername(),
                    'lineItem' => [
                        'uri' => $lineItem->getTaoUri(),
                        'login' => $user->getUsername(),
                        'name' => $lineItem->getTitle(),
                        'startDateTime' => $lineItem->getStartDateTime(),
                        'endDateTime' => $lineItem->getEndDateTime(),
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
     * @Route("/{id}/lti-link", name="api_v1_get_assignment_lti_link", methods={"GET"})
     */
    public function getAssignmentLtiLink()
    {
        //TODO
    }
}

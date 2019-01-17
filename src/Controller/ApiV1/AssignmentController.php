<?php declare(strict_types=1);

namespace App\Controller\ApiV1;

use App\Model\Assignment;
use App\Model\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function getAssignments(): Response
    {
        $assignmentsToOutput = [];

        /** @var User $user */
        $user = $this->getUser();
        foreach ($user->getAssignments() as $assignment) {
            if ($assignment->getState() === Assignment::STATE_CANCELLED) {
                $assignmentsToOutput[] = $assignment->getLineItemTaoUri();
            }
        }

        return $this->json($assignmentsToOutput);
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

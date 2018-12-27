<?php

namespace App\Controller\ApiV1;

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
        $assignments = $this->getUser()->getData()['assignments'];
        foreach ($assignments as $assignment) {
            if (!array_key_exists('state', $assignment) || $assignment['state'] !== 'cancelled') {
                $assignmentsToOutput[] = $assignment['line_item_tao_uri'];
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

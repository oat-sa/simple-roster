<?php declare(strict_types=1);

namespace App\Controller\ApiV1;

use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/assignments")
 */
class AssignmentController
{
    /**
     * @Route("/", name="api_v1_get_assignments", methods={"GET"})
     */
    public function getAssignments()
    {
        //TODO
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

<?php declare(strict_types=1);

namespace App\Controller\ApiV1;

use App\Lti\LaunchRequestBuilder;
use App\Model\Assignment;
use App\Model\Infrastructure;
use App\Model\LineItem;
use App\Model\User;
use App\ODM\ItemManagerInterface;
use App\Service\AssignmentProvider;
use App\Service\LtiLinkProvider;
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
    public function getAssignments(AssignmentProvider $assignmentProvider): Response
    {
        return $this->json(['assignments' => $assignmentProvider->getAssignmentsSerializedForListing()]);
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
     *
     * @param string $id
     * @param LtiLinkProvider $linkProvider
     * @return JsonResponse
     * @throws \Exception
     */
    public function getAssignmentLtiLink(string $id, LtiLinkProvider $linkProvider): Response
    {
        return $this->json($linkProvider->provideLtiRequestParametersForAssignmentId($id));
    }
}

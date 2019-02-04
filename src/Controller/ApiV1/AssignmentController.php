<?php declare(strict_types=1);

namespace App\Controller\ApiV1;

use App\Lti\LaunchRequestBuilder;
use App\Model\Assignment;
use App\Model\Infrastructure;
use App\Model\LineItem;
use App\Model\User;
use App\ODM\ItemManagerInterface;
use App\Service\AssignmentProvider;
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
     * @Route("/{id}/lti-link", name="api_v1_get_assignment_lti_link", methods={"GET"}, requirements={"id"="\d+"})
     *
     * @param int $id
     * @param ItemManagerInterface $itemManager
     * @param LaunchRequestBuilder $launchRequestBuilder
     * @return JsonResponse
     * @throws \Exception
     */
    public function getAssignmentLtiLink(int $id, ItemManagerInterface $itemManager, LaunchRequestBuilder $launchRequestBuilder)
    {
        /** @var User $user */
        $user = $this->getUser();
        $foundAssignment = null;
        foreach ($user->getAssignments() as $assignment) {
            if ($assignment->getId() === $id) {
                $foundAssignment = $assignment;
                break;
            }
        }

        if ($foundAssignment === null) {
            throw $this->createNotFoundException(sprintf('Assignment with ID %d has not been found', $id));
        }

        /** @var LineItem $lineItem */
        $lineItem = $itemManager->load(Assignment::class, $foundAssignment->getLineItemTaoUri());

        if ($lineItem === null) {
            throw new \Exception(sprintf('Line item "%s" has disappeared.', $lineItem->getTaoUri()));
        }

        /** @var Infrastructure $infrastructure */
        $infrastructure = $itemManager->load(Infrastructure::class, $lineItem->getInfrastructureId());
        if ($infrastructure === null) {
            throw new \Exception(sprintf('Infrastructure "%s" has disappeared.', $infrastructure->getId()));
        }

        $ltiRequestParameters = $launchRequestBuilder->build($user, $lineItem, $infrastructure);
        return new JsonResponse($ltiRequestParameters);
    }
}

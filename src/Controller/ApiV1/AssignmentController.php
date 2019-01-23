<?php declare(strict_types=1);

namespace App\Controller\ApiV1;

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
    public function getAssignmentLtiLink(?string $id, InfrastructureManager $infrastructureManager, LineItemManager $lineItemManager)
    {
        if ($id === null) {
            throw new BadRequestHttpException('Mandatory parameter "id" is missing');
        }

        if (!is_numeric($id)) {
            throw new BadRequestHttpException('"id" should be numeric');
        }

        /** @var User $user */
        $user = $this->getUser();
        $foundAssignment = null;
        foreach ($user->getAssignments() as $assignmentId => $assignment) {
            if ($assignmentId === $id - 1) {
                $foundAssignment = $assignment;
                continue;
            }
        }

        if (!$foundAssignment) {
            throw $this->createNotFoundException(sprintf('Assignment with ID %d has not been found', $id));
        }

        /** @var LineItem $lineItem */
        $lineItem = $lineItemManager->read($foundAssignment->getLineItemTaoUri());

        /** @var Infrastructure $infrastructure */
        $infrastructure = $infrastructureManager->read($lineItem->getInfrastructureId());

        $roles = [];

        // @todo make a signature

        return new JsonResponse(
            [
                'ltiLink' => $infrastructure->getLtiDirectorLink() . base64_encode($lineItem->getTaoUri()),
                'lti_message_type' => 'basic-lti-launch-request',
                'lti_version' => 'LTI-1p0',

                'resource_link_id' => rand(0, 9999999),
                'resource_link_title' => 'Launch Title',
                'resource_link_label' => 'Launch label',

                'context_id' => 'Service call ID',
                'context_title' => 'Launch Title',
                'context_label' => 'Launch label',

                'user_id' => $user->getLogin(),
                'roles' => implode(',', $roles),
                'lis_person_name_full' => $user->getLogin(),

                'tool_consumer_info_product_family_code' => 'Roster',
                'tool_consumer_info_version' => '1.0.0',
            ]
        );
    }
}

<?php declare(strict_types=1);

namespace App\Controller\ApiV1;

use App\Entity\User;
use App\Repository\UserRepository;
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
        return $this->json(['assignments' => $this->getUser()->getAvailableAssignments()]);
    }

    /**
     * @Route("/{id}/lti-link", name="api_v1_get_assignment_lti_link", methods={"GET"})
     */
    public function getAssignmentLtiLink()
    {
        //TODO
    }

    private function getUserRepository(): UserRepository
    {
        return $this->get('doctrine')->getEntityManager()->getRepository(User::class);
    }
}

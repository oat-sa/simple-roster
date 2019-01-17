<?php declare(strict_types=1);

namespace App\Controller\ApiV1;

use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/lti")
 */
class LtiController
{
    /**
     * @Route("/outcome", name="api_v1_lti_outcome", methods={"POST"})
     */
    public function outcome()
    {

    }
}
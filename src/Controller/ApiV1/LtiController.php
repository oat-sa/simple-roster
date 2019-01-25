<?php declare(strict_types=1);

namespace App\Controller\ApiV1;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/lti")
 */
class LtiController implements OAuthSignatureValidatedController
{
    /**
     * @Route("/outcome", name="api_v1_lti_outcome", methods={"POST"})
     */
    public function outcome()
    {
        // TODO
        return new Response();
    }
}

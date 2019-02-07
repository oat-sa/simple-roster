<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Assignment;

class GetUserAssignmentLtiLinkService
{
    public function getAssignmentLtiLink(Assignment $assignment): string
    {
        return $assignment->getLineItem()->getInfrastructure()->getLtiDirectorLink();
    }
}

<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

use OAT\SimpleRoster\Service\Rostering\Dto\RosteringImportStatus;

interface PrincipalPortalStatusClientInterface
{
    public function fetchStatus(string $referenceId): RosteringImportStatus;
}


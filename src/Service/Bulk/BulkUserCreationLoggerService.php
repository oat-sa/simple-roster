<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Bulk;

use Psr\Log\LoggerInterface;

class BulkUserCreationLoggerService
{
    private LoggerInterface $userAssignmentLogger;

    public function __construct(
        LoggerInterface $userAssignmentLogger
    ) {
        $this->userAssignmentLogger = $userAssignmentLogger;
    }

    public function userDataLogger(
        string $username,
        string $lineSlugs
    ): void {

        $this->userAssignmentLogger->info(
            sprintf('%s user has created successfully and assigned a line item slug %s', $username, $lineSlugs)
        );
    }
}

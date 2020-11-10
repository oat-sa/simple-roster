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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Action\Bulk;

use OAT\SimpleRoster\Bulk\Operation\BulkOperationCollection;
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Service\Bulk\BulkUpdateUsersAssignmentsStateService;
use Symfony\Component\HttpFoundation\Response;

class BulkUpdateUsersAssignmentsStateAction
{
    /** @var BulkUpdateUsersAssignmentsStateService */
    private $bulkAssignmentsStateService;

    /** @var SerializerResponder */
    private $responder;

    public function __construct(
        BulkUpdateUsersAssignmentsStateService $bulkUpdateAssignmentsStateService,
        SerializerResponder $responder
    ) {
        $this->bulkAssignmentsStateService = $bulkUpdateAssignmentsStateService;
        $this->responder = $responder;
    }

    public function __invoke(BulkOperationCollection $operationCollection): Response
    {
        return $this->responder->createJsonResponse(
            $this->bulkAssignmentsStateService->process($operationCollection)
        );
    }
}

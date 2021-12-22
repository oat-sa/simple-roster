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

namespace OAT\SimpleRoster\Action\CreateEntity;

use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Service\Bulk\BulkCreateUsersService;
use OAT\SimpleRoster\Request\Validator\BulkCreateUserValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BulkCreateUsersAction
{
    private SerializerResponder $responder;
    private BulkCreateUserValidator $bulkCreateUserValidator;
    private BulkCreateUsersService $bulkCreateUsersService;

    private const SERVICE_INVOKED_FROM = 'api';

    public function __construct(
        BulkCreateUsersService $bulkCreateUsersService,
        BulkCreateUserValidator $bulkCreateUserValidator,
        SerializerResponder $responder
    ) {
        $this->bulkCreateUserValidator = $bulkCreateUserValidator;
        $this->bulkCreateUsersService = $bulkCreateUsersService;
        $this->responder = $responder;
    }

    public function __invoke(Request $request): Response
    {
        $requestPayLoad = $this->bulkCreateUserValidator->validate($request);
        return $this->responder->createJsonResponse(
            $this->bulkCreateUsersService->createUsers(
                $requestPayLoad['lineItemIds'],
                $requestPayLoad['lineItemSlugs'],
                $requestPayLoad['userPrefixes'],
                $requestPayLoad['quantity'],
                $requestPayLoad['groupIdPrefix'],
            ),
            Response::HTTP_CREATED
        );
    }
}

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
use OAT\SimpleRoster\Service\Bulk\BulkCreateUsersServiceConsoleProxy;
use OAT\SimpleRoster\Service\AwsS3\FolderSyncService;
use OAT\SimpleRoster\Service\Bulk\BulkCreateUsersService;
use OAT\SimpleRoster\Request\Validator\BulkCreateUserValidator;
use OAT\SimpleRoster\Request\Initialize\BulkCreateUserRequestInitialize;
use OAT\SimpleRoster\Service\Bulk\CreateUserServiceContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BulkCreateUsersAction
{
    private SerializerResponder $responder;
    private BulkCreateUserValidator $bulkCreateUserValidator;
    private BulkCreateUsersServiceConsoleProxy $bulkCreateUsersService;
    private BulkCreateUserRequestInitialize $bulkCreateUserRequestInitialize;
    private FolderSyncService $userFolderSync;
    private CreateUserServiceContext $createUserServiceContext;

    public function __construct(
        BulkCreateUsersServiceConsoleProxy $bulkCreateUsersService,
        BulkCreateUserValidator $bulkCreateUserValidator,
        BulkCreateUserRequestInitialize $bulkCreateUserRequestInitialize,
        FolderSyncService $userFolderSync,
        SerializerResponder $responder,
        CreateUserServiceContext $createUserServiceContext
    ) {
        $this->bulkCreateUserValidator = $bulkCreateUserValidator;
        $this->bulkCreateUsersService = $bulkCreateUsersService;
        $this->bulkCreateUserRequestInitialize = $bulkCreateUserRequestInitialize;
        $this->userFolderSync = $userFolderSync;
        $this->responder = $responder;
        $this->createUserServiceContext = $createUserServiceContext;
    }

    public function __invoke(Request $request): Response
    {
        $this->bulkCreateUserValidator->validate($request);
        $requestPayLoad = $this->bulkCreateUserRequestInitialize->initializeRequestData($request);

        $folderName = date('Y-m-d');

        $context = $this->createUserServiceContext
            ->withBatch($requestPayLoad['quantity'])
            ->withPrefixes($requestPayLoad['userPrefixes']);

        $response = $this->bulkCreateUsersService->createUsers(
            $requestPayLoad['lineItemIds'],
            $requestPayLoad['lineItemSlugs'],
            $context,
            $requestPayLoad['groupIdPrefix'],
            $folderName
        );

        $this->userFolderSync->sync($folderName);

        return $this->responder->createJsonResponse($response, Response::HTTP_CREATED);
    }
}

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
 *  Copyright (c) 2026 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Action\Upload;

use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Service\Upload\Exception\UploadedFileValidationException;
use OAT\SimpleRoster\Service\Upload\UploadFileService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UploadFileAction
{
    public function __construct(
        private readonly UploadFileService $uploadFileService,
        private readonly SerializerResponder $responder
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile) {
            throw new BadRequestHttpException('No file found in the request.');
        }

        try {
            $result = $this->uploadFileService->upload($file);
        } catch (UploadedFileValidationException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        return $this->responder->createJsonResponse(['result' => $result], Response::HTTP_OK);
    }
}

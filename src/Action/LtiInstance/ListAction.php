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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Action\LtiInstance;

use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\Responder\LtiInstance\Serializer;
use Symfony\Component\HttpFoundation\Response;

class ListAction
{
    private LtiInstanceRepository $repository;
    private Serializer $serializer;

    public function __construct(LtiInstanceRepository $repository, Serializer $serializer)
    {
        $this->repository = $repository;
        $this->serializer = $serializer;
    }

    public function __invoke(): Response
    {
        return $this->serializer->createJsonFromCollection($this->repository->findAll(), 200);
    }
}

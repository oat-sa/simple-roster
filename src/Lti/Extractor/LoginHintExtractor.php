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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Lti\Extractor;

use LogicException;
use OAT\SimpleRoster\DataTransferObject\LoginHintDto;
use Symfony\Component\Uid\UuidV6;

class LoginHintExtractor
{
    /**
     * @throws LogicException
     */
    public function extract(string $loginHint): LoginHintDto
    {
        $matches = [];

        preg_match('/^(?P<username>.*)::(?P<assignmentId>.*)$/', $loginHint, $matches);

        if (empty($matches)) {
            throw new LogicException('Invalid Login hint format.');
        }

        if (empty($matches['username'])) {
            throw new LogicException('Missing username on login hint.');
        }

        if (empty($matches['assignmentId'])) {
            throw new LogicException('Missing assignment ID on login hint.');
        }

        return new LoginHintDto(
            $matches['username'],
            new UuidV6($matches['assignmentId']),
        );
    }
}

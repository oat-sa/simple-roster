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

namespace OAT\SimpleRoster\Lti\Request;

use JsonSerializable;

class LtiRequest implements JsonSerializable
{
    public const LTI_MESSAGE_TYPE = 'basic-lti-launch-request';
    public const LTI_VERSION_1P1 = '1.1.1';
    public const LTI_VERSION_1P3 = '1.3.0';
    public const LTI_ROLE = 'Learner';

    /** @var string */
    private $link;

    /** @var string */
    private $version;

    /** @var array  */
    private $parameters;

    public function __construct(string $link, string $version, array $parameters)
    {
        $this->link = $link;
        $this->version = $version;
        $this->parameters = $parameters;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function jsonSerialize(): array
    {
        return [
            'ltiLink' => $this->link,
            'ltiVersion' => $this->version,
            'ltiParams' => $this->parameters,
        ];
    }
}

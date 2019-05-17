<?php declare(strict_types=1);
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

namespace App\Lti\Request;

use JsonSerializable;

class LtiRequest implements JsonSerializable
{
    public const LTI_MESSAGE_TYPE = 'basic-lti-launch-request';
    public const LTI_VERSION = 'LTI-1p0';
    public const LTI_CONTEXT_TYPE = 'CourseSection';
    public const LTI_ROLE = 'Learner';

    /** @var string */
    private $link;

    /** @var array  */
    private $parameters;

    public function __construct(string $link, array $parameters)
    {
        $this->link = $link;
        $this->parameters = $parameters;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function jsonSerialize()
    {
        return [
            'ltiLink' => $this->link,
            'ltiParams' => $this->parameters,
        ];
    }
}

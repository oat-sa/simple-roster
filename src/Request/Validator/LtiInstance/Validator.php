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

namespace OAT\SimpleRoster\Request\Validator\LtiInstance;

use DateTimeInterface;
use OAT\SimpleRoster\Request\Validator\AbstractRequestValidator;
use Symfony\Component\Validator\Constraints as Assert;

class Validator extends AbstractRequestValidator
{
    protected function getConstraints(): Assert\Collection
    {
        return new Assert\Collection(
            [
                'fields' => [
                    'label' => new Assert\Required([new Assert\Type('string')]),
                    'lti_link' => new Assert\Required([new Assert\Type('string')]),
                    'lti_key' => new Assert\Required([new Assert\Type('string')]),
                    'lti_secret' => new Assert\Required([new Assert\Type('string')]),
                ],
                'allowExtraFields' => true,
            ],
        );
    }
}

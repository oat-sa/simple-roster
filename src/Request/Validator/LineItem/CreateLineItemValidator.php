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

namespace OAT\SimpleRoster\Request\Validator\LineItem;

use DateTimeInterface;
use OAT\SimpleRoster\Request\Validator\AbstractRequestValidator;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateLineItemValidator extends AbstractRequestValidator
{
    public function __construct(ValidatorInterface $validator)
    {
        parent::__construct($validator);
    }

    protected function getConstraints(): Assert\Collection
    {
        return new Assert\Collection(
            [
                'fields' => [
                    'slug' => new Assert\Type('string'),
                    'uri' => new Assert\Type('string'),
                    'label' => new Assert\Type('string'),
                    'isActive' => new Assert\Type('bool'),
                    'startAt' => new Assert\Optional([new Assert\DateTime(DateTimeInterface::ATOM)]),
                    'endAt' => new Assert\Optional([new Assert\DateTime(DateTimeInterface::ATOM)]),
                    'maxAttempts' => new Assert\PositiveOrZero(),
                ],
                'allowExtraFields' => true,
            ],
        );
    }
}

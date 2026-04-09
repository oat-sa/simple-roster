<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Request\Validator\LineItem;

use OAT\SimpleRoster\Request\Validator\AbstractRequestValidator;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateLineItemValidator extends AbstractRequestValidator
{
    protected function getConstraints(): Assert\Collection
    {
        return new Assert\Collection(
            [
                'fields' => [
                    'source' => new Assert\Optional([new Assert\Type('string')]),
                    'events' => new Assert\Sequentially(
                        [
                            new Assert\Type('array'),
                            new Assert\Count(['min' => 1]),
                            new Assert\All(
                                [
                                    new Assert\Collection(
                                        [
                                            'fields' => [
                                                'eventId' => new Assert\Type('string'),
                                                'eventName' => new Assert\Type('string'),
                                                'triggeredTimestamp' => new Assert\Type('int'),
                                                'eventData' => new Assert\Collection(
                                                    [
                                                        'fields' => [
                                                            "alias" => new Assert\Required(
                                                                [
                                                                    new Assert\Type('string'),
                                                                    new Assert\NotNull(array(
                                                                        'message' => 'Alias should be a valid string'
                                                                    ))
                                                                ],
                                                            ),
                                                            "remoteDeliveryId" => new Assert\Type('string'),
                                                            "label" => new Assert\Optional(
                                                                [
                                                                    new Assert\Type('string'),
                                                                ],
                                                            ),
                                                            "startAt" => new Assert\Optional(
                                                                [
                                                                    new Assert\Type('int'),
                                                                ],
                                                            ),
                                                            "endAt" => new Assert\Optional(
                                                                [
                                                                    new Assert\Type('int'),
                                                                ],
                                                            ),
                                                            "maxExecutions" => new Assert\Optional(
                                                                [
                                                                    new Assert\Type('int'),
                                                                ],
                                                            ),
                                                        ],
                                                        'allowExtraFields' => true,
                                                    ],
                                                ),
                                            ],
                                            'allowExtraFields' => true,
                                        ],
                                    ),
                                ],
                            ),
                        ],
                    ),
                ],
                'allowExtraFields' => true,
            ],
        );
    }
}

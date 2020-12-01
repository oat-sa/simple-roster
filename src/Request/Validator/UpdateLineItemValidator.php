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

namespace OAT\SimpleRoster\Request\Validator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

class UpdateLineItemValidator
{
    /** @var ValidatorInterface */
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function validate(Request $request): void
    {
        try {
            $responseBody = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $jsonException) {
            throw new BadRequestHttpException(
                sprintf(
                    'Invalid JSON request body received. Error: %s.',
                    $jsonException->getMessage()
                ),
                $jsonException
            );
        }

        $errors = $this->validator->validate($responseBody, $this->getConstraints());

        if ($errors->count() === 0) {
            return;
        }

        $errorsRaw = [];
        /** @var ConstraintViolationInterface $error */
        foreach ($errors as $error) {
            $errorsRaw[] = sprintf(
                "%s -> %s",
                $error->getPropertyPath(),
                $error->getMessage()
            );
        }

        throw new BadRequestHttpException(sprintf('Invalid Request Body: %s', implode(" ", $errorsRaw)));
    }

    /**
     * @return Assert\Collection
     */
    private function getConstraints(): Assert\Collection
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
                                                            "alias" => new Assert\Optional(
                                                                [
                                                                    new Assert\Type('string'),
                                                                ]
                                                            ),
                                                            "deliveryURI" => new Assert\Type('string'),
                                                        ],
                                                        'allowExtraFields' => true,
                                                    ]
                                                ),
                                            ],
                                            'allowExtraFields' => true,
                                        ]
                                    )
                                ]
                            )
                        ]
                    )
                ],
                'allowExtraFields' => true,
            ],
        );
    }
}

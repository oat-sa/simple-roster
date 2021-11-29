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

namespace OAT\SimpleRoster\Request\Validator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

class CreateLineItemValidator
{
    private ValidatorInterface $validator;

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

        $rawErrors = [];
        /** @var ConstraintViolationInterface $error */
        foreach ($errors as $error) {
            $rawErrors[] = sprintf(
                "%s -> %s",
                $error->getPropertyPath(),
                $error->getMessage()
            );
        }

        throw new BadRequestHttpException(sprintf('Invalid Request Body: %s', implode(" ", $rawErrors)));
    }

    private function getConstraints(): Assert\Collection
    {
        return new Assert\Collection(
            [
                'fields' => [
                    'slug' => new Assert\Type('string'),
                    'uri' => new Assert\Type('string'),
                    'label' => new Assert\Type('string'),
                    'isActive' => new Assert\Type('bool'),
                    'startAt' => new Assert\Optional([new Assert\DateTime(\DateTimeInterface::ATOM)]),
                    'endAt' => new Assert\Optional([new Assert\DateTime(\DateTimeInterface::ATOM)]),
                    'maxAttempts' => new Assert\PositiveOrZero(),
                ],
                'allowExtraFields' => true,
            ],
        );
    }
}

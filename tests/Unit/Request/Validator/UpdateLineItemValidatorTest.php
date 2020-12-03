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

namespace OAT\SimpleRoster\Tests\Unit\Request\Validator;

use ArrayIterator;
use OAT\SimpleRoster\Request\Validator\UpdateLineItemValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateLineItemValidatorTest extends TestCase
{
    /** @var MockObject|ValidatorInterface */
    private $validator;

    /** @var UpdateLineItemValidator */
    private $sut;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(
            ValidatorInterface::class
        );
        $this->sut = new UpdateLineItemValidator(
            $this->validator
        );
    }

    public function testItValidatesSuccessfull(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('getContent')
            ->willReturn('{}');

        $this->validator->expects($this->once())
            ->method('validate')
            ->with([], $this->getConstraints())
            ->willReturn(
                new ArrayIterator()
            );

        $this->sut->validate($request);
    }

    public function testItThrowsExceptionDuringValidation(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid Request Body: eventId -> empty');

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('getContent')
            ->willReturn('{}');

        $error = $this->createMock(ConstraintViolationInterface::class);
        $error->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn('eventId');
        $error->expects($this->once())
            ->method('getMessage')
            ->willReturn('empty');

        $this->validator->expects($this->once())
            ->method('validate')
            ->with([], $this->getConstraints())
            ->willReturn(
                new ArrayIterator(
                    [
                        $error
                    ]
                )
            );

        $this->sut->validate($request);
    }


    public function testItThrowsExceptionForInvalidJson(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(
            'Invalid JSON request body received. Error: json_decode() expects parameter 1 to be string, null given.'
        );

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('getContent');

        $this->validator->expects($this->never())
            ->method('validate');

        $this->sut->validate($request);
    }

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

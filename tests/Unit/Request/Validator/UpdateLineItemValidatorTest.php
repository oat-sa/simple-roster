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
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateLineItemValidatorTest extends TestCase
{
    /** @var MockObject|ValidatorInterface */
    private $validator;

    private UpdateLineItemValidator $subject;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->subject = new UpdateLineItemValidator($this->validator);
    }

    public function testItValidatesSuccessfully(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getContent')
            ->willReturn('{}');

        $this->validator->expects(self::once())
            ->method('validate')
            ->with([])
            ->willReturn(
                new ArrayIterator()
            );

        $this->subject->validate($request);
    }

    public function testItThrowsExceptionDuringValidation(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid Request Body: eventId -> empty');

        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getContent')
            ->willReturn('{}');

        $error = $this->createMock(ConstraintViolationInterface::class);
        $error->expects(self::once())
            ->method('getPropertyPath')
            ->willReturn('eventId');
        $error->expects(self::once())
            ->method('getMessage')
            ->willReturn('empty');

        $this->validator->expects(self::once())
            ->method('validate')
            ->with([])
            ->willReturn(
                new ArrayIterator(
                    [
                        $error
                    ]
                )
            );

        $this->subject->validate($request);
    }


    public function testItThrowsExceptionForInvalidJson(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(
            'Invalid JSON request body received. Error: json_decode() expects parameter 1 to be string, null given.'
        );

        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getContent');

        $this->validator->expects(self::never())
            ->method('validate');

        $this->subject->validate($request);
    }
}

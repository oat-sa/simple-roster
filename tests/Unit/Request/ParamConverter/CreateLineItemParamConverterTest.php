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

namespace OAT\SimpleRoster\Tests\Unit\Request\ParamConverter;

use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Request\ParamConverter\CreateLineItemParamConverter;
use OAT\SimpleRoster\Request\Validator\LineItem\CreateLineItemValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateLineItemParamConverterTest extends TestCase
{
    private CreateLineItemParamConverter $subject;
    private MockObject&CreateLineItemValidator $createLineItemValidator;
    private MockObject&LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createLineItemValidator = $this->createMock(CreateLineItemValidator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new CreateLineItemParamConverter(
            $this->createLineItemValidator,
            $this->logger
        );
    }

    public function testItReturnsEmptyForNonLineItemArgument(): void
    {
        $wrongArgument = new ArgumentMetadata('anything', \stdClass::class, false, false, null);

        $result = $this->subject->resolve(new Request(), $wrongArgument);

        self::assertEmpty(iterator_to_array($result, false));
        $this->createLineItemValidator->expects(self::never())->method('validate');
    }

    public function testItResolvesLineItemAndValidates(): void
    {
        $argument = new ArgumentMetadata('lineItem', LineItem::class, false, false, null);

        $payload = ['uri' => 'test-uri', 'slug' => 'test-slug'];
        $request = $this->createJsonRequest($payload);

        $this->createLineItemValidator
            ->expects(self::once())
            ->method('validate')
            ->with($request);

        $result = iterator_to_array($this->subject->resolve($request, $argument), false);

        self::assertCount(1, $result);
        self::assertInstanceOf(LineItem::class, $result[0]);
    }

    public function testItThrowsExceptionWhenValidationFails(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $argument = new ArgumentMetadata('lineItem', LineItem::class, false, false, null);
        $request = $this->createJsonRequest(['uri' => 'x', 'slug' => 'y']);

        $this->createLineItemValidator
            ->expects(self::once())
            ->method('validate')
            ->with($request)
            ->willThrowException(new BadRequestHttpException('validation failed'));

        iterator_to_array($this->subject->resolve($request, $argument), false);
    }

    public function testItConvertsParametersSuccessfully(): void
    {
        $argument = new ArgumentMetadata('lineItem', LineItem::class, false, false, null);

        $payload = [
            'slug' => 'my-slug',
            'uri' => 'my-uri',
            'startDateTime' => '2021-01-01T00:00:00+0000',
            'endDateTime' => '2021-01-31T00:00:00+0000',
        ];
        $request = $this->createJsonRequest($payload);

        $this->createLineItemValidator
            ->expects(self::once())
            ->method('validate')
            ->with($request);

        $result = iterator_to_array($this->subject->resolve($request, $argument), false);

        self::assertCount(1, $result);

        /** @var LineItem $lineItem */
        $lineItem = $result[0];

        self::assertInstanceOf(LineItem::class, $lineItem);
        self::assertSame('my-uri', $lineItem->getUri());
        self::assertSame('my-slug', $lineItem->getSlug());

        self::assertNotNull($lineItem->getStartAt());
        self::assertSame('2021-01-01T00:00:00+00:00', $lineItem->getStartAt()->format(\DateTimeInterface::ATOM));

        /** @var \DateTimeInterface|null $endAt */
        $endAt = $lineItem->getEndAt();
        self::assertNotNull($endAt);
        self::assertSame('2021-01-31T00:00:00+00:00', $endAt->format(\DateTimeInterface::ATOM));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createJsonRequest(array $payload): Request
    {
        return new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR)
        );
    }
}

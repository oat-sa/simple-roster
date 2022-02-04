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

use DateTimeInterface;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Request\ParamConverter\CreateLineItemParamConverter;
use OAT\SimpleRoster\Request\Validator\LineItem\CreateLineItemValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateLineItemParamConverterTest extends TestCase
{
    private CreateLineItemParamConverter $subject;

    /** @var MockObject|CreateLineItemValidator */
    private $createLineItemValidator;

    /** @var MockObject|LoggerInterface */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createLineItemValidator = $this->createMock(CreateLineItemValidator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new CreateLineItemParamConverter($this->createLineItemValidator, $this->logger);
    }

    public function testItIsAParamConverter(): void
    {
        self::assertInstanceOf(ParamConverterInterface::class, $this->subject);
    }

    public function testItSupportsLineItem(): void
    {
        $paramConverter = new ParamConverter([]);
        $paramConverter->setClass(LineItem::class);

        self::assertTrue($this->subject->supports($paramConverter));
    }

    public function testItThrowsExceptionCaseValidationFail(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $request = $this->createMock(Request::class);

        $this->createLineItemValidator->expects(self::once())
            ->method('validate')
            ->with($request)
            ->willThrowException(new BadRequestHttpException());

        $this->subject->apply($request, $this->createMock(ParamConverter::class));
    }

    public function testItConvertsParametersSuccessfully(): void
    {
        $request = $this->createMock(Request::class);

        $payload = json_encode([
            'slug' => 'my-slug',
            'uri' => 'my-uri',
            'label' => 'my-slug',
            'isActive' => true,
            'startDateTime' => '2021-01-01T00:00:00+0000',
            'endDateTime' => '2021-01-31T00:00:00+0000',
            'maxAttempts' => 0,
        ]);

        $request->expects(self::once())
            ->method('getContent')
            ->willReturn($payload);

        $request->attributes = new ParameterBag();

        $this->createLineItemValidator->expects(self::once())
            ->method('validate')
            ->with($request);

        $configuration = $this->createMock(ParamConverter::class);

        $configuration->expects(self::once())
            ->method('getName')
            ->willReturn('collection');

        self::assertTrue($this->subject->apply($request, $configuration));

        self::assertInstanceOf(LineItem::class, $request->attributes->get('collection'));

        /** @var LineItem $lineItem */
        $lineItem = $request->attributes->get('collection');

        self::assertInstanceOf(LineItem::class, $lineItem);
        self::assertSame('my-uri', $lineItem->getUri());
        self::assertSame('my-slug', $lineItem->getLabel());
        self::assertSame('my-slug', $lineItem->getSlug());
        self::assertTrue($lineItem->isActive());
        self::assertSame(0, $lineItem->getMaxAttempts());
        self::assertSame('2021-01-01T00:00:00+00:00', $lineItem->getStartAt()->format(DateTimeInterface::ATOM));
        self::assertSame('2021-01-31T00:00:00+00:00', $lineItem->getEndAt()->format(DateTimeInterface::ATOM));
    }
}

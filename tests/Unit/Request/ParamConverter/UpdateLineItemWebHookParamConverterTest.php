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

namespace OAT\SimpleRoster\Tests\Unit\Request\ParamConverter;

use ArrayIterator;
use OAT\SimpleRoster\Request\ParamConverter\UpdateLineItemWebHookParamConverter;
use OAT\SimpleRoster\Request\Validator\LineItem\UpdateLineItemValidator;
use OAT\SimpleRoster\WebHook\UpdateLineItemCollection;
use OAT\SimpleRoster\WebHook\UpdateLineItemDto;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UpdateLineItemWebHookParamConverterTest extends TestCase
{
    private UpdateLineItemWebHookParamConverter $subject;
    private MockObject&UpdateLineItemValidator $updateLineItemValidator;
    private MockObject&LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->updateLineItemValidator = $this->createMock(UpdateLineItemValidator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new UpdateLineItemWebHookParamConverter(
            $this->updateLineItemValidator,
            $this->logger
        );
    }

    public function testItConvertsSuccessful(): void
    {
        $eventName = 'oat\\\\taoPublishing\\\\model\\\\publishing\\\\event\\\\RemoteDeliveryCreatedEvent';

        $payload = '{
            "source":"https://someinstance.taocloud.org/",
            "events":[
                {
                    "eventId":"52a3de8dd0f270fd193f9f4bff05232c",
                    "eventName":"' . $eventName . '",
                    "triggeredTimestamp":1565602390,
                    "eventData":{
                        "alias":"qti-interactions-delivery",
                        "remoteDeliveryId":"https://tao.instance/ontologies/tao.rdf#kkkkzk",
                        "label":"qti-interactions-delivery-label",
                        "startAt":1665561600,
                        "endAt":1666094400,
                        "maxExecutions":9
                    }
                }
            ]
        }';

        $decodedPayload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        $request = Request::create('/test', Request::METHOD_POST, [], [], [], [], $payload);
        $argument = new ArgumentMetadata('collection', UpdateLineItemCollection::class, false, false, null);

        $this->updateLineItemValidator
            ->expects(self::once())
            ->method('validate')
            ->with($request);

        $this->logger
            ->expects(self::once())
            ->method('info')
            ->with('UpdateLineItems payload.', $decodedPayload);

        $result = iterator_to_array($this->subject->resolve($request, $argument), false);

        self::assertCount(1, $result);
        self::assertInstanceOf(UpdateLineItemCollection::class, $result[0]);

        /** @var UpdateLineItemCollection $collection */
        $collection = $result[0];

        /** @var ArrayIterator $iterator */
        $iterator = $collection->getIterator();
        self::assertCount(1, $iterator);

        $dto = $iterator[0];
        self::assertInstanceOf(UpdateLineItemDto::class, $dto);

        self::assertSame('52a3de8dd0f270fd193f9f4bff05232c', $dto->getId());
        self::assertSame('oat\\taoPublishing\\model\\publishing\\event\\RemoteDeliveryCreatedEvent', $dto->getName());
        self::assertSame(1565602390, $dto->getTriggeredTime()->getTimestamp());
        self::assertSame('qti-interactions-delivery', $dto->getSlug());
        self::assertSame('https://tao.instance/ontologies/tao.rdf#kkkkzk', $dto->getLineItemUri());
    }

    public function testItReturnsEmptyForUnsupportedClass(): void
    {
        $request = new Request();
        $argument = new ArgumentMetadata('wrong', \stdClass::class, false, false, null);

        $result = iterator_to_array($this->subject->resolve($request, $argument), false);

        self::assertEmpty($result);
    }

    public function testItThrowsExceptionCaseValidationFail(): void
    {
        $request = new Request();
        $argument = new ArgumentMetadata('collection', UpdateLineItemCollection::class, false, false, null);

        $this->updateLineItemValidator
            ->expects(self::once())
            ->method('validate')
            ->with($request)
            ->willThrowException(new BadRequestHttpException('Validation failed'));

        $this->expectException(BadRequestHttpException::class);

        iterator_to_array($this->subject->resolve($request, $argument), false);
    }
}

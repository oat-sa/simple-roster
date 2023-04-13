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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UpdateLineItemWebHookParamConverterTest extends TestCase
{
    private UpdateLineItemWebHookParamConverter $subject;

    /** @var MockObject|UpdateLineItemValidator */
    private $updateLineItemValidator;

    /** @var MockObject|LoggerInterface */
    private $logger;

    protected function setUp(): void
    {
        $this->updateLineItemValidator = $this->createMock(
            UpdateLineItemValidator::class
        );

        $this->logger = $this->createMock(
            LoggerInterface::class
        );

        $this->subject = new UpdateLineItemWebHookParamConverter(
            $this->updateLineItemValidator,
            $this->logger
        );
    }

    public function testItConvertsSuccessful(): void
    {
        $request = $this->createMock(
            Request::class
        );

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

        $request->expects(self::once())
            ->method('getContent')
            ->willReturn($payload);

        $request->attributes = new ParameterBag();

        $this->updateLineItemValidator->expects(self::once())
            ->method('validate')
            ->with($request);

        $configuration = $this->createMock(
            ParamConverter::class
        );

        $configuration->expects(self::once())
            ->method('getName')
            ->willReturn('collection');

        $this->logger->expects(self::once())
            ->method('info')
            ->with('UpdateLineItems payload.', json_decode($payload, true));

        self::assertTrue($this->subject->apply($request, $configuration));

        self::assertInstanceOf(UpdateLineItemCollection::class, $request->attributes->get('collection'));

        /** @var ArrayIterator $iterator */
        $iterator = $request->attributes->get('collection')->getIterator();
        $updateLineItemDto = $iterator[0];

        self::assertInstanceOf(UpdateLineItemDto::class, $updateLineItemDto);

        self::assertSame("52a3de8dd0f270fd193f9f4bff05232c", $updateLineItemDto->getId());
        self::assertSame(
            'oat\\taoPublishing\\model\\publishing\\event\\RemoteDeliveryCreatedEvent',
            $updateLineItemDto->getName()
        );
        self::assertSame(1565602390, $updateLineItemDto->getTriggeredTime()->getTimestamp());
        self::assertSame("qti-interactions-delivery", $updateLineItemDto->getSlug());
        self::assertSame(
            "https://tao.instance/ontologies/tao.rdf#kkkkzk",
            $updateLineItemDto->getLineItemUri()
        );
    }

    public function testItThrowsExceptionCaseValidationFail(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $request = $this->createMock(
            Request::class
        );

        $this->updateLineItemValidator->expects(self::once())
            ->method('validate')
            ->with($request)
            ->willThrowException(new BadRequestHttpException());

        $this->subject->apply($request, $this->createMock(ParamConverter::class));
    }
}

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
use OAT\SimpleRoster\Request\Validator\UpdateLineItemValidator;
use OAT\SimpleRoster\WebHook\UpdateLineItemCollection;
use OAT\SimpleRoster\WebHook\UpdateLineItemDto;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UpdateLineItemWebHookParamConverterTest extends TestCase
{
    /** @var UpdateLineItemWebHookParamConverter */
    private $subject;

    /** @var MockObject|UpdateLineItemValidator */
    private $updateLineItemValidator;

    protected function setUp(): void
    {
        $this->updateLineItemValidator = $this->createMock(
            UpdateLineItemValidator::class
        );

        $this->subject = new UpdateLineItemWebHookParamConverter(
            $this->updateLineItemValidator
        );
    }

    public function testItConvertsSuccessful(): void
    {
        $request = $this->createMock(
            Request::class
        );

        $request->expects($this->once())
            ->method('getContent')
            ->willReturn(
                '{
                    "source":"https://someinstance.taocloud.org/",
                    "events":[
                        {
                            "eventId":"52a3de8dd0f270fd193f9f4bff05232c",
                            "eventName":"RemoteDeliveryPublicationFinished",
                            "triggeredTimestamp":1565602390,
                            "eventData":{
                                "alias":"qti-interactions-delivery",
                                "deliveryURI":"https://tao.instance/ontologies/tao.rdf#kkkkzk"
                            }
                        }
                    ]
                }'
            );

        $request->attributes = new ParameterBag();

        $this->updateLineItemValidator->expects($this->once())
            ->method('validate')
            ->with($request);

        $configuration = $this->createMock(
            ParamConverter::class
        );

        $configuration->expects($this->once())
            ->method('getName')
            ->willReturn('collection');

        $this->subject->apply($request, $configuration);

        $this->assertInstanceOf(UpdateLineItemCollection::class, $request->attributes->get('collection'));

        /** @var ArrayIterator $iterator */
        $iterator = $request->attributes->get('collection')->getIterator();
        $updateLineItemDto = $iterator[0];

        $this->assertInstanceOf(UpdateLineItemDto::class, $updateLineItemDto);

        $this->assertEquals("52a3de8dd0f270fd193f9f4bff05232c", $updateLineItemDto->getId());
        $this->assertEquals("RemoteDeliveryPublicationFinished", $updateLineItemDto->getName());
        $this->assertEquals("1565602390", $updateLineItemDto->getTriggeredTime()->getTimestamp());
        $this->assertEquals("qti-interactions-delivery", $updateLineItemDto->getSlug());
        $this->assertEquals(
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

        $this->updateLineItemValidator->expects($this->once())
            ->method('validate')
            ->with($request)
            ->willThrowException(new BadRequestHttpException());

        $this->subject->apply($request, $this->createMock(ParamConverter::class));
    }
}

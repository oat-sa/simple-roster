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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Responder;

use OAT\SimpleRoster\Generator\MessageIdentifierGenerator;
use OAT\SimpleRoster\Responder\LtiOutcomeResponder;
use OAT\SimpleRoster\Responder\XmlResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidFactoryInterface;
use Twig\Environment;

class LtiOutcomeResponderTest extends TestCase
{
    /** @var Environment|MockObject */
    private $twig;

    /** @var UuidFactoryInterface|MockObject */
    private $uuidFactory;

    /** @var LtiOutcomeResponder */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->twig = $this->createMock(Environment::class);
        $this->uuidFactory = $this->createMock(UuidFactoryInterface::class);

        $this->subject = new LtiOutcomeResponder($this->twig, $this->uuidFactory);
    }

    public function testCreateReplaceResultResponse(): void
    {
        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with('basic-outcome/replace-result-response.xml.twig')
            ->willReturn('templateWithValues');

        $response = $this->subject->createXmlResponse(1);

        $this->assertEquals(new XmlResponse('templateWithValues'), $response);
    }
}

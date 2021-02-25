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

namespace OAT\SimpleRoster\Tests\Unit\Lti\Responder;

use OAT\SimpleRoster\Lti\Responder\XmlResponse;
use PHPUnit\Framework\TestCase;

class XmlResponseTest extends TestCase
{
    public function testXmlResponse(): void
    {
        $request = new XmlResponse('content');

        self::assertSame('content', $request->getContent());
        self::assertSame(XmlResponse::HTTP_OK, $request->getStatusCode());
        self::assertSame('text/xml', $request->headers->get('content-type'));
    }

    public function testXmlResponseWithSpecificStatusCode(): void
    {
        $request = new XmlResponse('error', XmlResponse::HTTP_BAD_REQUEST);

        self::assertSame('error', $request->getContent());
        self::assertSame(XmlResponse::HTTP_BAD_REQUEST, $request->getStatusCode());
        self::assertSame('text/xml', $request->headers->get('content-type'));
    }

    public function testXmlResponseWithExtraHeaders(): void
    {
        $request = new XmlResponse('content', XmlResponse::HTTP_OK, ['my-header' => 'my-value']);

        self::assertSame('content', $request->getContent());
        self::assertSame(XmlResponse::HTTP_OK, $request->getStatusCode());
        self::assertSame('text/xml', $request->headers->get('content-type'));
        self::assertSame('my-value', $request->headers->get('my-header'));
    }
}

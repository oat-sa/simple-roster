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

namespace OAT\SimpleRoster\Tests\Integration\Lti\Responder;

use OAT\SimpleRoster\Lti\Responder\LtiOutcomeResponder;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

class LtiOutcomeResponderTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testCreateReplaceResultResponse(): void
    {
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        $messageIdentifier = 'e36f227c-2946-11e8-b467-0ed5f89f718b';

        $uuidFactory = $this->createMock(UuidFactoryInterface::class);
        $uuidFactory
            ->method('uuid4')
            ->willreturn($messageIdentifier);

        $subject = new LtiOutcomeResponder($twig, $uuidFactory);

        $response = $subject->createXmlResponse(1);

        self::assertSame($this->getValidReplaceResultResponseXml($messageIdentifier), $response->getContent());
    }

    private function getValidReplaceResultResponseXml(string $messageIdentifier): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
    <imsx_POXHeader>
        <imsx_POXResponseHeaderInfo>
            <imsx_version>V1.0</imsx_version>
            <imsx_messageIdentifier>$messageIdentifier</imsx_messageIdentifier>
            <imsx_statusInfo>
                <imsx_codeMajor>success</imsx_codeMajor>
                <imsx_severity>status</imsx_severity>
                <imsx_description>Assignment with Id 1 was updated</imsx_description>
                <imsx_messageRefIdentifier>1</imsx_messageRefIdentifier>
                <imsx_operationRefIdentifier>replaceResult</imsx_operationRefIdentifier>
            </imsx_statusInfo>
        </imsx_POXResponseHeaderInfo>
    </imsx_POXHeader>
    <imsx_POXBody>
        <replaceResultResponse />
    </imsx_POXBody>
</imsx_POXEnvelopeResponse>

XML;
    }
}

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
use OAT\SimpleRoster\Tests\Traits\XmlTestingTrait;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\UuidV6;
use Twig\Environment;

class LtiOutcomeResponderTest extends KernelTestCase
{
    use XmlTestingTrait;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testCreateReplaceResultResponse(): void
    {
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        $messageIdentifier = UuidV4::fromString('e36f227c-2946-11e8-b467-0ed5f89f718b');

        $uuidFactory = $this->createMock(UuidFactoryInterface::class);
        $uuidFactory
            ->method('uuid4')
            ->willreturn($messageIdentifier);

        $subject = new LtiOutcomeResponder($twig, $uuidFactory);

        $assignmentId = new UuidV6('00000001-0000-6000-0000-000000000000');
        $response = $subject->createXmlResponse($assignmentId);

        self::assertSame(
            $this->getValidReplaceResultResponseXml($messageIdentifier, $assignmentId),
            $response->getContent()
        );
    }
}

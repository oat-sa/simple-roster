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
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
        $twig = static::$container->get(Environment::class);

        $messageIdentifier = 'e36f227c-2946-11e8-b467-0ed5f89f718b';

        $uuidFactory = $this->createMock(UuidFactoryInterface::class);
        $uuidFactory
            ->method('uuid4')
            ->willreturn($messageIdentifier);

        $subject = new LtiOutcomeResponder($twig, $uuidFactory);

        $response = $subject->createXmlResponse(1);

        self::assertEquals($this->getValidReplaceResultResponseXml($messageIdentifier), $response->getContent());
    }
}

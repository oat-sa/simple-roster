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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Lti\Extractor;

use OAT\SimpleRoster\Exception\InvalidLtiReplaceResultBodyException;
use OAT\SimpleRoster\Lti\Extractor\ReplaceResultSourceIdExtractor;
use OAT\SimpleRoster\Tests\Traits\XmlTestingTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV6;

class ReplaceResultSourceIdExtractorTest extends TestCase
{
    use XmlTestingTrait;

    private const LTI_OUTCOME_XML_NAMESPACE = 'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0';
    private const OLD_LTI_OUTCOME_XML_NAMESPACE = 'http://www.imsglobal.org/lis/oms1p0/pox';

    public function testItCanExtractSourceId(): void
    {
        $subject = new ReplaceResultSourceIdExtractor(self::LTI_OUTCOME_XML_NAMESPACE);
        $assignmentId = new UuidV6('00000001-0000-6000-0000-000000000000');

        self::assertEquals($assignmentId, $subject->extractSourceId($this->getXmlRequestTemplate($assignmentId)));
    }

    public function testItThrowsInvalidLtiReplaceResultBodyExceptionOnInvalidXmlContent(): void
    {
        $this->expectException(InvalidLtiReplaceResultBodyException::class);
        $this->expectExceptionMessage('Invalid XML received.');

        $subject = new ReplaceResultSourceIdExtractor(self::LTI_OUTCOME_XML_NAMESPACE);

        $subject->extractSourceId('invalid');
    }

    public function testItThrowsInvalidLtiReplaceResultBodyExceptionOnInvalidXmlNamespace(): void
    {
        $this->expectException(InvalidLtiReplaceResultBodyException::class);
        $this->expectExceptionMessage('Source id node cannot be extracted by Xpath.');

        $subject = new ReplaceResultSourceIdExtractor(self::LTI_OUTCOME_XML_NAMESPACE);
        $assignmentId = new UuidV6('00000001-0000-6000-0000-000000000000');
        $invalidNamespace = 'http://www.imsglobal.org/lis/oms1p0/pox';

        $subject->extractSourceId($this->getXmlRequestTemplate($assignmentId, $invalidNamespace));
    }

    public function testItThrowsInvalidLtiReplaceResultBodyExceptionOnMissingId(): void
    {
        $this->expectException(InvalidLtiReplaceResultBodyException::class);
        $this->expectExceptionMessage('Source id node cannot be extracted by Xpath.');

        $subject = new ReplaceResultSourceIdExtractor(self::LTI_OUTCOME_XML_NAMESPACE);

        $subject->extractSourceId($this->getXmlRequestTemplate(null));
    }

    public function testItThrowsInvalidLtiReplaceResultBodyExceptionOnInvalidSourceId(): void
    {
        $this->expectException(InvalidLtiReplaceResultBodyException::class);
        $this->expectExceptionMessage("Extracted source id 'notValidUuid' is not a valid UUID.");

        $subject = new ReplaceResultSourceIdExtractor(self::LTI_OUTCOME_XML_NAMESPACE);

        $uuidMock = $this->createPartialMock(UuidV6::class, ['__toString']);
        $uuidMock
            ->method('__toString')
            ->willReturn('notValidUuid');

        $subject->extractSourceId($this->getXmlRequestTemplate($uuidMock));
    }
}

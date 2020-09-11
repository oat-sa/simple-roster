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

namespace App\Tests\Unit\Lti\Extractor;

use App\Exception\InvalidLtiReplaceResultBodyException;
use App\Lti\Extractor\ReplaceResultSourceIdExtractor;
use PHPUnit\Framework\TestCase;

class ReplaceResultSourceIdExtractorTest extends TestCase
{
    private const LTI_OUTCOME_XML_NAMESPACE = 'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0';
    private const OLD_LTI_OUTCOME_XML_NAMESPACE = 'http://www.imsglobal.org/lis/oms1p0/pox';

    public function testItCanExtractSourceId(): void
    {
        $subject = new ReplaceResultSourceIdExtractor(self::LTI_OUTCOME_XML_NAMESPACE);

        /** @var string $xmlContent */
        $xmlContent = file_get_contents(
            dirname(__DIR__, 3) . DIRECTORY_SEPARATOR
            . 'Resources/LtiOutcome/valid_replace_result_body.xml'
        );

        $this->assertEquals(1, $subject->extractSourceId($xmlContent));
    }

    public function testItThrowsInvalidLtiReplaceResultBodyExceptionOnInvalidXmlContent(): void
    {
        $this->expectException(InvalidLtiReplaceResultBodyException::class);

        $subject = new ReplaceResultSourceIdExtractor(self::LTI_OUTCOME_XML_NAMESPACE);

        $subject->extractSourceId('invalid');
    }

    public function testItThrowsInvalidLtiReplaceResultBodyExceptionOnInvalidXmlNamespace(): void
    {
        $this->expectException(InvalidLtiReplaceResultBodyException::class);

        $subject = new ReplaceResultSourceIdExtractor(self::LTI_OUTCOME_XML_NAMESPACE);

        /** @var string $xmlContent */
        $xmlContent = file_get_contents(
            dirname(__DIR__, 3) . DIRECTORY_SEPARATOR
            . 'Resources/LtiOutcome/invalid_replace_result_body_wrong_namespace.xml'
        );

        $subject->extractSourceId($xmlContent);
    }

    public function testItThrowsInvalidLtiReplaceResultBodyExceptionOnMissingId(): void
    {
        $this->expectException(InvalidLtiReplaceResultBodyException::class);

        $subject = new ReplaceResultSourceIdExtractor(self::LTI_OUTCOME_XML_NAMESPACE);

        /** @var string $xmlContent */
        $xmlContent = file_get_contents(
            dirname(__DIR__, 3) . DIRECTORY_SEPARATOR
            . 'Resources/LtiOutcome/invalid_replace_result_body_missing_id.xml'
        );

        $subject->extractSourceId($xmlContent);
    }
}

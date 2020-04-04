<?php declare(strict_types=1);
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

namespace App\Tests\Unit\Lti\Extractor;

use App\Exception\InvalidLtiReplaceResultBodyException;
use App\Lti\Extractor\ReplaceResultSourceIdExtractor;
use PHPUnit\Framework\TestCase;

class ReplaceResultSourceIdExtractorTest extends TestCase
{
    public function testItCanExtractSourceId(): void
    {
        $subject = new ReplaceResultSourceIdExtractor();

        /** @var string $xmlContent */
        $xmlContent = file_get_contents(__DIR__ . '/../../../Resources/LtiOutcome/valid_replace_result_body.xml');

        $this->assertEquals(1, $subject->extractSourceId($xmlContent));
    }

    public function testItThrowsInvalidLtiReplaceResultBodyExceptionOnInvalidXmlContent(): void
    {
        $this->expectException(InvalidLtiReplaceResultBodyException::class);

        $subject = new ReplaceResultSourceIdExtractor();

        $subject->extractSourceId('invalid');
    }

    public function testItThrowsInvalidLtiReplaceResultBodyExceptionOnMissingId(): void
    {
        $this->expectException(InvalidLtiReplaceResultBodyException::class);

        $subject = new ReplaceResultSourceIdExtractor();

        /** @var string $xmlContent */
        $xmlContent = file_get_contents(
            __DIR__ . '/../../../Resources/LtiOutcome/invalid_replace_result_body_missing_id.xml'
        );

        $subject->extractSourceId($xmlContent);
    }
}

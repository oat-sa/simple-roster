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

namespace OAT\SimpleRoster\Tests\Traits;

use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\UuidV6;

trait XmlTestingTrait
{
    public function getValidReplaceResultResponseXml(UuidInterface $messageIdentifier, UuidV6 $assignmentId): string
    {
        $messageIdentifier = (string)$messageIdentifier;
        $assignmentId = (string)$assignmentId;

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
                <imsx_description>Assignment with Id $assignmentId was updated</imsx_description>
                <imsx_messageRefIdentifier>$assignmentId</imsx_messageRefIdentifier>
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

    public function getXmlRequestTemplate(
        ?UuidV6 $assignmentId,
        string $xmlNamespace = 'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0'
    ): string {
        $assignmentIdBlock = '';

        $assignmentId = (string)$assignmentId;
        if (!empty($assignmentId)) {
            $assignmentIdBlock = <<<sourceGUID
<sourcedGUID>
    <sourcedId>$assignmentId</sourcedId>
</sourcedGUID>
sourceGUID;
        }

        return <<<XML
<?xml version = "1.0" encoding = "UTF-8"?>
<imsx_POXEnvelopeRequest xmlns = "$xmlNamespace">
    <imsx_POXHeader>
        <imsx_POXRequestHeaderInfo>
            <imsx_version>V1.0</imsx_version>
            <imsx_messageIdentifier>5c592d046d5c6</imsx_messageIdentifier>
        </imsx_POXRequestHeaderInfo>
    </imsx_POXHeader>
    <imsx_POXBody>
        <replaceResultRequest>
            <resultRecord>
                $assignmentIdBlock
                <result>
                    <resultScore>
                        <language>en-us</language>
                        <textString>0.11111111111111</textString>
                    </resultScore>
                </result>
            </resultRecord>
        </replaceResultRequest>
    </imsx_POXBody>
</imsx_POXEnvelopeRequest>
XML;
    }
}

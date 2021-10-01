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

trait XmlTestingTrait
{
    public function getValidReplaceResultRequestXml(): string
    {
        return $this->getXmlRequestTemplate(
            1,
            'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0',
            'expectedMessageIdentifier'
        );
    }

    public function getValidReplaceResultRequestXmlWithWrongAssignment(): string
    {
        return $this->getXmlRequestTemplate(
            100000000,
            'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0',
            'expectedMessageIdentifier'
        );
    }

    public function getValidReplaceResultRequestXmlWithWrongNamespace(): string
    {
        return $this->getXmlRequestTemplate(
            1,
            'ttp://www.imsglobal.org/lis/oms1p0/pox',
            'expectedMessageIdentifier'
        );
    }

    public function getValidReplaceResultRequestXmlWithoutId(): string
    {
        return $this->getXmlRequestTemplate(
            null,
            'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0',
            'expectedMessageIdentifier'
        );
    }

    public function getValidReplaceResultResponseXml(string $messageIdentifier): string
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
                <imsx_description></imsx_description>
                <imsx_messageRefIdentifier>expectedMessageIdentifier</imsx_messageRefIdentifier>
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

    private function getXmlRequestTemplate(?int $assignmentId, string $namespace, string $messageIdentifier): string
    {
        $assignmentIdBlock = '';

        if ($assignmentId !== null) {
            $assignmentIdBlock = <<<sourceGUID
<sourcedGUID>
    <sourcedId>$assignmentId</sourcedId>
</sourcedGUID>
sourceGUID;
        }

        return <<<XML
<?xml version = "1.0" encoding = "UTF-8"?>
<imsx_POXEnvelopeRequest xmlns = "$namespace">
    <imsx_POXHeader>
        <imsx_POXRequestHeaderInfo>
            <imsx_version>V1.0</imsx_version>
            <imsx_messageIdentifier>$messageIdentifier</imsx_messageIdentifier>
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

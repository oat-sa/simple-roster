<?php

declare(strict_types=1);

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

namespace App\Lti\Extractor;

use App\Exception\InvalidLtiReplaceResultBodyException;
use SimpleXMLElement;
use Throwable;

class ReplaceResultSourceIdExtractor
{
    /**
     * @throws InvalidLtiReplaceResultBodyException
     */
    public function extractSourceId(string $xmlContent): int
    {
        try {
            $xml = new SimpleXMLElement($xmlContent);
        } catch (Throwable $exception) {
            throw new InvalidLtiReplaceResultBodyException();
        }

        $xml->registerXPathNamespace('x', 'http://www.imsglobal.org/lis/oms1p0/pox');

        $sourceIdNodes = $xml->xpath(
            '/x:imsx_POXEnvelopeRequest/x:imsx_POXBody/x:replaceResultRequest/x:resultRecord/x:sourcedGUID/x:sourcedId/text()'
        );

        if (false === $sourceIdNodes || count($sourceIdNodes) !== 1 || !$sourceIdNodes[0] instanceof SimpleXMLElement) {
            throw new InvalidLtiReplaceResultBodyException();
        }

        return (int)$sourceIdNodes[0];
    }
}

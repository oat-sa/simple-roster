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

namespace OAT\SimpleRoster\Lti\Extractor;

use OAT\SimpleRoster\Exception\InvalidLtiReplaceResultBodyException;
use SimpleXMLElement;
use Symfony\Component\Uid\UuidV6;
use Throwable;

class ReplaceResultSourceIdExtractor
{
    /** @var string */
    private $xmlNamespace;

    public function __construct(string $xmlNamespace)
    {
        $this->xmlNamespace = $xmlNamespace;
    }

    /**
     * @throws InvalidLtiReplaceResultBodyException
     */
    public function extractSourceId(string $xmlContent): UuidV6
    {
        try {
            $xml = new SimpleXMLElement($xmlContent);
        } catch (Throwable $exception) {
            throw new InvalidLtiReplaceResultBodyException('Invalid XML received.', 0, $exception);
        }

        $xml->registerXPathNamespace('x', $this->xmlNamespace);

        $sourceIdNodes = $xml->xpath(
            '/x:imsx_POXEnvelopeRequest/x:imsx_POXBody/x:replaceResultRequest/' .
            'x:resultRecord/x:sourcedGUID/x:sourcedId/text()'
        );

        if (false === $sourceIdNodes || count($sourceIdNodes) !== 1) {
            throw new InvalidLtiReplaceResultBodyException('Source id node cannot be extracted by Xpath.');
        }

        $sourceId = (string)$sourceIdNodes[0];

        try {
            return new UuidV6($sourceId);
        } catch (Throwable $exception) {
            throw new InvalidLtiReplaceResultBodyException(
                sprintf("Extracted source id '%s' is not a valid UUID.", $sourceId),
                0,
                $exception
            );
        }
    }
}

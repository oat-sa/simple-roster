<?php declare(strict_types=1);

namespace App\Service\LTI;

use App\Exception\InvalidLtiReplaceResultBodyException;
use SimpleXMLElement;

class ReplaceResultSourceIdExtractor
{
    public function extractSourceId(string $xmlContent): int
    {
        $xml = new SimpleXMLElement($xmlContent);

        $xml->registerXPathNamespace('x', 'http://www.imsglobal.org/lis/oms1p0/pox');

        $sourceIdNodes = $xml->xpath(
            '/x:imsx_POXEnvelopeRequest/x:imsx_POXBody/x:replaceResultRequest/x:resultRecord/x:sourcedGUID/x:sourcedId/text()'
        );

        if (count($sourceIdNodes) !== 1 || !$sourceIdNodes[0] instanceof SimpleXMLElement) {
            throw new InvalidLtiReplaceResultBodyException();
        }

        return (int)$sourceIdNodes[0];
    }
}

<?php

namespace App\Service\LTI;

use SimpleXMLElement;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
            throw new BadRequestHttpException();
        }

        return (int)$sourceIdNodes[0];
    }
}

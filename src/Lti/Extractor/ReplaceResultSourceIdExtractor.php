<?php declare(strict_types=1);

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

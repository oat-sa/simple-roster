<?php declare(strict_types=1);

namespace App\Tests\Unit\Lti\Extractor;

use App\Exception\InvalidLtiReplaceResultBodyException;
use App\Lti\Extractor\ReplaceResultSourceIdExtractor;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

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

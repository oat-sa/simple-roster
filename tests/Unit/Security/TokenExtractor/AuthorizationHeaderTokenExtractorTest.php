<?php declare(strict_types=1);

namespace App\Tests\Unit\Security\TokenExtractor;

use App\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class AuthorizationHeaderTokenExtractorTest extends TestCase
{
    /** @var AuthorizationHeaderTokenExtractor */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new AuthorizationHeaderTokenExtractor();
    }

    public function testExtractWithMissingAuthorizationHeader(): void
    {
        $request = new Request();

        $this->assertNull($this->subject->extract($request));
    }

    public function testExtractWithInvalidAuthorizationHeader(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => 'invalid']);

        $this->assertNull($this->subject->extract($request));
    }

    public function testExtractWithValidAuthorizationHeader(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer 12345']);

        $this->assertEquals('12345', $this->subject->extract($request));
    }
}

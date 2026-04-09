<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Integration\Security\Authenticator;

use OAT\SimpleRoster\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class AuthorizationHeaderTokenExtractorTest extends TestCase
{
    private AuthorizationHeaderTokenExtractor $subject;

    protected function setUp(): void
    {
        $this->subject = new AuthorizationHeaderTokenExtractor();
    }

    #[DataProvider('provideHeaders')]
    public function testExtract(array $headers, string $expected): void
    {
        $request = new Request([], [], [], [], [], $headers);

        self::assertSame($expected, $this->subject->extract($request));
    }

    public static function provideHeaders(): array
    {
        return [
            'valid bearer' => [
                ['HTTP_AUTHORIZATION' => 'Bearer myToken123'],
                'myToken123'
            ],
            'lowercase bearer' => [
                ['HTTP_AUTHORIZATION' => 'bearer myToken123'],
                'myToken123'
            ],
            'missing header' => [
                [],
                ''
            ],
            'invalid prefix' => [
                ['HTTP_AUTHORIZATION' => 'Basic credentials'],
                ''
            ],
            'too many parts' => [
                ['HTTP_AUTHORIZATION' => 'Bearer token extra'],
                ''
            ],
            'missing token' => [
                ['HTTP_AUTHORIZATION' => 'Bearer'],
                ''
            ],
        ];
    }
}

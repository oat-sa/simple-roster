<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering;

use OAT\SimpleRoster\Service\Rostering\PrincipalPortalStatusHttpClient;
use OAT\SimpleRoster\Service\Rostering\Exception\RosteringStatusException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class PrincipalPortalStatusHttpClientTest extends TestCase
{
    public function testItReadsSuccessfulResponse(): void
    {
        $appApiKey = $this->appApiKey();
        $baseUrl = $this->principalPortalBaseUrl();

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use ($appApiKey, $baseUrl): MockResponse {
            self::assertSame('GET', $method);
            self::assertSame(sprintf('%s/api/status/ref-1', rtrim($baseUrl, '/')), $url);

            $authorizationHeader = null;
            if (isset($options['headers']['Authorization'])) {
                $authorizationHeader = $options['headers']['Authorization'];
            } elseif (isset($options['headers']['authorization'])) {
                $authorizationHeader = $options['headers']['authorization'];
            } elseif (isset($options['normalized_headers']['authorization'][0])) {
                $authorizationHeader = preg_replace(
                    '/^authorization:\s*/i',
                    '',
                    (string) $options['normalized_headers']['authorization'][0]
                );
            }

            self::assertSame(sprintf('Bearer %s', $appApiKey), $authorizationHeader);

            return new MockResponse(
                '{"result":{"referenceId":"ref-1","status":"processed","fileLine":12,"messages":[],"resultFileUrl":"http://file"}}',
                ['http_code' => 200]
            );
        });

        $subject = new PrincipalPortalStatusHttpClient(
            $httpClient,
            $appApiKey,
            $baseUrl
        );

        $status = $subject->fetchStatus('ref-1');

        self::assertSame('ref-1', $status->getReferenceId());
        self::assertSame('processed', $status->getStatus());
        self::assertSame(12, $status->getFileLine());
    }

    public function testItThrowsExceptionWhenResponseIsNotSuccessful(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('{"error":{"message":"not found"}}', ['http_code' => 404]),
        ]);

        $subject = new PrincipalPortalStatusHttpClient(
            $httpClient,
            $this->appApiKey(),
            $this->principalPortalBaseUrl()
        );

        $this->expectException(RosteringStatusException::class);
        $subject->fetchStatus('ref-2');
    }

    public function testItThrowsExceptionOnInvalidPayload(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('{"result":"invalid"}', ['http_code' => 200]),
        ]);

        $subject = new PrincipalPortalStatusHttpClient(
            $httpClient,
            $this->appApiKey(),
            $this->principalPortalBaseUrl()
        );

        $this->expectException(RosteringStatusException::class);
        $subject->fetchStatus('ref-3');
    }

    private function appApiKey(): string
    {
        $apiKey = $_ENV['APP_API_KEY'] ?? '';
        self::assertIsString($apiKey);
        self::assertNotSame('', $apiKey);

        return $apiKey;
    }

    private function principalPortalBaseUrl(): string
    {
        $baseUrl = $_ENV['ROSTERING_PRINCIPALS_PORTAL_BASE_URL'] ?? '';
        self::assertIsString($baseUrl);
        self::assertNotSame('', $baseUrl);

        return $baseUrl;
    }
}

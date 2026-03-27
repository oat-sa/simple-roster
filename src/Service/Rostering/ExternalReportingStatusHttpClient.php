<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

use OAT\SimpleRoster\Service\Rostering\Dto\RosteringImportStatus;
use OAT\SimpleRoster\Service\Rostering\Exception\RosteringStatusException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class ExternalReportingStatusHttpClient implements ExternalReportingStatusClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $appApiKey,
        private readonly string $externalReportingSystemBaseUrl
    ) {
    }

    public function fetchStatus(string $referenceId): RosteringImportStatus
    {
        if ($this->externalReportingSystemBaseUrl === '') {
            throw new RosteringStatusException('External reporting system URL is not configured.');
        }

        $url = sprintf('%s/api/status/%s', rtrim($this->externalReportingSystemBaseUrl, '/'), rawurlencode($referenceId));

        try {
            $response = $this->httpClient->request(
                'GET',
                $url,
                [
                    'headers' => [
                        'Authorization' => sprintf('Bearer %s', $this->appApiKey),
                        'Accept' => 'application/json',
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new RosteringStatusException(
                    sprintf('Unable to fetch external reporting system status (HTTP %d).', $statusCode)
                );
            }

            $decodedBody = json_decode($response->getContent(false), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decodedBody) || !isset($decodedBody['result']) || !is_array($decodedBody['result'])) {
                throw new RosteringStatusException('Invalid external reporting system status payload.');
            }

            return RosteringImportStatus::fromApiResult($decodedBody['result'], $referenceId);
        } catch (Throwable $exception) {
            if ($exception instanceof RosteringStatusException) {
                throw $exception;
            }

            throw new RosteringStatusException(
                sprintf('Unable to fetch external reporting system status for referenceId "%s".', $referenceId),
                0,
                $exception
            );
        }
    }
}

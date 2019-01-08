<?php

namespace App\Ingesting\Source;

use App\Ingesting\Exception\InputOptionException;

class SourceFactory
{
    public function getSupportedAccessParameters(): array
    {
        return ['filename', 'delimiter', 's3_bucket', 's3_object', 's3_access_key', 's3_region', 's3_access_key', 's3_secret', 's3_client_factory'];
    }

    /**
     * @param string $parameterName
     * @param array $accessParameters
     * @throws InputOptionException
     */
    protected function assertParameterProvided(string $parameterName, array $accessParameters): void
    {
        if (!array_key_exists($parameterName, $accessParameters) || $accessParameters[$parameterName] === null) {
            throw new InputOptionException(sprintf('Option "%s" is not provided', $parameterName));
        }
    }

    /**
     * @param array $accessParameters
     * @return AbstractSource
     * @throws InputOptionException
     */
    public function createSource(array $accessParameters): AbstractSource
    {
        $source = null;

        if ($accessParameters['filename'] !== null && $accessParameters['filename'] !== '') {
            $source = new LocalFileAbstractSource();
        } else {
            $useS3 = false;
            foreach ($accessParameters as $parameterName => $parameter) {
                if (substr($parameterName, 0, 3) === 's3_') {
                    $useS3 = true;
                    break;
                }
            }

            if ($useS3) {
                foreach ($accessParameters as $parameterName => $parameter) {
                    if (substr($parameterName, 0, 3) === 's3_') {
                        $this->assertParameterProvided($parameterName, $accessParameters);
                    }
                }

                $source = new S3AbstractSource();
            }
        }

        if ($source) {
            foreach ($accessParameters as $parameterName => $parameterValue) {
                $source->setAccessParameter($parameterName, $parameterValue);
            }
            return $source;
        } else {
            throw new InputOptionException('Neither local filename nor AWS object provided');
        }
    }
}
<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Action\Documentation;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetOpenApiSpecAction
{
    public function __construct(
        private readonly string $projectDir
    ) {
    }

    public function __invoke(): Response
    {
        $specPath = sprintf('%s/openapi/api_v1.yml', rtrim($this->projectDir, '/'));

        if (!is_file($specPath) || !is_readable($specPath)) {
            throw new NotFoundHttpException('OpenAPI specification file was not found.');
        }

        $content = file_get_contents($specPath);
        if ($content === false) {
            throw new NotFoundHttpException('Unable to read OpenAPI specification file.');
        }

        return new Response(
            $content,
            Response::HTTP_OK,
            ['Content-Type' => 'application/yaml; charset=utf-8']
        );
    }
}


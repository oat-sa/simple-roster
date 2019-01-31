<?php

namespace App\ApiProblem;

interface ApiProblemInterface
{
    public function toArray(): array ;

    public function getStatusCode(): int ;

    public function getTitle(): string ;

    public function setDetail(string $detail): ApiProblemInterface ;
}
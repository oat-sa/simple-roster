<?php

namespace App\Tests;

trait GeneratorHelperTrait
{
    protected function arrayAsGenerator(array $array): \Generator
    {
        foreach ($array as $item) {
            yield $item;
        }
    }
}
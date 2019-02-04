<?php

namespace App\ODM\Id;

interface IdGeneratorInterface
{
    public function generate(?string $prefix = null): string ;
}
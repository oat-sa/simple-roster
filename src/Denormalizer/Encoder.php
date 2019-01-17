<?php

namespace App\Denormalizer;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

class Encoder implements EncoderInterface, DecoderInterface
{
    public function encode($data, $format, array $context = [])
    {
        return $data;
    }

    public function supportsEncoding($format)
    {
        return 'plain' === $format;
    }

    public function decode($data, $format, array $context = [])
    {
        return $data;
    }

    public function supportsDecoding($format)
    {
        return 'plain' === $format;
    }
}
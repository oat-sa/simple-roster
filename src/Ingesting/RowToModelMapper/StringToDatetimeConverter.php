<?php declare(strict_types=1);

namespace App\Ingesting\RowToModelMapper;

class StringToDatetimeConverter
{
    public function convert(string $dateTimeString): ?\DateTimeImmutable
    {
        $result = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTimeString);
        if ($result === false) {
            return null;
        }
        return $result;
    }
}
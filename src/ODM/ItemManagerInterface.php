<?php

namespace App\ODM;

interface ItemManagerInterface
{
    public function load(string $itemClass, string $key): ?object ;

    public function save(object $item): void ;

    public function delete(string $itemClass, string $key): void ;

    public function isExist(object $item): bool ;

    public function isExistById(string $itemClass, string $key): bool;
}
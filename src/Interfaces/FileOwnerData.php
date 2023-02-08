<?php

namespace MGGFLOW\Storage\Interfaces;

interface FileOwnerData
{
    public function findOwnerFile(int $ownerId, int $fileId): ?object;

    public function create(int $ownerId, int $fileId, string $filename, string $ext): ?int;
}
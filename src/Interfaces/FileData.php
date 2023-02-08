<?php

namespace MGGFLOW\Storage\Interfaces;

interface FileData
{
    public function findByHash(string $hash): ?object;

    public function create(string $hash, int $size, string $mimeType, string $dir): ?int;
}
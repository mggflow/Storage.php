<?php

namespace MGGFLOW\Storage\Interfaces;

interface FileReplicaData
{
    public function findForFile(int $fileId, int $count): ?array;
    public function create(int $fileId, int $storageId, int $locationId, string $contextData): ?int;
}
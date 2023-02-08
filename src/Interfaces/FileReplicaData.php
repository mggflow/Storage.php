<?php

namespace MGGFLOW\Storage\Interfaces;

interface FileReplicaData
{
    public function findForFile(int $fileId, int $count): ?array;
}
<?php

namespace MGGFLOW\Storage\Interfaces;

interface FileReplicatorFactory
{
    public function makeReplicator(string $filepath, object $fileNote, ?array $currentReplicas): ?FileReplicator;
}
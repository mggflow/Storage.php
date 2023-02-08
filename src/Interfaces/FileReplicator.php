<?php

namespace MGGFLOW\Storage\Interfaces;

interface FileReplicator
{
    public function replicate(string $filepath, object $fileNote, ?array $currentReplicas):  ?array;
}
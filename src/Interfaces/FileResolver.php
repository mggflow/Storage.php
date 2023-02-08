<?php

namespace MGGFLOW\Storage\Interfaces;

interface FileResolver
{
    public function resolve(object $fileOwner, object $fileNote, ?array $fileReplicasNotes): array;
}
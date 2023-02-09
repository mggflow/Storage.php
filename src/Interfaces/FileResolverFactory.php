<?php

namespace MGGFLOW\Storage\Interfaces;

interface FileResolverFactory
{
    public function make(object $fileOwner, object $fileNote, ?array $fileReplicasNotes): ?FileResolver;
}
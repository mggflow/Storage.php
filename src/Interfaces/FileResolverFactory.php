<?php

namespace MGGFLOW\Storage\Interfaces;

interface FileResolverFactory
{
    public function makeForLocal(object $fileOwner, object $fileNote, ?array $fileReplicasNotes): ?FileResolver;
    public function makeForReplica(object $fileOwner, object $fileNote, object $replicaNote): ?FileResolver;
}
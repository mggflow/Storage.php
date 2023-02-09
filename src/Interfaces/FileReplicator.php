<?php

namespace MGGFLOW\Storage\Interfaces;

interface FileReplicator
{
    public function replicate(): ?array;
}
<?php

namespace MGGFLOW\Storage\Interfaces;

interface FileHasher
{
    public function hash(string $path): string;
}
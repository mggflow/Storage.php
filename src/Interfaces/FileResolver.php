<?php

namespace MGGFLOW\Storage\Interfaces;

interface FileResolver
{
    public function resolve(): array;
}
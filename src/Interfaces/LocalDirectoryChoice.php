<?php

namespace MGGFLOW\Storage\Interfaces;

interface LocalDirectoryChoice
{
    public function chooseForStore(int $fileSize, string $mimeType): string;
}
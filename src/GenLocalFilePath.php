<?php

namespace MGGFLOW\Storage;

class GenLocalFilePath
{
    public static function gen(string $storageDir, string $fileHash): string {
        return join('/', [rtrim($storageDir, '\\/'), $fileHash]);
    }
}
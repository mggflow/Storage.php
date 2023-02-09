<?php

namespace MGGFLOW\Storage;

use MGGFLOW\Storage\Entities\File;
use MGGFLOW\Storage\Exceptions\EmptyFile;
use MGGFLOW\Storage\Exceptions\FailedToIdentifyFile;
use MGGFLOW\Storage\Exceptions\FailedToIdentifyOwnership;
use MGGFLOW\Storage\Exceptions\FileTooBig;
use MGGFLOW\Storage\Exceptions\LocalCopyFailed;
use MGGFLOW\Storage\Exceptions\NoFile;
use MGGFLOW\Storage\Interfaces\FileHasher;
use MGGFLOW\Storage\Interfaces\FileData;
use MGGFLOW\Storage\Interfaces\FileMover;
use MGGFLOW\Storage\Interfaces\FileOwnerData;

class StoreFile
{
    protected FileHasher $fileHasher;
    protected FileMover $fileMover;
    protected FileData $fileData;
    protected FileOwnerData $fileOwnerData;
    protected int $userId;
    protected string $filepath;
    protected string $storageDir;

    protected string $mimeType;
    protected string $fileSize;
    protected string $filename;
    protected string $extension;

    protected string $fileHash;
    protected string $localFilepath;
    protected ?object $file;
    protected ?int $fileId;

    protected ?object $fileOwner;
    protected ?int $ownershipId;

    public function __construct(FileHasher $fileHasher, FileMover $fileMover,
                                FileData   $fileData, FileOwnerData $fileOwnerData,
                                int        $userId)
    {
        $this->fileHasher = $fileHasher;
        $this->fileMover = $fileMover;
        $this->fileData = $fileData;
        $this->fileOwnerData = $fileOwnerData;

        $this->userId = $userId;
    }

    public function store(string $filePath, string $localStorageDir): array
    {
        $this->setFields($filePath, $localStorageDir);
        $this->validatePath();
        $this->parseFileInfo();
        $this->validateFileInfo();
        $this->genFileHash();
        $this->genLocalFilepath();
        $this->provideLocalCopy();
        $this->findFile();
        $this->provideFileId();
        $this->checkFileId();
        $this->findFileOwner();
        $this->provideOwnership();
        $this->checkOwnership();

        return $this->createResult();
    }

    protected function setFields(string $filePath, string $storageDir)
    {
        $this->filepath = $filePath;
        $this->storageDir = $storageDir;
    }

    protected function validatePath()
    {
        if (!is_file($this->filepath)) throw new NoFile();
    }

    protected function parseFileInfo()
    {
        $this->mimeType = mime_content_type($this->filepath);
        $this->fileSize = filesize($this->filepath);

        $pathInfo = pathinfo($this->filepath, PATHINFO_EXTENSION + PATHINFO_FILENAME);
        $this->filename = $pathInfo['filename'];
        $this->extension = $pathInfo['extension'];
    }

    protected function validateFileInfo()
    {
        if ($this->fileSize == 0) throw new EmptyFile();
        if ($this->fileSize > File::MAX_FILE_SIZE) throw new FileTooBig();
    }

    protected function genFileHash()
    {
        $this->fileHash = $this->fileHasher->hash($this->filepath);
    }

    protected function genLocalFilepath()
    {
        $this->localFilepath = GenLocalFilePath::gen($this->storageDir, $this->fileHash);
    }

    protected function provideLocalCopy()
    {
        if (is_file($this->localFilepath)) return;

        if (!$this->fileMover->move($this->filepath, $this->localFilepath)) throw new LocalCopyFailed();
    }

    protected function findFile()
    {
        $this->file = $this->fileData->findByHash($this->fileHash);
    }

    protected function provideFileId()
    {
        if (!empty($this->file)) {
            $this->fileId = $this->file->id;
        }

        $this->fileId = $this->fileData->create($this->fileHash, $this->fileSize, $this->mimeType, $this->storageDir);
    }

    protected function checkFileId()
    {
        if (empty($this->fileId)) throw new FailedToIdentifyFile();
    }

    protected function findFileOwner()
    {
        $this->fileOwner = $this->fileOwnerData->findOwnerFile($this->userId, $this->fileId);
    }

    protected function provideOwnership()
    {
        if (empty($this->fileOwner)) {
            $this->ownershipId = $this->fileOwner->id;
            return;
        }

        $this->ownershipId = $this->fileOwnerData->create($this->userId, $this->fileId, $this->filename, $this->extension);
    }

    protected function checkOwnership()
    {
        if (empty($this->ownershipId)) throw new FailedToIdentifyOwnership();
    }

    protected function createResult(): array
    {
        return [
            'fileId' => $this->fileId,
            'ownershipId' => $this->ownershipId
        ];
    }
}
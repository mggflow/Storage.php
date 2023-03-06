<?php

namespace MGGFLOW\Storage;

use MGGFLOW\ExceptionManager\ManageException;
use MGGFLOW\Storage\Entities\File;
use MGGFLOW\Storage\Interfaces\FileHasher;
use MGGFLOW\Storage\Interfaces\FileData;
use MGGFLOW\Storage\Interfaces\FileMover;
use MGGFLOW\Storage\Interfaces\FileOwnerData;
use MGGFLOW\Storage\Interfaces\LocalDirectoryChoice;

class StoreFile
{
    protected FileHasher $fileHasher;
    protected FileMover $fileMover;
    protected FileData $fileData;
    protected FileOwnerData $fileOwnerData;
    protected LocalDirectoryChoice $localDirectoryChoice;

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

    public function __construct(FileHasher           $fileHasher, FileMover $fileMover,
                                FileData             $fileData, FileOwnerData $fileOwnerData,
                                LocalDirectoryChoice $localDirectoryChoice,
                                int                  $userId)
    {
        $this->fileHasher = $fileHasher;
        $this->fileMover = $fileMover;
        $this->fileData = $fileData;
        $this->fileOwnerData = $fileOwnerData;
        $this->localDirectoryChoice = $localDirectoryChoice;

        $this->userId = $userId;
    }

    public function store(string $filePath, string $filename, string $extension): array
    {
        $this->setFields($filePath, $filename, $extension);
        $this->validatePath();
        $this->parseFileInfo();
        $this->validateFileInfo();
        $this->genFileHash();
        $this->chooseLocalDir();
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

    protected function setFields(string $filePath, string $filename, string $ext)
    {
        $this->filepath = $filePath;
        $this->filename = $filename;
        $this->extension = $ext;
    }

    protected function validatePath()
    {
        if (!is_file($this->filepath)) throw ManageException::build()
            ->log()->info()->b()
            ->desc()->isInvalid('File')
            ->context($this->userId, 'userId')
            ->context($this->filepath, 'filepath')
            ->context($this->filename, 'filename')
            ->context($this->extension, 'extension')->b()
            ->fill();
    }

    protected function parseFileInfo()
    {
        $this->mimeType = mime_content_type($this->filepath);
        $this->fileSize = filesize($this->filepath);
    }

    protected function validateFileInfo()
    {
        if ($this->fileSize == 0)
            throw ManageException::build()
                ->log()->info()->b()
                ->desc()->isEmpty('File')
                ->context($this->userId, 'userId')->b()
                ->fill();
        if ($this->fileSize > File::MAX_FILE_SIZE)
            throw ManageException::build()
                ->log()->info()->b()
                ->desc()->tooMany('File has', 'bytes')
                ->context($this->userId, 'userId')->b()
                ->fill();
    }

    protected function genFileHash()
    {
        $this->fileHash = $this->fileHasher->hash($this->filepath);
    }

    protected function chooseLocalDir()
    {
        $this->storageDir = $this->localDirectoryChoice->chooseForStore($this->fileSize, $this->mimeType);
    }

    protected function genLocalFilepath()
    {
        $this->localFilepath = GenLocalFilePath::gen($this->storageDir, $this->fileHash);
    }

    protected function provideLocalCopy()
    {
        if (is_file($this->localFilepath)) return;

        if (!$this->fileMover->move($this->filepath, $this->localFilepath)) throw ManageException::build()
            ->log()->info()->b()
            ->desc()->failed(null, 'to Local Copy File')
            ->context($this->userId, 'userId')
            ->context($this->fileHash, 'fileHash')
            ->context($this->localFilepath, 'localFilepath')->b()
            ->fill();
    }

    protected function findFile()
    {
        $this->file = $this->fileData->findByHash($this->fileHash);
    }

    protected function provideFileId()
    {
        if (!empty($this->file)) {
            $this->fileId = $this->file->id;
            return;
        }

        $this->fileId = $this->fileData->create($this->fileHash, $this->fileSize, $this->mimeType, $this->storageDir);
    }

    protected function checkFileId()
    {
        if (empty($this->fileId)) throw ManageException::build()
            ->log()->info()->b()
            ->desc()->failed(null, 'to Identify or Create File')
            ->context($this->userId, 'userId')
            ->context($this->fileHash, 'fileHash')->b()
            ->fill();
    }

    protected function findFileOwner()
    {
        $this->fileOwner = $this->fileOwnerData->findOwnerFile($this->userId, $this->fileId);
    }

    protected function provideOwnership()
    {
        if (!empty($this->fileOwner)) {
            $this->ownershipId = $this->fileOwner->id;
            return;
        }

        $this->ownershipId = $this->fileOwnerData->create($this->userId, $this->fileId, $this->filename, $this->extension);
    }

    protected function checkOwnership()
    {
        if (empty($this->ownershipId)) throw ManageException::build()
            ->log()->info()->b()
            ->desc()->failed(null, 'to Identify Ownership')
            ->context($this->userId, 'userId')
            ->context($this->fileId, 'fileId')
            ->context($this->filename, 'filename')
            ->context($this->extension, 'extension')->b()
            ->fill();
    }

    protected function createResult(): array
    {
        return [
            'fileId' => $this->fileId,
            'ownershipId' => $this->ownershipId
        ];
    }
}
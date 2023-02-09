<?php

namespace MGGFLOW\Storage;

use MGGFLOW\Storage\Exceptions\FileNotFound;
use MGGFLOW\Storage\Exceptions\ResolverMakingFailed;
use MGGFLOW\Storage\Interfaces\FileData;
use MGGFLOW\Storage\Interfaces\FileOwnerData;
use MGGFLOW\Storage\Interfaces\FileReplicaData;
use MGGFLOW\Storage\Interfaces\FileResolverFactory;

class LocateFile
{
    protected FileData $fileData;
    protected FileOwnerData $fileOwnerData;
    protected FileReplicaData $fileReplicaData;
    protected FileResolverFactory $fileResolverFactory;

    protected int $userId;

    protected int $fileId;
    protected int $replicas;

    protected ?object $fileOwnership;
    protected ?object $file;
    protected ?array $replicasNotes;

    public function __construct(FileData        $fileData, FileOwnerData $fileOwnerData,
                                FileReplicaData $fileReplicaData, FileResolverFactory $fileResolverFactory,
                                int             $userId)
    {
        $this->fileData = $fileData;
        $this->fileOwnerData = $fileOwnerData;
        $this->fileReplicaData = $fileReplicaData;
        $this->fileResolverFactory = $fileResolverFactory;

        $this->userId = $userId;
    }

    public function locate(int $fileId, int $replicas = 0): array
    {
        $this->setFields($fileId, $replicas);
        $this->loadFileOwnership();
        $this->checkOwnership();
        $this->loadFileNote();
        $this->checkFileNote();
        $this->loadReplicasNotes();

        return $this->resolveFile();
    }

    protected function setFields(int $fileId, int $replicas)
    {
        $this->fileId = $fileId;
        $this->replicas = $replicas;
    }

    protected function loadFileOwnership()
    {
        $this->fileOwnership = $this->fileOwnerData->findOwnerFile($this->userId, $this->fileId);
    }

    protected function checkOwnership()
    {
        if (empty($this->fileOwnership)) throw new FileNotFound();
    }

    protected function loadFileNote()
    {
        $this->file = $this->fileData->getById($this->fileId);
    }

    protected function checkFileNote()
    {
        if (empty($this->file)) throw new FileNotFound();
    }

    protected function loadReplicasNotes()
    {
        if (empty($this->replicas)) return;

        $this->replicasNotes = $this->fileReplicaData->findForFile($this->fileId, $this->replicas);
    }

    protected function resolveFile(): array
    {
        $resolver = $this->fileResolverFactory->make($this->fileOwnership, $this->file, $this->replicasNotes);
        if (empty($resolver)) throw new ResolverMakingFailed();

        return $resolver->resolve();
    }

}
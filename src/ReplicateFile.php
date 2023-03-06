<?php

namespace MGGFLOW\Storage;

use MGGFLOW\ExceptionManager\ManageException;
use MGGFLOW\Storage\Interfaces\FileData;
use MGGFLOW\Storage\Interfaces\FileReplicaData;
use MGGFLOW\Storage\Interfaces\FileReplicator;
use MGGFLOW\Storage\Interfaces\FileReplicatorFactory;

class ReplicateFile
{
    protected FileData $fileData;
    protected FileReplicaData $fileReplicaData;
    protected FileReplicatorFactory $fileReplicatorFactory;

    protected ?object $file;
    protected string $localPath;
    protected ?array $replicasNotes;
    protected FileReplicator $fileReplicator;
    protected ?array $replicationResult;
    protected ?int $replicaId;

    public function __construct(FileData              $fileData, FileReplicaData $fileReplicaData,
                                FileReplicatorFactory $fileReplicatorFactory)
    {
        $this->fileData = $fileData;
        $this->fileReplicaData = $fileReplicaData;
        $this->fileReplicatorFactory = $fileReplicatorFactory;
    }

    public function replicate(): array
    {
        $this->chooseFile();
        $this->checkFile();
        $this->genLocalPath();
        $this->checkLocalCopy();
        $this->loadReplicasNotes();
        $this->replicateFile();
        $this->checkReplicationResult();
        $this->addReplicaNote();
        $this->checkReplicaNoteCreation();

        return $this->createSummary();
    }

    protected function chooseFile()
    {
        $this->file = $this->fileData->chooseFileForReplication();
    }

    protected function checkFile()
    {
        if (empty($this->file)) throw ManageException::build()
            ->log()->info()->b()
            ->desc()->not('File for Replication')->found()->b()
            ->fill();
    }

    protected function genLocalPath()
    {
        $this->localPath = GenLocalFilePath::gen($this->file->dir, $this->file->hash);
    }

    protected function checkLocalCopy()
    {
        if (!is_file($this->localPath)) throw ManageException::build()
            ->log()->info()->b()
            ->desc()->not('File local copy')->found()
            ->context($this->file, 'file')
            ->context($this->localPath, 'localPath')->b()
            ->fill();
    }

    protected function loadReplicasNotes()
    {
        $this->replicasNotes = $this->fileReplicaData->findForFile($this->file->id, $this->file->importance);
    }

    protected function replicateFile()
    {
        $this->fileReplicator = $this->fileReplicatorFactory->makeReplicator($this->localPath, $this->file, $this->replicasNotes);
        if (empty($this->fileReplicator)) throw ManageException::build()
            ->log()->info()->b()
            ->desc()->failed(null, 'to make File Replicator')
            ->context($this->file, 'file')
            ->context($this->localPath, 'localPath')
            ->context($this->replicasNotes, 'replicasNotes')->b()
            ->fill();

        $this->replicationResult = $this->fileReplicator->replicate();
    }

    protected function checkReplicationResult()
    {
        if (empty($this->replicationResult)) throw ManageException::build()
            ->log()->info()->b()
            ->desc()->failed(null, 'to Replicate File')
            ->context($this->file, 'file')
            ->context($this->localPath, 'localPath')
            ->context($this->replicasNotes, 'replicasNotes')
            ->context($this->fileReplicator, 'replicator')->b()
            ->fill();
    }

    protected function addReplicaNote()
    {
        $this->replicaId = $this->fileReplicaData->create(
            $this->file->id, $this->replicationResult['storageId'],
            $this->replicationResult['locationId'], $this->replicationResult['context']
        );
    }

    protected function checkReplicaNoteCreation()
    {
        if (empty($this->replicaId)) throw ManageException::build()
            ->log()->info()->b()
            ->desc()->failed(null, 'to Create Replica Note')
            ->context($this->file, 'file')
            ->context($this->replicationResult, 'replicationResult')->b()
            ->fill();
    }

    protected function createSummary(): array
    {
        return [
            'fileId' => $this->file->id,
            'prevReplicas' => $this->replicasNotes,
            'replicationResult' => $this->replicationResult,
            'replicaId' => $this->replicaId
        ];
    }
}
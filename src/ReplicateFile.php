<?php

namespace MGGFLOW\Storage;

use MGGFLOW\Storage\Exceptions\FileNotFound;
use MGGFLOW\Storage\Exceptions\NoFile;
use MGGFLOW\Storage\Exceptions\NoteReplicationFailed;
use MGGFLOW\Storage\Exceptions\ReplicationFailed;
use MGGFLOW\Storage\Interfaces\FileData;
use MGGFLOW\Storage\Interfaces\FileReplicaData;
use MGGFLOW\Storage\Interfaces\FileReplicator;

class ReplicateFile
{
    protected FileData $fileData;
    protected FileReplicaData $fileReplicaData;
    protected FileReplicator $fileReplicator;

    protected ?object $file;
    protected string $localPath;
    protected ?array $replicasNotes;
    protected ?array $replicationResult;
    protected ?int $replicaId;

    public function __construct(FileData       $fileData, FileReplicaData $fileReplicaData,
                                FileReplicator $fileReplicator)
    {
        $this->fileData = $fileData;
        $this->fileReplicaData = $fileReplicaData;
        $this->fileReplicator = $fileReplicator;
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
        if (empty($this->file)) throw new FileNotFound();
    }

    protected function genLocalPath()
    {
        $this->localPath = GenLocalFilePath::gen($this->file->dir, $this->file->hash);
    }

    protected function checkLocalCopy()
    {
        if (!is_file($this->localPath)) throw new NoFile();
    }

    protected function loadReplicasNotes()
    {
        $this->replicasNotes = $this->fileReplicaData->findForFile($this->file->id, $this->file->importance);
    }

    protected function replicateFile()
    {
        $this->replicationResult = $this->fileReplicator->replicate($this->localPath, $this->file, $this->replicasNotes);
    }

    protected function checkReplicationResult()
    {
        if (empty($this->replicationResult)) throw new ReplicationFailed();
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
        if (empty($this->replicaId)) throw new NoteReplicationFailed();
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
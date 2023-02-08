<?php

namespace MGGFLOW\Storage\Exceptions;

class NoteReplicationFailed extends \Exception
{
    protected $message = 'Failed to create Replica Note.';
}
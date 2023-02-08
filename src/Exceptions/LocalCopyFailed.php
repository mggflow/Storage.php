<?php

namespace MGGFLOW\Storage\Exceptions;

class LocalCopyFailed extends \Exception
{
    protected $message = 'Failed to create local copy.';
}
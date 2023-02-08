<?php

namespace MGGFLOW\Storage\Exceptions;

class NoFile extends \Exception
{
    protected $message = 'File doesnt exist.';
}
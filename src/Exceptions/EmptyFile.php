<?php

namespace MGGFLOW\Storage\Exceptions;

class EmptyFile extends \Exception
{
    protected $message = 'The File is empty.';
}
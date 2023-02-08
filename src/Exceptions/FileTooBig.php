<?php

namespace MGGFLOW\Storage\Exceptions;

class FileTooBig extends \Exception
{
    protected $message = 'The File is too big.';
}
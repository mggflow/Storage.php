<?php

namespace MGGFLOW\Storage\Interfaces;

interface FileMover
{
    public function move($from, $to): bool;
}
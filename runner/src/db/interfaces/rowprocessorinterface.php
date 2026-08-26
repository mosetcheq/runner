<?php
namespace Rnr\DB\Interfaces;

interface RowProcessorInterface
{
    public function process(object $row) : object;
}
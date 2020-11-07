<?php

namespace Unlooped\GridBundle\Exception;

use Exception;
use Throwable;
use Unlooped\GridBundle\ColumnType\AbstractColumnType;

class TypeNotAColumnException extends Exception
{
    public function __construct(string $type = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($type.' is not an instance of '.AbstractColumnType::class, $code, $previous);
    }
}

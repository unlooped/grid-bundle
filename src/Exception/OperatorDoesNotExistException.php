<?php

namespace Unlooped\GridBundle\Exception;

use Exception;
use Throwable;

class OperatorDoesNotExistException extends Exception
{
    public function __construct(string $operator, string $filterType, $code = 0, Throwable $previous = null)
    {
        parent::__construct($operator.' does not exist in '.$filterType, $code, $previous);
    }
}

<?php

namespace Unlooped\GridBundle\Exception;

use Exception;
use Throwable;

class DuplicateColumnException extends Exception
{
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

<?php

namespace Unlooped\GridBundle\Exception;

use Exception;
use Throwable;
use Unlooped\GridBundle\FilterType\FilterType;

class TypeNotAFilterException extends Exception
{
    public function __construct(string $type = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($type.' is not a '.FilterType::class, $code, $previous);
    }
}

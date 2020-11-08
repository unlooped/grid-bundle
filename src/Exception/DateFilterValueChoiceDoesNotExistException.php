<?php

namespace Unlooped\GridBundle\Exception;

use Exception;
use Throwable;

class DateFilterValueChoiceDoesNotExistException extends Exception
{
    public function __construct(string $valueChoice, string $filterType, $code = 0, Throwable $previous = null)
    {
        parent::__construct($valueChoice.' does not exist in '.$filterType, $code, $previous);
    }
}

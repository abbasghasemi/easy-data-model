<?php

declare(strict_types=1);

namespace AG\DataModel;

use Exception;

class ModelBuilderException extends Exception
{
    public function __construct(string $class, string $message)
    {
        parent::__construct("$message in `$class`.");
    }

}
<?php

declare(strict_types=1);

namespace AG\DataModel;

use Exception;

class ModelBuilderException extends Exception
{
    public string $class, $property;
    public mixed $propertyValue;

    public function __construct(string $class, string $property, mixed $propertyValue, string $message)
    {
        $this->class = $class;
        $this->property = $property;
        $this->propertyValue = $propertyValue;
        parent::__construct("$message in `$class`.");
    }

}
<?php

declare(strict_types=1);

namespace AG\DataModel;

use function defined;
use function constant;

trait EnumBuilder
{
    public static function tryFrom(string $name): ?self
    {
        $constName = 'self::' . $name;
        return defined($constName)
            ? constant($constName)
            : null;
    }
}
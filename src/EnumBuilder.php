<?php

declare(strict_types=1);

namespace AG\DataModel;

use UnitEnum;
use function constant;
use function defined;
use function get_called_class;
use function is_subclass_of;

trait EnumBuilder
{
    public static function tryFrom(string $name): ?self
    {
        if (!is_subclass_of(get_called_class(), UnitEnum::class)) {
            new ModelBuilderException(get_called_class(), $name, null, 'EnumBuilder can\'t be used');
        }
        $constName = 'self::' . $name;
        return defined($constName)
            ? constant($constName)
            : null;
    }
}
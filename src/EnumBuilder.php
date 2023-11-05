<?php

declare(strict_types=1);

namespace AG\DataModel;

use UnitEnum;

use function defined;
use function constant;
use function is_subclass_of;
use function get_called_class;

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
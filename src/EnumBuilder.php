<?php

declare(strict_types=1);

namespace AG\DataModel;

use UnitEnum;

use function defined;
use function constant;
use function is_subclass_of;
//use function is_numeric;
//use function is_string;
//use function intval;
use function get_called_class;
//use function sizeof;

trait EnumBuilder
{
    public static function tryFrom(string $name): ?self
    {
        if (!is_subclass_of(get_called_class(), UnitEnum::class)) {
            new ModelBuilderException(get_called_class(), $name, null, 'EnumBuilder can\'t be used');
        }
//        if (is_numeric($name)) {
//            if (is_string($name)) $name = intval($name);
//            $cases = self::cases();
//            if ($name >= sizeof($cases)) {
//                return null;
//            }
//            return $cases[$name];
//        }
        $constName = 'self::' . $name;
        return defined($constName)
            ? constant($constName)
            : null;
    }
}
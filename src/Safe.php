<?php

declare(strict_types=1);

namespace AG\DataModel;

use Attribute;
use function assert;

#[Attribute(Attribute::TARGET_PROPERTY)]
final /*readonly*/ class Safe
{
    /**
     * @param ?array<string> $alternate the alternative names of the field when it is deserialized.
     * @param ?string $pattern the value for field must match the pattern.
     * @param ?float $min the minimum length for string, minimum value for int|float and minimum size for array must be correct.
     * @param ?float $max the maximum length for string, maximum value for int|float and maximum size for array must be correct.
     * @param bool $ignore ignores only values greater than the value defined for string|int|float|array.
     * @param ?string $type only checks in the arrays,if true and whether all the data are of the defined type.
     * Also type can be a @link ModelBuilder
     */
    public function __construct(
        ?array  $alternate = null,
        ?string $pattern = null,
        ?float  $min = null,
        ?float  $max = null,
        bool    $ignore = false,
        ?string $type = null
    )
    {
        assert(!$ignore || $max !== null, '!$ignore || $max !== null');
    }
}
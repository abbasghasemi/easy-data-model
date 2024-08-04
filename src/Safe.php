<?php

declare(strict_types=1);

namespace AG\DataModel;

use Attribute;
use function assert;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Safe
{
    public readonly ?array $alternate;

    /**
     * @param ?string|string[] $alternate the alternative names of the field when it is deserialized.
     * If it is empty, it selects the input data as the value!.
     * @param ?string $pattern the value for field must match the pattern.
     * @param ?float $min the minimum length for string, minimum value for int|float and minimum size for array must be correct.
     * @param ?float $max the maximum length for string, maximum value for int|float and maximum size for array must be correct.
     * @param bool $ignore ignores only values greater than the value defined for string|int|float|array.
     * @param ?string|class-string $type If data is array, checks all the data are of the defined type.
     * If $followConvert is true and the type value is non-null, checks data with this type.
     * @param bool $followConvert If true, you should implements interface PropertyValueConvertor
     * @see PropertyValueConvertor,AllowsPropertyNull,FinallyAssert
     */
    public function __construct(
        null|string|array       $alternate = null,
        public readonly ?string $pattern = null,
        public readonly ?float  $min = null,
        public readonly ?float  $max = null,
        public readonly bool    $ignore = false,
        public readonly ?string $type = null,
        public readonly bool    $followConvert = false,
    )
    {
        assert(!$ignore || $max !== null, '!$ignore || $max !== null');
        if (is_string($alternate)) {
            $this->alternate = $alternate === '' ? [] : [$alternate];
        } else {
            $this->alternate = $alternate;
        }
    }
}
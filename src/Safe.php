<?php

declare(strict_types=1);

namespace AG\DataModel;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final /*readonly*/ class Safe
{
    public function __construct(
        ?string $name = null,
        ?string $pattern = null,
        ?float $min = null,
        ?float $max = null,
        ?int $length = null,
        ?string $type = null
    )
    {

    }
}
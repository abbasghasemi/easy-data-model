<?php

declare(strict_types=1);

namespace AG\DataModel;

use Attribute;

/**
 * property is ignored if access modifier is public or protected
 * property is seen if access modifier is private
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Ignore
{

}
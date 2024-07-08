<?php
declare(strict_types=1);

namespace AG\DataModel;

use Closure;

class ModelBuilderPlugins
{
    public static bool $exception = true;

    /**
     * @var ?Closure
     * @example
     * <pre>
     * ModelBuilderPlugins::$allowsNull = function (string $propertyName) {
     *      return true;
     * };
     * </pre>
     */
    public static ?Closure $allowsNull = null;

}
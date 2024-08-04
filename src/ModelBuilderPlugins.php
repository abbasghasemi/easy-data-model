<?php
declare(strict_types=1);

namespace AG\DataModel;

use Closure;

class ModelBuilderPlugins
{
    public static bool $ignoreWithoutType = false;

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

    /**
     * @var ?Closure
     * @example
     * <pre>
     * ModelBuilderPlugins::$convertor = function (string $propertyName, mixed $propertyValue): mixed {
     *      return 'NewValue';
     * };
     * </pre>
     */
    public static ?Closure $convertor = null;

}
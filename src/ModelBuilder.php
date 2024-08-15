<?php

declare(strict_types=1);

namespace AG\DataModel;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use TypeError;
use UnitEnum;
use function boolval;
use function call_user_func;
use function floatval;
use function get_debug_type;
use function in_array;
use function intval;
use function is_float;
use function is_null;
use function is_numeric;
use function is_object;
use function is_string;
use function is_subclass_of;
use function mb_strlen;
use function mb_substr;
use function method_exists;
use function preg_match;
use function sizeof;
use function strtolower;
use function strval;

//use function class_uses;

class ModelBuilder
{

    public function __construct(array $data)
    {
        self::fromArray($data, $this);
    }

    /**
     * @template T
     * @param array $data
     * @param object<T>|class-string<T> $objectOrClass
     * @return T
     * @throws ModelBuilderException
     * @throws ReflectionException
     */
    public static function fromArray(array $data, object|string $objectOrClass): object
    {
        if (is_string($objectOrClass)) {
            $class = new $objectOrClass;
        } else {
            $class = $objectOrClass;
        }
        $reflection = new ReflectionClass($class);
        $properties = $reflection->getProperties();
        for ($i = 0, $j = sizeof($properties); $i < $j; $i++) {
            $property = $properties[$i];
            if ($property->isPrivate() || $property->isStatic()) continue;
            $attrs = $property->getAttributes();
            /**
             * @var ?Safe $safeData
             */
            $safeData = null;
            foreach ($attrs as $attr) {
                if ($attr->getName() === Ignore::class) continue 2;
                if ($attr->getName() === Safe::class) {
                    $safeData = $attr->newInstance();
                    break;
                }
            }
            unset($propertyName);
            if ($safeData === null || $safeData->alternate === null) {
                $propertyName = $property->name;
            } elseif (!empty($safeData->alternate)) {
                if (count($safeData->alternate) === 1) {
                    $propertyName = $safeData->alternate[0];
                } else {
                    foreach ($safeData->alternate as $name) {
                        if (isset($data[$name])) {
                            $propertyName = $name;
                            break;
                        }
                    }
                    if (!isset($propertyName)) $propertyName = $safeData->alternate[0];
                }
            }
            $propertyType = $property->getType();
            if (null === $propertyType) {
                if (ModelBuilderPlugins::$ignoreWithoutType) continue;
                throw new ModelBuilderException($reflection->name, $property->name, null, "The property type '$property->name' can't be empty");
            }
            if (isset($propertyName)) {
                $value = $data[$propertyName] ?? null;
            } else {
                $propertyName = $property->name;
                $value = $data;
            }
            $followConvert = $safeData !== null && $safeData->followConvert;
            $object = null;
            if ($value !== null) {
                $dataType = get_debug_type($value);
                $useSafeType = $followConvert && !empty($safeData->type);
                if ($useSafeType || $propertyType instanceof ReflectionNamedType) {
                    $type = $useSafeType ? $safeData->type : $propertyType->getName();
                    if ("mixed" === $type || $dataType === $type) {
                        $object = $value;
                    } else if ($useSafeType && in_array($type, ['array', 'null', 'bool',
                            'float', 'int', 'string', 'object', 'resource']) ||
                        !$useSafeType && $propertyType->isBuiltin()) {
                        if ('string' === $dataType) {
                            if ('bool' === $type) {
                                $value = strtolower($value);
                                if ('false' === $value) $object = false;
                                elseif (in_array($value, ['0', '1', 'true'])) $object = boolval($value);
                            } else if (is_numeric($value)) {
                                if ('int' === $type) $object = intval($value);
                                else /*if ('float' === $type)*/ $object = floatval($value);
                            }
                        } else if ('int' === $dataType) {
                            if ('bool' === $type) {
                                if ($value === 0 || $value === 1)
                                    $object = boolval($value);
                            } else if ('float' === $type) {
                                $object = $value;
                            } else if ('string' === $type) {
                                if (is_numeric($value))
                                    $object = strval($value);
                            }
                        } else if ('int' === $type && is_float($value)) {
                            $object = intval($value);
                        } else if ('object' === $type && is_object($value)) {
                            $object = $value;
                        } else if ('resource' === $type && is_resource($value)) {
                            $object = $value;
                        }
                    } else {
                        if (is_subclass_of($type, UnitEnum::class)) {
                            $object = self::findEnum($type, $value);
                        } else if (is_array($value)) {
                            if (is_subclass_of($type, ModelBuilder::class))
                                $object = new $type($value);
                            else {
                                $object = self::fromArray($value, $type);
                            }
                        }
                    }
                    unset($type);
                } else {
                    $types = $propertyType->getTypes();
                    for ($k = 0, $l = sizeof($types); $k < $l; $k++) {
                        if ($dataType === $types[$k]->getName() ||
                            'object' === $types[$k]->getName() && is_object($value) ||
                            'resource' === $types[$k]->getName() && is_resource($value)) {
                            $object = $value;
                            break;
                        }
                    }
                    unset($types, $k, $l);
                }
                if (!is_null($object) && !empty($safeData) && (
                        !empty($safeData->pattern) && is_string($object) && !preg_match($safeData->pattern, $object) ||
                        !empty($safeData->min) && !self::checkMinObject($safeData->min, $object) ||
                        !empty($safeData->max) && !self::checkMaxObject($safeData->max, $object, $safeData->ignore) ||
                        !empty($safeData->type) && !self::checkTypeArray($safeData->type, $object)
                    )
                ) {
                    if (is_object($value) && !method_exists($value, '__toString')) {
                        $value = "Object";
                    } else {
                        $value = is_array($value) ? 'Array' : strval($value);
                    }
                    throw new ModelBuilderException($reflection->name, $propertyName, $value, "The value of '{$value}' is invalid for parameter '{$property->name}'");
                }
                unset($dataType, $useSafeType);
            }
            if ($followConvert) {
                self::resolveConvertor($class, $property->name, $object, $reflection->name);
            }
            if ($object === null) {
                if ($property->hasDefaultValue()) {
                    continue;
                } elseif (!$propertyType->allowsNull() || !self::resolveAllowsNull($class, $property->name)) {
                    throw new ModelBuilderException($reflection->name, $propertyName, null, "The parameter '{$property->name}' is required");
                }
            }
            try {
                if ($property->isProtected()) {
                    $property->setValue($class, $object);
                } else {
                    $class->{$property->name} = $object;
                }
            } catch (TypeError $e) {
                throw new ModelBuilderException($reflection->name, '', null, $e->getMessage());
            }
        }
        if (is_subclass_of($class, FinallyAssert::class)) {
            $assert = $class->onAssert();
            if (!empty($assert))
                throw new ModelBuilderException($reflection->name, '', null, $assert);
        }
        return $class;
    }

    private static function resolveConvertor(object $class, string $propertyName, mixed &$object, string $className): void
    {
        if (is_subclass_of($class, PropertyValueConvertor::class)) {
            $object = $class->onConvert($propertyName, $object);
        } elseif (!empty(ModelBuilderPlugins::$convertor)) {
            $object = ModelBuilderPlugins::$convertor->call($class, $propertyName, $object);
        } else {
            throw new ModelBuilderException($className, $propertyName, $object, "You have enabled the followConvert feature, but you forgot to launch it!");
        }
    }

    private static function resolveAllowsNull(object $class, string $propertyName): bool
    {
        if (is_subclass_of($class, AllowsPropertyNull::class)) {
            return $class->onAllowsNull($propertyName);
        }
        if (ModelBuilderPlugins::$allowsNull !== null) {
            return ModelBuilderPlugins::$allowsNull->call($class, $propertyName);
        }
        return true;
    }

    private static function checkMinObject(float $min, mixed $object): bool
    {
        if (is_array($object)) {
            return sizeof($object) >= $min;
        } elseif (is_string($object)) {
            return mb_strlen($object, 'UTF-8') >= $min;
        } elseif (is_numeric($object)) {
            return $object >= $min;
        }
        return true;
    }

    private static function checkMaxObject(float $max, mixed &$object, bool $ignore): bool
    {
        if ($ignore) {
            return self::checkLengthObject($max, $object);
        }
        if (is_array($object)) {
            return sizeof($object) <= $max;
        } elseif (is_string($object)) {
            return mb_strlen($object, 'UTF-8') <= $max;
        } elseif (is_numeric($object)) {
            return $object <= $max;
        }
        return true;
    }

    private static function checkLengthObject(float $length, mixed &$object): bool/*true*/
    {
        if (is_array($object)) {
            if (sizeof($object) > $length)
                $object = array_slice($object, 0, (int)$length);
        } elseif (is_string($object)) {
            if (mb_strlen($object, 'UTF-8') > $length)
                $object = mb_substr($object, 0, (int)$length, 'UTF-8');
        } elseif (is_numeric($object)) {
            if ($object > $length)
                $object = $length;
        }
        return true;
    }

    private static function checkTypeArray(mixed $type, mixed &$object): bool
    {
        if (is_array($object)) {
            foreach ($object as $k => $v) {
                $dataType = get_debug_type($v);
                if ($type !== $dataType) {
                    if ('array' === $dataType) {
                        if (is_subclass_of($type, ModelBuilder::class))
                            $object[$k] = new $type($v);
                        else {
                            $object[$k] = self::fromArray($v, $type);
                        }
                        continue;
                    }
                    return false;
                }
            }
        }
        return true;
    }

    private static function findEnum(string $className, mixed $with): ?object
    {
        if (!empty($with) || $with === 0) foreach (call_user_func("$className::cases") as $key => $value)
            if ($value->name === $with || isset($value->value) && $value->value === $with)
                return $value;
        return null;
    }
}
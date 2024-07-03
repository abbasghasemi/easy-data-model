<?php

declare(strict_types=1);

namespace AG\DataModel;

use ReflectionClass;
use ReflectionNamedType;
use UnitEnum;

use function mb_strlen;
use function mb_substr;
use function sizeof;
use function get_debug_type;
use function strtolower;
use function in_array;
use function boolval;
use function intval;
use function strval;
use function floatval;
use function is_float;
use function is_object;
use function is_string;
use function is_numeric;
use function is_subclass_of;
use function method_exists;
use function preg_match;
use function call_user_func;
use function is_null;

//use function class_uses;

class ModelBuilder
{
    /**
     * @throws ModelBuilderException
     */
    public function __construct(array $data, bool $exception = true)
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties();
        for ($i = 0, $j = sizeof($properties); $i < $j; $i++) {
            $property = $properties[$i];
            if ($property->isPrivate()) continue;
            $attrs = $property->getAttributes();
            $safeData = null;
            if (!empty($attrs)) {
                if ($attrs[0]->getName() === Ignore::class) continue;
                if ($attrs[0]->getName() === Safe::class) $safeData = $attrs[0]->getArguments();
            }
            unset($propertyName);
            if (empty($safeData['alternate'])) {
                $propertyName = $property->name;
            } else if (count($safeData['alternate']) === 1) {
                $propertyName = $safeData['alternate'][0];
            } else {
                foreach ($safeData['alternate'] as $name) {
                    if (isset($data[$name])) {
                        $propertyName = $name;
                        break;
                    }
                }
                if (!isset($propertyName)) $propertyName = $safeData['alternate'][0];
            }
            $propertyType = $property->getType();
            if (null === $propertyType) {
                if (!$exception) continue;
                throw new ModelBuilderException($reflection->name, $property->name, null, "The property type '$property->name' can't be empty");
            }
            $object = null;
            if (isset($data[$propertyName])) {
                $dataType = get_debug_type($data[$propertyName]);
                if ($propertyType instanceof ReflectionNamedType) {
                    $type = $propertyType->getName();
                    if ("mixed" === $type || $dataType === $type) {
                        $object = $data[$propertyName];
                    } else if ($propertyType->isBuiltin()) {
                        if ('string' === $dataType) {
                            if ('bool' === $type) {
                                $value = strtolower($data[$propertyName]);
                                if ('false' === $value) $object = false;
                                elseif (in_array($value, ['0', '1', 'true'])) $object = boolval($data[$propertyName]);
                                unset($value);
                            } else if (is_numeric($data[$propertyName])) {
                                if ('int' === $type) $object = intval($data[$propertyName]);
                                else /*if ('float' === $type)*/ $object = floatval($data[$propertyName]);
                            }
                        } else if ('int' === $dataType) {
                            if ('bool' === $type) {
                                if ($data[$propertyName] === 0 || $data[$propertyName] === 1)
                                    $object = boolval($data[$propertyName]);
                            } else if ('float' === $type) {
                                $object = $data[$propertyName];
                            } else if ('string' === $type) {
                                if (is_numeric($data[$propertyName]))
                                    $object = strval($data[$propertyName]);
                            }
                        } else if ('int' === $type && is_float($data[$propertyName])) {
                            $object = intval($data[$propertyName]);
                        } else if ('object' === $type && is_object($data[$propertyName])) {
                            $object = $data[$propertyName];
                        }
                    } else {
                        if (is_subclass_of($type, UnitEnum::class)) {
                            $object = self::findEnum($type, $data[$propertyName]);
                        } else if (is_subclass_of($type, ModelBuilder::class)) {
                            if (is_array($data[$propertyName]))
                                $object = new $type($data[$propertyName], $exception);
                        }
                    }
                    unset($type);
                } else {
                    $types = $propertyType->getTypes();
                    for ($k = 0, $l = sizeof($types); $k < $l; $k++) {
                        if ($dataType === $types[$k]->getName() ||
                            'object' === $types[$k]->getName() && is_object($data[$propertyName])) {
                            $object = $data[$propertyName];
                            break;
                        }
                    }
                    unset($types, $k, $l);
                }
                if (is_null($object) && (!$propertyType->allowsNull() || !$this->allowsNull($propertyName)) ||
                    !is_null($object) && !empty($safeData) && (
                        !empty($safeData['pattern']) && is_string($object) && !preg_match($safeData['pattern'], $object) ||
                        !empty($safeData['min']) && !self::checkMinObject($safeData['min'], $object) ||
                        !empty($safeData['max']) && !self::checkMaxObject($safeData['max'], $object, $safeData['ignore']) ||
                        !empty($safeData['type']) && !self::checkTypeArray($safeData['type'], $object, $exception)
                    )
                ) {
                    if (!$exception) continue;
                    if (is_object($data[$propertyName]) && !method_exists($data[$propertyName], '__toString')) {
                        $value = "Object";
                    } else {
                        $value = is_array($data[$propertyName]) ? 'Array' : strval($data[$propertyName]);
                    }
                    throw new ModelBuilderException($reflection->name, $propertyName, $data[$propertyName], "The value of '$value' is invalid for parameter '$propertyName'");
                }
                unset($dataType);
            } elseif ($property->hasDefaultValue()) {
                continue;
            } elseif (!$propertyType->allowsNull() || !$this->allowsNull($propertyName)) {
                if (!$exception) continue;
                throw new ModelBuilderException($reflection->name, $propertyName, null, "The parameter '$propertyName' is required");
            }
            $this->{$property->name} = $object;
        }
    }

    /**
     * Control of nullable properties.
     * @param string $propertyName
     * @return bool
     */
    protected function allowsNull(string $propertyName): bool
    {
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
                $object = array_slice($object, 0, (int) $length);
        } elseif (is_string($object)) {
            if (mb_strlen($object, 'UTF-8') > $length)
                $object = mb_substr($object, 0,  (int) $length, 'UTF-8');
        } elseif (is_numeric($object)) {
            if ($object > $length)
                $object = $length;
        }
        return true;
    }

    private static function checkTypeArray(mixed $type, mixed &$object, bool $exception): bool
    {
        if (is_array($object)) {
            for ($i = sizeof($object) - 1; $i > -1; $i--) {
                $dataType = get_debug_type($object[$i]);
                if ($type !== $dataType) {
                    if ('array' === $dataType && is_subclass_of($type, ModelBuilder::class)) {
                        $object[$i] = new $type($object[$i], $exception);
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
        if (!empty($with)) foreach (call_user_func("$className::cases") as $ley => $value)
            if ($value->name === $with || isset($value->value) && $value->value === $with)
                return $value;
        return null;
    }
}
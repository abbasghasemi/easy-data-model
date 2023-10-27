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

class ModelBuilder
{
    /**
     * @throws ModelBuilderException
     */
    public function __construct(array $data, bool $exception = true)
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties();
        for ($i = sizeof($properties) - 1; $i >= 0; $i--) {
            $property = $properties[$i];
            if ($property->isPrivate()) continue;
            $attrs = $property->getAttributes();
            $safeData = null;
            if (!empty($attrs)) {
                if ($attrs[0]->getName() === Ignore::class) continue;
                if ($attrs[0]->getName() === Safe::class) $safeData = $attrs[0]->getArguments();
            }
            $propertyName = empty($safeData['name']) ? $property->name : $safeData['name'];
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
                            } else if ('string' === $type && is_numeric($data[$propertyName])) {
                                $object = strval($data[$propertyName]);
                            }
                        } else if ('int' === $type && is_float($data[$propertyName])) {
                            $object = intval($data[$propertyName]);
                        } else if ('object' === $type && is_object($data[$propertyName])) {
                            $object = $data[$propertyName];
                        }
                    } else {
                        if (is_subclass_of($type, UnitEnum::class)) {
                            if (method_exists($type, 'tryFrom'))
                                $object = call_user_func("$type::tryFrom", $data[$propertyName]);
                        } else if (is_subclass_of($type, ModelBuilder::class)) {
                            $object = new $type($data[$propertyName], $exception);
                        }
                    }
                    unset($type);
                } else {
                    $types = $propertyType->getTypes();
                    for ($j = 0, $k = sizeof($types); $j < $k; $j++) {
                        if ($dataType === $types[$j]->getName() ||
                            'object' === $types[$j]->getName() && is_object($data[$propertyName])) {
                            $object = $data[$propertyName];
                            break;
                        }
                    }
                    unset($types);
                }
                if (is_null($object) && !$propertyType->allowsNull() ||
                    !is_null($object) && !empty($safeData) && (
                        !empty($safeData['pattern']) && is_string($object) && !preg_match($safeData['pattern'], $object) ||
                        !empty($safeData['min']) && !$this->checkMinObject($safeData['min'], $object) ||
                        !empty($safeData['max']) && !$this->checkMaxObject($safeData['max'], $object) ||
                        !empty($safeData['length']) && !$this->checkLengthObject($safeData['length'], $object) ||
                        !empty($safeData['type']) && !$this->checkTypeArray($safeData['type'], $object, $exception)
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
            } else if (!$propertyType->allowsNull()) {
                if (!$exception) continue;
                throw new ModelBuilderException($reflection->name, $propertyName, null, "The parameter '$propertyName' is required");
            }
            $this->{$property->name} = $object;
        }
    }

    private function checkMinObject(float $min, mixed $object): bool
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

    private function checkMaxObject(float $max, mixed $object): bool
    {
        if (is_array($object)) {
            return sizeof($object) <= $max;
        } elseif (is_string($object)) {
            return mb_strlen($object, 'UTF-8') <= $max;
        } elseif (is_numeric($object)) {
            return $object <= $max;
        }
        return true;
    }

    private function checkLengthObject(int $length, mixed &$object): bool/*true*/
    {
        if (is_array($object)) {
            if (sizeof($object) > $length)
                $object = array_slice($object, 0, $length);
        } elseif (is_string($object)) {
            if (mb_strlen($object, 'UTF-8') > $length)
                $object = mb_substr($object, 0, $length, 'UTF-8');
        } elseif (is_numeric($object)) {
            if ($object > $length)
                $object = $length;
        }
        return true;
    }

    private function checkTypeArray(mixed $type, mixed &$object, bool $exception): bool
    {
        if (is_array($object)) {
            for ($i = 0, $j = sizeof($object); $i < $j; $i++) {
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
}
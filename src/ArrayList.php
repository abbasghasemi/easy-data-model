<?php
declare(strict_types=1);

namespace AG\DataModel;

use Countable;
use Iterator;
use JsonSerializable;
use OutOfRangeException;
use RuntimeException;

/**
 * @template T
 */
class ArrayList implements Iterator, Countable, JsonSerializable
{

    /**
     * @param int<0,max> $length
     * @param T $element
     * @return ArrayList<T>
     */
    public static function filled(int $length, mixed $element): self
    {
        assert($length > -1);
        $list = [];
        while ($length > 0) {
            $list[] = $element;
            $length--;
        }
        return new ArrayList($list);
    }

    /**
     * @var T[]
     */
    private array $list;

    private int $size;
    private int $pointer = 0;

    public function __construct(
        ?array $list = null
    )
    {
        $list ??= array();
        $this->list = array_values($list);
        $this->size = count($list);
    }

    /**
     * @return T
     */
    public function first(): mixed
    {
        return $this->get(0);
    }

    /**
     * @return ?T
     */
    public function firstOrNull(): mixed
    {
        if ($this->isNotEmpty()) {
            return $this->first();
        }
        return null;
    }

    /**
     * @return T
     */
    public function last(): mixed
    {
        return $this->get($this->size() - 1);
    }

    /**
     * @return ?T
     */
    public function lastOrNull(): mixed
    {
        if ($this->isNotEmpty()) {
            return $this->last();
        }
        return null;
    }

    /**
     * @param ?T $element
     * @return void
     */
    public function add(mixed $element): void
    {
        $this->list[] = $element;
        $this->size++;
    }

    /**
     * @param T[] $elements
     * @return void
     */
    public function addAll(array $elements): void
    {
        $this->list = [...$this->list, ...array_values($elements)];
        $this->size += count($elements);
    }

    /**
     * @param int<0,max> $index
     * @param T $element
     * @return void
     */
    public function insert(int $index, mixed $element): void
    {
        assert($index > -1);
        array_splice($this->list, $index, 0, $element);
        $this->size++;
    }

    /**
     * @param int<0,max> $index
     * @param T[]|ArrayList<T> $elements
     * @return void
     */
    public function insertAll(int $index, array|ArrayList $elements): void
    {
        assert($index > -1);
        if (is_array($elements)) {
            array_splice($this->list, $index, 0, $elements);
            $this->size += count($elements);
        } else {
            array_splice($this->list, $index, 0, $elements->toArray());
            $this->size += $elements->size;
        }
    }


    /**
     * @param int<0,max> $start
     * @param int<1,max> $length
     * @param T $element
     * @return void
     */
    public function fillRange(int $start, int $length, mixed $element): void
    {
        assert($start > -1 && $length > 0 && $this->size() >= $start + $length);
        for ($i = $start; $i < $start + $length; $i++)
            $this->list[$i] = $element;
    }


    /**
     * @param T $element
     * @param int<0,max> $start
     * @return int
     */
    public function indexOf(mixed $element, int $start = 0): int
    {
        assert($start > -1);
        while ($this->size() > $start) {
            if ($element === $this->get($start)) {
                return $start;
            }
            $start++;
        }
        return -1;
    }

    /**
     * @param T $element
     * @param ?int<0,max> $start
     * @return int
     */
    public function lastIndexOf(mixed $element, ?int $start = null): int
    {
        assert($start === null || $start > -1);
        $start ??= $this->size() - 1;
        while ($start > -1) {
            if ($element === $this->get($start)) {
                return $start;
            }
            $start--;
        }
        return -1;
    }

    /**
     * @param callable(T): bool $test
     * @param int<0,max> $start
     * @return int
     */
    public function indexWhere(callable $test, int $start = 0): int
    {
        assert($start > -1);
        $this->pointer = $start;
        while ($this->valid()) {
            if (call_user_func($test, $this->current())) {
                return $this->key();
            }
            $this->next();
        }
        return -1;
    }

    /**
     * @param callable(T): bool $test
     * @param ?int<0,max> $start
     * @return int
     */
    public function lastIndexWhere(callable $test, ?int $start = null): int
    {
        assert($start === null || $start > -1);
        $start ??= $this->size() - 1;
        while ($start > -1) {
            if ($test($this->get($start))) {
                return $start;
            }
            $start--;
        }
        return -1;
    }

    /**
     * @param int<0,max> $index
     * @return T
     */
    public function remove(int $index): mixed
    {
        assert($index > -1);
        $this->size--;
        return array_shift($this->list);
    }

    public function removeLast(): void
    {
        $this->remove($this->size() - 1);
    }

    /**
     * @param int<0,max> $start
     * @param int<1,max> $length
     * @return void
     */
    public function removeRange(int $start, int $length): void
    {
        assert($start > -1 && $length > 0);
        if ($start >= $this->size() || $start + $length > $this->size()) {
            throw new OutOfRangeException("Range [$start:$length] is out of bounds! size={$this->size()}");
        }
        for ($i = $start; $i < $start + $length; $i++)
            unset($this->list[$i]);
        $this->list = array_values($this->toArray());
        $this->size -= $length;
    }

    /**
     * @param callable(T): bool $test
     * @return void
     */
    public function removeWhere(callable $test): void
    {
        $this->rewind();
        while ($this->valid()) {
            if (call_user_func($test, $this->current())) {
                $this->remove($this->key());
            } else $this->next();
        }
    }

    /**
     * @param int<0,max> $index
     * @return T
     */
    public function get(int $index): mixed
    {
        assert($index > -1);
        if ($index < $this->size()) {
            return $this->list[$index];
        }
        throw new OutOfRangeException("Index $index is out of bounds! size={$this->size()}");
    }

    /**
     * @param int<0,max> $start
     * @param int<0,max> $length
     * @return ArrayList<T>
     */
    public function getRange(int $start, int $length): ArrayList
    {
        assert($start > -1 && $length > -1);
        if ($start >= $this->size() || $start + $length > $this->size()) {
            throw new OutOfRangeException("Range [$start:$length] is out of bounds! size={$this->size()}");
        }
        return new ArrayList(array_slice($this->list, $start, $length));
    }

    /**
     * @param int<1,max> $count
     * @return ArrayList<T>
     */
    public function take(int $count): ArrayList
    {
        if ($this->isEmpty()) return $this;
        if ($count > $this->size()) $count = $this->size();
        return $this->getRange(0, $count);
    }

    /**
     * @param callable(T): void $callback
     * @return void
     */
    public function forEach(callable $callback): void
    {
        foreach ($this as $i) call_user_func($callback, $i);
    }


    /**
     * @param callable(T): bool $test
     * @param ?callable():T $orElse
     * @return T
     */
    public function firstWhere(callable $test, callable $orElse = null): mixed
    {
        for ($i = 0; $i < $this->size(); $i++) {
            $args = $this->get($i);
            if (call_user_func($test, $args)) {
                return $args;
            }
        }
        if ($orElse === null) {
            throw new RuntimeException("Not found element!");
        }
        return call_user_func($orElse);
    }

    /**
     * @param callable(T): bool $test
     * @return ArrayList<T>
     */
    public function where(callable $test): ArrayList
    {
        $list = [];
        $this->forEach(function ($element) use ($test, &$list) {
            if (call_user_func($test, $element)) {
                $list[] = $element;
            }
        });
        return new ArrayList($list);
    }

    /**
     * @param callable(T): bool $test
     * @param ?callable(): T $orElse
     * @return T
     */
    public function lastWhere(callable $test, callable $orElse = null): mixed
    {
        for ($i = $this->size() - 1; $i > -1; $i--) {
            $args = $this->get($i);
            if (call_user_func($test, $args)) {
                return $args;
            }
        }
        if ($orElse === null) {
            throw new RuntimeException("Not found element!");
        }
        return call_user_func($orElse);
    }

    /**
     * @return ArrayList<T>
     */
    public function reversed(): ArrayList
    {
        return new ArrayList(array_reverse($this->list));
    }

    public function join(string $separator): string
    {
        return implode($separator, $this->list);
    }

    /**
     * @param T $element
     * @return bool
     */
    public function contains(mixed $element): bool
    {
        foreach ($this->list as $i) if ($element === $i) {
            return true;
        }
        return false;
    }

    /**
     * @param callable(T $value,T $element):T $combine
     * @return T
     */
    public function reduce(callable $combine): mixed
    {
        if ($this->isEmpty()) {
            throw new RuntimeException('No element!');
        }
        $this->rewind();
        $value = $this->current();
        $this->next();
        while ($this->valid()) {
            $value = $combine($value, $this->current());
            $this->next();
        }
        return $value;
    }

    public function shuffle(): void
    {
        shuffle($this->list);
    }

    /**
     * @param callable(T,T):int $cmp
     * @return void
     * @example
     * sort('cmp');
     * function cmp(T $a,T $b): int
     * {
     *  if ($a === $b) return 0;
     *  return ($a < $b) ? -1 : 1;
     * }
     */
    public function sort(callable $cmp): void
    {
        usort($this->list, $cmp);
    }

    public function isEmpty(): bool
    {
        return $this->size() === 0;
    }

    public function isNotEmpty(): bool
    {
        return $this->size() !== 0;
    }

    public function clear(): void
    {
        $this->list = [];
        $this->size = 0;
        $this->rewind();
    }

    public function size(): int
    {
        return $this->size;
    }

    /**
     * @return T
     */
    public function current(): mixed
    {
        return $this->get($this->pointer);
    }

    public function next(): void
    {
        $this->pointer++;
    }

    public function key(): int
    {
        return $this->pointer;
    }

    public function valid(): bool
    {
        return $this->pointer < $this->size();
    }

    public function rewind(): void
    {
        $this->pointer = 0;
    }

    public function forward(int $count = 1): void
    {
        assert($count > 0 && $this->pointer + $count < $this->size());
        $this->pointer += $count;
    }

    public function back(int $count = 1): void
    {
        assert($count > 0 && $this->pointer - $count > -1);
        $this->pointer -= $count;
    }

    /**
     * @return T[]
     */
    public function toArray(): array
    {
        return $this->list;
    }

    public function count(): int
    {
        return $this->size();
    }

    public function __toString(): string
    {
        return "ArrayList({$this->size()})";
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
<?php

namespace AG\DataModel\Serialize;

abstract class Serialized
{
    public abstract function write(int $value): void;

    public abstract function writeByte(int $byte): void;

    public abstract function writeBytes(array $bytes, int $offset = 0, int $count = -1): void;

    public abstract function writeNullableBytes(?array $bytes, int $offset = 0, int $count = -1): void;

    public abstract function writeBool(?bool $bool): void;

    public abstract function writeInt32(int $value): void;

    public abstract function writeInt64(int $value): void;

    public abstract function writeFloat(float $value): void;

    public abstract function writeDouble(float $value): void;

    public abstract function writeByteArray(array $value): void;

    public abstract function writeNullableByteArray(?array $value): void;

    public abstract function writeString(string $value): void;

    public abstract function writeNullableString(?string $value): void;

    public abstract function read(bool $exception = false): int;

    public abstract function readByte(): int;

    public abstract function readBytes(int $count, bool $exception = false): array;

    public abstract function readNullableBytes(int $count, bool $exception = false): ?array;

    public abstract function readBool(bool $exception = false): ?bool;

    public abstract function readInt32(bool $exception = false): int;

    public abstract function readInt64(bool $exception = false): int;

    public abstract function readFloat(bool $exception = false): float;

    public abstract function readDouble(bool $exception = false): float;

    public abstract function readByteArray(bool $exception = false): array;

    public abstract function readNullableByteArray(bool $exception = false): ?array;

    public abstract function readString(bool $exception = false): string;

    public abstract function readNullableString(bool $exception = false): ?string;

    public abstract function skip(int $count): void;

    public abstract function length(): int;

    public abstract function toBytes(): string;

    public abstract function cleanup(): void;
}
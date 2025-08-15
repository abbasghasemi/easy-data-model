<?php
declare(strict_types=1);

namespace AG\DataModel\Serialize;

use OutOfRangeException;
use RuntimeException;
use function array_slice;
use function base64_decode;
use function base64_encode;
use function count;
use function pack;
use function unpack;

class SerializedData extends Serialized
{
    private bool $isOut;

    private int $position = 0;

    private array $byteArray = [];

    public function __construct(string $bytes = null, bool $fromB64 = false)
    {
        $this->isOut = $bytes !== null;
        if ($this->isOut) {
            if ($fromB64) $bytes = base64_decode($bytes);
            $this->byteArray = $this->bytesval($bytes);
        }
    }

    public function write(int $value): void
    {
        $this->writeByte($this->toByte($value));
    }

    public function writeByte(int $byte): void
    {
        if ($byte > 127 || $byte < -128) {
            throw new OutOfRangeException("The byte value must be between -128 and 127");
        }
        if ($this->length() >= 2147483647) {
            throw new RuntimeException("Out of memory");
        }
        $this->byteArray[] = $byte;
    }

    public function writeBytes(array $bytes, int $offset = 0, int $count = -1): void
    {
        if ($count > -1 || $offset > 0) {
            $bytes = array_slice($bytes, $offset, $count);
        }
        foreach ($bytes as $byte) $this->writeByte($byte);
    }

    public function writeNullableBytes(?array $bytes, int $offset = 0, int $count = -1): void
    {
        $this->writeByte($bytes === null ? 0 : 1);
        if ($bytes === null) return;
        $this->writeBytes($bytes, $offset, $count);
    }

    public function writeBool(?bool $bool): void
    {
        if ($bool === null) {
            $this->writeByte(0);
        } elseif (!$bool) {
            $this->writeByte(1);
        } else {
            $this->writeByte(2);
        }
    }

    public function writeInt32(int $value): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->write($value >> $i * 8);
        }
    }

    public function writeInt64(int $value): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->write($value >> $i * 8);
        }
    }

    public function writeFloat(float $value): void
    {
        $this->writeInt32($this->floatToIntBits($value));
    }

    public function writeDouble(float $value): void
    {
        $this->writeInt64($this->doubleToLongBits($value));
    }

    public function writeByteArray(array $value): void
    {
        $count = count($value);
        if ($count <= 253) {
            $i = 1;
            $this->write($count);
        } else {
            $i = 4;
            $this->write(254);
            $this->write($count);
            $this->write($count >> 8);
            $this->write($count >> 16);
        }
        $this->writeBytes($value);
        while (($count + $i) % 4 != 0) {
            $this->writeByte(0);
            $i++;
        }
    }

    public function writeNullableByteArray(?array $value): void
    {
        $this->writeByte($value === null ? 0 : 1);
        if ($value === null) return;
        $this->writeByteArray($value);
    }

    public function writeString(string $value): void
    {
        $this->writeByteArray($this->bytesval($value));
    }

    public function writeNullableString(?string $value): void
    {
        $this->writeByte($value === null ? 0 : 1);
        if ($value === null) return;
        $this->writeString($value);
    }


    /**
     * @param bool $exception
     * @return int in the range 0 to 255. If no byte is available because the end of the stream
     * has been reached, the value -1 is returned.
     */
    public function read(bool $exception = false): int
    {
        if ($this->length() > $this->position) {
            $b = $this->byteArray[$this->position++];
            if ($b < 128 && $b > -129) {
                return $b & 0xff;
            }
        }
        return $exception ? throw new RuntimeException("Not byte value!") : -1;
    }

    public function readByte(): int
    {
        $b = $this->read(true);
        if ($b < 128) return $b;
        return $b | ~0xff;
    }

    public function readBytes(int $count, bool $exception = false): array
    {
        if ($count < 1) return [];
        $bytes = [];
        try {
            while (count($bytes) < $count) {
                $bytes[] = $this->readByte();
            }
        } catch (RuntimeException $e) {
            if ($exception) {
                throw $e;
            }
        }
        return $bytes;
    }

    public function readNullableBytes(int $count, bool $exception = false): ?array
    {
        if ($this->read($exception) === 0) return null;
        return $this->readBytes($count, $exception);
    }

    public function readBool(bool $exception = false): ?bool
    {
        try {
            return match ($this->readByte()) {
                0 => null,
                1 => false,
                2 => true,
                default => $exception ? throw new RuntimeException("Not bool value!") : null,
            };
        } catch (RuntimeException $e) {
            if ($exception) throw $e;
        }
        return null;
    }

    public function readInt32(bool $exception = false): int
    {
        $i = 0;
        for ($j = 0; $j < 4; $j++) {
            $i |= $this->read($exception) << ($j * 8);
        }
        return $i;
    }

    public function readInt64(bool $exception = false): int
    {
        $i = 0;
        for ($j = 0; $j < 8; $j++) {
            $i |= $this->read($exception) << ($j * 8);
        }
        return $i;
    }

    public function readFloat(bool $exception = false): float
    {
        return $this->intBitsToFloat($this->readInt32($exception));
    }

    public function readDouble(bool $exception = false): float
    {
        return $this->longBitsToDouble($this->readInt64($exception));
    }

    public function readByteArray(bool $exception = false): array
    {
        $sl = 1;
        $l = $this->read($exception);
        if ($l >= 254) {
            $l = $this->read($exception) | ($this->read($exception) << 8) | ($this->read($exception) << 16);
            $sl = 4;
        }
        $b = $this->readBytes($l, $exception);
        $i = $sl;
        while (($l + $i) % 4 != 0) {
            $this->read($exception);
            $i++;
        }
        return $b;
    }

    public function readNullableByteArray(bool $exception = false): ?array
    {
        if ($this->read($exception) === 0) return null;
        return $this->readByteArray($exception);
    }

    public function readString(bool $exception = false): string
    {
        return $this->strval($this->readByteArray($exception));
    }

    public function readNullableString(bool $exception = false): ?string
    {
        if ($this->read($exception) === 0) return null;
        return $this->readString($exception);
    }

    public function skip(int $count): void
    {
        if ($count < 1) {
            return;
        }
        $this->position += $count;
    }


    public function length(): int
    {
        return count($this->byteArray);
    }

    public function toBytes(): string
    {
        return $this->strval($this->byteArray);
    }

    public function cleanup(): void
    {
        $this->byteArray = [];
        $this->position = 0;
    }

    public function bytesval(string $str): array
    {
        return array_values(unpack("c*", $str));
    }

    public function strval(array $byteArray): string
    {
        return pack("c*", ...$byteArray);
    }

    public function toBase64(): string
    {
        return base64_encode($this->toBytes());
    }

    public function toByte(int $value): int
    {
        if ($value > 127 || $value < -128) {
            // cast to byte
            return unpack("c*", pack("l", $value))[1];
        }
        return $value;
    }

    public function getByteArray(): array
    {
        return $this->byteArray;
    }

    private function floatToIntBits(float $value): int
    {
        return unpack('L', pack('f', $value))[1];
    }

    private function intBitsToFloat(int $value): float
    {
        return unpack('f', pack('L', $value))[1];
    }

    private function doubleToLongBits(float $value): int
    {
        return unpack('Q', pack('d', $value))[1];
    }

    private function longBitsToDouble(int $value): float
    {
        return unpack('d', pack('Q', $value))[1];
    }

}
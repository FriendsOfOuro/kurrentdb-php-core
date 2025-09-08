<?php

namespace EventStore\ValueObjects\Identity;

use EventStore\ValueObjects\Exception\InvalidNativeArgumentException;
use EventStore\ValueObjects\StringLiteral\StringLiteral;
use EventStore\ValueObjects\Util\Util;
use EventStore\ValueObjects\ValueObjectInterface;
use Ramsey\Uuid\Uuid as BaseUuid;

final readonly class UUID extends StringLiteral
{
    /**
     * @throws InvalidNativeArgumentException
     */
    public static function fromNative(string $uuid): static
    {
        return new self($uuid);
    }

    /**
     * Generate a new UUID string.
     */
    public static function generateAsString(): string
    {
        $uuid = new self();

        return $uuid->toNative();
    }

    public function __construct(?string $value = null)
    {
        $uuid_str = BaseUuid::uuid4();

        if (null !== $value) {
            $pattern = '/'.BaseUuid::VALID_PATTERN.'/';

            if (!\preg_match($pattern, $value)) {
                throw new InvalidNativeArgumentException($value, ['UUID string']);
            }

            $uuid_str = $value;
        }

        $value = \strval($uuid_str);
        parent::__construct($value);
    }

    /**
     * Tells whether two UUID are equal by comparing their values.
     */
    #[\Override]
    public function sameValueAs(ValueObjectInterface $uuid): bool
    {
        if (false === Util::classEquals($this, $uuid)) {
            return false;
        }

        return $this->toNative() === $uuid->toNative();
    }
}

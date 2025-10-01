<?php

declare(strict_types=1);

namespace KurrentDB\ValueObjects\Identity;

use KurrentDB\ValueObjects\Exception\InvalidNativeArgumentException;
use KurrentDB\ValueObjects\StringLiteral\StringLiteral;
use KurrentDB\ValueObjects\Util\Util;
use KurrentDB\ValueObjects\ValueObjectInterface;
use Ramsey\Uuid\Uuid as BaseUuid;
use Ramsey\Uuid\Validator\GenericValidator;

final readonly class UUID extends StringLiteral
{
    public function __construct(?string $value = null)
    {
        $validator = new GenericValidator();
        if (is_string($value) && !$validator->validate($value)) {
            throw new InvalidNativeArgumentException($value, ['UUID string']);
        }

        $value ??= (string) BaseUuid::uuid4();
        parent::__construct($value);
    }

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

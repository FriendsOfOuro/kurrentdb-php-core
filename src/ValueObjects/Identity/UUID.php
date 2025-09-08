<?php

namespace EventStore\ValueObjects\Identity;

use EventStore\ValueObjects\Exception\InvalidNativeArgumentException;
use EventStore\ValueObjects\StringLiteral\StringLiteral;
use EventStore\ValueObjects\Util\Util;
use EventStore\ValueObjects\ValueObjectInterface;
use Ramsey\Uuid\Uuid as BaseUuid;

class UUID extends StringLiteral
{
    /** @var BaseUuid */
    protected $value;

    /**
     * @throws InvalidNativeArgumentException
     */
    #[\Override]
    public static function fromNative(): static
    {
        $uuid_str = \func_get_arg(0);

        return new static($uuid_str);
    }

    /**
     * Generate a new UUID string.
     *
     * @return string
     */
    public static function generateAsString()
    {
        $uuid = new static();

        return $uuid->toNative();
    }

    public function __construct($value = null)
    {
        $uuid_str = BaseUuid::uuid4();

        if (null !== $value) {
            $pattern = '/' . BaseUuid::VALID_PATTERN . '/';

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
     *
     * @param UUID $uuid
     *
     * @return bool
     */
    #[\Override]
    public function sameValueAs(ValueObjectInterface $uuid)
    {
        if (false === Util::classEquals($this, $uuid)) {
            return false;
        }

        return $this->toNative() === $uuid->toNative();
    }
}

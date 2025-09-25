<?php

declare(strict_types=1);

namespace KurrentDB\Http\Auth;

final readonly class Credentials implements \Stringable
{
    public function __construct(
        public string $user,
        public ?string $pass = null,
    ) {
    }

    public static function fromString(string $credentials): self
    {
        if (str_contains($credentials, ':')) {
            [$user, $pass] = explode(':', $credentials, 2);

            return new self($user, $pass);
        }

        return new self($credentials);
    }

    public function __toString(): string
    {
        if ('' === $this->user) {
            return '';
        }

        return null !== $this->pass ? sprintf('%s:%s', $this->user, $this->pass) : $this->user;
    }
}

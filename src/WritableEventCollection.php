<?php

declare(strict_types=1);

namespace KurrentDB;

/**
 * Class WritableEventCollection.
 */
final readonly class WritableEventCollection
{
    /**
     * @param WritableEvent[] $events
     */
    private function __construct(public array $events)
    {
    }

    public static function of(WritableEvent ...$events): self
    {
        return new self($events);
    }
}

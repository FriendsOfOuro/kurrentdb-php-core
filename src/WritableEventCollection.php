<?php

declare(strict_types=1);

namespace KurrentDB;

use KurrentDB\Exception\InvalidWritableEventObjectException;

/**
 * Class WritableEventCollection.
 */
final readonly class WritableEventCollection
{
    /**
     * @param WritableEvent[] $events
     */
    public function __construct(public array $events)
    {
        $this->validateEvents($events);
    }

    public static function of(WritableEvent ...$events): self
    {
        return new self($events);
    }

    /**
     * @param WritableEvent[] $events
     */
    private function validateEvents(array $events): void
    {
        foreach ($events as $event) {
            if (!$event instanceof WritableEvent) {
                throw new InvalidWritableEventObjectException();
            }
        }
    }
}

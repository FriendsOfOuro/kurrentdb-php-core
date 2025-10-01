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
    public function __construct(private array $events)
    {
        $this->validateEvents($events);
    }

    public static function of(WritableEvent ...$events): self
    {
        return new self($events);
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function toStreamData(): array
    {
        return array_map(fn (WritableEvent $event): array => $event->toStreamData(), $this->events);
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

<?php

namespace KurrentDB;

use KurrentDB\Exception\InvalidWritableEventObjectException;

/**
 * Class WritableEventCollection.
 */
final readonly class WritableEventCollection implements WritableToStream
{
    /**
     * @var WritableEvent[]
     */
    private array $events;

    public function __construct(array $events)
    {
        $this->validateEvents($events);
        $this->events = $events;
    }

    public function toStreamData(): array
    {
        return array_map(fn (WritableEvent $event): array => $event->toStreamData(), $this->events);
    }

    private function validateEvents(array $events): void
    {
        foreach ($events as $event) {
            if (!$event instanceof WritableEvent) {
                throw new InvalidWritableEventObjectException();
            }
        }
    }
}

<?php
namespace EventStore;

use EventStore\Exception\InvalidWritableEventObjectException;

/**
 * Class WritableEventCollection.
 */
final class WritableEventCollection implements WritableToStream
{
    /**
     * @var WritableEvent[]
     */
    private $events = [];

    public function __construct(array $events)
    {
        $this->validateEvents($events);
        $this->events = $events;
    }

    /**
     * @return array
     */
    public function toStreamData()
    {
        return array_map(function (WritableEvent $event) {
            return $event->toStreamData();
        }, $this->events);
    }

    private function validateEvents(array $events)
    {
        foreach ($events as $event) {
            if (!$event instanceof WritableEvent) {
                throw new InvalidWritableEventObjectException();
            }
        }
    }
}

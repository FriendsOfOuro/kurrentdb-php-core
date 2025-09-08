<?php

namespace EventStore\StreamFeed;

final readonly class EntryWithEvent
{
    public function __construct(private Entry $entry, private Event $event)
    {
    }

    public function getEntry(): Entry
    {
        return $this->entry;
    }

    /**
     * return @Event.
     */
    public function getEvent(): Event
    {
        return $this->event;
    }
}

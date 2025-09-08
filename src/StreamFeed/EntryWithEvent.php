<?php

namespace EventStore\StreamFeed;

final class EntryWithEvent
{
    private $entry;
    private $event;

    public function __construct(Entry $entry, Event $event)
    {
        $this->entry = $entry;
        $this->event = $event;
    }

    /**
     * @return Entry
     */
    public function getEntry()
    {
        return $this->entry;
    }

    /**
     * return @Event.
     */
    public function getEvent()
    {
        return $this->event;
    }
}

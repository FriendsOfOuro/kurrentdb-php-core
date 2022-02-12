<?php
namespace EventStore\StreamFeed;

use ArrayIterator;
use EventStore\EventStoreInterface;

final class StreamFeedIterator implements \Iterator
{
    private $eventStore;

    private $streamName;

    private $feed;

    private $innerIterator;

    private $startingRelation;

    private $navigationRelation;

    private $arraySortingFunction;

    private $rewinded;

    private function __construct(
        EventStoreInterface $eventStore,
        $streamName,
        LinkRelation $startingRelation,
        LinkRelation $navigationRelation,
        callable $arraySortingFunction
    ) {
        $this->eventStore = $eventStore;
        $this->streamName = $streamName;
        $this->startingRelation = $startingRelation;
        $this->navigationRelation = $navigationRelation;
        $this->arraySortingFunction = $arraySortingFunction;
    }

    public static function forward(EventStoreInterface $eventStore, $streamName)
    {
        return new self(
            $eventStore,
            $streamName,
            LinkRelation::LAST(),
            LinkRelation::PREVIOUS(),
            'array_reverse'
        );
    }

    public static function backward(EventStoreInterface $eventStore, $streamName)
    {
        return new self(
            $eventStore,
            $streamName,
            LinkRelation::FIRST(),
            LinkRelation::NEXT(),
            function (array $array) {
                return $array;
            }
        );
    }

    public function current()
    {
        return $this->innerIterator->current();
    }

    public function next(): void
    {
        $this->rewinded = false;
        $this->innerIterator->next();

        if (!$this->innerIterator->valid()) {
            $this->feed = $this
                ->eventStore
                ->navigateStreamFeed(
                    $this->feed,
                    $this->navigationRelation
                )
            ;

            $this->createInnerIterator();
        }
    }

    public function key()
    {
        return $this->innerIterator->current()->getEntry()->getTitle();
    }

    public function valid(): bool
    {
        return $this->innerIterator->valid();
    }

    public function rewind(): void
    {
        if ($this->rewinded) {
            return;
        }

        $this->feed = $this->eventStore->openStreamFeed($this->streamName);

        if ($this->feed->hasLink($this->startingRelation)) {
            $this->feed = $this
                ->eventStore
                ->navigateStreamFeed(
                    $this->feed,
                    $this->startingRelation
                )
            ;
        }

        $this->createInnerIterator();

        $this->rewinded = true;
    }

    private function createInnerIterator()
    {
        if (null !== $this->feed) {
            $entries = $this->feed->getEntries();
        } else {
            $entries = [];
        }

        if (empty($entries)) {
            $this->innerIterator = new ArrayIterator([]);

            return;
        }

        $entries = call_user_func(
            $this->arraySortingFunction,
            $entries
        );

        $urls = array_map(
            function ($entry) {
                return $entry->getEventUrl();
            },
            $entries
        );

        $this->innerIterator = new ArrayIterator(
            array_filter(
                array_map(
                    function ($entry, $event) {
                        if (null === $entry || null === $event) {
                            return null;
                        }

                        return new EntryWithEvent(
                            $entry,
                            $event
                        );
                    },
                    $entries,
                    $this->eventStore->readEventBatch($urls)
                ),
                function ($entryWithEvent) {
                    return null !== $entryWithEvent;
                }
            )
        );
    }
}

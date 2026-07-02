<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use KurrentDB\Exception\BadRequestException;
use KurrentDB\Exception\StreamGoneException;
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\StreamReaderInterface;
use Psr\Http\Message\UriInterface;

/**
 * @implements \Iterator<string, EntryWithEvent>
 */
final class StreamFeedIterator implements \Iterator
{
    private ?StreamFeed $feed = null;

    /**
     * @var \ArrayIterator<int, EntryWithEvent>
     */
    private \ArrayIterator $innerIterator;

    private readonly \Closure $arraySortingFunction;

    private bool $rewinded = false;

    private int $pagesLeft;

    private function __construct(
        private readonly StreamReaderInterface $streamReader,
        private readonly string $streamName,
        private readonly LinkRelation $startingRelation,
        private readonly LinkRelation $navigationRelation,
        callable $arraySortingFunction,
        int $pageLimit = PHP_INT_MAX,
    ) {
        $this->arraySortingFunction = $arraySortingFunction(...);
        $this->pagesLeft = max(0, $pageLimit - 1); // Reserve one for initial page
        $this->innerIterator = new \ArrayIterator([]);
    }

    public static function forward(StreamReaderInterface $streamReader, string $streamName, int $pageLimit = PHP_INT_MAX): self
    {
        return new self(
            $streamReader,
            $streamName,
            LinkRelation::LAST,
            LinkRelation::PREVIOUS,
            'array_reverse',
            $pageLimit
        );
    }

    public static function backward(StreamReaderInterface $streamReader, string $streamName, int $pageLimit = PHP_INT_MAX): self
    {
        static $identity = fn (array $a): array => $a;

        return new self(
            $streamReader,
            $streamName,
            LinkRelation::FIRST,
            LinkRelation::NEXT,
            $identity,
            $pageLimit
        );
    }

    public function current(): EntryWithEvent
    {
        return $this->innerIterator->current();
    }

    /**
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    public function next(): void
    {
        $this->rewinded = false;
        $this->innerIterator->next();

        $this->advanceToPageWithEvents();
    }

    public function key(): string
    {
        return $this->innerIterator->current()->getEntry()->getTitle();
    }

    public function valid(): bool
    {
        return $this->innerIterator->valid();
    }

    public function nextUrl(): ?UriInterface
    {
        if (!$this->feed instanceof StreamFeed) {
            return null;
        }

        return $this->feed->getLinkUrl($this->navigationRelation);
    }

    /**
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    public function rewind(): void
    {
        if ($this->rewinded) {
            return;
        }

        $this->feed = $this->streamReader->openStreamFeed($this->streamName, EntryEmbedMode::BODY);

        if ($this->feed->hasLink($this->startingRelation)) {
            $this->feed = $this
                ->streamReader
                ->navigateStreamFeed(
                    $this->feed,
                    $this->startingRelation
                )
            ;
        }

        $this->createInnerIterator();
        $this->advanceToPageWithEvents();

        $this->rewinded = true;
    }

    /**
     * Keeps navigating while the current page yields no events, e.g. pages of
     * $streams made entirely of linkTos into hard-deleted streams.
     *
     * @throws BadRequestException
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    private function advanceToPageWithEvents(): void
    {
        while (!$this->innerIterator->valid() && $this->pagesLeft > 0 && $this->feed instanceof StreamFeed) {
            $this->feed = $this
                ->streamReader
                ->navigateStreamFeed(
                    $this->feed,
                    $this->navigationRelation
                )
            ;

            if (!$this->feed instanceof StreamFeed) {
                return;
            }

            --$this->pagesLeft;
            $this->createInnerIterator();
        }
    }

    private function createInnerIterator(): void
    {
        $entries = $this->feed?->getEntries() ?: [];

        if ([] === $entries) {
            $this->innerIterator = new \ArrayIterator([]);

            return;
        }

        $entries = call_user_func(
            $this->arraySortingFunction,
            $entries
        );

        $this->innerIterator = new \ArrayIterator(
            array_values(array_filter(
                array_map(
                    static function (Entry $entry): ?EntryWithEvent {
                        $event = $entry->getEmbeddedEvent();

                        if (!$event instanceof Event) {
                            return null;
                        }

                        return new EntryWithEvent($entry, $event);
                    },
                    $entries
                ),
                static fn (?EntryWithEvent $entryWithEvent): bool => $entryWithEvent instanceof EntryWithEvent
            ))
        );
    }
}

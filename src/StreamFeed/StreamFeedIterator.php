<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use KurrentDB\Exception\BadRequestException;
use KurrentDB\Exception\StreamGoneException;
use KurrentDB\Exception\StreamNotFoundException;
use KurrentDB\Exception\WrongExpectedVersionException;
use KurrentDB\StreamReaderInterface;
use Psr\Http\Client\ClientExceptionInterface;
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
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    public function next(): void
    {
        $this->rewinded = false;
        $this->innerIterator->next();

        if (!$this->innerIterator->valid() && $this->pagesLeft > 0) {
            $this->feed = $this
                ->streamReader
                ->navigateStreamFeed(
                    $this->feed,
                    $this->navigationRelation
                )
            ;

            if ($this->feed instanceof StreamFeed) {
                --$this->pagesLeft;
                $this->createInnerIterator();
            }
        }
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
     * @throws ClientExceptionInterface
     * @throws StreamGoneException
     * @throws StreamNotFoundException
     * @throws WrongExpectedVersionException
     */
    public function rewind(): void
    {
        if ($this->rewinded) {
            return;
        }

        $this->feed = $this->streamReader->openStreamFeed($this->streamName);

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

        $this->rewinded = true;
    }

    /**
     * @throws ClientExceptionInterface
     */
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

        $urls = array_filter(array_map(
            fn (Entry $entry): ?UriInterface => $entry->getEventUrl(),
            $entries
        ));

        $this->innerIterator = new \ArrayIterator(
            array_filter(
                array_map(
                    function (?Entry $entry, ?Event $event): ?EntryWithEvent {
                        if (!$entry instanceof Entry || !$event instanceof Event) {
                            return null;
                        }

                        return new EntryWithEvent(
                            $entry,
                            $event
                        );
                    },
                    $entries,
                    $this->streamReader->readEventBatch($urls)
                ),
                fn (?EntryWithEvent $entryWithEvent): bool => $entryWithEvent instanceof EntryWithEvent
            )
        );
    }
}

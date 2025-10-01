<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

/**
 * Single event response from GET /streams/{stream}/{eventNumber}.
 * Contains the event data in a 'content' field.
 */
final readonly class EventResponse extends Entry
{
    /**
     * @param Link[]               $links
     * @param array<string, mixed> $content Event content with eventStreamId, eventNumber, eventType, eventId, data, metadata
     */
    public function __construct(
        string $title,
        string $id,
        string $updated,
        string $summary,
        ?int $retryCount,
        array $links,
        public array $content,
    ) {
        parent::__construct($title, $id, $updated, $summary, $retryCount, $links);
    }

    public function getEventStreamId(): string
    {
        return $this->content['eventStreamId'];
    }

    public function getEventNumber(): int
    {
        return $this->content['eventNumber'];
    }

    public function getEventType(): string
    {
        return $this->content['eventType'];
    }

    public function getEventId(): string
    {
        return $this->content['eventId'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->content['data'];
    }

    public function getMetadata(): mixed
    {
        return $this->content['metadata'] ?? null;
    }
}

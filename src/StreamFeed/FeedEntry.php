<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

/**
 * Entry within a stream feed page.
 * Fields like eventId, eventType, data, etc. are populated based on embed mode.
 */
final readonly class FeedEntry extends Entry
{
    /**
     * @param Link[] $links
     */
    public function __construct(
        string $title,
        string $id,
        string $updated,
        string $summary,
        ?int $retryCount,
        array $links,
        // Present only when embed=body or embed=rich
        public ?string $eventId,
        public ?string $eventType,
        public ?int $eventNumber,
        /** @var array<string, mixed>|null */
        public ?array $data,
        /** @var array<string, mixed>|null */
        public ?array $metaData,
        public mixed $linkMetaData,
        public ?string $streamId,
        public ?bool $isJson,
        public ?bool $isMetaData,
        public ?bool $isLinkMetaData,
        public ?bool $isRedacted,
        public ?int $positionEventNumber,
        public ?string $positionStreamId,
    ) {
        parent::__construct($title, $id, $updated, $summary, $retryCount, $links);
    }
}

<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class FeedEntryDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private DenormalizerInterface $linkDenormalizer,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): FeedEntry
    {
        $links = [];
        if (isset($data['links']) && is_array($data['links'])) {
            foreach ($data['links'] as $linkData) {
                $links[] = $this->linkDenormalizer->denormalize($linkData, Link::class);
            }
        }

        // Parse data if it's a JSON string (embed=body)
        $parsedData = null;
        if (isset($data['data'])) {
            if (is_string($data['data']) && '' !== $data['data']) {
                $parsedData = json_decode($data['data'], true);
            } elseif (is_array($data['data'])) {
                $parsedData = $data['data'];
            }
        }

        // Parse metadata if it's a JSON string (embed=body)
        $parsedMetaData = null;
        if (isset($data['metaData'])) {
            if (is_string($data['metaData']) && '' !== $data['metaData']) {
                $parsedMetaData = json_decode($data['metaData'], true);
            } elseif (is_array($data['metaData'])) {
                $parsedMetaData = $data['metaData'];
            }
        }

        return new FeedEntry(
            $data['title'] ?? '',
            $data['id'] ?? '',
            $data['updated'] ?? '',
            $data['summary'] ?? '',
            $data['retryCount'] ?? null,
            $links,
            $data['eventId'] ?? null,
            $data['eventType'] ?? null,
            $data['eventNumber'] ?? null,
            $parsedData,
            $parsedMetaData,
            $data['linkMetaData'] ?? null,
            $data['streamId'] ?? null,
            $data['isJson'] ?? null,
            $data['isMetaData'] ?? null,
            $data['isLinkMetaData'] ?? null,
            $data['isRedacted'] ?? null,
            $data['positionEventNumber'] ?? null,
            $data['positionStreamId'] ?? null,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return FeedEntry::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            FeedEntry::class => true,
        ];
    }
}

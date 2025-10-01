<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class StreamFeedDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private DenormalizerInterface $linkDenormalizer,
        private DenormalizerInterface $entryDenormalizer,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): StreamFeed
    {
        $links = [];
        if (isset($data['links']) && is_array($data['links'])) {
            foreach ($data['links'] as $linkData) {
                $links[] = $this->linkDenormalizer->denormalize($linkData, Link::class);
            }
        }

        $entries = [];
        if (isset($data['entries']) && is_array($data['entries'])) {
            foreach ($data['entries'] as $entryData) {
                $entries[] = $this->entryDenormalizer->denormalize($entryData, Entry::class);
            }
        }

        $embedMode = $context['embedMode'] ?? EntryEmbedMode::NONE;

        return new StreamFeed(
            $links,
            $entries,
            $data,
            $embedMode,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return StreamFeed::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            StreamFeed::class => true,
        ];
    }
}

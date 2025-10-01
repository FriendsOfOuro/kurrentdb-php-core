<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class StreamFeedDenormalizer implements DenormalizerInterface, StreamFeedFactoryInterface
{
    public function __construct(
        private DenormalizerInterface $linkDenormalizer,
        private EntryFactoryInterface $entryFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $json
     */
    public function create(array $json, ?EntryEmbedMode $embedMode = null): StreamFeed
    {
        return $this->denormalize($json, StreamFeed::class, context: ['embedMode' => $embedMode ?? EntryEmbedMode::NONE]);
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
                $entries[] = $this->entryFactory->create($entryData);
            }
        }

        $embedMode = $context['embedMode'] ?? EntryEmbedMode::NONE;

        return new StreamFeed($links, $entries, $data, $embedMode);
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

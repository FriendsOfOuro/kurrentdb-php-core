<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class EntryDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private DenormalizerInterface $linkDenormalizer,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Entry
    {
        $links = [];
        if (isset($data['links']) && is_array($data['links'])) {
            foreach ($data['links'] as $linkData) {
                $links[] = $this->linkDenormalizer->denormalize($linkData, Link::class);
            }
        }

        return new Entry(
            $links,
            $data,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Entry::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Entry::class => true,
        ];
    }
}

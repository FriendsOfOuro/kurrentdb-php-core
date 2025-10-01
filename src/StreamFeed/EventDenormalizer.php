<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use KurrentDB\ValueObjects\Identity\UUID;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class EventDenormalizer implements DenormalizerInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Event
    {
        // Handle empty string metadata from KurrentDB API
        $metadata = $data['metadata'] ?? null;
        if ('' === $metadata || [] === $metadata) {
            $metadata = null;
        }

        // Handle eventId
        $eventId = null;
        if (isset($data['eventId']) && '' !== $data['eventId']) {
            $eventId = UUID::fromNative($data['eventId']);
        }

        return new Event(
            $data['eventType'],
            (int) $data['eventNumber'],
            $data['data'],
            $metadata,
            $eventId
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Event::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Event::class => true,
        ];
    }
}

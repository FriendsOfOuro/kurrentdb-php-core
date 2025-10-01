<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use KurrentDB\ValueObjects\Identity\UUID;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class EventDenormalizer implements DenormalizerInterface
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Event
    {
        // Handle both wrapped (from readEvent) and unwrapped (from embedded feed) formats
        $content = isset($data['content']) ? $data['content'] : $data;

        $eventType = $content['eventType'];
        $version = (int) $content['eventNumber'];
        $eventData = $content['data'];
        $metadata = (empty($content['metadata'])) ? null : $content['metadata'];
        $eventId = (empty($content['eventId']) ? null : UUID::fromNative($content['eventId']));

        return new Event($eventType, $version, $eventData, $metadata, $eventId);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (Event::class !== $type) {
            return false;
        }

        // Check if data has the required structure
        if (!is_array($data)) {
            return false;
        }

        $content = isset($data['content']) ? $data['content'] : $data;

        return isset($content['eventType'], $content['eventNumber'], $content['data']);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Event::class => true,
        ];
    }
}

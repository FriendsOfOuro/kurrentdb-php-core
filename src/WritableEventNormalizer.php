<?php

declare(strict_types=1);

namespace KurrentDB;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class WritableEventNormalizer implements NormalizerInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return array{eventId: string, eventType: string, data: array<string, mixed>, metadata: array<string, mixed>}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        \assert($object instanceof WritableEvent);

        return [
            'eventId' => $object->uuid->toNative(),
            'eventType' => $object->type,
            'data' => $object->data,
            'metadata' => $object->metadata,
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof WritableEvent;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            WritableEvent::class => true,
        ];
    }
}

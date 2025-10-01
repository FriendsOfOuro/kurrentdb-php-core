<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class StreamFeedFactory implements StreamFeedFactoryInterface
{
    public function __construct(
        private DenormalizerInterface $denormalizer,
    ) {
    }

    /**
     * @param array<string, mixed> $json
     */
    public function create(
        array $json,
        EntryEmbedMode $embedMode = EntryEmbedMode::NONE,
    ): StreamFeed {
        return $this->denormalizer->denormalize(
            $json,
            StreamFeed::class,
            context: ['embedMode' => $embedMode]
        );
    }
}

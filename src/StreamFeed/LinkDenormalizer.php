<?php

declare(strict_types=1);

namespace KurrentDB\StreamFeed;

use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class LinkDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private UriFactoryInterface $uriFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Link
    {
        return new Link(
            LinkRelation::from($data['relation']),
            $this->uriFactory->createUri($data['uri']),
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Link::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Link::class => true,
        ];
    }
}

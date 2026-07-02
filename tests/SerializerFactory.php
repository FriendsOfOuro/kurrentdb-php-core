<?php

declare(strict_types=1);

namespace KurrentDB\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use KurrentDB\StreamFeed\EntryDenormalizer;
use KurrentDB\StreamFeed\EventDenormalizer;
use KurrentDB\StreamFeed\LinkDenormalizer;
use KurrentDB\StreamFeed\StreamFeedDenormalizer;
use KurrentDB\WritableEventNormalizer;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

final class SerializerFactory
{
    public static function create(?UriFactoryInterface $uriFactory = null): SerializerInterface
    {
        $uriFactory ??= new HttpFactory();

        $linkDenormalizer = new LinkDenormalizer($uriFactory);
        $eventDenormalizer = new EventDenormalizer();
        $entryDenormalizer = new EntryDenormalizer($linkDenormalizer, $eventDenormalizer);
        $streamFeedDenormalizer = new StreamFeedDenormalizer($linkDenormalizer, $entryDenormalizer);
        $writableEventNormalizer = new WritableEventNormalizer();

        return new Serializer(
            [
                $writableEventNormalizer,
                $linkDenormalizer,
                $entryDenormalizer,
                $streamFeedDenormalizer,
                $eventDenormalizer,
                new ObjectNormalizer(),
            ],
            [new JsonEncoder()]
        );
    }
}

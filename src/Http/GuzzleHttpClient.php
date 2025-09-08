<?php

namespace KurrentDB\Http;

use Exception as PhpException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\PublicCacheStrategy;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final readonly class GuzzleHttpClient implements HttpClientInterface
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?: new Client([
            'handler' => new CurlMultiHandler(),
        ]);
    }

    public static function withFilesystemCache(string $path): self
    {
        return self::withPsr6Cache(
            new FilesystemAdapter(directory: $path)
        );
    }

    public static function withApcCache(): self
    {
        return self::withPsr6Cache(
            new ApcuAdapter()
        );
    }

    public static function withPsr6Cache(CacheItemPoolInterface $pool): self
    {
        $stack = new HandlerStack(new CurlMultiHandler());

        $stack->push(
            new CacheMiddleware(new PublicCacheStrategy(new Psr6CacheStorage($pool))),
            'cache'
        );

        $client = new Client([
            'handler' => $stack,
        ]);

        return new self($client);
    }

    public function sendRequestBatch(array $requests): array
    {
        $responses = Pool::batch(
            $this->client,
            $requests
        );

        foreach ($responses as $response) {
            if ($response instanceof PhpException) {
                throw $response;
            }
        }

        return $responses;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->client->send($request);
        } catch (GuzzleClientException $e) {
            throw new Exception\ClientException($e->getMessage(), $e->getCode(), $e);
        } catch (GuzzleRequestException $e) {
            throw new Exception\RequestException($e->getMessage(), $e->getCode(), $e);
        }
    }
}

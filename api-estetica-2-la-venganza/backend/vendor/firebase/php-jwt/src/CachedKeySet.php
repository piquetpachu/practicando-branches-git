<?php

namespace Firebase\JWT;

use ArrayAccess;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use RuntimeException;
use UnexpectedValueException;

/**
 * @implements ArrayAccess<string, Key>
 */
class CachedKeySet implements ArrayAccess
{
    /**
     * @var string
     */
    private $jwksUri;
    /**
     * @var ClientInterface
     */
    private $httpClient;
    /**
     * @var RequestFactoryInterface
     */
    private $httpFactory;
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;
    /**
     * @var ?int
     */
    private $expiresAfter;
    /**
     * @var ?CacheItemInterface
     */
    private $cacheItem;
    /**
     * @var array<string, array<mixed>>
     */
    private $keySet;
    /**
     * @var string
     */
    private $cacheKey;
    /**
     * @var string
     */
    private $cacheKeyPrefix = 'jwks';
    /**
     * @var int
     */
    private $maxKeyLength = 64;
    /**
     * @var bool
     */
    private $rateLimit;
    /**
     * @var string
     */
    private $rateLimitCacheKey;
    /**
     * @var int
     */
    private $maxCallsPerMinute = 10;
    /**
     * @var string|null
     */
    private $defaultAlg;

    /**
     * Initializes a CachedKeySet instance for managing and caching JWKS keys from a remote URI.
     *
     * @param string $jwksUri The URI of the JWKS endpoint.
     * @param ClientInterface $httpClient PSR-18 HTTP client for fetching JWKS data.
     * @param RequestFactoryInterface $httpFactory PSR-17 request factory for creating HTTP requests.
     * @param CacheItemPoolInterface $cache PSR-6 cache pool for storing key sets and rate limit data.
     * @param int|null $expiresAfter Optional cache expiration time in seconds.
     * @param bool $rateLimit Whether to enable rate limiting for JWKS fetches.
     * @param string|null $defaultAlg Optional default algorithm to use when parsing keys.
     */
    public function __construct(
        string $jwksUri,
        ClientInterface $httpClient,
        RequestFactoryInterface $httpFactory,
        CacheItemPoolInterface $cache,
        ?int $expiresAfter = null,
        bool $rateLimit = false,
        ?string $defaultAlg = null
    ) {
        $this->jwksUri = $jwksUri;
        $this->httpClient = $httpClient;
        $this->httpFactory = $httpFactory;
        $this->cache = $cache;
        $this->expiresAfter = $expiresAfter;
        $this->rateLimit = $rateLimit;
        $this->defaultAlg = $defaultAlg;
        $this->setCacheKeys();
    }

    /****
     * Retrieves the cryptographic key associated with the specified key ID.
     *
     * @param string $keyId The key ID to retrieve.
     * @return Key The parsed cryptographic key corresponding to the given key ID.
     * @throws OutOfBoundsException If the key ID does not exist in the key set.
     */
    public function offsetGet($keyId): Key
    {
        if (!$this->keyIdExists($keyId)) {
            throw new OutOfBoundsException('Key ID not found');
        }
        return JWK::parseKey($this->keySet[$keyId], $this->defaultAlg);
    }

    /**
     * Determines whether a key with the specified key ID exists in the key set.
     *
     * Checks the cached key set for the given key ID, fetching and caching keys from the JWKS URI if necessary.
     *
     * @param string $keyId The key ID to check for existence.
     * @return bool True if the key exists, false otherwise.
     */
    public function offsetExists($keyId): bool
    {
        return $this->keyIdExists($keyId);
    }

    /****
     * Disallows setting keys directly in the key set.
     *
     * @throws LogicException Always thrown, as direct key assignment is not supported.
     */
    public function offsetSet($offset, $value): void
    {
        throw new LogicException('Method not implemented');
    }

    /****
     * Throws an exception as unsetting keys is not supported.
     *
     * @throws LogicException Always thrown to indicate this operation is not implemented.
     */
    public function offsetUnset($offset): void
    {
        throw new LogicException('Method not implemented');
    }

    /**
     * Parses a JWKS JSON string and returns an associative array of keys indexed by key ID.
     *
     * Decodes the provided JWKS JSON, validates the presence and non-emptiness of the "keys" member,
     * and constructs an array where each key is indexed by its "kid" value or by its array index if "kid" is missing.
     *
     * @param string $jwks JWKS JSON string to parse.
     * @return array<mixed> Associative array of keys indexed by key ID.
     * @throws UnexpectedValueException If the "keys" member is missing.
     * @throws InvalidArgumentException If the "keys" array is empty.
     */
    private function formatJwksForCache(string $jwks): array
    {
        $jwks = json_decode($jwks, true);

        if (!isset($jwks['keys'])) {
            throw new UnexpectedValueException('"keys" member must exist in the JWK Set');
        }

        if (empty($jwks['keys'])) {
            throw new InvalidArgumentException('JWK Set did not contain any keys');
        }

        $keys = [];
        foreach ($jwks['keys'] as $k => $v) {
            $kid = isset($v['kid']) ? $v['kid'] : $k;
            $keys[(string) $kid] = $v;
        }

        return $keys;
    }

    /**
     * Checks if a key with the specified key ID exists in the key set.
     *
     * Attempts to load the key set from cache; if not found, optionally fetches the JWKS from the remote URI (subject to rate limiting), parses and caches the result, and checks for the key ID.
     *
     * @param string $keyId The key ID to check for existence.
     * @return bool True if the key ID exists in the key set; false otherwise.
     * @throws UnexpectedValueException If the JWKS fetch returns a non-200 HTTP response.
     */
    private function keyIdExists(string $keyId): bool
    {
        if (null === $this->keySet) {
            $item = $this->getCacheItem();
            // Try to load keys from cache
            if ($item->isHit()) {
                // item found! retrieve it
                $this->keySet = $item->get();
                // If the cached item is a string, the JWKS response was cached (previous behavior).
                // Parse this into expected format array<kid, jwk> instead.
                if (\is_string($this->keySet)) {
                    $this->keySet = $this->formatJwksForCache($this->keySet);
                }
            }
        }

        if (!isset($this->keySet[$keyId])) {
            if ($this->rateLimitExceeded()) {
                return false;
            }
            $request = $this->httpFactory->createRequest('GET', $this->jwksUri);
            $jwksResponse = $this->httpClient->sendRequest($request);
            if ($jwksResponse->getStatusCode() !== 200) {
                throw new UnexpectedValueException(
                    \sprintf('HTTP Error: %d %s for URI "%s"',
                        $jwksResponse->getStatusCode(),
                        $jwksResponse->getReasonPhrase(),
                        $this->jwksUri,
                    ),
                    $jwksResponse->getStatusCode()
                );
            }
            $this->keySet = $this->formatJwksForCache((string) $jwksResponse->getBody());

            if (!isset($this->keySet[$keyId])) {
                return false;
            }

            $item = $this->getCacheItem();
            $item->set($this->keySet);
            if ($this->expiresAfter) {
                $item->expiresAfter($this->expiresAfter);
            }
            $this->cache->save($item);
        }

        return true;
    }

    /**
     * Determines if the rate limit for JWKS fetches has been exceeded.
     *
     * Checks and updates the number of JWKS fetch attempts within the current minute using the cache. Returns true if the maximum allowed calls per minute has been surpassed; otherwise, increments the call count and returns false.
     *
     * @return bool True if the rate limit is exceeded, false otherwise.
     */
    private function rateLimitExceeded(): bool
    {
        if (!$this->rateLimit) {
            return false;
        }

        $cacheItem = $this->cache->getItem($this->rateLimitCacheKey);

        $cacheItemData = [];
        if ($cacheItem->isHit() && \is_array($data = $cacheItem->get())) {
            $cacheItemData = $data;
        }

        $callsPerMinute = $cacheItemData['callsPerMinute'] ?? 0;
        $expiry = $cacheItemData['expiry'] ?? new \DateTime('+60 seconds', new \DateTimeZone('UTC'));

        if (++$callsPerMinute > $this->maxCallsPerMinute) {
            return true;
        }

        $cacheItem->set(['expiry' => $expiry, 'callsPerMinute' => $callsPerMinute]);
        $cacheItem->expiresAt($expiry);
        $this->cache->save($cacheItem);
        return false;
    }

    /**
     * Retrieves and caches the main cache item for the JWKS key set.
     *
     * Lazily loads the cache item associated with the JWKS URI, storing it for future access.
     *
     * @return CacheItemInterface The cache item containing the JWKS key set.
     */
    private function getCacheItem(): CacheItemInterface
    {
        if (\is_null($this->cacheItem)) {
            $this->cacheItem = $this->cache->getItem($this->cacheKey);
        }

        return $this->cacheItem;
    }

    /**
     * Generates and assigns sanitized cache keys for the JWKS and rate limiting based on the JWKS URI.
     *
     * Ensures cache keys are valid, prefixed, and within the maximum allowed length. Hashes keys if necessary.
     *
     * @throws RuntimeException If the JWKS URI is empty.
     */
    private function setCacheKeys(): void
    {
        if (empty($this->jwksUri)) {
            throw new RuntimeException('JWKS URI is empty');
        }

        // ensure we do not have illegal characters
        $key = preg_replace('|[^a-zA-Z0-9_\.!]|', '', $this->jwksUri);

        // add prefix
        $key = $this->cacheKeyPrefix . $key;

        // Hash keys if they exceed $maxKeyLength of 64
        if (\strlen($key) > $this->maxKeyLength) {
            $key = substr(hash('sha256', $key), 0, $this->maxKeyLength);
        }

        $this->cacheKey = $key;

        if ($this->rateLimit) {
            // add prefix
            $rateLimitKey = $this->cacheKeyPrefix . 'ratelimit' . $key;

            // Hash keys if they exceed $maxKeyLength of 64
            if (\strlen($rateLimitKey) > $this->maxKeyLength) {
                $rateLimitKey = substr(hash('sha256', $rateLimitKey), 0, $this->maxKeyLength);
            }

            $this->rateLimitCacheKey = $rateLimitKey;
        }
    }
}

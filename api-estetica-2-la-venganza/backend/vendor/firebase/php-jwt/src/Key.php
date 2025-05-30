<?php

namespace Firebase\JWT;

use InvalidArgumentException;
use OpenSSLAsymmetricKey;
use OpenSSLCertificate;
use TypeError;

class Key
{
    /**
     * Initializes a cryptographic key with its associated algorithm.
     *
     * Validates that the provided key material is a non-empty string, resource, OpenSSLAsymmetricKey, or OpenSSLCertificate, and that the algorithm is a non-empty string.
     *
     * @param string|resource|OpenSSLAsymmetricKey|OpenSSLCertificate $keyMaterial The cryptographic key material.
     * @param string $algorithm The algorithm associated with the key.
     *
     * @throws TypeError If the key material is not a valid type.
     * @throws InvalidArgumentException If the key material or algorithm is empty.
     */
    public function __construct(
        private $keyMaterial,
        private string $algorithm
    ) {
        if (
            !\is_string($keyMaterial)
            && !$keyMaterial instanceof OpenSSLAsymmetricKey
            && !$keyMaterial instanceof OpenSSLCertificate
            && !\is_resource($keyMaterial)
        ) {
            throw new TypeError('Key material must be a string, resource, or OpenSSLAsymmetricKey');
        }

        if (empty($keyMaterial)) {
            throw new InvalidArgumentException('Key material must not be empty');
        }

        if (empty($algorithm)) {
            throw new InvalidArgumentException('Algorithm must not be empty');
        }
    }

    /**
     * Returns the algorithm associated with this key.
     *
     * @return string The algorithm name.
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * Retrieves the cryptographic key material associated with this instance.
     *
     * @return string|resource|OpenSSLAsymmetricKey|OpenSSLCertificate The key material, which may be a string, resource, OpenSSLAsymmetricKey, or OpenSSLCertificate.
     */
    public function getKeyMaterial()
    {
        return $this->keyMaterial;
    }
}

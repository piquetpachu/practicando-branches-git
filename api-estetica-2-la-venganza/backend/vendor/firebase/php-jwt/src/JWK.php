<?php

namespace Firebase\JWT;

use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * JSON Web Key implementation, based on this spec:
 * https://tools.ietf.org/html/draft-ietf-jose-json-web-key-41
 *
 * PHP version 5
 *
 * @category Authentication
 * @package  Authentication_JWT
 * @author   Bui Sy Nguyen <nguyenbs@gmail.com>
 * @license  http://opensource.org/licenses/BSD-3-Clause 3-clause BSD
 * @link     https://github.com/firebase/php-jwt
 */
class JWK
{
    private const OID = '1.2.840.10045.2.1';
    private const ASN1_OBJECT_IDENTIFIER = 0x06;
    private const ASN1_SEQUENCE = 0x10; // also defined in JWT
    private const ASN1_BIT_STRING = 0x03;
    private const EC_CURVES = [
        'P-256' => '1.2.840.10045.3.1.7', // Len: 64
        'secp256k1' => '1.3.132.0.10', // Len: 64
        'P-384' => '1.3.132.0.34', // Len: 96
        // 'P-521' => '1.3.132.0.35', // Len: 132 (not supported)
    ];

    // For keys with "kty" equal to "OKP" (Octet Key Pair), the "crv" parameter must contain the key subtype.
    // This library supports the following subtypes:
    private const OKP_SUBTYPES = [
        'Ed25519' => true, // RFC 8037
    ];

    /**
     * Parses a JSON Web Key Set (JWK Set) and returns an associative array of key IDs to Key objects.
     *
     * Validates the presence and non-emptiness of the "keys" member in the JWK Set. Each key is parsed using `parseKey`, and only supported keys are included in the result. Throws exceptions for invalid input or if no supported keys are found.
     *
     * @param array $jwks The JWK Set as an associative array.
     * @param string|null $defaultAlg Optional default algorithm to use if a key does not specify "alg".
     * @return array<string, Key> Associative array mapping key IDs to Key objects.
     * @throws InvalidArgumentException If the JWK Set is empty.
     * @throws UnexpectedValueException If the JWK Set is missing required members or contains no supported keys.
     * @throws DomainException If an OpenSSL error occurs during key parsing.
     */
    public static function parseKeySet(array $jwks, ?string $defaultAlg = null): array
    {
        $keys = [];

        if (!isset($jwks['keys'])) {
            throw new UnexpectedValueException('"keys" member must exist in the JWK Set');
        }

        if (empty($jwks['keys'])) {
            throw new InvalidArgumentException('JWK Set did not contain any keys');
        }

        foreach ($jwks['keys'] as $k => $v) {
            $kid = isset($v['kid']) ? $v['kid'] : $k;
            if ($key = self::parseKey($v, $defaultAlg)) {
                $keys[(string) $kid] = $key;
            }
        }

        if (0 === \count($keys)) {
            throw new UnexpectedValueException('No supported algorithms found in JWK Set');
        }

        return $keys;
    }

    /**
     * Parses an individual JSON Web Key (JWK) and returns a corresponding Key object.
     *
     * Supports RSA, EC (Elliptic Curve), OKP (Octet Key Pair), and symmetric ("oct") key types. Validates required parameters for each key type and rejects private keys. Converts key material to appropriate formats as needed.
     *
     * @param array<mixed> $jwk The JWK associative array to parse.
     * @param string|null $defaultAlg Algorithm to use if the JWK does not specify "alg".
     * @return Key|null The resulting Key object, or null if the key type is unsupported.
     *
     * @throws InvalidArgumentException If the JWK is empty.
     * @throws UnexpectedValueException If required parameters are missing or the JWK is invalid.
     * @throws DomainException If OpenSSL operations fail or an unsupported curve/subtype is encountered.
     */
    public static function parseKey(array $jwk, ?string $defaultAlg = null): ?Key
    {
        if (empty($jwk)) {
            throw new InvalidArgumentException('JWK must not be empty');
        }

        if (!isset($jwk['kty'])) {
            throw new UnexpectedValueException('JWK must contain a "kty" parameter');
        }

        if (!isset($jwk['alg'])) {
            if (\is_null($defaultAlg)) {
                // The "alg" parameter is optional in a KTY, but an algorithm is required
                // for parsing in this library. Use the $defaultAlg parameter when parsing the
                // key set in order to prevent this error.
                // @see https://datatracker.ietf.org/doc/html/rfc7517#section-4.4
                throw new UnexpectedValueException('JWK must contain an "alg" parameter');
            }
            $jwk['alg'] = $defaultAlg;
        }

        switch ($jwk['kty']) {
            case 'RSA':
                if (!empty($jwk['d'])) {
                    throw new UnexpectedValueException('RSA private keys are not supported');
                }
                if (!isset($jwk['n']) || !isset($jwk['e'])) {
                    throw new UnexpectedValueException('RSA keys must contain values for both "n" and "e"');
                }

                $pem = self::createPemFromModulusAndExponent($jwk['n'], $jwk['e']);
                $publicKey = \openssl_pkey_get_public($pem);
                if (false === $publicKey) {
                    throw new DomainException(
                        'OpenSSL error: ' . \openssl_error_string()
                    );
                }
                return new Key($publicKey, $jwk['alg']);
            case 'EC':
                if (isset($jwk['d'])) {
                    // The key is actually a private key
                    throw new UnexpectedValueException('Key data must be for a public key');
                }

                if (empty($jwk['crv'])) {
                    throw new UnexpectedValueException('crv not set');
                }

                if (!isset(self::EC_CURVES[$jwk['crv']])) {
                    throw new DomainException('Unrecognised or unsupported EC curve');
                }

                if (empty($jwk['x']) || empty($jwk['y'])) {
                    throw new UnexpectedValueException('x and y not set');
                }

                $publicKey = self::createPemFromCrvAndXYCoordinates($jwk['crv'], $jwk['x'], $jwk['y']);
                return new Key($publicKey, $jwk['alg']);
            case 'OKP':
                if (isset($jwk['d'])) {
                    // The key is actually a private key
                    throw new UnexpectedValueException('Key data must be for a public key');
                }

                if (!isset($jwk['crv'])) {
                    throw new UnexpectedValueException('crv not set');
                }

                if (empty(self::OKP_SUBTYPES[$jwk['crv']])) {
                    throw new DomainException('Unrecognised or unsupported OKP key subtype');
                }

                if (empty($jwk['x'])) {
                    throw new UnexpectedValueException('x not set');
                }

                // This library works internally with EdDSA keys (Ed25519) encoded in standard base64.
                $publicKey = JWT::convertBase64urlToBase64($jwk['x']);
                return new Key($publicKey, $jwk['alg']);
            case 'oct':
                if (!isset($jwk['k'])) {
                    throw new UnexpectedValueException('k not set');
                }

                return new Key(JWT::urlsafeB64Decode($jwk['k']), $jwk['alg']);
            default:
                break;
        }

        return null;
    }

    /**
     * Converts EC JWK curve name and base64url-encoded x and y coordinates into a PEM-formatted public key.
     *
     * @param string $crv The elliptic curve name (e.g., 'P-256', 'P-384', or 'secp256k1').
     * @param string $x Base64url-encoded x-coordinate of the public key.
     * @param string $y Base64url-encoded y-coordinate of the public key.
     * @return string PEM-formatted EC public key.
     */
    private static function createPemFromCrvAndXYCoordinates(string $crv, string $x, string $y): string
    {
        $pem =
            self::encodeDER(
                self::ASN1_SEQUENCE,
                self::encodeDER(
                    self::ASN1_SEQUENCE,
                    self::encodeDER(
                        self::ASN1_OBJECT_IDENTIFIER,
                        self::encodeOID(self::OID)
                    )
                    . self::encodeDER(
                        self::ASN1_OBJECT_IDENTIFIER,
                        self::encodeOID(self::EC_CURVES[$crv])
                    )
                ) .
                self::encodeDER(
                    self::ASN1_BIT_STRING,
                    \chr(0x00) . \chr(0x04)
                    . JWT::urlsafeB64Decode($x)
                    . JWT::urlsafeB64Decode($y)
                )
            );

        return \sprintf(
            "-----BEGIN PUBLIC KEY-----\n%s\n-----END PUBLIC KEY-----\n",
            wordwrap(base64_encode($pem), 64, "\n", true)
        );
    }

    /**
     * Generates a PEM-formatted RSA public key from base64url-encoded modulus and exponent values.
     *
     * @param string $n The RSA modulus, base64url-encoded.
     * @param string $e The RSA public exponent, base64url-encoded.
     * @return string The PEM-formatted RSA public key.
     */
    private static function createPemFromModulusAndExponent(
        string $n,
        string $e
    ): string {
        $mod = JWT::urlsafeB64Decode($n);
        $exp = JWT::urlsafeB64Decode($e);

        $modulus = \pack('Ca*a*', 2, self::encodeLength(\strlen($mod)), $mod);
        $publicExponent = \pack('Ca*a*', 2, self::encodeLength(\strlen($exp)), $exp);

        $rsaPublicKey = \pack(
            'Ca*a*a*',
            48,
            self::encodeLength(\strlen($modulus) + \strlen($publicExponent)),
            $modulus,
            $publicExponent
        );

        // sequence(oid(1.2.840.113549.1.1.1), null)) = rsaEncryption.
        $rsaOID = \pack('H*', '300d06092a864886f70d0101010500'); // hex version of MA0GCSqGSIb3DQEBAQUA
        $rsaPublicKey = \chr(0) . $rsaPublicKey;
        $rsaPublicKey = \chr(3) . self::encodeLength(\strlen($rsaPublicKey)) . $rsaPublicKey;

        $rsaPublicKey = \pack(
            'Ca*a*',
            48,
            self::encodeLength(\strlen($rsaOID . $rsaPublicKey)),
            $rsaOID . $rsaPublicKey
        );

        return "-----BEGIN PUBLIC KEY-----\r\n" .
            \chunk_split(\base64_encode($rsaPublicKey), 64) .
            '-----END PUBLIC KEY-----';
    }

    /****
     * Encodes an integer length value using DER (Distinguished Encoding Rules) ASN.1 format.
     *
     * Supports encoding lengths up to 4 bytes, as required for DER-encoded structures.
     *
     * @param int $length The length value to encode.
     * @return string The DER-encoded length as a binary string.
     */
    private static function encodeLength(int $length): string
    {
        if ($length <= 0x7F) {
            return \chr($length);
        }

        $temp = \ltrim(\pack('N', $length), \chr(0));

        return \pack('Ca*', 0x80 | \strlen($temp), $temp);
    }

    /****
     * Encodes a value as a DER object with the specified ASN.1 tag.
     *
     * Adds the constructed bit for sequences and encodes the type, length, and value according to DER rules.
     *
     * @param int $type ASN.1 tag type.
     * @param string $value Value to encode.
     * @return string DER-encoded binary string.
     */
    private static function encodeDER(int $type, string $value): string
    {
        $tag_header = 0;
        if ($type === self::ASN1_SEQUENCE) {
            $tag_header |= 0x20;
        }

        // Type
        $der = \chr($tag_header | $type);

        // Length
        $der .= \chr(\strlen($value));

        return $der . $value;
    }

    /**
     * Encodes a dotted-decimal object identifier (OID) string into its binary DER representation.
     *
     * @param string $oid Dotted-decimal OID string (e.g., "1.2.840.113549").
     * @return string DER-encoded binary representation of the OID.
     */
    private static function encodeOID(string $oid): string
    {
        $octets = explode('.', $oid);

        // Get the first octet
        $first = (int) array_shift($octets);
        $second = (int) array_shift($octets);
        $oid = \chr($first * 40 + $second);

        // Iterate over subsequent octets
        foreach ($octets as $octet) {
            if ($octet == 0) {
                $oid .= \chr(0x00);
                continue;
            }
            $bin = '';

            while ($octet) {
                $bin .= \chr(0x80 | ($octet & 0x7f));
                $octet >>= 7;
            }
            $bin[0] = $bin[0] & \chr(0x7f);

            // Convert to big endian if necessary
            if (pack('V', 65534) == pack('L', 65534)) {
                $oid .= strrev($bin);
            } else {
                $oid .= $bin;
            }
        }

        return $oid;
    }
}

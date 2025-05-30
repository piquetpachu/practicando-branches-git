<?php

namespace Firebase\JWT;

use ArrayAccess;
use DateTime;
use DomainException;
use Exception;
use InvalidArgumentException;
use OpenSSLAsymmetricKey;
use OpenSSLCertificate;
use stdClass;
use UnexpectedValueException;

/**
 * JSON Web Token implementation, based on this spec:
 * https://tools.ietf.org/html/rfc7519
 *
 * PHP version 5
 *
 * @category Authentication
 * @package  Authentication_JWT
 * @author   Neuman Vong <neuman@twilio.com>
 * @author   Anant Narayanan <anant@php.net>
 * @license  http://opensource.org/licenses/BSD-3-Clause 3-clause BSD
 * @link     https://github.com/firebase/php-jwt
 */
class JWT
{
    private const ASN1_INTEGER = 0x02;
    private const ASN1_SEQUENCE = 0x10;
    private const ASN1_BIT_STRING = 0x03;

    /**
     * When checking nbf, iat or expiration times,
     * we want to provide some extra leeway time to
     * account for clock skew.
     *
     * @var int
     */
    public static $leeway = 0;

    /**
     * Allow the current timestamp to be specified.
     * Useful for fixing a value within unit testing.
     * Will default to PHP time() value if null.
     *
     * @var ?int
     */
    public static $timestamp = null;

    /**
     * @var array<string, string[]>
     */
    public static $supported_algs = [
        'ES384' => ['openssl', 'SHA384'],
        'ES256' => ['openssl', 'SHA256'],
        'ES256K' => ['openssl', 'SHA256'],
        'HS256' => ['hash_hmac', 'SHA256'],
        'HS384' => ['hash_hmac', 'SHA384'],
        'HS512' => ['hash_hmac', 'SHA512'],
        'RS256' => ['openssl', 'SHA256'],
        'RS384' => ['openssl', 'SHA384'],
        'RS512' => ['openssl', 'SHA512'],
        'EdDSA' => ['sodium_crypto', 'EdDSA'],
    ];

    /****
     * Decodes a JWT string, verifies its signature and claims, and returns the payload as a PHP object.
     *
     * Validates the JWT structure, decodes and parses the header and payload, checks the algorithm and key, verifies the signature, and enforces 'nbf', 'iat', and 'exp' claims with optional leeway. Throws exceptions for malformed tokens, invalid signatures, unsupported algorithms, or invalid time-based claims.
     *
     * @param string $jwt The JWT string to decode.
     * @param Key|ArrayAccess<string,Key>|array<string,Key> $keyOrKeyArray A Key object or an associative array of key IDs to Key objects.
     * @param stdClass|null $headers Optional. If provided, will be populated with the JWT header.
     * @return stdClass The decoded JWT payload as a PHP object.
     * @throws InvalidArgumentException If the provided key or key array is empty or malformed.
     * @throws DomainException If the JWT is malformed.
     * @throws UnexpectedValueException If the JWT is invalid or uses an unsupported algorithm.
     * @throws SignatureInvalidException If signature verification fails.
     * @throws BeforeValidException If the token is used before its 'nbf' or 'iat' claim.
     * @throws ExpiredException If the token has expired according to the 'exp' claim.
     */
    public static function decode(
        string $jwt,
        $keyOrKeyArray,
        ?stdClass &$headers = null
    ): stdClass {
        // Validate JWT
        $timestamp = \is_null(static::$timestamp) ? \time() : static::$timestamp;

        if (empty($keyOrKeyArray)) {
            throw new InvalidArgumentException('Key may not be empty');
        }
        $tks = \explode('.', $jwt);
        if (\count($tks) !== 3) {
            throw new UnexpectedValueException('Wrong number of segments');
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        $headerRaw = static::urlsafeB64Decode($headb64);
        if (null === ($header = static::jsonDecode($headerRaw))) {
            throw new UnexpectedValueException('Invalid header encoding');
        }
        if ($headers !== null) {
            $headers = $header;
        }
        $payloadRaw = static::urlsafeB64Decode($bodyb64);
        if (null === ($payload = static::jsonDecode($payloadRaw))) {
            throw new UnexpectedValueException('Invalid claims encoding');
        }
        if (\is_array($payload)) {
            // prevent PHP Fatal Error in edge-cases when payload is empty array
            $payload = (object) $payload;
        }
        if (!$payload instanceof stdClass) {
            throw new UnexpectedValueException('Payload must be a JSON object');
        }
        $sig = static::urlsafeB64Decode($cryptob64);
        if (empty($header->alg)) {
            throw new UnexpectedValueException('Empty algorithm');
        }
        if (empty(static::$supported_algs[$header->alg])) {
            throw new UnexpectedValueException('Algorithm not supported');
        }

        $key = self::getKey($keyOrKeyArray, property_exists($header, 'kid') ? $header->kid : null);

        // Check the algorithm
        if (!self::constantTimeEquals($key->getAlgorithm(), $header->alg)) {
            // See issue #351
            throw new UnexpectedValueException('Incorrect key for this algorithm');
        }
        if (\in_array($header->alg, ['ES256', 'ES256K', 'ES384'], true)) {
            // OpenSSL expects an ASN.1 DER sequence for ES256/ES256K/ES384 signatures
            $sig = self::signatureToDER($sig);
        }
        if (!self::verify("{$headb64}.{$bodyb64}", $sig, $key->getKeyMaterial(), $header->alg)) {
            throw new SignatureInvalidException('Signature verification failed');
        }

        // Check the nbf if it is defined. This is the time that the
        // token can actually be used. If it's not yet that time, abort.
        if (isset($payload->nbf) && floor($payload->nbf) > ($timestamp + static::$leeway)) {
            $ex = new BeforeValidException(
                'Cannot handle token with nbf prior to ' . \date(DateTime::ISO8601, (int) floor($payload->nbf))
            );
            $ex->setPayload($payload);
            throw $ex;
        }

        // Check that this token has been created before 'now'. This prevents
        // using tokens that have been created for later use (and haven't
        // correctly used the nbf claim).
        if (!isset($payload->nbf) && isset($payload->iat) && floor($payload->iat) > ($timestamp + static::$leeway)) {
            $ex = new BeforeValidException(
                'Cannot handle token with iat prior to ' . \date(DateTime::ISO8601, (int) floor($payload->iat))
            );
            $ex->setPayload($payload);
            throw $ex;
        }

        // Check if this token has expired.
        if (isset($payload->exp) && ($timestamp - static::$leeway) >= $payload->exp) {
            $ex = new ExpiredException('Expired token');
            $ex->setPayload($payload);
            throw $ex;
        }

        return $payload;
    }

    /**
     * Encodes a PHP array payload into a signed JSON Web Token (JWT) string.
     *
     * Builds a JWT header with the specified algorithm and optional key ID and additional header elements, encodes the header and payload as base64url, signs the result, and returns the complete JWT string.
     *
     * @param array<mixed> $payload The payload to include in the JWT.
     * @param string|resource|OpenSSLAsymmetricKey|OpenSSLCertificate $key The cryptographic key used for signing.
     * @param string $alg The signing algorithm to use.
     * @param string|null $keyId Optional key ID to include in the JWT header.
     * @param array<string, string>|null $head Optional additional header elements.
     * @return string The encoded and signed JWT string.
     */
    public static function encode(
        array $payload,
        $key,
        string $alg,
        ?string $keyId = null,
        ?array $head = null
    ): string {
        $header = ['typ' => 'JWT'];
        if (isset($head)) {
            $header = \array_merge($header, $head);
        }
        $header['alg'] = $alg;
        if ($keyId !== null) {
            $header['kid'] = $keyId;
        }
        $segments = [];
        $segments[] = static::urlsafeB64Encode((string) static::jsonEncode($header));
        $segments[] = static::urlsafeB64Encode((string) static::jsonEncode($payload));
        $signing_input = \implode('.', $segments);

        $signature = static::sign($signing_input, $key, $alg);
        $segments[] = static::urlsafeB64Encode($signature);

        return \implode('.', $segments);
    }

    /**
     * Generates a cryptographic signature for a message using the specified key and algorithm.
     *
     * Supports HMAC (HS256, HS384, HS512), RSA (RS256, RS384, RS512), ECDSA (ES256, ES256K, ES384), and EdDSA algorithms.
     *
     * @param string $msg The message to sign.
     * @param string|resource|OpenSSLAsymmetricKey|OpenSSLCertificate $key The signing key, which must be appropriate for the chosen algorithm.
     * @param string $alg The algorithm to use for signing.
     * @return string The generated binary signature.
     * @throws DomainException If the algorithm is unsupported, the key is invalid, or signing fails.
     */
    public static function sign(
        string $msg,
        $key,
        string $alg
    ): string {
        if (empty(static::$supported_algs[$alg])) {
            throw new DomainException('Algorithm not supported');
        }
        list($function, $algorithm) = static::$supported_algs[$alg];
        switch ($function) {
            case 'hash_hmac':
                if (!\is_string($key)) {
                    throw new InvalidArgumentException('key must be a string when using hmac');
                }
                return \hash_hmac($algorithm, $msg, $key, true);
            case 'openssl':
                $signature = '';
                if (!\is_resource($key) && !openssl_pkey_get_private($key)) {
                    throw new DomainException('OpenSSL unable to validate key');
                }
                $success = \openssl_sign($msg, $signature, $key, $algorithm); // @phpstan-ignore-line
                if (!$success) {
                    throw new DomainException('OpenSSL unable to sign data');
                }
                if ($alg === 'ES256' || $alg === 'ES256K') {
                    $signature = self::signatureFromDER($signature, 256);
                } elseif ($alg === 'ES384') {
                    $signature = self::signatureFromDER($signature, 384);
                }
                return $signature;
            case 'sodium_crypto':
                if (!\function_exists('sodium_crypto_sign_detached')) {
                    throw new DomainException('libsodium is not available');
                }
                if (!\is_string($key)) {
                    throw new InvalidArgumentException('key must be a string when using EdDSA');
                }
                try {
                    // The last non-empty line is used as the key.
                    $lines = array_filter(explode("\n", $key));
                    $key = base64_decode((string) end($lines));
                    if (\strlen($key) === 0) {
                        throw new DomainException('Key cannot be empty string');
                    }
                    return sodium_crypto_sign_detached($msg, $key);
                } catch (Exception $e) {
                    throw new DomainException($e->getMessage(), 0, $e);
                }
        }

        throw new DomainException('Algorithm not supported');
    }

    /**
     * Verifies a cryptographic signature for a given message using the specified algorithm and key.
     *
     * Supports HMAC, RSA, ECDSA, and EdDSA algorithms. Returns true if the signature is valid, false if invalid, and throws a DomainException for unsupported algorithms or cryptographic errors.
     *
     * @param string $msg The original message to verify.
     * @param string $signature The signature to validate.
     * @param string|resource|OpenSSLAsymmetricKey|OpenSSLCertificate $keyMaterial The key or certificate used for verification.
     * @param string $alg The algorithm identifier.
     * @return bool True if the signature is valid, false otherwise.
     * @throws DomainException If the algorithm is unsupported, the key is invalid, or a cryptographic error occurs.
     */
    private static function verify(
        string $msg,
        string $signature,
        $keyMaterial,
        string $alg
    ): bool {
        if (empty(static::$supported_algs[$alg])) {
            throw new DomainException('Algorithm not supported');
        }

        list($function, $algorithm) = static::$supported_algs[$alg];
        switch ($function) {
            case 'openssl':
                $success = \openssl_verify($msg, $signature, $keyMaterial, $algorithm); // @phpstan-ignore-line
                if ($success === 1) {
                    return true;
                }
                if ($success === 0) {
                    return false;
                }
                // returns 1 on success, 0 on failure, -1 on error.
                throw new DomainException(
                    'OpenSSL error: ' . \openssl_error_string()
                );
            case 'sodium_crypto':
                if (!\function_exists('sodium_crypto_sign_verify_detached')) {
                    throw new DomainException('libsodium is not available');
                }
                if (!\is_string($keyMaterial)) {
                    throw new InvalidArgumentException('key must be a string when using EdDSA');
                }
                try {
                    // The last non-empty line is used as the key.
                    $lines = array_filter(explode("\n", $keyMaterial));
                    $key = base64_decode((string) end($lines));
                    if (\strlen($key) === 0) {
                        throw new DomainException('Key cannot be empty string');
                    }
                    if (\strlen($signature) === 0) {
                        throw new DomainException('Signature cannot be empty string');
                    }
                    return sodium_crypto_sign_verify_detached($signature, $msg, $key);
                } catch (Exception $e) {
                    throw new DomainException($e->getMessage(), 0, $e);
                }
            case 'hash_hmac':
            default:
                if (!\is_string($keyMaterial)) {
                    throw new InvalidArgumentException('key must be a string when using hmac');
                }
                $hash = \hash_hmac($algorithm, $msg, $keyMaterial, true);
                return self::constantTimeEquals($hash, $signature);
        }
    }

    /**
     * Decodes a JSON string into a PHP object.
     *
     * Returns the decoded object, or throws a DomainException if the input is not valid JSON or if decoding yields null for non-null input.
     *
     * @param string $input JSON string to decode.
     * @return mixed Decoded PHP object or value.
     * @throws DomainException If the input is invalid JSON or decoding fails.
     */
    public static function jsonDecode(string $input)
    {
        $obj = \json_decode($input, false, 512, JSON_BIGINT_AS_STRING);

        if ($errno = \json_last_error()) {
            self::handleJsonError($errno);
        } elseif ($obj === null && $input !== 'null') {
            throw new DomainException('Null result with non-null input');
        }
        return $obj;
    }

    /**
     * Converts a PHP array to a JSON string.
     *
     * @param array<mixed> $input The array to encode.
     * @return string The JSON-encoded representation of the array.
     * @throws DomainException If encoding fails or results in null.
     */
    public static function jsonEncode(array $input): string
    {
        $json = \json_encode($input, \JSON_UNESCAPED_SLASHES);
        if ($errno = \json_last_error()) {
            self::handleJsonError($errno);
        } elseif ($json === 'null') {
            throw new DomainException('Null result with non-null input');
        }
        if ($json === false) {
            throw new DomainException('Provided object could not be encoded to valid JSON');
        }
        return $json;
    }

    /****
     * Decodes a URL-safe base64 encoded string to its original binary form.
     *
     * Converts base64url encoding to standard base64 before decoding.
     *
     * @param string $input URL-safe base64 encoded string.
     * @return string Decoded binary string.
     * @throws InvalidArgumentException If the input contains invalid base64 characters.
     */
    public static function urlsafeB64Decode(string $input): string
    {
        return \base64_decode(self::convertBase64UrlToBase64($input));
    }

    /****
     * Converts a base64url-encoded string to standard base64 encoding with appropriate padding.
     *
     * @param string $input Base64url-encoded string using '-' and '_' instead of '+' and '/', without padding.
     * @return string Standard base64-encoded string using '+' and '/', with '=' padding as needed.
     *
     * @see https://www.rfc-editor.org/rfc/rfc4648
     */
    public static function convertBase64UrlToBase64(string $input): string
    {
        $remainder = \strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= \str_repeat('=', $padlen);
        }
        return \strtr($input, '-_', '+/');
    }

    /****
     * Encodes binary data into a URL-safe base64 string without padding.
     *
     * @param string $input Binary data to encode.
     * @return string URL-safe base64 encoded string without trailing '=' padding.
     */
    public static function urlsafeB64Encode(string $input): string
    {
        return \str_replace('=', '', \strtr(\base64_encode($input), '+/', '-_'));
    }


    /**
     * Retrieves the appropriate Key object based on the provided key or key array and key ID.
     *
     * If a single Key is provided, it is returned directly. If an array or ArrayAccess of keys is provided,
     * the key corresponding to the given key ID (`kid`) is returned. Throws an exception if the key ID is missing or invalid.
     *
     * @param Key|ArrayAccess<string,Key>|array<string,Key> $keyOrKeyArray A single Key or a collection of keys indexed by key ID.
     * @param string|null $kid The key ID used to select the correct key from the collection.
     * @return Key The resolved Key object.
     * @throws UnexpectedValueException If the key ID is missing or does not correspond to a valid key.
     */
    private static function getKey(
        $keyOrKeyArray,
        ?string $kid
    ): Key {
        if ($keyOrKeyArray instanceof Key) {
            return $keyOrKeyArray;
        }

        if (empty($kid) && $kid !== '0') {
            throw new UnexpectedValueException('"kid" empty, unable to lookup correct key');
        }

        if ($keyOrKeyArray instanceof CachedKeySet) {
            // Skip "isset" check, as this will automatically refresh if not set
            return $keyOrKeyArray[$kid];
        }

        if (!isset($keyOrKeyArray[$kid])) {
            throw new UnexpectedValueException('"kid" invalid, unable to lookup correct key');
        }

        return $keyOrKeyArray[$kid];
    }

    /**
     * Compares two strings in constant time to prevent timing attacks.
     *
     * Returns true if the strings are identical in both content and length, false otherwise.
     *
     * @param string $left The string of known length to compare against.
     * @param string $right The user-supplied string.
     * @return bool True if the strings are equal, false otherwise.
     */
    public static function constantTimeEquals(string $left, string $right): bool
    {
        if (\function_exists('hash_equals')) {
            return \hash_equals($left, $right);
        }
        $len = \min(self::safeStrlen($left), self::safeStrlen($right));

        $status = 0;
        for ($i = 0; $i < $len; $i++) {
            $status |= (\ord($left[$i]) ^ \ord($right[$i]));
        }
        $status |= (self::safeStrlen($left) ^ self::safeStrlen($right));

        return ($status === 0);
    }

    /**
     * Throws a DomainException with a descriptive message for a given JSON error code.
     *
     * @param int $errno Error code returned by json_last_error().
     * @throws DomainException Always thrown with a message corresponding to the JSON error.
     */
    private static function handleJsonError(int $errno): void
    {
        $messages = [
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters' //PHP >= 5.3.3
        ];
        throw new DomainException(
            isset($messages[$errno])
            ? $messages[$errno]
            : 'Unknown JSON error: ' . $errno
        );
    }

    /****
     * Returns the byte length of a string, using `mb_strlen` with '8bit' encoding if available.
     *
     * Ensures accurate length calculation for binary or multibyte strings in cryptographic contexts.
     *
     * @param string $str Input string.
     * @return int Number of bytes in the string.
     */
    private static function safeStrlen(string $str): int
    {
        if (\function_exists('mb_strlen')) {
            return \mb_strlen($str, '8bit');
        }
        return \strlen($str);
    }

    /**
     * Converts a raw ECDSA signature (concatenated r and s values) into an ASN.1 DER-encoded sequence.
     *
     * @param string $sig Raw ECDSA signature as a binary string.
     * @return string ASN.1 DER-encoded ECDSA signature.
     */
    private static function signatureToDER(string $sig): string
    {
        // Separate the signature into r-value and s-value
        $length = max(1, (int) (\strlen($sig) / 2));
        list($r, $s) = \str_split($sig, $length);

        // Trim leading zeros
        $r = \ltrim($r, "\x00");
        $s = \ltrim($s, "\x00");

        // Convert r-value and s-value from unsigned big-endian integers to
        // signed two's complement
        if (\ord($r[0]) > 0x7f) {
            $r = "\x00" . $r;
        }
        if (\ord($s[0]) > 0x7f) {
            $s = "\x00" . $s;
        }

        return self::encodeDER(
            self::ASN1_SEQUENCE,
            self::encodeDER(self::ASN1_INTEGER, $r) .
            self::encodeDER(self::ASN1_INTEGER, $s)
        );
    }

    /****
     * Encodes a value as a DER (Distinguished Encoding Rules) object with the specified ASN.1 type.
     *
     * @param int $type ASN.1 tag type for the DER object.
     * @param string $value The value to encode within the DER object.
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
     * Converts an ASN.1 DER-encoded ECDSA signature to a raw concatenated format.
     *
     * Extracts the r and s values from a DER-encoded ECDSA signature and returns them as a single binary string, each padded to the specified key size in bytes.
     *
     * @param string $der DER-encoded ECDSA signature.
     * @param int $keySize Key size in bits.
     * @return string Raw signature as concatenated r and s values, each padded to key size.
     */
    private static function signatureFromDER(string $der, int $keySize): string
    {
        // OpenSSL returns the ECDSA signatures as a binary ASN.1 DER SEQUENCE
        list($offset, $_) = self::readDER($der);
        list($offset, $r) = self::readDER($der, $offset);
        list($offset, $s) = self::readDER($der, $offset);

        // Convert r-value and s-value from signed two's compliment to unsigned
        // big-endian integers
        $r = \ltrim($r, "\x00");
        $s = \ltrim($s, "\x00");

        // Pad out r and s so that they are $keySize bits long
        $r = \str_pad($r, $keySize / 8, "\x00", STR_PAD_LEFT);
        $s = \str_pad($s, $keySize / 8, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }

    /****
     * Decodes a single ASN.1 DER-encoded object from binary data at the specified offset.
     *
     * Parses the DER structure, determines the type and length, and extracts the value for primitive types.
     * For constructed types, the value is returned as null.
     *
     * @param string $der   Binary DER-encoded data.
     * @param int $offset   Offset in the data to start decoding from.
     * @return array{int, string|null} Tuple containing the new offset after reading the object and the decoded value (or null for constructed types).
     */
    private static function readDER(string $der, int $offset = 0): array
    {
        $pos = $offset;
        $size = \strlen($der);
        $constructed = (\ord($der[$pos]) >> 5) & 0x01;
        $type = \ord($der[$pos++]) & 0x1f;

        // Length
        $len = \ord($der[$pos++]);
        if ($len & 0x80) {
            $n = $len & 0x1f;
            $len = 0;
            while ($n-- && $pos < $size) {
                $len = ($len << 8) | \ord($der[$pos++]);
            }
        }

        // Value
        if ($type === self::ASN1_BIT_STRING) {
            $pos++; // Skip the first contents octet (padding indicator)
            $data = \substr($der, $pos, $len - 1);
            $pos += $len - 1;
        } elseif (!$constructed) {
            $data = \substr($der, $pos, $len);
            $pos += $len;
        } else {
            $data = null;
        }

        return [$pos, $data];
    }
}

<?php
namespace Firebase\JWT;

interface JWTExceptionWithPayloadInterface
{
    /**
 * Returns the payload object associated with the exception.
 *
 * @return object The payload that caused this exception.
 */
    public function getPayload(): object;

    /**
 * Sets the payload object associated with the exception.
 *
 * @param object $payload The payload that caused the exception.
 */
    public function setPayload(object $payload): void;
}

<?php

namespace Firebase\JWT;

class ExpiredException extends \UnexpectedValueException implements JWTExceptionWithPayloadInterface
{
    private object $payload;

    /****
     * Attaches a payload object to the exception.
     *
     * @param object $payload The payload to associate with this exception.
     */
    public function setPayload(object $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * Retrieves the payload object associated with this exception.
     *
     * @return object The payload attached to the exception.
     */
    public function getPayload(): object
    {
        return $this->payload;
    }
}

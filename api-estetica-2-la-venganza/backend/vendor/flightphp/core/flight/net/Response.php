<?php

declare(strict_types=1);

namespace flight\net;

use Exception;
use flight\core\EventDispatcher;

/**
 * The Response class represents an HTTP response. The object
 * contains the response headers, HTTP status code, and response
 * body.
 *
 * @license MIT, http://flightphp.com/license
 * @copyright Copyright (c) 2011, Mike Cao <mike@mikecao.com>
 */
class Response
{
    /**
     * Content-Length header.
     */
    public bool $content_length = true;

    /**
     * This is to maintain legacy handling of output buffering
     * which causes a lot of problems. This will be removed
     * in v4
     *
     * @var boolean
     */
    public bool $v2_output_buffering = false;

    /**
     * HTTP status codes
     *
     * @var array<int, ?string> $codes
     */
    public static array $codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',

        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',

        226 => 'IM Used',

        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',

        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',

        426 => 'Upgrade Required',

        428 => 'Precondition Required',
        429 => 'Too Many Requests',

        431 => 'Request Header Fields Too Large',

        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',

        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * HTTP status
     */
    protected int $status = 200;

    /**
     * HTTP response headers
     *
     * @var array<string,int|string|array<int,string>> $headers
     */
    protected array $headers = [];

    /**
     * HTTP response body
     */
    protected string $body = '';

    /**
     * HTTP response sent
     */
    protected bool $sent = false;

    /**
     * These are callbacks that can process the response body before it's sent
     *
     * @var array<int, callable> $responseBodyCallbacks
     */
    protected array $responseBodyCallbacks = [];

    /**
     * Gets or sets the HTTP status code for the response.
     *
     * If called without arguments, returns the current status code. If a code is provided, sets the response status to that code if it is valid.
     *
     * @param int|null $code Optional HTTP status code to set.
     * @return int|$this Current status code if no argument is given, or self for method chaining.
     * @throws Exception If an invalid status code is provided.
     */
    public function status(?int $code = null)
    {
        if ($code === null) {
            return $this->status;
        }

        if (\array_key_exists($code, self::$codes)) {
            $this->status = $code;
        } else {
            throw new Exception('Invalid status code.');
        }

        return $this;
    }

    /**
     * Sets one or more headers for the HTTP response.
     *
     * Accepts either a single header name and value, or an associative array of headers to set multiple at once. Overwrites any existing header with the same name.
     *
     * @param array<string, int|string>|string $name Header name as a string, or an associative array of header names and values.
     * @param ?string $value Header value if setting a single header.
     * @return $this
     */
    public function header($name, ?string $value = null): self
    {
        if (\is_array($name)) {
            foreach ($name as $k => $v) {
                $this->headers[$k] = $v;
            }
        } else {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    /**
     * Retrieves the value of a response header by name, case-insensitively.
     *
     * @param string $name Header name to look up.
     * @return string|null The header value if set, or null if not present.
     */
    public function getHeader(string $name): ?string
    {
        $headers = $this->headers;
        // lowercase all the header keys
        $headers = array_change_key_case($headers, CASE_LOWER);
        return $headers[strtolower($name)] ?? null;
    }

    /****
     * Adds a header or multiple headers to the response.
     *
     * This method is an alias of `header()`. It can be used to add a single header by name and value, or multiple headers by passing an associative array.
     *
     * @param array<string, int|string>|string $name Header name as a string, or an associative array of header names and values.
     * @param ?string $value Header value if setting a single header.
     * @return $this
     */
    public function setHeader($name, ?string $value): self
    {
        return $this->header($name, $value);
    }

    /**
     * Retrieves all HTTP headers set for the response.
     *
     * @return array<string, int|string|array<int, string>> Associative array of header names and their values.
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Returns all headers set in the response.
     *
     * @return array<string, int|string|array<int, string>> Associative array of response headers.
     */
    public function getHeaders(): array
    {
        return $this->headers();
    }

    /****
     * Appends content to the response body, or overwrites it if specified.
     *
     * @param string $str The content to add to the response body.
     * @param bool $overwrite If true, replaces the existing body content before writing.
     * @return $this
     */
    public function write(string $str, bool $overwrite = false): self
    {
        if ($overwrite === true) {
            $this->clearBody();
        }

        $this->body .= $str;

        return $this;
    }

    /**
     * Removes all content from the response body.
     *
     * @return $this The current Response instance for method chaining.
     */
    public function clearBody(): self
    {
        $this->body = '';
        return $this;
    }

    /**
     * Resets the response to its default state, clearing status, headers, body, and output buffer if applicable.
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->status = 200;
        $this->headers = [];
        $this->clearBody();

        // This needs to clear the output buffer if it's on
        if ($this->v2_output_buffering === false && ob_get_length() > 0) {
            ob_clean();
        }

        return $this;
    }

    /**
     * Sets HTTP caching headers based on the provided expiration time.
     *
     * If $expires is false or 0, disables caching by setting appropriate headers. Otherwise, sets the Expires and Cache-Control headers to enable caching until the specified time.
     *
     * @param int|string|false $expires Expiration time as a Unix timestamp, a string parseable by strtotime(), or false/0 to disable caching.
     * @return $this
     */
    public function cache($expires): self
    {
        if ($expires === false || $expires === 0) {
            $this->headers['Expires'] = 'Mon, 26 Jul 1997 05:00:00 GMT';
            $this->headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
            $this->headers['Pragma'] = 'no-cache';
        } else {
            $expires = \is_int($expires) ? $expires : strtotime($expires);
            $this->headers['Expires'] = gmdate('D, d M Y H:i:s', $expires) . ' GMT';
            $this->headers['Cache-Control'] = 'max-age=' . ($expires - time());

            if (isset($this->headers['Pragma']) && $this->headers['Pragma'] === 'no-cache') {
                unset($this->headers['Pragma']);
            }
        }

        return $this;
    }

    /**
     * Sends the HTTP status line and all response headers to the client.
     *
     * Handles both CGI and non-CGI environments, includes the Content-Length header if enabled, and sends all custom headers set on the response.
     *
     * @return $this Self reference.
     */
    public function sendHeaders(): self
    {
        // Send status code header
        if (strpos(\PHP_SAPI, 'cgi') !== false) {
            // @codeCoverageIgnoreStart
            $this->setRealHeader(
                sprintf(
                    'Status: %d %s',
                    $this->status,
                    self::$codes[$this->status]
                ),
                true
            );
            // @codeCoverageIgnoreEnd
        } else {
            $this->setRealHeader(
                sprintf(
                    '%s %d %s',
                    $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1',
                    $this->status,
                    self::$codes[$this->status]
                ),
                true,
                $this->status
            );
        }

        if ($this->content_length === true) {
            // Send content length
            $length = $this->getContentLength();

            if ($length > 0) {
                $this->setHeader('Content-Length', (string) $length);
            }
        }

        // Send other headers
        foreach ($this->headers as $field => $value) {
            if (\is_array($value)) {
                foreach ($value as $v) {
                    $this->setRealHeader($field . ': ' . $v, false);
                }
            } else {
                $this->setRealHeader($field . ': ' . $value);
            }
        }

        return $this;
    }

    /**
     * Sends a raw HTTP header string using PHP's header() function.
     *
     * @param string $header_string The complete header string to send.
     * @param bool $replace Whether to replace a previous similar header (default true).
     * @param int $response_code Optional HTTP response code to send.
     * @return self
     */
    public function setRealHeader(string $header_string, bool $replace = true, int $response_code = 0): self
    {
        header($header_string, $replace, $response_code);
        return $this;
    }

    /**
     * Returns the byte length of the response body.
     *
     * Uses `mb_strlen` with 'latin1' encoding if the `mbstring` extension is available; otherwise, falls back to `strlen`.
     *
     * @return int The length of the response body in bytes.
     */
    public function getContentLength(): int
    {
        return \extension_loaded('mbstring') ?
            mb_strlen($this->body, 'latin1') :
            \strlen($this->body);
    }

    /**
     * Returns the current response body as a string.
     *
     * @return string The response body content.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /****
     * Returns whether the response has already been sent.
     *
     * @return bool True if the response has been sent, false otherwise.
     */
    public function sent(): bool
    {
        return $this->sent;
    }

    /**
     * Sets the response as sent to prevent further modifications or output.
     */
    public function markAsSent(): void
    {
        $this->sent = true;
    }

    /**
     * Sends the HTTP response, including headers and body, and triggers the response sent event.
     *
     * If output buffering is enabled, clears the output buffer before sending. Processes response body callbacks if legacy buffering is off. Marks the response as sent and triggers the 'flight.response.sent' event with timing information.
     */
    public function send(): void
    {
        // legacy way of handling this
        if ($this->v2_output_buffering === true) {
            if (ob_get_length() > 0) {
                ob_end_clean(); // @codeCoverageIgnore
            }
        }

        $start = microtime(true);
        // Only for the v3 output buffering.
        if ($this->v2_output_buffering === false) {
            $this->processResponseCallbacks();
        }

        if ($this->headersSent() === false) {
            $this->sendHeaders();
        }

        echo $this->body;
        $this->sent = true;

        EventDispatcher::getInstance()->trigger('flight.response.sent', $this, microtime(true) - $start);
    }

    /****
     * Checks if HTTP headers have already been sent.
     *
     * @return bool True if headers have been sent, false otherwise.
     */
    public function headersSent(): bool
    {
        return headers_sent();
    }

    /****
     * Registers a callback to modify the response body before it is sent.
     *
     * Callbacks are executed in the order they are added, allowing for sequential processing of the response body.
     *
     * @param callable $callback A function that receives and returns the response body string.
     */
    public function addResponseBodyCallback(callable $callback): void
    {
        $this->responseBodyCallbacks[] = $callback;
    }

    /**
     * Applies all registered response body callbacks to the response body in sequence.
     *
     * Each callback receives the current body content and its return value replaces the body for the next callback.
     */
    protected function processResponseCallbacks(): void
    {
        foreach ($this->responseBodyCallbacks as $callback) {
            $this->body = $callback($this->body);
        }
    }

    /**
     * Sends the specified file as a downloadable response to the client.
     *
     * Sets appropriate headers for file transfer, outputs the file content, and terminates execution unless running under PHPUnit testing.
     *
     * @param string $filePath Absolute or relative path to the file to be downloaded.
     * @throws Exception If the file does not exist.
     */
    public function downloadFile(string $filePath): void
    {
        if (file_exists($filePath) === false) {
            throw new Exception("$filePath cannot be found.");
        }

        $fileSize = filesize($filePath);

        $mimeType = mime_content_type($filePath);
        $mimeType = $mimeType !== false ? $mimeType : 'application/octet-stream';

        $this->send();
        $this->setRealHeader('Content-Description: File Transfer');
        $this->setRealHeader('Content-Type: ' . $mimeType);
        $this->setRealHeader('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        $this->setRealHeader('Expires: 0');
        $this->setRealHeader('Cache-Control: must-revalidate');
        $this->setRealHeader('Pragma: public');
        $this->setRealHeader('Content-Length: ' . $fileSize);

        // // Clear the output buffer
        ob_clean();
        flush();

        // // Read the file and send it to the output buffer
        readfile($filePath);
        if (empty(getenv('PHPUNIT_TEST'))) {
            exit; // @codeCoverageIgnore
        }
    }
}

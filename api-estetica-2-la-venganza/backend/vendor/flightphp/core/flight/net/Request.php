<?php

declare(strict_types=1);

namespace flight\net;

use flight\util\Collection;

/**
 * The Request class represents an HTTP request. Data from
 * all the super globals $_GET, $_POST, $_COOKIE, and $_FILES
 * are stored and accessible via the Request object.
 *
 * @license MIT, http://flightphp.com/license
 * @copyright Copyright (c) 2011, Mike Cao <mike@mikecao.com>
 *
 * The default request properties are:
 *
 *   - **url** - The URL being requested
 *   - **base** - The parent subdirectory of the URL
 *   - **method** - The request method (GET, POST, PUT, DELETE)
 *   - **referrer** - The referrer URL
 *   - **ip** - IP address of the client
 *   - **ajax** - Whether the request is an AJAX request
 *   - **scheme** - The server protocol (http, https)
 *   - **user_agent** - Browser information
 *   - **type** - The content type
 *   - **length** - The content length
 *   - **query** - Query string parameters
 *   - **data** - Post parameters
 *   - **cookies** - Cookie parameters
 *   - **files** - Uploaded files
 *   - **secure** - Connection is secure
 *   - **accept** - HTTP accept parameters
 *   - **proxy_ip** - Proxy IP address of the client
 */
class Request
{
    /**
     * URL being requested
     */
    public string $url;

    /**
     * Parent subdirectory of the URL
     */
    public string $base;

    /**
     * Request method (GET, POST, PUT, DELETE)
     */
    public string $method;

    /**
     * Referrer URL
     */
    public string $referrer;

    /**
     * IP address of the client
     */
    public string $ip;

    /**
     * Whether the request is an AJAX request
     */
    public bool $ajax;

    /**
     * Server protocol (http, https)
     */
    public string $scheme;

    /**
     * Browser information
     */
    public string $user_agent;

    /**
     * Content type
     */
    public string $type;

    /**
     * Content length
     */
    public int $length;

    /**
     * Query string parameters
     */
    public Collection $query;

    /**
     * Post parameters
     */
    public Collection $data;

    /**
     * Cookie parameters
     */
    public Collection $cookies;

    /**
     * Uploaded files
     */
    public Collection $files;

    /**
     * Whether the connection is secure
     */
    public bool $secure;

    /**
     * HTTP accept parameters
     */
    public string $accept;

    /**
     * Proxy IP address of the client
     */
    public string $proxy_ip;

    /**
     * HTTP host name
     */
    public string $host;

    /**
     * Stream path for where to pull the request body from
     */
    private string $stream_path = 'php://input';

    /**
     * Raw HTTP request body
     */
    public string $body = '';

    /**
     * Initializes a new Request object with HTTP request data.
     *
     * If no configuration array is provided, populates properties from PHP superglobals and server variables, including URL, method, headers, parameters, cookies, files, and client metadata.
     *
     * @param array<string, mixed> $config Optional request configuration to override default values.
     */
    public function __construct(array $config = [])
    {
        // Default properties
        if (empty($config)) {
            $config = [
                'url'        => str_replace('@', '%40', self::getVar('REQUEST_URI', '/')),
                'base'       => str_replace(['\\', ' '], ['/', '%20'], \dirname(self::getVar('SCRIPT_NAME'))),
                'method'     => self::getMethod(),
                'referrer'   => self::getVar('HTTP_REFERER'),
                'ip'         => self::getVar('REMOTE_ADDR'),
                'ajax'       => self::getVar('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest',
                'scheme'     => self::getScheme(),
                'user_agent' => self::getVar('HTTP_USER_AGENT'),
                'type'       => self::getVar('CONTENT_TYPE'),
                'length'     => intval(self::getVar('CONTENT_LENGTH', 0)),
                'query'      => new Collection($_GET),
                'data'       => new Collection($_POST),
                'cookies'    => new Collection($_COOKIE),
                'files'      => new Collection($_FILES),
                'secure'     => self::getScheme() === 'https',
                'accept'     => self::getVar('HTTP_ACCEPT'),
                'proxy_ip'   => self::getProxyIpAddress(),
                'host'       => self::getVar('HTTP_HOST'),
            ];
        }

        $this->init($config);
    }

    /**
     * Initializes the request object with provided properties and normalizes request data.
     *
     * Sets request properties from the given array, adjusts the URL relative to the base path, merges URL query parameters into the query collection, and parses the request body for JSON or URL-encoded data as appropriate for the HTTP method.
     *
     * @param array<string, mixed> $properties Associative array of request properties to set.
     * @return self The initialized request instance.
     */
    public function init(array $properties = []): self
    {
        // Set all the defined properties
        foreach ($properties as $name => $value) {
            $this->{$name} = $value;
        }

        // Get the requested URL without the base directory
        // This rewrites the url in case the public url and base directories match
        // (such as installing on a subdirectory in a web server)
        // @see testInitUrlSameAsBaseDirectory
        if ($this->base !== '/' && $this->base !== '' && strpos($this->url, $this->base) === 0) {
            $this->url = substr($this->url, \strlen($this->base));
        }

        // Default url
        if (empty($this->url) === true) {
            $this->url = '/';
        } else {
            // Merge URL query parameters with $_GET
            $_GET = array_merge($_GET, self::parseQuery($this->url));

            $this->query->setData($_GET);
        }

        // Check for JSON input
        if (strpos($this->type, 'application/json') === 0) {
            $body = $this->getBody();
            if ($body !== '') {
                $data = json_decode($body, true);
                if (is_array($data) === true) {
                    $this->data->setData($data);
                }
            }
            // Check PUT, PATCH, DELETE for application/x-www-form-urlencoded data
        } elseif (in_array($this->method, ['PUT', 'DELETE', 'PATCH'], true) === true) {
            $body = $this->getBody();
            if ($body !== '') {
                $data = [];
                parse_str($body, $data);
                $this->data->setData($data);
            }
        }

        return $this;
    }

    /****
     * Returns the raw HTTP request body.
     *
     * For POST, PUT, DELETE, and PATCH requests, reads the body from the input stream if not already cached.
     *
     * @return string The raw HTTP request body.
     */
    public function getBody(): string
    {
        $body = $this->body;

        if ($body !== '') {
            return $body;
        }

        $method = $this->method ?? self::getMethod();

        if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE' || $method === 'PATCH') {
            $body = file_get_contents($this->stream_path);
        }

        $this->body = $body;

        return $body;
    }

    /**
     * Returns the HTTP request method, supporting overrides via headers or parameters.
     *
     * Checks for the `HTTP_X_HTTP_METHOD_OVERRIDE` header or `_method` request parameter to allow method overriding, falling back to the `REQUEST_METHOD` server variable.
     *
     * @return string The HTTP method in uppercase (e.g., 'GET', 'POST', 'PUT').
     */
    public static function getMethod(): string
    {
        $method = self::getVar('REQUEST_METHOD', 'GET');

        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) === true) {
            $method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
        } elseif (isset($_REQUEST['_method']) === true) {
            $method = $_REQUEST['_method'];
        }

        return strtoupper($method);
    }

    /****
     * Attempts to determine the client's real public IP address by checking common proxy headers.
     *
     * Checks various HTTP headers that may contain the originating IP address when the request passes through proxies or load balancers. Returns the first valid public IP found, or an empty string if none are available.
     *
     * @return string The detected public IP address, or an empty string if not found.
     */
    public static function getProxyIpAddress(): string
    {
        $forwarded = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
        ];

        $flags = \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE;

        foreach ($forwarded as $key) {
            if (\array_key_exists($key, $_SERVER) === true) {
                sscanf($_SERVER[$key], '%[^,]', $ip);
                if (filter_var($ip, \FILTER_VALIDATE_IP, $flags) !== false) {
                    return $ip;
                }
            }
        }

        return '';
    }

    /**
     * Retrieves a value from the $_SERVER superglobal, returning a default if the variable is not set.
     *
     * @param string $var Name of the server variable to retrieve.
     * @param mixed $default Value to return if the server variable is not set.
     * @return mixed The value of the server variable, or the default if not present.
     */
    public static function getVar(string $var, $default = '')
    {
        return $_SERVER[$var] ?? $default;
    }

    /**
     * Retrieves the value of a specific HTTP header from the request.
     *
     * Header names are case-insensitive and may be provided in any format. If the header is not present, the specified default value is returned.
     *
     * @param string $header Name of the HTTP header.
     * @param string $default Value to return if the header is not found.
     * @return string The header value or the default if not present.
     */
    public static function getHeader(string $header, $default = ''): string
    {
        $header = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        return self::getVar($header, $default);
    }

    /**
     * Retrieves all HTTP request headers as an associative array.
     *
     * @return array<string, string|int> An array of header names and their corresponding values.
     */
    public static function getHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                // converts headers like HTTP_CUSTOM_HEADER to Custom-Header
                $key = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    /****
     * Retrieves the value of a specific HTTP header.
     *
     * This is an alias for getHeader(). Returns the header value if present, or the provided default if not found.
     *
     * @param string $header Name of the HTTP header (case-insensitive).
     * @param string $default Value to return if the header is not set.
     * @return string The header value or the default.
     */
    public static function header(string $header, $default = '')
    {
        return self::getHeader($header, $default);
    }

    /**
     * Returns all HTTP request headers as an associative array.
     *
     * @return array<string, string|int> Associative array of header names and their values.
     */
    public static function headers(): array
    {
        return self::getHeaders();
    }

    /****
     * Returns the complete request URL, including scheme, host, and path.
     *
     * @return string The full URL of the current HTTP request.
     */
    public function getFullUrl(): string
    {
        return $this->scheme . '://' . $this->host . $this->url;
    }

    /**
     * Returns the base URL consisting of the scheme and host, without a trailing slash.
     *
     * @return string The base URL (e.g., "https://example.com").
     */
    public function getBaseUrl(): string
    {
        return $this->scheme . '://' . $this->host;
    }

    /****
     * Extracts and parses the query string from a URL into an associative array.
     *
     * @param string $url The URL containing the query string to parse.
     * @return array<string, int|string|array<int|string, int|string>> Associative array of query parameters.
     */
    public static function parseQuery(string $url): array
    {
        $params = [];

        $args = parse_url($url);
        if (isset($args['query']) === true) {
            parse_str($args['query'], $params);
        }

        return $params;
    }

    /**
     * Determines the URL scheme ('http' or 'https') for the current request.
     *
     * Checks various server variables to detect if the request was made over HTTPS, including proxy and front-end headers.
     *
     * @return string Returns 'https' if the request is secure, otherwise 'http'.
     */
    public static function getScheme(): string
    {
        if (
            (isset($_SERVER['HTTPS']) === true && strtolower($_SERVER['HTTPS']) === 'on')
            ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) === true && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            ||
            (isset($_SERVER['HTTP_FRONT_END_HTTPS']) === true && $_SERVER['HTTP_FRONT_END_HTTPS'] === 'on')
            ||
            (isset($_SERVER['REQUEST_SCHEME']) === true && $_SERVER['REQUEST_SCHEME'] === 'https')
        ) {
            return 'https';
        }

        return 'http';
    }

    /**
     * Returns uploaded files as `UploadedFile` instances, handling both single and multiple file uploads.
     *
     * @return array<string, UploadedFile|array<UploadedFile>> An associative array where each key corresponds to an input field name and the value is either an `UploadedFile` instance or an array of `UploadedFile` instances for multiple uploads.
     */
    public function getUploadedFiles(): array
    {
        $files = [];
        $correctedFilesArray = $this->reArrayFiles($this->files);
        foreach ($correctedFilesArray as $keyName => $files) {
            foreach ($files as $file) {
                $UploadedFile = new UploadedFile(
                    $file['name'],
                    $file['type'],
                    $file['size'],
                    $file['tmp_name'],
                    $file['error']
                );
                if (count($files) > 1) {
                    $files[$keyName][] = $UploadedFile;
                } else {
                    $files[$keyName] = $UploadedFile;
                }
            }
        }

        return $files;
    }

    /**
     * Normalizes the structure of uploaded files from a Collection to a consistent array format.
     *
     * Converts the nested structure of PHP's uploaded files array into a flat, indexed array for each file input,
     * supporting both single and multiple file uploads.
     *
     * @param Collection $filesCollection Collection of uploaded files, typically from $_FILES.
     * @return array<string, array<int, array<string, mixed>>> An array where each file input name maps to an array of file attribute arrays.
     */
    protected function reArrayFiles(Collection $filesCollection): array
    {

        $fileArray = [];
        foreach ($filesCollection as $fileKeyName => $file) {
            $isMulti = is_array($file['name']) === true && count($file['name']) > 1;
            $fileCount = $isMulti === true ? count($file['name']) : 1;
            $fileKeys = array_keys($file);

            for ($i = 0; $i < $fileCount; $i++) {
                foreach ($fileKeys as $key) {
                    if ($isMulti === true) {
                        $fileArray[$fileKeyName][$i][$key] = $file[$key][$i];
                    } else {
                        $fileArray[$fileKeyName][$i][$key] = $file[$key];
                    }
                }
            }
        }

        return $fileArray;
    }
}

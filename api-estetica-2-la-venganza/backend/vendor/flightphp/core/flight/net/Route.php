<?php

declare(strict_types=1);

namespace flight\net;

/**
 * The Route class is responsible for routing an HTTP request to
 * an assigned callback function. The Router tries to match the
 * requested URL against a series of URL patterns.
 *
 * @license MIT, http://flightphp.com/license
 * @copyright Copyright (c) 2011, Mike Cao <mike@mikecao.com>
 */
class Route
{
    /**
     * URL pattern
     */
    public string $pattern;

    /**
     * Callback function
     *
     * @var mixed
     */
    public $callback;

    /**
     * HTTP methods
     *
     * @var array<int, string>
     */
    public array $methods = [];

    /**
     * Route parameters
     *
     * @var array<int, ?string>
     */
    public array $params = [];

    /**
     * Matching regular expression
     */
    public ?string $regex = null;

    /**
     * URL splat content
     */
    public string $splat = '';

    /**
     * Pass self in callback parameters
     */
    public bool $pass = false;

    /**
     * The alias is a way to identify the route using a simple name ex: 'login' instead of /admin/login
     */
    public string $alias = '';

    /**
     * The middleware to be applied to the route
     *
     * @var array<int, callable|object|string>
     */
    public array $middleware = [];

    /** Whether the response for this route should be streamed. */
    public bool $is_streamed = false;

    /**
     * If this route is streamed, the headers to be sent before the response.
     *
     * @var array<string, mixed>
     */
    public array $streamed_headers = [];

    /**
     * Initializes a new Route instance with a URL pattern, callback, allowed HTTP methods, and optional alias.
     *
     * @param string $pattern The URL pattern to match for this route.
     * @param callable|string $callback The handler to invoke when the route matches.
     * @param array<int, string> $methods List of allowed HTTP methods for this route.
     * @param bool $pass Whether to pass the Route instance to the callback.
     * @param string $alias Optional alias for identifying the route.
     */
    public function __construct(string $pattern, $callback, array $methods, bool $pass, string $alias = '')
    {
        $this->pattern = str_replace('//', '/', $pattern);
        $this->callback = $callback;
        $this->methods = $methods;
        $this->pass = $pass;
        $this->alias = $alias;
    }

    /**
     * Determines if the given URL matches the route's pattern, extracting named parameters and wildcard segments.
     *
     * Supports exact matches, wildcards, and named parameters, with optional case sensitivity. Extracted parameters are stored in the route's `params` property, and wildcard segments in `splat`.
     *
     * @param string $url The requested URL in its original (not URL-decoded) form.
     * @param bool $caseSensitive Whether matching should be case sensitive.
     * @return bool True if the URL matches the route pattern; otherwise, false.
     */
    public function matchUrl(string $url, bool $caseSensitive = false): bool
    {
        // Wildcard or exact match
        if ($this->pattern === '*' || $this->pattern === $url) {
            return true;
        }

        // if the last character of the incoming url is a slash, only allow one trailing slash, not multiple
        if (substr($url, -2) === '//') {
            // remove all trailing slashes, and then add one back.
            $url = rtrim($url, '/') . '/';
        }


        $ids = [];
        $last_char = substr($this->pattern, -1);

        // Get splat
        if ($last_char === '*') {
            $n = 0;
            $len = \strlen($url);
            $count = substr_count($this->pattern, '/');

            for ($i = 0; $i < $len; $i++) {
                if ($url[$i] === '/') {
                    ++$n;
                }

                if ($n === $count) {
                    break;
                }
            }

            $this->splat = urldecode(strval(substr($url, $i + 1)));
        }

        // Build the regex for matching
        $pattern_utf_chars_encoded = preg_replace_callback(
            '#(\\p{L}+)#u',
            static function ($matches) {
                return urlencode($matches[0]);
            },
            $this->pattern
        );
        $regex = str_replace([')', '/*'], [')?', '(/?|/.*?)'], $pattern_utf_chars_encoded);

        $regex = preg_replace_callback(
            '#@([\w]+)(:([^/\(\)]*))?#',
            static function ($matches) use (&$ids) {
                $ids[$matches[1]] = null;
                if (isset($matches[3])) {
                    return '(?P<' . $matches[1] . '>' . $matches[3] . ')';
                }

                return '(?P<' . $matches[1] . '>[^/\?]+)';
            },
            $regex
        );

        $regex .= $last_char === '/' ? '?' : '/?';

        // Attempt to match route and named parameters
        if (!preg_match('#^' . $regex . '(?:\?[\s\S]*)?$#' . (($caseSensitive) ? '' : 'i'), $url, $matches)) {
            return false;
        }

        foreach (array_keys($ids) as $k) {
            $this->params[$k] = (\array_key_exists($k, $matches)) ? urldecode($matches[$k]) : null;
        }

        $this->regex = $regex;

        return true;
    }

    /****
     * Determines if the given HTTP method is allowed for this route.
     *
     * Returns true if the method matches one of the route's allowed methods or if the wildcard '*' is present.
     *
     * @param string $method The HTTP method to check.
     * @return bool True if the method is allowed; otherwise, false.
     */
    public function matchMethod(string $method): bool
    {
        return \count(array_intersect([$method, '*'], $this->methods)) > 0;
    }

    /****
     * Determines whether the provided alias matches the route's alias.
     *
     * @param string $alias The alias to compare.
     * @return bool True if the alias matches; otherwise, false.
     */
    public function matchAlias(string $alias): bool
    {
        return $this->alias === $alias;
    }

    /**
     * Generates a URL by substituting named parameters into the route's pattern.
     *
     * Replaces parameter placeholders in the route pattern with corresponding values from the provided array. Handles optional parameters and removes trailing slashes from the resulting URL.
     *
     * @param array<string, mixed> $params Associative array of parameter values to insert into the URL pattern.
     * @return string The hydrated URL with parameters substituted.
     */
    public function hydrateUrl(array $params = []): string
    {
        $url = preg_replace_callback("/(?:@([\w]+)(?:\:([^\/]+))?\)*)/i", function ($match) use ($params) {
            if (isset($params[$match[1]]) === true) {
                return $params[$match[1]];
            }
        }, $this->pattern);

        // catches potential optional parameter
        $url = str_replace('(/', '/', $url);
        // trim any trailing slashes
        if ($url !== '/') {
            $url = rtrim($url, '/');
        }
        return $url;
    }

    /****
     * Assigns an alias to the route for identification or lookup.
     *
     * @param string $alias The alias to assign to the route.
     * @return self The route instance for method chaining.
     */
    public function setAlias(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Adds middleware to the route.
     *
     * Accepts a single middleware or an array of middleware, and appends them to the route's middleware stack.
     *
     * @param array<int, callable|string>|callable|string $middleware Middleware to add.
     * @return self The route instance for method chaining.
     */
    public function addMiddleware($middleware): self
    {
        if (is_array($middleware) === true) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }
        return $this;
    }

    /**
     * Marks the route's response to be streamed.
     *
     * @return self The current route instance for method chaining.
     */
    public function stream(): self
    {
        $this->is_streamed = true;
        return $this;
    }

    /****
     * Marks the route's response as streamed and sets headers to send before streaming.
     *
     * @param array<string, mixed> $headers Headers to send before the streamed response.
     * @return $this
     */
    public function streamWithHeaders(array $headers): self
    {
        $this->is_streamed = true;
        $this->streamed_headers = $headers;

        return $this;
    }
}

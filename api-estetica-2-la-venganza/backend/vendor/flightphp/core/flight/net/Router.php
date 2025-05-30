<?php

declare(strict_types=1);

namespace flight\net;

use Exception;
use flight\net\Route;

/**
 * The Router class is responsible for routing an HTTP request to
 * an assigned callback function. The Router tries to match the
 * requested URL against a series of URL patterns.
 *
 * @license MIT, http://flightphp.com/license
 * @copyright Copyright (c) 2011, Mike Cao <mike@mikecao.com>
 */
class Router
{
    /**
     * Case sensitive matching.
     */
    public bool $caseSensitive = false;

    /**
     * Mapped routes.
     *
     * @var array<int,Route> $routes
     */
    protected array $routes = [];

    /**
     * The current route that is has been found and executed.
     */
    public ?Route $executedRoute = null;

    /**
     * Pointer to current route.
     */
    protected int $index = 0;

    /**
     * When groups are used, this is mapped against all the routes
     */
    protected string $groupPrefix = '';

    /**
     * Group Middleware
     *
     * @var array<int,mixed>
     */
    protected array $groupMiddlewares = [];

    /**
     * Allowed HTTP methods
     *
     * @var array<int, string>
     */
    protected array $allowedMethods = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'HEAD',
        'OPTIONS'
    ];

    /****
     * Returns all routes currently mapped in the router.
     *
     * @return array<int, Route> List of mapped Route objects.
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Removes all mapped routes from the router.
     */
    public function clear(): void
    {
        $this->routes = [];
    }

    /**
     * Registers a new route by mapping a URL pattern and HTTP methods to a callback.
     *
     * Supports optional HTTP method prefixes in the pattern (e.g., "GET /path"), route grouping with URL prefixing, and assignment of route aliases and middlewares. Returns the created Route instance.
     *
     * @param string $pattern URL pattern, optionally prefixed with HTTP methods (e.g., "GET|POST /users").
     * @param callable|string|array{0: class-string, 1: string} $callback Handler to execute when the route matches.
     * @param bool $pass_route If true, passes the matched Route object to the callback.
     * @param string $route_alias Optional alias for referencing the route.
     * @return Route The created Route instance.
     */
    public function map(string $pattern, $callback, bool $pass_route = false, string $route_alias = ''): Route
    {

        // This means that the route is defined in a group, but the defined route is the base
        // url path. Note the '' in route()
        // Ex: Flight::group('/api', function() {
        //    Flight::route('', function() {});
        // }
        // Keep the space so that it can execute the below code normally
        if ($this->groupPrefix !== '') {
            $url = ltrim($pattern);
        } else {
            $url = trim($pattern);
        }

        $methods = ['*'];

        if (strpos($url, ' ') !== false) {
            [$method, $url] = explode(' ', $url, 2);
            $url = trim($url);
            $methods = explode('|', $method);

            // Add head requests to get methods, should they come in as a get request
            if (in_array('GET', $methods, true) === true && in_array('HEAD', $methods, true) === false) {
                $methods[] = 'HEAD';
            }
        }

        // And this finishes it off.
        if ($this->groupPrefix !== '') {
            $url = rtrim($this->groupPrefix . $url);
        }

        $route = new Route($url, $callback, $methods, $pass_route, $route_alias);

        // to handle group middleware
        foreach ($this->groupMiddlewares as $gm) {
            $route->addMiddleware($gm);
        }

        $this->routes[] = $route;

        return $route;
    }

    /**
     * Registers a route that matches HTTP GET requests for the specified URL pattern.
     *
     * @param string $pattern URL pattern to match.
     * @param callable|string|array{0: class-string, 1: string} $callback Handler for the matched route.
     * @param bool $pass_route Whether to pass the matched route object to the callback.
     * @param string $alias Optional alias for the route.
     * @return Route The created route instance.
     */
    public function get(string $pattern, $callback, bool $pass_route = false, string $alias = ''): Route
    {
        return $this->map('GET ' . $pattern, $callback, $pass_route, $alias);
    }

    /****
     * Registers a route that matches HTTP POST requests for the given URL pattern.
     *
     * @param string $pattern URL pattern to match.
     * @param callable|string|array{0: class-string, 1: string} $callback Handler for the matched route.
     * @param bool $pass_route Whether to pass the matched route object to the callback.
     * @param string $alias Optional alias for the route.
     * @return Route The created route object.
     */
    public function post(string $pattern, $callback, bool $pass_route = false, string $alias = ''): Route
    {
        return $this->map('POST ' . $pattern, $callback, $pass_route, $alias);
    }

    /****
     * Registers a route that responds to HTTP PUT requests for the specified URL pattern.
     *
     * @param string $pattern URL pattern to match.
     * @param callable|string|array{0: class-string, 1: string} $callback Handler for the matched route.
     * @param bool $pass_route Whether to pass the matched route object to the callback.
     * @param string $alias Optional alias for the route.
     * @return Route The created route instance.
     */
    public function put(string $pattern, $callback, bool $pass_route = false, string $alias = ''): Route
    {
        return $this->map('PUT ' . $pattern, $callback, $pass_route, $alias);
    }

    /****
     * Registers a route that responds to HTTP PATCH requests.
     *
     * @param string $pattern URL pattern to match.
     * @param callable|string|array{0: class-string, 1: string} $callback Callback handler for the route.
     * @param bool $pass_route Whether to pass the matched route object to the callback.
     * @param string $alias Optional alias for the route.
     * @return Route The created route instance.
     */
    public function patch(string $pattern, $callback, bool $pass_route = false, string $alias = ''): Route
    {
        return $this->map('PATCH ' . $pattern, $callback, $pass_route, $alias);
    }

    /**
     * Registers a route that responds to HTTP DELETE requests for the specified URL pattern.
     *
     * @return Route The created route instance.
     */
    public function delete(string $pattern, $callback, bool $pass_route = false, string $alias = ''): Route
    {
        return $this->map('DELETE ' . $pattern, $callback, $pass_route, $alias);
    }

    /****
     * Defines a group of routes that share a common URL prefix and optional middlewares.
     *
     * All routes registered within the callback will inherit the specified group prefix and middlewares.
     *
     * @param string $groupPrefix URL prefix applied to all routes in the group (e.g., "/api/v1").
     * @param callable $callback Callback that receives the Router instance for route registration.
     * @param array $groupMiddlewares Middlewares to apply to all routes in the group.
     */
    public function group(string $groupPrefix, callable $callback, array $groupMiddlewares = []): void
    {
        $oldGroupPrefix = $this->groupPrefix;
        $oldGroupMiddlewares = $this->groupMiddlewares;
        $this->groupPrefix .= $groupPrefix;
        $this->groupMiddlewares = array_merge($this->groupMiddlewares, $groupMiddlewares);
        $callback($this);
        $this->groupPrefix = $oldGroupPrefix;
        $this->groupMiddlewares = $oldGroupMiddlewares;
    }

    /****
     * Attempts to match the given HTTP request to a registered route.
     *
     * Iterates through all mapped routes, returning the first route that matches both the request URL and HTTP method. If a route matches the URL but not the method, it is recorded for potential method-not-allowed handling. Returns false if no route matches the request.
     *
     * @param Request $request The HTTP request to match.
     * @return Route|false The matched route, or false if no match is found.
     */
    public function route(Request $request)
    {
        while ($route = $this->current()) {
            $urlMatches = $route->matchUrl($request->url, $this->caseSensitive);
            $methodMatches = $route->matchMethod($request->method);
            if ($urlMatches === true && $methodMatches === true) {
                $this->executedRoute = $route;
                return $route;
                // capture the route but don't execute it. We'll use this in Engine->start() to throw a 405
            } elseif ($urlMatches === true && $methodMatches === false) {
                $this->executedRoute = $route;
            }
            $this->next();
        }

        return false;
    }

    /**
     * Returns the URL corresponding to a given route alias, substituting any provided parameters.
     *
     * If no route with the specified alias exists, throws an exception and suggests the closest matching alias.
     *
     * @param string $alias The route alias to look up.
     * @param array<string, mixed> $params Parameters to substitute into the route URL.
     * @return string The hydrated URL for the matched route alias.
     * @throws Exception If no route with the given alias is found.
     */
    public function getUrlByAlias(string $alias, array $params = []): string
    {
        $potential_aliases = [];
        foreach ($this->routes as $route) {
            $potential_aliases[] = $route->alias;
            if ($route->matchAlias($alias)) {
                // This will make it so the params that already
                // exist in the url will be passed in.
                if (!empty($this->executedRoute->params)) {
                    $params = $params + $this->executedRoute->params;
                }
                return $route->hydrateUrl($params);
            }
        }

        // use a levenshtein to find the closest match and make a recommendation
        $closest_match = '';
        $closest_match_distance = 0;
        foreach ($potential_aliases as $potential_alias) {
            $levenshtein_distance = levenshtein($alias, $potential_alias);
            if ($levenshtein_distance > $closest_match_distance) {
                $closest_match = $potential_alias;
                $closest_match_distance = $levenshtein_distance;
            }
        }

        $exception_message = 'No route found with alias: \'' . $alias . '\'.';
        if ($closest_match !== '') {
            $exception_message .= ' Did you mean \'' . $closest_match . '\'?';
        }

        throw new Exception($exception_message);
    }

    /**
     * Maps a resource controller to RESTful routes with customizable method mappings, aliases, and middleware.
     *
     * Defines standard RESTful routes (index, create, store, show, edit, update, destroy) for a controller class, allowing selective inclusion or exclusion of methods, custom alias base, and group middleware assignment.
     *
     * @param string $pattern URL prefix for the resource routes.
     * @param string $controllerClass Fully qualified controller class name.
     * @param array $options Options to customize route mapping:
     *   - 'only': array of controller methods to include.
     *   - 'except': array of controller methods to exclude.
     *   - 'alias_base': string to use as the base for route aliases.
     *   - 'middleware': array of middleware to apply to the group.
     */
    public function mapResource(
        string $pattern,
        string $controllerClass,
        array $options = []
    ): void {

        $defaultMapping = [
            'index' => 'GET ',
            'create' => 'GET /create',
            'store' => 'POST ',
            'show' => 'GET /@id',
            'edit' => 'GET /@id/edit',
            'update' => 'PUT /@id',
            'destroy' => 'DELETE /@id'
        ];

        // Create a custom alias base
        $aliasBase = trim(basename($pattern), '/');
        if (isset($options['alias_base']) === true) {
            $aliasBase = $options['alias_base'];
        }

        // Only use these controller methods
        if (isset($options['only']) === true) {
            $only = $options['only'];
            $defaultMapping = array_filter($defaultMapping, function ($key) use ($only) {
                return in_array($key, $only, true) === true;
            }, ARRAY_FILTER_USE_KEY);

            // Exclude these controller methods
        } elseif (isset($options['except']) === true) {
            $except = $options['except'];
            $defaultMapping = array_filter($defaultMapping, function ($key) use ($except) {
                return in_array($key, $except, true) === false;
            }, ARRAY_FILTER_USE_KEY);
        }

        // Add group middleware
        $middleware = [];
        if (isset($options['middleware']) === true) {
            $middleware = $options['middleware'];
        }

        $this->group(
            $pattern,
            function (Router $router) use ($controllerClass, $defaultMapping, $aliasBase): void {
                foreach ($defaultMapping as $controllerMethod => $methodPattern) {
                    $router->map(
                        $methodPattern,
                        [$controllerClass, $controllerMethod]
                    )->setAlias($aliasBase . '.' . $controllerMethod);
                }
            },
            $middleware
        );
    }

    /**
     * Resets the internal route pointer to the first route.
     */
    public function rewind(): void
    {
        $this->index = 0;
    }

    /**
     * Determines whether the current route index points to a valid route.
     *
     * @return bool True if there is a route at the current index; otherwise, false.
     */
    public function valid(): bool
    {
        return isset($this->routes[$this->index]);
    }

    /**
     * Returns the currently selected route in the router, or false if none is selected.
     *
     * @return Route|false The current Route object, or false if the index is out of bounds.
     */
    public function current()
    {
        return $this->routes[$this->index] ?? false;
    }

    /**
     * Moves the internal route pointer to the previous route in the list.
     */
    public function previous(): void
    {
        --$this->index;
    }

    /**
     * Advances the internal route pointer to the next route in the list.
     */
    public function next(): void
    {
        ++$this->index;
    }

    /**
     * Resets the internal route pointer to the first route.
     */
    public function reset(): void
    {
        $this->rewind();
    }
}

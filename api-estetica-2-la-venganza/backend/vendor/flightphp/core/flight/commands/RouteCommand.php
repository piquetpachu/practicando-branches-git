<?php

declare(strict_types=1);

namespace flight\commands;

use Flight;
use flight\net\Route;

/**
 * @property-read ?bool $get
 * @property-read ?bool $post
 * @property-read ?bool $delete
 * @property-read ?bool $put
 * @property-read ?bool $patch
 */
class RouteCommand extends AbstractBaseCommand
{
    /****
     * Initializes the RouteCommand with options to filter routes by HTTP method.
     *
     * @param array<string, mixed> $config Configuration array, typically loaded from .runway-config.json.
     */
    public function __construct(array $config)
    {
        parent::__construct('routes', 'Gets all routes for an application', $config);

        $this->option('--get', 'Only return GET requests');
        $this->option('--post', 'Only return POST requests');
        $this->option('--delete', 'Only return DELETE requests');
        $this->option('--put', 'Only return PUT requests');
        $this->option('--patch', 'Only return PATCH requests');
    }

    /**
     * Displays all registered routes in the Flight PHP application, optionally filtered by HTTP method.
     *
     * Retrieves route definitions from the application's index file, collects route details including pattern, methods, alias, streaming status, and middleware, and outputs them in a formatted table. If the `index_root` configuration is missing, an error is displayed and execution stops.
     *
     * @return void
     */
    public function execute()
    {
        $io = $this->app()->io();

        if (isset($this->config['index_root']) === false) {
            $io->error('index_root not set in .runway-config.json', true);
            return;
        }

        $io->bold('Routes', true);

        $cwd = getcwd();

        $index_root = $cwd . '/' . $this->config['index_root'];

        // This makes it so the framework doesn't actually execute
        Flight::map('start', function () {
            return;
        });
        include($index_root);
        $routes = Flight::router()->getRoutes();
        $arrayOfRoutes = [];
        foreach ($routes as $route) {
            if ($this->shouldAddRoute($route) === true) {
                $middlewares = [];
                if (!empty($route->middleware)) {
                    try {
                        $middlewares = array_map(function ($middleware) {
                            $middleware_class_name = explode("\\", get_class($middleware));
                            return preg_match("/^class@anonymous/", end($middleware_class_name)) ? 'Anonymous' : end($middleware_class_name);
                        }, $route->middleware);
                    } catch (\TypeError $e) {
                        $middlewares[] = 'Bad Middleware';
                    } finally {
                        if (is_string($route->middleware) === true) {
                            $middlewares[] = $route->middleware;
                        }
                    }
                }

                $arrayOfRoutes[] = [
                    'Pattern' => $route->pattern,
                    'Methods' => implode(', ', $route->methods),
                    'Alias' => $route->alias ?? '',
                    'Streamed' => $route->is_streamed ? 'Yes' : 'No',
                    'Middleware' => !empty($middlewares) ? implode(",", $middlewares) : '-'
                ];
            }
        }
        $io->table($arrayOfRoutes, [
            'head' => 'boldGreen'
        ]);
    }

    /**
     * Determines whether a route should be included based on the enabled HTTP method filters.
     *
     * Includes the route if no method filters are set, or if the route matches any of the selected HTTP methods (GET, POST, PUT, DELETE, PATCH), including wildcard routes.
     *
     * @param Route $route The route to evaluate for inclusion.
     * @return bool True if the route matches the filter criteria; otherwise, false.
     */
    public function shouldAddRoute(Route $route)
    {
        $boolval = false;

        $showAll = !$this->get && !$this->post && !$this->put && !$this->delete && !$this->patch;
        if ($showAll === true) {
            $boolval = true;
        } else {
            $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
            foreach ($methods as $method) {
                $lowercaseMethod = strtolower($method);
                if (
                    $this->{$lowercaseMethod} === true &&
                    (
                        $route->methods[0] === '*' ||
                        in_array($method, $route->methods, true) === true
                    )
                ) {
                    $boolval = true;
                    break;
                }
            }
        }
        return $boolval;
    }
}

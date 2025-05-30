<?php

declare(strict_types=1);

namespace flight;

use Closure;
use ErrorException;
use Exception;
use flight\core\Dispatcher;
use flight\core\EventDispatcher;
use flight\core\Loader;
use flight\net\Request;
use flight\net\Response;
use flight\net\Router;
use flight\template\View;
use Throwable;
use flight\net\Route;
use Psr\Container\ContainerInterface;

/**
 * The Engine class contains the core functionality of the framework.
 * It is responsible for loading an HTTP request, running the assigned services,
 * and generating an HTTP response.
 *
 * @license MIT, http://flightphp.com/license
 * @copyright Copyright (c) 2011, Mike Cao <mike@mikecao.com>
 *
 * # Core methods
 * @method void start() Starts engine
 * @method void stop() Stops framework and outputs current response
 * @method void halt(int $code = 200, string $message = '', bool $actuallyExit = true) Stops processing and returns a given response.
 *
 * # Class registration
 * @method EventDispatcher eventDispatcher() Gets event dispatcher
 *
 * # Routing
 * @method Route route(string $pattern, callable|string|array{0: class-string, 1: string} $callback, bool $pass_route = false, string $alias = '')
 * Routes a URL to a callback function with all applicable methods
 * @method void group(string $pattern, callable $callback, (class-string|callable|array{0: class-string, 1: string})[] $group_middlewares = [])
 * Groups a set of routes together under a common prefix.
 * @method Route post(string $pattern, callable|string|array{0: class-string, 1: string} $callback, bool $pass_route = false, string $alias = '')
 * Routes a POST URL to a callback function.
 * @method Route put(string $pattern, callable|string|array{0: class-string, 1: string} $callback, bool $pass_route = false, string $alias = '')
 * Routes a PUT URL to a callback function.
 * @method Route patch(string $pattern, callable|string|array{0: class-string, 1: string} $callback, bool $pass_route = false, string $alias = '')
 * Routes a PATCH URL to a callback function.
 * @method Route delete(string $pattern, callable|string|array{0: class-string, 1: string} $callback, bool $pass_route = false, string $alias = '')
 * Routes a DELETE URL to a callback function.
 * @method void resource(string $pattern, class-string $controllerClass, array<string, string|array<string>> $methods = [])
 * Adds standardized RESTful routes for a controller.
 * @method Router router() Gets router
 * @method string getUrl(string $alias) Gets a url from an alias
 *
 * # Views
 * @method void render(string $file, ?array<string,mixed> $data = null, ?string $key = null) Renders template
 * @method View view() Gets current view
 *
 * # Events
 * @method void onEvent(string $event, callable $callback) Registers a callback for an event.
 * @method void triggerEvent(string $event, ...$args) Triggers an event.
 *
 * # Request-Response
 * @method Request request() Gets current request
 * @method Response response() Gets current response
 * @method void error(Throwable $e) Sends an HTTP 500 response for any errors.
 * @method void notFound() Sends an HTTP 404 response when a URL is not found.
 * @method void redirect(string $url, int $code = 303)  Redirects the current request to another URL.
 * @method void json(mixed $data, int $code = 200, bool $encode = true, string $charset = 'utf-8', int $option = 0)
 * Sends a JSON response.
 * @method void jsonHalt(mixed $data, int $code = 200, bool $encode = true, string $charset = 'utf-8', int $option = 0)
 * Sends a JSON response and immediately halts the request.
 * @method void jsonp(mixed $data, string $param = 'jsonp', int $code = 200, bool $encode = true, string $charset = 'utf-8', int $option = 0)
 * Sends a JSONP response.
 *
 * # HTTP methods
 * @method void etag(string $id, ('strong'|'weak') $type = 'strong') Handles ETag HTTP caching.
 * @method void lastModified(int $time) Handles last modified HTTP caching.
 * @method void download(string $filePath) Downloads a file
 *
 * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
 */
class Engine
{
    /**
     * @var array<string> List of methods that can be extended in the Engine class.
     */
    private const MAPPABLE_METHODS = [
        'start',
        'stop',
        'route',
        'halt',
        'error',
        'notFound',
        'render',
        'redirect',
        'etag',
        'lastModified',
        'json',
        'jsonHalt',
        'jsonp',
        'post',
        'put',
        'patch',
        'delete',
        'group',
        'getUrl',
        'download',
        'resource',
        'onEvent',
        'triggerEvent'
    ];

    /** @var array<string, mixed> Stored variables. */
    protected array $vars = [];

    /** Class loader. */
    protected Loader $loader;

    /** Method and class dispatcher. */
    protected Dispatcher $dispatcher;

    /** Event dispatcher. */
    protected EventDispatcher $eventDispatcher;

    /** If the framework has been initialized or not. */
    protected bool $initialized = false;

    /** If the request has been handled or not. */
    protected bool $requestHandled = false;

    /**
     * Constructs the Engine instance, initializing the class loader, dispatcher, and framework state.
     */
    public function __construct()
    {
        $this->loader = new Loader();
        $this->dispatcher = new Dispatcher();
        $this->init();
    }

    /**
     * Dynamically handles calls to unmapped or undefined methods.
     *
     * Delegates the call to a registered callback if available, or loads a mapped class instance. Throws an exception if the method is not mapped.
     *
     * @param string $name The method name being called.
     * @param array<int, mixed> $params Arguments to pass to the callback or loader.
     * @throws Exception If the method is not mapped.
     * @return mixed The result of the callback execution or the loaded class instance.
     */
    public function __call(string $name, array $params)
    {
        $callback = $this->dispatcher->get($name);

        if (\is_callable($callback)) {
            return $this->dispatcher->run($name, $params);
        }

        if (!$this->loader->get($name)) {
            throw new Exception("$name must be a mapped method.");
        }

        $shared = empty($params) || $params[0];

        return $this->loader->load($name, $shared);
    }

    //////////////////
    // Core Methods //
    //////////////////

    /**
     * Initializes or resets the framework state, registers core components, sets default configuration, and prepares error handling and startup hooks.
     *
     * This method sets up the framework for handling HTTP requests by registering essential services (event dispatcher, request, response, router, view), mapping core methods, and applying default configuration values. If the framework was previously initialized, it resets variables and internal loaders. It also configures error and exception handlers and prepares the response and router according to configuration settings.
     */
    public function init(): void
    {
        $initialized = $this->initialized;
        $self = $this;

        if ($initialized) {
            $this->vars = [];
            $this->loader->reset();
            $this->dispatcher->reset();
        }

        // Add this class to Dispatcher
        $this->dispatcher->setEngine($this);

        // Register default components
        $this->map('eventDispatcher', function () {
            return EventDispatcher::getInstance();
        });
        $this->loader->register('request', Request::class);
        $this->loader->register('response', Response::class);
        $this->loader->register('router', Router::class);

        $this->loader->register('view', View::class, [], function (View $view) use ($self) {
            $view->path = $self->get('flight.views.path');
            $view->extension = $self->get('flight.views.extension');
        });

        foreach (self::MAPPABLE_METHODS as $name) {
            $this->dispatcher->set($name, [$this, "_$name"]);
        }

        // Default configuration settings
        $this->set('flight.base_url');
        $this->set('flight.case_sensitive', false);
        $this->set('flight.handle_errors', true);
        $this->set('flight.log_errors', false);
        $this->set('flight.views.path', './views');
        $this->set('flight.views.extension', '.php');
        $this->set('flight.content_length', true);
        $this->set('flight.v2.output_buffering', false);

        // Startup configuration
        $this->before('start', function () use ($self) {
            // Enable error handling
            if ($self->get('flight.handle_errors')) {
                set_error_handler([$self, 'handleError']);
                set_exception_handler([$self, 'handleException']);
            }

            // Set case-sensitivity
            $self->router()->caseSensitive = $self->get('flight.case_sensitive');
            // Set Content-Length
            $self->response()->content_length = $self->get('flight.content_length');
            // This is to maintain legacy handling of output buffering
            // which causes a lot of problems. This will be removed
            // in v4
            $self->response()->v2_output_buffering = $this->get('flight.v2.output_buffering');
        });

        $this->initialized = true;
    }

    /**
     * Handles PHP errors by converting them to ErrorException if error reporting is enabled for the error.
     *
     * @param int $errno The level of the error raised.
     * @param string $errstr The error message.
     * @param string $errfile The filename where the error was raised.
     * @param int $errline The line number where the error was raised.
     * @return false Always returns false if the error is not converted to an exception.
     * @throws ErrorException If the error level is included in error_reporting.
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if ($errno & error_reporting()) {
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        }

        return false;
    }

    /**
     * Handles uncaught exceptions by optionally logging them and invoking the framework's error handler.
     *
     * @param Throwable $e The uncaught exception to handle.
     */
    public function handleException(Throwable $e): void
    {
        if ($this->get('flight.log_errors')) {
            error_log($e->getMessage()); // @codeCoverageIgnore
        }

        $this->error($e);
    }

    /****
     * Registers a container handler for dependency injection.
     *
     * The handler can be a PSR-11 container or a callable that resolves class dependencies for route callbacks and middleware.
     */
    public function registerContainerHandler($containerHandler): void
    {
        $this->dispatcher->setContainerHandler($containerHandler);
    }

    /****
     * Associates a user-defined callback with a framework method name.
     *
     * Registers a custom callback to be invoked when the specified method name is called, unless the method already exists in the framework. Throws an exception if attempting to override a core framework method.
     *
     * @param string $name The name of the method to map.
     * @param callable $callback The callback to associate with the method name.
     * @throws Exception If attempting to override an existing framework method.
     */
    public function map(string $name, callable $callback): void
    {
        if (method_exists($this, $name)) {
            throw new Exception('Cannot override an existing framework method.');
        }

        $this->dispatcher->set($name, $callback);
    }

    /**
     * Registers a class to be instantiated and accessed via a framework method.
     *
     * Associates a method name with a class, allowing the method to return an instance of the class when called. Optional constructor parameters and a post-instantiation callback can be provided. Throws an exception if attempting to override an existing framework method.
     *
     * @param string $name The method name to register.
     * @param string $class The fully qualified class name to instantiate.
     * @param array $params Optional constructor parameters for the class.
     * @param callable|null $callback Optional callback to execute after instantiation.
     * @throws Exception If attempting to override a core framework method.
     */
    public function register(string $name, string $class, array $params = [], ?callable $callback = null): void
    {
        if (method_exists($this, $name)) {
            throw new Exception('Cannot override an existing framework method.');
        }

        $this->loader->register($name, $class, $params, $callback);
    }

    /**
     * Unregisters a class or method from the framework loader.
     *
     * Removes the association of a previously registered class or method, making it unavailable for dynamic invocation.
     *
     * @param string $methodName The name of the method or class to unregister.
     */
    public function unregister(string $methodName): void
    {
        $this->loader->unregister($methodName);
    }

    /****
     * Registers a callback to be executed before the specified method.
     *
     * The callback acts as a pre-filter and can modify the method's parameters or output before the method is invoked.
     *
     * @param string $name The name of the method to attach the pre-filter to.
     * @param callable $callback The pre-filter callback to execute before the method.
     */
    public function before(string $name, callable $callback): void
    {
        $this->dispatcher->hook($name, 'before', $callback);
    }

    /**
     * Registers a post-filter callback to be executed after a specified method.
     *
     * The callback receives the method's parameters and output by reference, allowing modification or inspection after the method runs.
     *
     * @param string $name The name of the method to attach the post-filter to.
     * @param callable $callback The callback to execute after the method. Receives parameters and output by reference.
     */
    public function after(string $name, callable $callback): void
    {
        $this->dispatcher->hook($name, 'after', $callback);
    }

    /**
     * Retrieves a stored variable by key, or all variables if no key is provided.
     *
     * @param string|null $key The variable name to retrieve, or null to retrieve all variables.
     * @return mixed The value of the specified variable, all variables as an array if no key is given, or null if the key does not exist.
     */
    public function get(?string $key = null)
    {
        if ($key === null) {
            return $this->vars;
        }

        return $this->vars[$key] ?? null;
    }

    /**
     * Sets one or more variables in the framework's variable store.
     *
     * If an iterable is provided as the first argument, each key-value pair is set as a variable. If a string is provided, sets the variable with the given name to the specified value.
     *
     * @param string|iterable<string, mixed> $key Variable name as a string, or an iterable of variable names and values.
     * @param mixed $value Value to assign if setting a single variable; ignored if setting multiple variables.
     */
    public function set($key, $value = null): void
    {
        if (\is_iterable($key)) {
            foreach ($key as $k => $v) {
                $this->vars[$k] = $v;
            }

            return;
        }

        $this->vars[$key] = $value;
    }

    /**
     * Determines whether a variable with the given key exists in the framework's variable store.
     *
     * @param string $key The name of the variable to check.
     * @return bool True if the variable is set, false otherwise.
     */
    public function has(string $key): bool
    {
        return isset($this->vars[$key]);
    }

    /****
     * Removes a stored variable by key, or clears all variables if no key is provided.
     *
     * @param string|null $key The variable name to remove. If null, all variables are cleared.
     */
    public function clear(?string $key = null): void
    {
        if ($key === null) {
            $this->vars = [];
            return;
        }

        unset($this->vars[$key]);
    }

    /**
     * Registers a directory to be used for class autoloading.
     *
     * @param string $dir The directory path to add for autoloading classes.
     */
    public function path(string $dir): void
    {
        $this->loader->addDirectory($dir);
    }

    /**
     * Executes all middleware associated with a route for a given event.
     *
     * Processes each middleware attached to the route in order (for 'before' events) or in reverse order (for 'after' events). Supports closures, objects with event methods, and class names resolved via container or direct instantiation. If any middleware returns false, processing stops and the function returns true to indicate failure.
     *
     * @param Route $route The route whose middleware should be processed.
     * @param string $eventName The event type, typically 'before' or 'after'.
     * @return bool True if any middleware returns false (indicating failure), false otherwise.
     */
    protected function processMiddleware(Route $route, string $eventName): bool
    {
        $atLeastOneMiddlewareFailed = false;

        // Process things normally for before, and then in reverse order for after.
        $middlewares = $eventName === Dispatcher::FILTER_BEFORE
            ? $route->middleware
            : array_reverse($route->middleware);
        $params = $route->params;

        foreach ($middlewares as $middleware) {
            // Assume that nothing is going to be executed for the middleware.
            $middlewareObject = false;

            // Closure functions can only run on the before event
            if ($eventName === Dispatcher::FILTER_BEFORE && is_object($middleware) === true && ($middleware instanceof Closure)) {
                $middlewareObject = $middleware;

                // If the object has already been created, we can just use it if the event name exists.
            } elseif (is_object($middleware) === true) {
                $middlewareObject = method_exists($middleware, $eventName) === true ? [$middleware, $eventName] : false;

                // If the middleware is a string, we need to create the object and then call the event.
            } elseif (is_string($middleware) === true && method_exists($middleware, $eventName) === true) {
                $resolvedClass = null;

                // if there's a container assigned, we should use it to create the object
                if ($this->dispatcher->mustUseContainer($middleware) === true) {
                    $resolvedClass = $this->dispatcher->resolveContainerClass($middleware, $params);
                    // otherwise just assume it's a plain jane class, so inject the engine
                    // just like in Dispatcher::invokeCallable()
                } elseif (class_exists($middleware) === true) {
                    $resolvedClass = new $middleware($this);
                }

                // If something was resolved, create an array callable that will be passed in later.
                if ($resolvedClass !== null) {
                    $middlewareObject = [$resolvedClass, $eventName];
                }
            }

            // If nothing was resolved, go to the next thing
            if ($middlewareObject === false) {
                continue;
            }

            // This is the way that v3 handles output buffering (which captures output correctly)
            $useV3OutputBuffering =
                $this->response()->v2_output_buffering === false &&
                $route->is_streamed === false;

            if ($useV3OutputBuffering === true) {
                ob_start();
            }

            // Here is the array callable $middlewareObject that we created earlier.
            // It looks bizarre but it's really calling [ $class, $method ]($params)
            // Which loosely translates to $class->$method($params)
            $start = microtime(true);
            $middlewareResult = $middlewareObject($params);
            $this->triggerEvent('flight.middleware.executed', $route, $middleware, $eventName, microtime(true) - $start);

            if ($useV3OutputBuffering === true) {
                $this->response()->write(ob_get_clean());
            }

            // If you return false in your middleware, it will halt the request
            // and throw a 403 forbidden error by default.
            if ($middlewareResult === false) {
                $atLeastOneMiddlewareFailed = true;
                break;
            }
        }

        return $atLeastOneMiddlewareFailed;
    }

    ////////////////////////
    // Extensible Methods //
    ////////////////////////
    /**
     * Starts processing the current HTTP request, handling routing, middleware, and response generation.
     *
     * This method manages the full request lifecycle: it initializes or resets request and response objects as needed, matches routes, executes middleware (before and after route handlers), invokes route callbacks, and sends the appropriate HTTP response. It handles streamed routes, output buffering, and special cases such as HEAD requests. If middleware fails, it halts with a 403 response; if no route matches, it sends a 404 or 405 as appropriate.
     *
     * @throws Exception If an error occurs during request processing.
     */
    public function _start(): void
    {
        $dispatched = false;
        $self = $this;

        // This behavior is specifically for test suites, and for async platforms like swoole, workerman, etc.
        if ($this->requestHandled === false) {
            // not doing much here, just setting the requestHandled flag to true
            $this->requestHandled = true;
        } else {
            // deregister the request and response objects and re-register them with new instances
            $this->unregister('request');
            $this->unregister('response');
            $this->register('request', Request::class);
            $this->register('response', Response::class);
            $this->router()->reset();
        }
        $request = $this->request();
        $this->triggerEvent('flight.request.received', $request);

        $response = $this->response();
        $router = $this->router();

        // Allow filters to run
        $this->after('start', function () use ($self) {
            $self->stop();
        });

        if ($response->v2_output_buffering === true) {
            // Flush any existing output
            if (ob_get_length() > 0) {
                $response->write(ob_get_clean()); // @codeCoverageIgnore
            }

            // Enable output buffering
            // This is closed in the Engine->_stop() method
            ob_start();
        }

        // Route the request
        $failedMiddlewareCheck = false;
        while ($route = $router->route($request)) {
            $this->triggerEvent('flight.route.matched', $route);
            $params = array_values($route->params);

            // Add route info to the parameter list
            if ($route->pass) {
                $params[] = $route;
            }

            // If this route is to be streamed, we need to output the headers now
            if ($route->is_streamed === true) {
                if (count($route->streamed_headers) > 0) {
                    $response->status($route->streamed_headers['status'] ?? 200);
                    unset($route->streamed_headers['status']);
                    foreach ($route->streamed_headers as $header => $value) {
                        $response->header($header, $value);
                    }
                }

                $response->header('X-Accel-Buffering', 'no');
                $response->header('Connection', 'close');

                // We obviously don't know the content length right now. This must be false.
                $response->content_length = false;
                $response->sendHeaders();
                $response->markAsSent();
            }

            // Run any before middlewares
            if (count($route->middleware) > 0) {
                $atLeastOneMiddlewareFailed = $this->processMiddleware($route, 'before');
                if ($atLeastOneMiddlewareFailed === true) {
                    $failedMiddlewareCheck = true;
                    break;
                }
                $this->triggerEvent('flight.middleware.before', $route);
            }

            $useV3OutputBuffering =
                $this->response()->v2_output_buffering === false &&
                $route->is_streamed === false;

            if ($useV3OutputBuffering === true) {
                ob_start();
            }

            // Call route handler
            $routeStart = microtime(true);
            $continue = $this->dispatcher->execute(
                $route->callback,
                $params
            );
            $this->triggerEvent('flight.route.executed', $route, microtime(true) - $routeStart);
            if ($useV3OutputBuffering === true) {
                $response->write(ob_get_clean());
            }

            // Run any before middlewares
            if (count($route->middleware) > 0) {
                // process the middleware in reverse order now
                $atLeastOneMiddlewareFailed = $this->processMiddleware($route, 'after');

                if ($atLeastOneMiddlewareFailed === true) {
                    $failedMiddlewareCheck = true;
                    break;
                }
                $this->triggerEvent('flight.middleware.after', $route);
            }

            $dispatched = true;

            if (!$continue) {
                break;
            }

            $router->next();

            $dispatched = false;
        }

        // HEAD requests should be identical to GET requests but have no body
        if ($request->method === 'HEAD') {
            $response->clearBody();
        }

        if ($failedMiddlewareCheck === true) {
            $this->halt(403, 'Forbidden', empty(getenv('PHPUNIT_TEST')));
        } elseif ($dispatched === false) {
            // Get the previous route and check if the method failed, but the URL was good.
            $lastRouteExecuted = $router->executedRoute;
            if ($lastRouteExecuted !== null && $lastRouteExecuted->matchUrl($request->url) === true && $lastRouteExecuted->matchMethod($request->method) === false) {
                $this->halt(405, 'Method Not Allowed', empty(getenv('PHPUNIT_TEST')));
            } else {
                $this->notFound();
            }
        }
    }

    /**
     * Sends a 500 Internal Server Error response with exception details.
     *
     * Triggers the 'flight.error' event and outputs an HTML error message containing the exception message, code, and stack trace.
     *
     * @param Throwable $e The exception to report in the error response.
     */
    public function _error(Throwable $e): void
    {
        $this->triggerEvent('flight.error', $e);
        $msg = sprintf(
            <<<'HTML'
            <h1>500 Internal Server Error</h1>
                <h3>%s (%s)</h3>
                <pre>%s</pre>
            HTML,
            $e->getMessage(),
            $e->getCode(),
            $e->getTraceAsString()
        );

        try {
            $this->response()
                ->cache(0)
                ->clearBody()
                ->status(500)
                ->write($msg)
                ->send();
            // @codeCoverageIgnoreStart
        } catch (Throwable $t) {
            exit($msg);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Sends the current response and halts further processing.
     *
     * If a status code is provided, it sets the response status before sending. Handles output buffering if enabled. Deprecated and will be removed in a future version.
     *
     * @param ?int $code Optional HTTP status code to set before sending the response.
     * @deprecated 3.5.3 This method will be removed in v4.
     */
    public function _stop(?int $code = null): void
    {
        $response = $this->response();

        if ($response->sent() === false) {
            if ($code !== null) {
                $response->status($code);
            }

            if ($response->v2_output_buffering === true && ob_get_length() > 0) {
                $response->write(ob_get_clean());
            }

            $response->send();
        }
    }

    /**
     * Registers a route for all HTTP methods with a specified URL pattern and callback.
     *
     * @param string $pattern The URL pattern to match.
     * @param callable|string $callback The callback to execute when the route is matched.
     * @param bool $pass_route Whether to pass the matched route object to the callback.
     * @param string $alias Optional alias for the route.
     * @return Route The registered route object.
     */
    public function _route(string $pattern, $callback, bool $pass_route = false, string $alias = ''): Route
    {
        return $this->router()->map($pattern, $callback, $pass_route, $alias);
    }

    /**
     * Groups routes under a common URL pattern with optional middleware.
     *
     * Registers a group of routes sharing a URL prefix and applies the specified middleware to all routes within the group.
     *
     * @param string $pattern URL prefix for the route group.
     * @param callable $callback Callback that defines the routes within the group.
     * @param array<int, callable|object> $group_middlewares Middleware to apply to all routes in the group.
     */
    public function _group(string $pattern, callable $callback, array $group_middlewares = []): void
    {
        $this->router()->group($pattern, $callback, $group_middlewares);
    }

    /**
     * Registers a POST route with the specified URL pattern and callback.
     *
     * Maps an HTTP POST request matching the given pattern to the provided callback or controller method. Optionally passes the matched route object to the callback and assigns a route alias.
     *
     * @param string $pattern URL pattern to match.
     * @param callable|string $callback Callback function or controller method reference.
     * @param bool $pass_route Whether to pass the matched route object to the callback.
     * @param string $route_alias Optional alias for the route.
     * @return Route The registered route instance.
     */
    public function _post(string $pattern, $callback, bool $pass_route = false, string $route_alias = ''): Route
    {
        return $this->router()->map('POST ' . $pattern, $callback, $pass_route, $route_alias);
    }

    /**
     * Registers a PUT route with the specified URL pattern and callback.
     *
     * @param string $pattern The URL pattern to match for PUT requests.
     * @param callable|string $callback The callback to execute when the route is matched.
     * @param bool $pass_route Whether to pass the matched route object to the callback.
     * @param string $route_alias Optional alias for the route.
     * @return Route The registered route object.
     */
    public function _put(string $pattern, $callback, bool $pass_route = false, string $route_alias = ''): Route
    {
        return $this->router()->map('PUT ' . $pattern, $callback, $pass_route, $route_alias);
    }

    /****
     * Registers a PATCH route with the specified URL pattern and callback.
     *
     * @param string $pattern The URL pattern to match for the PATCH request.
     * @param callable|string $callback The callback to execute when the route is matched.
     * @param bool $pass_route Whether to pass the matched route object to the callback.
     * @param string $route_alias Optional alias for the route.
     * @return Route The registered route instance.
     */
    public function _patch(string $pattern, $callback, bool $pass_route = false, string $route_alias = ''): Route
    {
        return $this->router()->map('PATCH ' . $pattern, $callback, $pass_route, $route_alias);
    }

    /**
     * Registers a DELETE route with the specified URL pattern and callback.
     *
     * @param string $pattern The URL pattern to match for DELETE requests.
     * @param callable|string $callback The callback to execute when the route is matched.
     * @param bool $pass_route Whether to pass the matched route object to the callback.
     * @param string $route_alias Optional alias for the route.
     * @return Route The registered route instance.
     */
    public function _delete(string $pattern, $callback, bool $pass_route = false, string $route_alias = ''): Route
    {
        return $this->router()->map('DELETE ' . $pattern, $callback, $pass_route, $route_alias);
    }

    /**
     * Registers RESTful resource routes for a controller with customizable method mappings.
     *
     * @param string $pattern URL pattern for the resource routes.
     * @param string $controllerClass Fully qualified controller class name.
     * @param array $options Optional mapping of HTTP methods or actions to controller methods or custom route names.
     */
    public function _resource(
        string $pattern,
        string $controllerClass,
        array $options = []
    ): void {
        $this->router()->mapResource($pattern, $controllerClass, $options);
    }

    /**
     * Immediately sends an HTTP response with the specified status code and message, then optionally terminates script execution.
     *
     * If no Cache-Control header is set, disables caching for the response. Clears any existing response body before sending.
     *
     * @param int $code HTTP status code to send.
     * @param string $message Response body content.
     * @param bool $actuallyExit If true, terminates script execution after sending the response.
     */
    public function _halt(int $code = 200, string $message = '', bool $actuallyExit = true): void
    {
        if ($this->response()->getHeader('Cache-Control') === null) {
            $this->response()->cache(0);
        }

        $this->response()
            ->clearBody()
            ->status($code)
            ->write($message)
            ->send();
        if ($actuallyExit === true) {
            exit(); // @codeCoverageIgnore
        }
    }

    /**
     * Sends a 404 Not Found HTTP response with a standard error message.
     *
     * This method clears the response body, sets the status code to 404, writes a default "Not Found" message, and sends the response to the client.
     */
    public function _notFound(): void
    {
        $output = '<h1>404 Not Found</h1><h3>The page you have requested could not be found.</h3>';

        $this->response()
            ->clearBody()
            ->status(404)
            ->write($output)
            ->send();
    }

    /****
     * Redirects the client to a specified URL with the given HTTP status code.
     *
     * If a base URL is configured and the target URL is relative, the base URL is prepended. Triggers the `flight.redirect` event before sending the redirect response.
     *
     * @param string $url The destination URL for the redirect.
     * @param int $code HTTP status code for the redirect (default is 303).
     */
    public function _redirect(string $url, int $code = 303): void
    {
        $base = $this->get('flight.base_url');

        if ($base === null) {
            $base = $this->request()->base;
        }

        // Append base url to redirect url
        if ($base !== '/'   && strpos($url, '://') === false) {
            $url = $base . preg_replace('#/+#', '/', '/' . $url);
        }

        $this->triggerEvent('flight.redirect', $url, $code);

        $this->response()
            ->clearBody()
            ->status($code)
            ->header('Location', $url)
            ->send();
    }

    /**
     * Renders a template file with optional data and stores or outputs the result.
     *
     * If a view variable name is provided, the rendered output is stored in the view; otherwise, it is sent to the response. Triggers a view rendered event after rendering.
     *
     * @param string $file The template file to render.
     * @param ?array<string, mixed> $data Optional data to pass to the template.
     * @param ?string $key If provided, stores the rendered output in this view variable instead of outputting.
     *
     * @throws Exception If the template file is not found.
     */
    public function _render(string $file, ?array $data = null, ?string $key = null): void
    {
        if ($key !== null) {
            $this->view()->set($key, $this->view()->fetch($file, $data));
            return;
        }

        $start = microtime(true);
        $this->view()->render($file, $data);
        $this->triggerEvent('flight.view.rendered', $file, microtime(true) - $start);
    }

    /**
     * Sends a JSON response with the specified data and HTTP status code.
     *
     * Optionally encodes the data as JSON and sets the Content-Type header to application/json. If output buffering is enabled, the response is sent immediately.
     *
     * @param mixed $data The data to send in the JSON response.
     * @param int $code HTTP status code for the response.
     * @param bool $encode Whether to encode the data as JSON.
     * @param string|null $charset Character set for the response.
     * @param int $option Bitmask of JSON encoding options.
     *
     * @throws Exception If JSON encoding fails.
     */
    public function _json(
        $data,
        int $code = 200,
        bool $encode = true,
        ?string $charset = 'utf-8',
        int $option = 0
    ): void {
        // add some default flags
        $option |= JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;
        $json = $encode ? json_encode($data, $option) : $data;

        $this->response()
            ->status($code)
            ->header('Content-Type', 'application/json')
            ->write($json);
        if ($this->response()->v2_output_buffering === true) {
            $this->response()->send();
        }
    }

    /**
     * Sends a JSON response with the specified data and immediately halts further execution.
     *
     * The response is sent with the given HTTP status code and optional JSON encoding options.
     * If output buffering is disabled, the response body is cleared and sent before halting.
     *
     * @param mixed $data The data to be returned as JSON.
     * @param int $code HTTP status code for the response.
     * @param bool $encode Whether to encode the data as JSON.
     * @param string $charset Character set for the response.
     * @param int $option Bitmask for JSON encoding options.
     */
    public function _jsonHalt(
        $data,
        int $code = 200,
        bool $encode = true,
        string $charset = 'utf-8',
        int $option = 0
    ): void {
        $this->json($data, $code, $encode, $charset, $option);
        $jsonBody = $this->response()->getBody();
        if ($this->response()->v2_output_buffering === false) {
            $this->response()->clearBody();
            $this->response()->send();
        }
        $this->halt($code, $jsonBody, empty(getenv('PHPUNIT_TEST')));
    }

    /**
     * Sends a JSONP response using the specified callback parameter from the query string.
     *
     * Outputs the provided data as a JSONP response, wrapping it in a JavaScript callback function named according to the query parameter. Sets the appropriate content type and HTTP status code. If output buffering is enabled, the response is sent immediately.
     *
     * @param mixed $data Data to be encoded as JSON and wrapped in the callback.
     * @param string $param Name of the query parameter that specifies the callback function name.
     * @param int $code HTTP status code for the response.
     * @param bool $encode Whether to encode the data as JSON.
     * @param string $charset Character set for the response.
     * @param int $option Bitmask for JSON encoding options.
     */
    public function _jsonp(
        $data,
        string $param = 'jsonp',
        int $code = 200,
        bool $encode = true,
        string $charset = 'utf-8',
        int $option = 0
    ): void {
        $json = $encode ? json_encode($data, $option) : $data;
        $callback = $this->request()->query[$param];

        $this->response()
            ->status($code)
            ->header('Content-Type', 'application/javascript; charset=' . $charset)
            ->write($callback . '(' . $json . ');');
        if ($this->response()->v2_output_buffering === true) {
            $this->response()->send();
        }
    }

    /**
     * Initiates a file download response for the specified file path.
     *
     * @param string $filePath Absolute or relative path to the file to be downloaded.
     * @throws Exception If the file does not exist or cannot be accessed.
     */
    public function _download(string $filePath): void
    {
        $this->response()->downloadFile($filePath);
    }

    /**
     * Sets the ETag header for HTTP caching and handles conditional requests.
     *
     * If the provided ETag matches the client's `If-None-Match` header, sends a 304 Not Modified response and halts execution.
     *
     * @param string $id ETag identifier.
     * @param 'strong'|'weak' $type ETag type; use 'weak' for a weak ETag.
     */
    public function _etag(string $id, string $type = 'strong'): void
    {
        $id = (($type === 'weak') ? 'W/' : '') . $id;

        $this->response()->header('ETag', '"' . str_replace('"', '\"', $id) . '"');

        $hit = isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $id;
        $this->triggerEvent('flight.cache.checked', 'etag', $hit, 0.0);

        if ($hit === true) {
            $this->response()->clear();
            $this->halt(304, '', empty(getenv('PHPUNIT_TEST')));
        }
    }

    /**
     * Sets the Last-Modified HTTP header and handles conditional requests based on the provided timestamp.
     *
     * If the client's `If-Modified-Since` header matches the given timestamp, sends a 304 Not Modified response and halts execution.
     *
     * @param int $time Unix timestamp representing the last modification time.
     */
    public function _lastModified(int $time): void
    {
        $this->response()->header('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T', $time));

        $hit = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $time;
        $this->triggerEvent('flight.cache.checked', 'lastModified', $hit, 0.0);

        if ($hit === true) {
            $this->response()->clear();
            $this->halt(304, '', empty(getenv('PHPUNIT_TEST')));
        }
    }

    /****
     * Retrieves the URL associated with a given route alias, substituting any provided parameters.
     *
     * @param string $alias The route alias to resolve.
     * @param array<string, mixed> $params Optional parameters to include in the generated URL.
     * @return string The generated URL for the specified alias and parameters.
     */
    public function _getUrl(string $alias, array $params = []): string
    {
        return $this->router()->getUrlByAlias($alias, $params);
    }

    /**
     * Registers a callback to be executed when a specific event is triggered.
     *
     * @param string $eventName Name of the event to listen for.
     * @param callable $callback Function to execute when the event occurs.
     */
    public function _onEvent(string $eventName, callable $callback): void
    {
        $this->eventDispatcher()->on($eventName, $callback);
    }

    /**
     * Triggers an event and passes arguments to all registered listeners.
     *
     * @param string $eventName The name of the event to trigger.
     * @param mixed ...$args Arguments to pass to the event listeners.
     */
    public function _triggerEvent(string $eventName, ...$args): void
    {
        $this->eventDispatcher()->trigger($eventName, ...$args);
    }
}

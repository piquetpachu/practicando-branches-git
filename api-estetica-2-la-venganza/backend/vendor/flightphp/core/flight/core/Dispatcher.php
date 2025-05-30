<?php

declare(strict_types=1);

namespace flight\core;

use Exception;
use flight\Engine;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionFunction;
use Throwable;
use TypeError;

/**
 * The Dispatcher class is responsible for dispatching events. Events
 * are simply aliases for class methods or functions. The Dispatcher
 * allows you to hook other functions to an event that can modify the
 * input parameters and/or the output.
 *
 * @license MIT, http://flightphp.com/license
 * @copyright Copyright (c) 2011, Mike Cao <mike@mikecao.com>
 */
class Dispatcher
{
    public const FILTER_BEFORE = 'before';
    public const FILTER_AFTER = 'after';

    /** Exception message if thrown by setting the container as a callable method. */
    protected ?Throwable $containerException = null;

    /** @var ?Engine $engine Engine instance. */
    protected ?Engine $engine = null;

    /** @var array<string, callable(): (void|mixed)> Mapped events. */
    protected array $events = [];

    /**
     * Method filters.
     *
     * @var array<string, array<'before'|'after', array<int, callable(array<int, mixed> &$params, mixed &$output): (void|false)>>>
     */
    protected array $filters = [];

    /**
     * This is a container for the dependency injection.
     *
     * @var null|ContainerInterface|(callable(string $classString, array<int, mixed> $params): (null|object))
     */
    protected $containerHandler = null;

    /****
     * Assigns a dependency injection container handler for resolving class instances.
     *
     * Accepts either a PSR-11 container or a callable that returns an object instance for a given class name and parameters.
     *
     * @param mixed $containerHandler A PSR-11 container or a callable for resolving class instances.
     * @throws InvalidArgumentException If the provided handler is neither callable nor a PSR-11 container.
     */
    public function setContainerHandler($containerHandler): void
    {
        $containerInterfaceNS = '\Psr\Container\ContainerInterface';

        if (
            is_a($containerHandler, $containerInterfaceNS)
            || is_callable($containerHandler)
        ) {
            $this->containerHandler = $containerHandler;

            return;
        }

        throw new InvalidArgumentException(
            "\$containerHandler must be of type callable or instance $containerInterfaceNS"
        );
    }

    /**
     * Assigns an Engine instance to the dispatcher for use in event and callback resolution.
     *
     * @param Engine $engine The Engine instance to associate with the dispatcher.
     */
    public function setEngine(Engine $engine): void
    {
        $this->engine = $engine;
    }

    /**
     * Executes the specified event by name, applying pre- and post-filters.
     *
     * Runs all 'before' filters for the event, invokes the event callback with the provided parameters, and then applies all 'after' filters to the callback's output.
     *
     * @param string $name The event name to dispatch.
     * @param array<int, mixed> $params Optional parameters to pass to the event callback.
     * @return mixed The result of the event callback, possibly modified by post-filters.
     * @throws Exception If the event is not found or if the event callback throws an exception.
     */
    public function run(string $name, array $params = [])
    {
        $this->runPreFilters($name, $params);
        $output = $this->runEvent($name, $params);

        return $this->runPostFilters($name, $output);
    }

    /**
     * Executes all 'before' filters for the specified event, allowing modification of parameters.
     *
     * Each filter receives the event parameters by reference and can alter them before the main event callback is executed. If any filter throws an exception, it is propagated to the caller.
     *
     * @param string $eventName Name of the event whose pre-filters should be executed.
     * @param array<int, mixed> &$params Parameters to be passed to the event, passed by reference and modifiable by filters.
     * @return $this Returns the dispatcher instance for method chaining.
     * @throws Exception If any pre-filter throws an exception.
     */
    protected function runPreFilters(string $eventName, array &$params): self
    {
        $thereAreBeforeFilters = !empty($this->filters[$eventName][self::FILTER_BEFORE]);

        if ($thereAreBeforeFilters) {
            $this->filter($this->filters[$eventName][self::FILTER_BEFORE], $params, $output);
        }

        return $this;
    }

    /****
     * Executes the callback associated with the specified event.
     *
     * Retrieves the event callback by name and invokes it with the provided parameters. Throws an exception if the event is not registered.
     *
     * @param string $eventName Name of the event to execute.
     * @param array<int, mixed> &$params Parameters to pass to the event callback, passed by reference.
     * @return mixed The result of the event callback.
     * @throws Exception If the event is not found or if the callback throws an exception.
     */
    protected function runEvent(string $eventName, array &$params)
    {
        $requestedMethod = $this->get($eventName);

        if ($requestedMethod === null) {
            throw new Exception("Event '$eventName' isn't found.");
        }

        return $this->execute($requestedMethod, $params);
    }

    /**
     * Executes all 'after' filters for the specified event, allowing modification of the event output.
     *
     * @param string $eventName Name of the event whose post-filters should be run.
     * @param mixed &$output The output from the event callback, passed by reference and may be modified by filters.
     * @return mixed The (possibly modified) output after all post-filters have been applied.
     * @throws Exception If any filter throws an exception during execution.
     */
    protected function runPostFilters(string $eventName, &$output)
    {
        static $params = [];

        $thereAreAfterFilters = !empty($this->filters[$eventName][self::FILTER_AFTER]);

        if ($thereAreAfterFilters) {
            $this->filter($this->filters[$eventName][self::FILTER_AFTER], $params, $output);
        }

        return $output;
    }

    /****
     * Registers a callback function for a specific event name.
     *
     * @param string $name The event name to associate with the callback.
     * @param callable $callback The function to execute when the event is dispatched.
     * @return $this
     */
    public function set(string $name, callable $callback): self
    {
        $this->events[$name] = $callback;

        return $this;
    }

    /**
     * Retrieves the callback assigned to the specified event name.
     *
     * @param string $name The name of the event.
     * @return callable|null The callback function if assigned, or null if not set.
     */
    public function get(string $name): ?callable
    {
        return $this->events[$name] ?? null;
    }

    /**
     * Determines whether an event with the given name is registered.
     *
     * @param string $name The event name to check.
     * @return bool True if the event exists; false otherwise.
     */
    public function has(string $name): bool
    {
        return isset($this->events[$name]);
    }

    /**
     * Removes a specific event and its filters, or clears all events and filters if no name is provided.
     *
     * @param string|null $name The name of the event to remove, or null to clear all events and filters.
     */
    public function clear(?string $name = null): void
    {
        if ($name !== null) {
            unset($this->events[$name]);
            unset($this->filters[$name]);

            return;
        }

        $this->reset();
    }

    /**
     * Attaches a filter callback to an event, to be executed either before or after the event's main callback.
     *
     * The filter type must be 'before' or 'after'. For 'after' filters, callbacks accepting only the output parameter are supported for backward compatibility, but this usage is deprecated.
     *
     * @param string $name The event name.
     * @param string $type The filter type, either 'before' or 'after'.
     * @param callable $callback The filter callback to attach.
     * @return $this
     */
    public function hook(string $name, string $type, callable $callback): self
    {
        static $filterTypes = [self::FILTER_BEFORE, self::FILTER_AFTER];

        if (!in_array($type, $filterTypes, true)) {
            $noticeMessage = "Invalid filter type '$type', use " . join('|', $filterTypes);

            trigger_error($noticeMessage, E_USER_NOTICE);
        }

        if ($type === self::FILTER_AFTER) {
            $callbackInfo = new ReflectionFunction($callback);
            $parametersNumber = $callbackInfo->getNumberOfParameters();

            if ($parametersNumber === 1) {
                /** @disregard &$params in after filters are deprecated. */
                $callback = fn(array &$params, &$output) => $callback($output);
            }
        }

        $this->filters[$name][$type][] = $callback;

        return $this;
    }

    /**
     * Executes a sequence of filter callbacks, passing parameters and output by reference.
     *
     * Each filter receives the current parameters and output, and may modify them. If any filter returns false, the chain is stopped.
     *
     * @param array $filters List of filter callbacks to execute.
     * @param array $params Parameters to be passed to each filter, passed by reference.
     * @param mixed $output Output value to be passed to each filter, passed by reference.
     *
     * @throws InvalidArgumentException If any filter is not callable.
     */
    public function filter(array $filters, array &$params, &$output): void
    {
        foreach ($filters as $key => $callback) {
            if (!is_callable($callback)) {
                throw new InvalidArgumentException("Invalid callable \$filters[$key].");
            }

            $continue = $callback($params, $output);

            if ($continue === false) {
                break;
            }
        }
    }

    /**
     * Executes a callback, resolving string representations of class methods if necessary.
     *
     * If the callback is a string containing '->' or '::', it is parsed into a class and method array before invocation.
     *
     * @param callable|string|array $callback The function, closure, or class method to execute. String representations of class methods (e.g., "Class::method") are supported.
     * @param array $params Parameters to pass to the callback, passed by reference.
     * @return mixed The result of the callback execution.
     * @throws Exception If the callback throws an exception.
     */
    public function execute($callback, array &$params = [])
    {
        if (
            is_string($callback) === true
            && (strpos($callback, '->') !== false || strpos($callback, '::') !== false)
        ) {
            $callback = $this->parseStringClassAndMethod($callback);
        }

        return $this->invokeCallable($callback, $params);
    }

    /**
     * Splits a string containing a class and method into an array.
     *
     * Accepts strings in the format "Class::method" or "Class->method" and returns an array with the class and method parts.
     *
     * @param string $classAndMethod String containing class and method, separated by '::' or '->'.
     * @return array{0: class-string|object, 1: string} Array with class (or object) as the first element and method name as the second.
     */
    public function parseStringClassAndMethod(string $classAndMethod): array
    {
        $classParts = explode('->', $classAndMethod);

        if (count($classParts) === 1) {
            $classParts = explode('::', $classParts[0]);
        }

        return $classParts;
    }

    /****
     * Invokes a callable function with the provided parameters.
     *
     * This method is deprecated as of version 3.7.0. Use invokeCallable() instead.
     *
     * @param callable $func The function to invoke.
     * @param array<int, mixed> &$params Parameters to pass to the function.
     * @return mixed The result of the function call.
     * @deprecated 3.7.0 Use invokeCallable instead.
     */
    public function callFunction(callable $func, array &$params = [])
    {
        return $this->invokeCallable($func, $params);
    }

    /****
     * Invokes a class method with the given parameters.
     *
     * This method is deprecated as of version 3.7.0; use invokeCallable() instead.
     *
     * @param array{0: class-string|object, 1: string} $func Array containing the class (as a string or object) and method name.
     * @param array<int, mixed> &$params Parameters to pass to the method.
     * @return mixed The result of the invoked method.
     * @throws TypeError If the specified class does not exist.
     */
    public function invokeMethod(array $func, array &$params = [])
    {
        return $this->invokeCallable($func, $params);
    }

    /**
     * Invokes a callable function or class method, resolving class instances via the container or Engine if necessary.
     *
     * If the callable is a class method, the class instance is resolved using the container handler if configured, or instantiated with the Engine. Validates the existence of the function, class, and method before invocation.
     *
     * @param array{0: class-string|object, 1: string}|callable $func The function or class method to invoke.
     * @param array<int, mixed> &$params Parameters to pass to the callable, passed by reference.
     * @return mixed The result of the invoked callable.
     * @throws TypeError If the class does not exist.
     * @throws InvalidArgumentException If the class constructor requires parameters and cannot be instantiated.
     */
    public function invokeCallable($func, array &$params = [])
    {
        // If this is a directly callable function, call it
        if (is_array($func) === false) {
            $this->verifyValidFunction($func);

            return call_user_func_array($func, $params);
        }

        [$class, $method] = $func;

        $mustUseTheContainer = $this->mustUseContainer($class);

        if ($mustUseTheContainer === true) {
            $resolvedClass = $this->resolveContainerClass($class, $params);

            if ($resolvedClass) {
                $class = $resolvedClass;
            }
        }

        $this->verifyValidClassCallable($class, $method, $resolvedClass ?? null);

        // Class is a string, and method exists, create the object by hand and inject only the Engine
        if (is_string($class)) {
            $class = new $class($this->engine);
        }

        return call_user_func_array([$class, $method], $params);
    }

    /**
     * Validates that a string callback refers to an existing function.
     *
     * Throws an InvalidArgumentException if the provided string does not correspond to a defined function.
     *
     * @param mixed $callback Callback to validate.
     * @throws InvalidArgumentException If the callback is a string and the function does not exist.
     */
    protected function verifyValidFunction($callback): void
    {
        if (is_string($callback) && !function_exists($callback)) {
            throw new InvalidArgumentException('Invalid callback specified.');
        }
    }


    /**
     * Validates that the specified class and method are callable.
     *
     * Throws an exception if the class does not exist, if the method is not found on the class or object, or if a container resolution error occurred.
     *
     * @param class-string|object $class The class name or object instance.
     * @param string $method The method name to validate.
     * @param object|null $resolvedClass The resolved class instance, if available.
     *
     * @throws Exception If the class does not exist, the method is not found, or a container resolution error was encountered.
     */
    protected function verifyValidClassCallable($class, $method, $resolvedClass): void
    {
        $exception = null;

        // Final check to make sure it's actually a class and a method, or throw an error
        if (is_object($class) === false && class_exists($class) === false) {
            $exception = new Exception("Class '$class' not found. Is it being correctly autoloaded with Flight::path()?");

            // If this tried to resolve a class in a container and failed somehow, throw the exception
        } elseif (!$resolvedClass && $this->containerException !== null) {
            $exception = $this->containerException;

            // Class is there, but no method
        } elseif (is_object($class) === true && method_exists($class, $method) === false) {
            $classNamespace = get_class($class);
            $exception = new Exception("Class found, but method '$classNamespace::$method' not found.");
        }

        if ($exception !== null) {
            $this->fixOutputBuffering();

            throw $exception;
        }
    }

    /**
     * Attempts to resolve and instantiate a class using the configured container handler.
     *
     * Supports both PSR-11 containers and callable container handlers. Returns the resolved object instance, or null if resolution fails. If an exception occurs during resolution with a callable container, the exception is stored for later retrieval.
     *
     * @param string $class The fully qualified class name to resolve.
     * @param array $params Parameters to pass to the container or constructor.
     * @return object|null The resolved class instance, or null if not found.
     */
    public function resolveContainerClass(string $class, array &$params)
    {
        // PSR-11
        if (is_a($this->containerHandler, '\Psr\Container\ContainerInterface')) {
            try {
                return $this->containerHandler->get($class);
            } catch (Throwable $exception) {
                return null;
            }
        }

        // Just a callable where you configure the behavior (Dice, PHP-DI, etc.)
        if (is_callable($this->containerHandler)) {
            /* This is to catch all the error that could be thrown by whatever
            container you are using */
            try {
                return ($this->containerHandler)($class, $params);

                // could not resolve a class for some reason
            } catch (Exception $exception) {
                // If the container throws an exception, we need to catch it
                // and store it somewhere. If we just let it throw itself, it
                // doesn't properly close the output buffers and can cause other
                // issues.
                // This is thrown in the verifyValidClassCallable method.
                $this->containerException = $exception;
            }
        }

        return null;
    }

    /****
     * Determines whether the container handler should be used to resolve the given class.
     *
     * Returns true if a container handler is set and the class is either a string or an object not in the 'flight' namespace.
     *
     * @param string|object $class Class name or object to check.
     * @return bool True if the container should be used; otherwise, false.
     */
    public function mustUseContainer($class): bool
    {
        return $this->containerHandler !== null && (
            (is_object($class) === true && strpos(get_class($class), 'flight\\') === false)
            || is_string($class)
        );
    }

    /****
     * Cleans the output buffer if any levels are active, except for the default PHPUnit buffer.
     *
     * This method ensures that any active output buffers are cleared to prevent issues when exceptions are thrown during output buffering. If running under PHPUnit, it preserves the default buffer level.
     */
    protected function fixOutputBuffering(): void
    {
        // Cause PHPUnit has 1 level of output buffering by default
        if (ob_get_level() > (getenv('PHPUNIT_TEST') ? 1 : 0)) {
            ob_end_clean();
        }
    }

    /**
     * Clears all registered events and filters, restoring the dispatcher to its initial state.
     *
     * @return $this The dispatcher instance.
     */
    public function reset(): self
    {
        $this->events = [];
        $this->filters = [];

        return $this;
    }
}

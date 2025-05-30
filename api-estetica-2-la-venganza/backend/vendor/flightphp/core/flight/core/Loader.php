<?php

declare(strict_types=1);

namespace flight\core;

use Closure;
use Exception;

/**
 * The Loader class is responsible for loading objects. It maintains
 * a list of reusable class instances and can generate a new class
 * instances with custom initialization parameters. It also performs
 * class autoloading.
 *
 * @license MIT, http://flightphp.com/license
 * @copyright Copyright (c) 2011, Mike Cao <mike@mikecao.com>
 */
class Loader
{
    /**
     * Registered classes.
     *
     * @var array<string, array{class-string|Closure(): object, array<int, mixed>, ?callable}> $classes
     */
    protected array $classes = [];

    /**
     * If this is disabled, classes can load with underscores
     */
    protected static bool $v2ClassLoading = true;

    /**
     * Class instances.
     *
     * @var array<string, object>
     */
    protected array $instances = [];

    /**
     * Autoload directories.
     *
     * @var array<int, string>
     */
    protected static array $dirs = [];

    /**
     * Registers a class or factory closure under a given name with optional constructor parameters and a post-instantiation callback.
     *
     * If a shared instance with the same name exists, it is removed from the registry.
     *
     * @param string $name The unique name to register the class or factory under.
     * @param string|Closure $class The class name or a closure that returns an instance.
     * @param array $params Optional parameters to use when instantiating the class or invoking the factory.
     * @param callable|null $callback Optional callback to execute after instantiation.
     */
    public function register(string $name, $class, array $params = [], ?callable $callback = null): void
    {
        unset($this->instances[$name]);

        $this->classes[$name] = [$class, $params, $callback];
    }

    /**
     * Removes a registered class from the loader by its registry name.
     *
     * After calling this method, the class will no longer be available for instantiation or retrieval via the loader.
     *
     * @param string $name The registry name of the class to remove.
     */
    public function unregister(string $name): void
    {
        unset($this->classes[$name]);
    }

    /**
     * Returns an instance of a registered class by name, optionally as a shared singleton.
     *
     * If the class is registered, returns either a shared instance (singleton) or a new instance, depending on the $shared flag. If a callback was registered, it is invoked after instantiation. Returns null if the class is not registered.
     *
     * @param string $name Name of the registered class or service.
     * @param bool $shared Whether to return a shared instance (default true).
     * @return object|null The class instance, or null if not registered.
     * @throws Exception If instantiation fails.
     */
    public function load(string $name, bool $shared = true): ?object
    {
        $obj = null;

        if (isset($this->classes[$name])) {
            [0 => $class, 1 => $params, 2 => $callback] = $this->classes[$name];

            $exists = isset($this->instances[$name]);

            if ($shared) {
                $obj = ($exists) ?
                    $this->getInstance($name) :
                    $this->newInstance($class, $params);

                if (!$exists) {
                    $this->instances[$name] = $obj;
                }
            } else {
                $obj = $this->newInstance($class, $params);
            }

            if ($callback && (!$shared || !$exists)) {
                $ref = [&$obj];
                \call_user_func_array($callback, $ref);
            }
        }

        return $obj;
    }

    /**
     * Returns the shared instance of a registered class by name, or null if no instance exists.
     *
     * @param string $name The name under which the class was registered.
     * @return object|null The shared class instance, or null if not instantiated.
     */
    public function getInstance(string $name): ?object
    {
        return $this->instances[$name] ?? null;
    }

    /**
     * Creates and returns a new instance of a class or from a factory closure, using the provided parameters.
     *
     * @param string|Closure $class Class name or a factory closure that returns an object.
     * @param array $params Parameters to pass to the class constructor or factory closure.
     * @template T of object
     * @throws Exception If instantiation fails.
     * @return T The newly created class instance.
     */
    public function newInstance($class, array $params = [])
    {
        if (\is_callable($class)) {
            return \call_user_func_array($class, $params);
        }

        return new $class(...$params);
    }

    /**
     * Retrieves the registration details for a given name.
     *
     * Returns the class, parameters, and optional callback associated with the specified registry name, or null if not registered.
     *
     * @param string $name The name under which the class or factory was registered.
     * @return mixed Registration details array or null if the name is not registered.
     */
    public function get(string $name)
    {
        return $this->classes[$name] ?? null;
    }

    /**
     * Clears all registered classes and shared instances, restoring the loader to its initial state.
     */
    public function reset(): void
    {
        $this->classes = [];
        $this->instances = [];
    }

    // Autoloading Functions

    /**
     * Enables or disables the Loader's autoloader and optionally adds directories to the autoload search path.
     *
     * @param bool $enabled Whether to enable or disable autoloading.
     * @param string|iterable<int, string> $dirs Optional directories to add to the autoload search path.
     */
    public static function autoload(bool $enabled = true, $dirs = []): void
    {
        if ($enabled) {
            spl_autoload_register([__CLASS__, 'loadClass']);
        } else {
            spl_autoload_unregister([__CLASS__, 'loadClass']); // @codeCoverageIgnore
        }

        if (!empty($dirs)) {
            self::addDirectory($dirs);
        }
    }

    /****
     * Attempts to autoload a class by searching registered directories for its file.
     *
     * Converts the class name to a file path using namespace separators and, if enabled, underscores, then includes the file if found in any registered directory.
     *
     * @param string $class The fully qualified class name to load.
     */
    public static function loadClass(string $class): void
    {
        $replace_chars = self::$v2ClassLoading === true ? ['\\', '_'] : ['\\'];
        $classFile = str_replace($replace_chars, '/', $class) . '.php';

        foreach (self::$dirs as $dir) {
            $filePath = "$dir/$classFile";

            if (file_exists($filePath)) {
                require_once $filePath;
                return;
            }
        }
    }

    /**
     * Registers one or more directories to be searched for class files during autoloading.
     *
     * Accepts a single directory path or an iterable of directory paths. Duplicate directories are ignored.
     *
     * @param string|iterable<int, string> $dir Directory path or iterable of paths to add.
     */
    public static function addDirectory($dir): void
    {
        if (\is_array($dir) || \is_object($dir)) {
            foreach ($dir as $value) {
                self::addDirectory($value);
            }
        } elseif (\is_string($dir)) {
            if (!\in_array($dir, self::$dirs, true)) {
                self::$dirs[] = $dir;
            }
        }
    }


    /****
     * Configures whether underscores in class names are converted to directory separators during autoloading.
     *
     * @param bool $value If true, underscores in class names are treated as directory separators for autoloading; if false, only namespace separators are used.
     */
    public static function setV2ClassLoading(bool $value): void
    {
        self::$v2ClassLoading = $value;
    }
}

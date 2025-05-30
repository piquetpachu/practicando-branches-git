<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Autoload;

/**
 * ClassLoader implements a PSR-0, PSR-4 and classmap class loader.
 *
 *     $loader = new \Composer\Autoload\ClassLoader();
 *
 *     // register classes with namespaces
 *     $loader->add('Symfony\Component', __DIR__.'/component');
 *     $loader->add('Symfony',           __DIR__.'/framework');
 *
 *     // activate the autoloader
 *     $loader->register();
 *
 *     // to enable searching the include path (eg. for PEAR packages)
 *     $loader->setUseIncludePath(true);
 *
 * In this example, if you try to use a class in the Symfony\Component
 * namespace or one of its children (Symfony\Component\Console for instance),
 * the autoloader will first look for the class under the component/
 * directory, and it will then fallback to the framework/ directory if not
 * found before giving up.
 *
 * This class is loosely based on the Symfony UniversalClassLoader.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @see    https://www.php-fig.org/psr/psr-0/
 * @see    https://www.php-fig.org/psr/psr-4/
 */
class ClassLoader
{
    /** @var \Closure(string):void */
    private static $includeFile;

    /** @var string|null */
    private $vendorDir;

    // PSR-4
    /**
     * @var array<string, array<string, int>>
     */
    private $prefixLengthsPsr4 = array();
    /**
     * @var array<string, list<string>>
     */
    private $prefixDirsPsr4 = array();
    /**
     * @var list<string>
     */
    private $fallbackDirsPsr4 = array();

    // PSR-0
    /**
     * List of PSR-0 prefixes
     *
     * Structured as array('F (first letter)' => array('Foo\Bar (full prefix)' => array('path', 'path2')))
     *
     * @var array<string, array<string, list<string>>>
     */
    private $prefixesPsr0 = array();
    /**
     * @var list<string>
     */
    private $fallbackDirsPsr0 = array();

    /** @var bool */
    private $useIncludePath = false;

    /**
     * @var array<string, string>
     */
    private $classMap = array();

    /** @var bool */
    private $classMapAuthoritative = false;

    /**
     * @var array<string, bool>
     */
    private $missingClasses = array();

    /** @var string|null */
    private $apcuPrefix;

    /**
     * @var array<string, self>
     */
    private static $registeredLoaders = array();

    /****
     * Initializes a new ClassLoader instance and sets the optional vendor directory.
     *
     * @param string|null $vendorDir Optional vendor directory path used for loader registration and isolation.
     */
    public function __construct($vendorDir = null)
    {
        $this->vendorDir = $vendorDir;
        self::initializeIncludeClosure();
    }

    /**
     * Returns the PSR-0 prefix-to-directories mapping.
     *
     * @return array<string, list<string>> An associative array where each key is a PSR-0 prefix and each value is a list of directories associated with that prefix.
     */
    public function getPrefixes()
    {
        if (!empty($this->prefixesPsr0)) {
            return call_user_func_array('array_merge', array_values($this->prefixesPsr0));
        }

        return array();
    }

    /**
     * Returns the PSR-4 namespace prefixes and their associated directories.
     *
     * @return array<string, list<string>> An associative array mapping PSR-4 namespace prefixes to arrays of directory paths.
     */
    public function getPrefixesPsr4()
    {
        return $this->prefixDirsPsr4;
    }

    /****
     * Returns the list of PSR-0 fallback directories.
     *
     * @return string[] List of fallback directories used for PSR-0 autoloading.
     */
    public function getFallbackDirs()
    {
        return $this->fallbackDirsPsr0;
    }

    /**
     * Returns the list of PSR-4 fallback directories.
     *
     * @return string[] List of directories used as PSR-4 fallbacks.
     */
    public function getFallbackDirsPsr4()
    {
        return $this->fallbackDirsPsr4;
    }

    /****
     * Returns the current class map of class names to file paths.
     *
     * @return array<string, string> Associative array mapping fully qualified class names to their corresponding file paths.
     */
    public function getClassMap()
    {
        return $this->classMap;
    }

    /**
     * Adds or merges class-to-file mappings into the class map.
     *
     * If the class map already exists, the provided mappings are merged; otherwise, the class map is set to the provided array.
     *
     * @param array<string, string> $classMap Map of class names to file paths.
     */
    public function addClassMap(array $classMap)
    {
        if ($this->classMap) {
            $this->classMap = array_merge($this->classMap, $classMap);
        } else {
            $this->classMap = $classMap;
        }
    }

    /**
     * Adds one or more PSR-0 root directories for a given class prefix.
     *
     * If the prefix is empty, the directories are added as PSR-0 fallback directories. Directories can be prepended or appended to the existing list for the prefix or fallback.
     *
     * @param string $prefix Class prefix for which to register directories. An empty string adds fallback directories.
     * @param string[]|string $paths One or more directories to associate with the prefix.
     * @param bool $prepend If true, directories are prepended; otherwise, they are appended.
     */
    public function add($prefix, $paths, $prepend = false)
    {
        $paths = (array) $paths;
        if (!$prefix) {
            if ($prepend) {
                $this->fallbackDirsPsr0 = array_merge(
                    $paths,
                    $this->fallbackDirsPsr0
                );
            } else {
                $this->fallbackDirsPsr0 = array_merge(
                    $this->fallbackDirsPsr0,
                    $paths
                );
            }

            return;
        }

        $first = $prefix[0];
        if (!isset($this->prefixesPsr0[$first][$prefix])) {
            $this->prefixesPsr0[$first][$prefix] = $paths;

            return;
        }
        if ($prepend) {
            $this->prefixesPsr0[$first][$prefix] = array_merge(
                $paths,
                $this->prefixesPsr0[$first][$prefix]
            );
        } else {
            $this->prefixesPsr0[$first][$prefix] = array_merge(
                $this->prefixesPsr0[$first][$prefix],
                $paths
            );
        }
    }

    /**
     * Adds one or more PSR-4 base directories for a given namespace prefix.
     *
     * If the prefix is empty, the directories are registered as PSR-4 fallback directories. For non-empty prefixes, the directories are either appended or prepended to the existing set for that namespace. The prefix must end with a namespace separator (`\`); otherwise, an `InvalidArgumentException` is thrown.
     *
     * @param string $prefix Namespace prefix, must end with a trailing backslash for non-empty prefixes.
     * @param string[]|string $paths One or more PSR-4 base directories to associate with the prefix.
     * @param bool $prepend If true, directories are prepended; otherwise, they are appended.
     *
     * @throws \InvalidArgumentException If a non-empty prefix does not end with a namespace separator.
     */
    public function addPsr4($prefix, $paths, $prepend = false)
    {
        $paths = (array) $paths;
        if (!$prefix) {
            // Register directories for the root namespace.
            if ($prepend) {
                $this->fallbackDirsPsr4 = array_merge(
                    $paths,
                    $this->fallbackDirsPsr4
                );
            } else {
                $this->fallbackDirsPsr4 = array_merge(
                    $this->fallbackDirsPsr4,
                    $paths
                );
            }
        } elseif (!isset($this->prefixDirsPsr4[$prefix])) {
            // Register directories for a new namespace.
            $length = strlen($prefix);
            if ('\\' !== $prefix[$length - 1]) {
                throw new \InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
            }
            $this->prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
            $this->prefixDirsPsr4[$prefix] = $paths;
        } elseif ($prepend) {
            // Prepend directories for an already registered namespace.
            $this->prefixDirsPsr4[$prefix] = array_merge(
                $paths,
                $this->prefixDirsPsr4[$prefix]
            );
        } else {
            // Append directories for an already registered namespace.
            $this->prefixDirsPsr4[$prefix] = array_merge(
                $this->prefixDirsPsr4[$prefix],
                $paths
            );
        }
    }

    /**
     * Sets the PSR-0 base directories for a given prefix, replacing any existing directories for that prefix.
     *
     * If the prefix is empty, sets the fallback directories for PSR-0 autoloading.
     *
     * @param string $prefix Namespace prefix for PSR-0 autoloading. An empty string sets fallback directories.
     * @param string[]|string $paths One or more base directories to associate with the prefix.
     */
    public function set($prefix, $paths)
    {
        if (!$prefix) {
            $this->fallbackDirsPsr0 = (array) $paths;
        } else {
            $this->prefixesPsr0[$prefix[0]][$prefix] = (array) $paths;
        }
    }

    /**
     * Sets the PSR-4 base directories for a namespace prefix, replacing any existing directories for that prefix.
     *
     * If the prefix is an empty string, sets the PSR-4 fallback directories instead. Non-empty prefixes must end with a namespace separator (`\`).
     *
     * @param string $prefix Namespace prefix, with trailing backslash.
     * @param string[]|string $paths One or more PSR-4 base directories.
     * @throws \InvalidArgumentException If a non-empty prefix does not end with a backslash.
     */
    public function setPsr4($prefix, $paths)
    {
        if (!$prefix) {
            $this->fallbackDirsPsr4 = (array) $paths;
        } else {
            $length = strlen($prefix);
            if ('\\' !== $prefix[$length - 1]) {
                throw new \InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
            }
            $this->prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
            $this->prefixDirsPsr4[$prefix] = (array) $paths;
        }
    }

    /**
     * Enables or disables searching the PHP include path when locating class files.
     *
     * @param bool $useIncludePath True to enable include path searching, false to disable.
     */
    public function setUseIncludePath($useIncludePath)
    {
        $this->useIncludePath = $useIncludePath;
    }

    /****
     * Returns whether the autoloader searches the PHP include path for classes.
     *
     * @return bool True if include path searching is enabled, false otherwise.
     */
    public function getUseIncludePath()
    {
        return $this->useIncludePath;
    }

    /**
     * Enables or disables authoritative classmap mode.
     *
     * When enabled, only classes present in the class map will be loaded; PSR-0, PSR-4, and fallback directory lookups are skipped.
     *
     * @param bool $classMapAuthoritative True to enable authoritative mode, false to allow directory lookups.
     */
    public function setClassMapAuthoritative($classMapAuthoritative)
    {
        $this->classMapAuthoritative = $classMapAuthoritative;
    }

    /****
     * Determines whether class loading is restricted to the class map only.
     *
     * @return bool True if only the class map is used for class resolution; false if other lookup strategies are allowed.
     */
    public function isClassMapAuthoritative()
    {
        return $this->classMapAuthoritative;
    }

    /**
     * Sets the APCu cache prefix for class lookups if APCu is enabled.
     *
     * If APCu is not available or not enabled, disables APCu caching by setting the prefix to null.
     *
     * @param string|null $apcuPrefix Prefix to use for APCu cache, or null to disable.
     */
    public function setApcuPrefix($apcuPrefix)
    {
        $this->apcuPrefix = function_exists('apcu_fetch') && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN) ? $apcuPrefix : null;
    }

    /**
     * Returns the APCu cache prefix if APCu caching is enabled, or null otherwise.
     *
     * @return string|null The APCu prefix string, or null if APCu caching is disabled.
     */
    public function getApcuPrefix()
    {
        return $this->apcuPrefix;
    }

    /****
     * Registers this class loader instance with the SPL autoload stack.
     *
     * If a vendor directory is set, the loader is also tracked in the static registry keyed by vendor directory.
     *
     * @param bool $prepend If true, the loader is prepended to the autoload stack; otherwise, it is appended.
     */
    public function register($prepend = false)
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);

        if (null === $this->vendorDir) {
            return;
        }

        if ($prepend) {
            self::$registeredLoaders = array($this->vendorDir => $this) + self::$registeredLoaders;
        } else {
            unset(self::$registeredLoaders[$this->vendorDir]);
            self::$registeredLoaders[$this->vendorDir] = $this;
        }
    }

    /**
     * Removes this class loader instance from the SPL autoload stack and unregisters it from the internal registry.
     *
     * This prevents the loader from being used for future class loading operations.
     *
     * @return void
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));

        if (null !== $this->vendorDir) {
            unset(self::$registeredLoaders[$this->vendorDir]);
        }
    }

    /****
     * Attempts to load the specified class or interface by locating and including its file.
     *
     * @param string $class The fully qualified name of the class or interface to load.
     * @return true|null Returns true if the class was successfully loaded, or null if not found.
     */
    public function loadClass($class)
    {
        if ($file = $this->findFile($class)) {
            $includeFile = self::$includeFile;
            $includeFile($file);

            return true;
        }

        return null;
    }

    /**
     * Returns the file path for a given class name if it can be resolved by the loader.
     *
     * Searches the class map, APCu cache, PSR-4 and PSR-0 mappings, fallback directories, and optionally the PHP include path to locate the file defining the specified class. If running on HHVM, also checks for `.hh` files. Caches results and missing classes for faster subsequent lookups.
     *
     * @param string $class Fully qualified class name to locate.
     * @return string|false Absolute file path if found, or false if the class cannot be resolved.
     */
    public function findFile($class)
    {
        // class map lookup
        if (isset($this->classMap[$class])) {
            return $this->classMap[$class];
        }
        if ($this->classMapAuthoritative || isset($this->missingClasses[$class])) {
            return false;
        }
        if (null !== $this->apcuPrefix) {
            $file = apcu_fetch($this->apcuPrefix.$class, $hit);
            if ($hit) {
                return $file;
            }
        }

        $file = $this->findFileWithExtension($class, '.php');

        // Search for Hack files if we are running on HHVM
        if (false === $file && defined('HHVM_VERSION')) {
            $file = $this->findFileWithExtension($class, '.hh');
        }

        if (null !== $this->apcuPrefix) {
            apcu_add($this->apcuPrefix.$class, $file);
        }

        if (false === $file) {
            // Remember that this class does not exist.
            $this->missingClasses[$class] = true;
        }

        return $file;
    }

    /****
     * Retrieves all currently registered ClassLoader instances.
     *
     * @return array<string, self> An associative array mapping vendor directory paths to their registered ClassLoader instances.
     */
    public static function getRegisteredLoaders()
    {
        return self::$registeredLoaders;
    }

    /**
     * Resolves the file path for a given class name and file extension using PSR-4, PSR-0, and fallback directory mappings.
     *
     * Searches for the class file by applying PSR-4 and PSR-0 autoloading rules, including fallback directories and optionally the PHP include path. Returns the full file path if found, or false if the class file cannot be located.
     *
     * @param string $class Fully qualified class name.
     * @param string $ext File extension to append (e.g., '.php').
     * @return string|false The resolved file path if found, or false if not found.
     */
    private function findFileWithExtension($class, $ext)
    {
        // PSR-4 lookup
        $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR) . $ext;

        $first = $class[0];
        if (isset($this->prefixLengthsPsr4[$first])) {
            $subPath = $class;
            while (false !== $lastPos = strrpos($subPath, '\\')) {
                $subPath = substr($subPath, 0, $lastPos);
                $search = $subPath . '\\';
                if (isset($this->prefixDirsPsr4[$search])) {
                    $pathEnd = DIRECTORY_SEPARATOR . substr($logicalPathPsr4, $lastPos + 1);
                    foreach ($this->prefixDirsPsr4[$search] as $dir) {
                        if (file_exists($file = $dir . $pathEnd)) {
                            return $file;
                        }
                    }
                }
            }
        }

        // PSR-4 fallback dirs
        foreach ($this->fallbackDirsPsr4 as $dir) {
            if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPathPsr4)) {
                return $file;
            }
        }

        // PSR-0 lookup
        if (false !== $pos = strrpos($class, '\\')) {
            // namespaced class name
            $logicalPathPsr0 = substr($logicalPathPsr4, 0, $pos + 1)
                . strtr(substr($logicalPathPsr4, $pos + 1), '_', DIRECTORY_SEPARATOR);
        } else {
            // PEAR-like class name
            $logicalPathPsr0 = strtr($class, '_', DIRECTORY_SEPARATOR) . $ext;
        }

        if (isset($this->prefixesPsr0[$first])) {
            foreach ($this->prefixesPsr0[$first] as $prefix => $dirs) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($dirs as $dir) {
                        if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPathPsr0)) {
                            return $file;
                        }
                    }
                }
            }
        }

        // PSR-0 fallback dirs
        foreach ($this->fallbackDirsPsr0 as $dir) {
            if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPathPsr0)) {
                return $file;
            }
        }

        // PSR-0 include paths.
        if ($this->useIncludePath && $file = stream_resolve_include_path($logicalPathPsr0)) {
            return $file;
        }

        return false;
    }

    /****
     * Initializes the static closure used for scope-isolated file inclusion.
     *
     * Sets up a closure that includes files without exposing `$this` or `self` to the included file's scope.
     * Does nothing if the closure is already initialized.
     *
     * @return void
     */
    private static function initializeIncludeClosure()
    {
        if (self::$includeFile !== null) {
            return;
        }

        /**
         * Scope isolated include.
         *
         * Prevents access to $this/self from included files.
         *
         * @param  string $file
         * @return void
         */
        self::$includeFile = \Closure::bind(static function($file) {
            include $file;
        }, null, null);
    }
}

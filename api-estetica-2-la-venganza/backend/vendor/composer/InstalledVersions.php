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

namespace Composer;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;

/**
 * This class is copied in every Composer installed project and available to all
 *
 * See also https://getcomposer.org/doc/07-runtime.md#installed-versions
 *
 * To require its presence, you can require `composer-runtime-api ^2.0`
 *
 * @final
 */
class InstalledVersions
{
    /**
     * @var string|null if set (by reflection by Composer), this should be set to the path where this class is being copied to
     * @internal
     */
    private static $selfDir = null;

    /**
     * @var mixed[]|null
     * @psalm-var array{root: array{name: string, pretty_version: string, version: string, reference: string|null, type: string, install_path: string, aliases: string[], dev: bool}, versions: array<string, array{pretty_version?: string, version?: string, reference?: string|null, type?: string, install_path?: string, aliases?: string[], dev_requirement: bool, replaced?: string[], provided?: string[]}>}|array{}|null
     */
    private static $installed;

    /**
     * @var bool
     */
    private static $installedIsLocalDir;

    /**
     * @var bool|null
     */
    private static $canGetVendors;

    /**
     * @var array[]
     * @psalm-var array<string, array{root: array{name: string, pretty_version: string, version: string, reference: string|null, type: string, install_path: string, aliases: string[], dev: bool}, versions: array<string, array{pretty_version?: string, version?: string, reference?: string|null, type?: string, install_path?: string, aliases?: string[], dev_requirement: bool, replaced?: string[], provided?: string[]}>}>
     */
    private static $installedByVendor = array();

    /**
     * Returns the names of all packages currently present, including installed, replaced, or provided packages.
     *
     * @return string[] List of package names.
     * @psalm-return list<string>
     */
    public static function getInstalledPackages()
    {
        $packages = array();
        foreach (self::getInstalled() as $installed) {
            $packages[] = array_keys($installed['versions']);
        }

        if (1 === \count($packages)) {
            return $packages[0];
        }

        return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
    }

    /**
     * Returns the names of all installed packages matching the specified type.
     *
     * @param string $type The package type to filter by (e.g., 'library').
     * @return string[] List of package names with the given type.
     */
    public static function getInstalledPackagesByType($type)
    {
        $packagesByType = array();

        foreach (self::getInstalled() as $installed) {
            foreach ($installed['versions'] as $name => $package) {
                if (isset($package['type']) && $package['type'] === $type) {
                    $packagesByType[] = $name;
                }
            }
        }

        return $packagesByType;
    }

    /****
     * Determines if a package is installed, including if it is provided or replaced by another package.
     *
     * Returns true if the specified package is present in any installed dataset. If $includeDevRequirements is false, only considers packages that are not marked as development requirements.
     *
     * @param string $packageName Name of the package to check.
     * @param bool $includeDevRequirements Whether to include packages required for development only.
     * @return bool True if the package is installed (or provided/replaced), false otherwise.
     */
    public static function isInstalled($packageName, $includeDevRequirements = true)
    {
        foreach (self::getInstalled() as $installed) {
            if (isset($installed['versions'][$packageName])) {
                return $includeDevRequirements || !isset($installed['versions'][$packageName]['dev_requirement']) || $installed['versions'][$packageName]['dev_requirement'] === false;
            }
        }

        return false;
    }

    /****
     * Determines if the installed version(s) of a package satisfy a given version constraint.
     *
     * Uses the provided VersionParser to evaluate whether the installed package version(s) match the specified constraint.
     *
     * @param VersionParser $parser Instance used to parse version constraints.
     * @param string $packageName Name of the package to check.
     * @param string|null $constraint Version constraint to test against.
     * @return bool True if the installed version(s) satisfy the constraint, false otherwise.
     *
     * @throws OutOfBoundsException If the package is not installed.
     */
    public static function satisfies(VersionParser $parser, $packageName, $constraint)
    {
        $constraint = $parser->parseConstraints((string) $constraint);
        $provided = $parser->parseConstraints(self::getVersionRanges($packageName));

        return $provided->matches($constraint);
    }

    /****
     * Returns a version constraint string representing all installed version ranges for the specified package.
     *
     * The returned constraint includes the pretty version, aliases, replaced, and provided versions, joined by '||'.
     * Throws an OutOfBoundsException if the package is not installed.
     *
     * @param string $packageName The name of the package to query.
     * @return string A version constraint string usable with composer/semver.
     * @throws \OutOfBoundsException If the package is not installed.
     */
    public static function getVersionRanges($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            $ranges = array();
            if (isset($installed['versions'][$packageName]['pretty_version'])) {
                $ranges[] = $installed['versions'][$packageName]['pretty_version'];
            }
            if (array_key_exists('aliases', $installed['versions'][$packageName])) {
                $ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
            }
            if (array_key_exists('replaced', $installed['versions'][$packageName])) {
                $ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
            }
            if (array_key_exists('provided', $installed['versions'][$packageName])) {
                $ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
            }

            return implode(' || ', $ranges);
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }

    /**
     * Returns the normalized version string of the specified installed package.
     *
     * If the package is replaced or provided but not actually installed, null is returned. Throws an OutOfBoundsException if the package is not installed.
     *
     * @param string $packageName The name of the package to query.
     * @return string|null The normalized version string, or null if not available.
     * @throws \OutOfBoundsException If the package is not installed.
     */
    public static function getVersion($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            if (!isset($installed['versions'][$packageName]['version'])) {
                return null;
            }

            return $installed['versions'][$packageName]['version'];
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }

    /****
     * Returns the human-readable version string of the specified installed package.
     *
     * If the package is only replaced or provided but not actually installed, null is returned. Throws an OutOfBoundsException if the package is not installed.
     *
     * @param string $packageName The name of the package to query.
     * @return string|null The pretty version string, or null if not available.
     * @throws \OutOfBoundsException If the package is not installed.
     */
    public static function getPrettyVersion($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            if (!isset($installed['versions'][$packageName]['pretty_version'])) {
                return null;
            }

            return $installed['versions'][$packageName]['pretty_version'];
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }

    /**
     * Returns the source control reference (e.g., commit hash) for the specified installed package.
     *
     * If the package is only replaced or provided but not actually installed, null is returned. Throws an OutOfBoundsException if the package is not installed.
     *
     * @param string $packageName The name of the package to query.
     * @return string|null The reference string, or null if not available.
     * @throws \OutOfBoundsException If the package is not installed.
     */
    public static function getReference($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            if (!isset($installed['versions'][$packageName]['reference'])) {
                return null;
            }

            return $installed['versions'][$packageName]['reference'];
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }

    /****
     * Returns the installation path of the specified package, or null if not available.
     *
     * If the package is replaced, provided, or is a metapackage, null is returned as the install path.
     * Throws OutOfBoundsException if the package is not installed.
     *
     * @param string $packageName The name of the package to query.
     * @return string|null The installation path, or null if not applicable.
     */
    public static function getInstallPath($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            return isset($installed['versions'][$packageName]['install_path']) ? $installed['versions'][$packageName]['install_path'] : null;
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }

    /****
     * Returns metadata for the root Composer package of the project.
     *
     * @return array Associative array containing the root package's name, pretty version, normalized version, reference, type, install path, aliases, and dev flag.
     */
    public static function getRootPackage()
    {
        $installed = self::getInstalled();

        return $installed[0]['root'];
    }

    /**
     * Returns the raw installed package data from the first loaded dataset.
     *
     * This method is deprecated; use getAllRawData() to retrieve all datasets from all autoloaders. The returned array contains information about the root package and all installed package versions as loaded from the first available installed.php file.
     *
     * @deprecated Use getAllRawData() instead.
     * @return array[] Raw installed package data from the first dataset loaded.
     */
    public static function getRawData()
    {
        @trigger_error('getRawData only returns the first dataset loaded, which may not be what you expect. Use getAllRawData() instead which returns all datasets for all autoloaders present in the process.', E_USER_DEPRECATED);

        if (null === self::$installed) {
            // only require the installed.php file if this file is loaded from its dumped location,
            // and not from its source location in the composer/composer package, see https://github.com/composer/composer/issues/9937
            if (substr(__DIR__, -8, 1) !== 'C') {
                self::$installed = include __DIR__ . '/installed.php';
            } else {
                self::$installed = array();
            }
        }

        return self::$installed;
    }

    /**
     * Retrieves all loaded raw installed package data arrays.
     *
     * @return array[] List of installed package datasets from all detected vendor directories and autoloaders.
     */
    public static function getAllRawData()
    {
        return self::getInstalled();
    }

    /****
     * Reloads the installed package data with a new dataset.
     *
     * Replaces the current installed package information with the provided data array. This is intended for advanced scenarios where multiple autoloaders or projects coexist in the same process, allowing each to access its own set of installed package metadata without interference.
     *
     * @param array[] $data The dataset representing installed package information, typically from a vendor/composer/installed.php file.
     */
    public static function reload($data)
    {
        self::$installed = $data;
        self::$installedByVendor = array();

        // when using reload, we disable the duplicate protection to ensure that self::$installed data is
        // always returned, but we cannot know whether it comes from the installed.php in __DIR__ or not,
        // so we have to assume it does not, and that may result in duplicate data being returned when listing
        // all installed packages for example
        self::$installedIsLocalDir = false;
    }

    /****
     * Returns the directory path where this class is located, using forward slashes.
     *
     * @return string The normalized directory path of the current class file.
     */
    private static function getSelfDir()
    {
        if (self::$selfDir === null) {
            self::$selfDir = strtr(__DIR__, '\\', '/');
        }

        return self::$selfDir;
    }

    /**
     * Retrieves all installed Composer package datasets from available vendor directories.
     *
     * Aggregates installed package metadata from all detected vendor directories and the local directory, supporting multiple autoloaders. Returns an array of datasets, each containing root package information and a list of installed package versions.
     *
     * @return array[] List of installed package datasets, each with root package info and versions.
     */
    private static function getInstalled()
    {
        if (null === self::$canGetVendors) {
            self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
        }

        $installed = array();
        $copiedLocalDir = false;

        if (self::$canGetVendors) {
            $selfDir = self::getSelfDir();
            foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
                $vendorDir = strtr($vendorDir, '\\', '/');
                if (isset(self::$installedByVendor[$vendorDir])) {
                    $installed[] = self::$installedByVendor[$vendorDir];
                } elseif (is_file($vendorDir.'/composer/installed.php')) {
                    /** @var array{root: array{name: string, pretty_version: string, version: string, reference: string|null, type: string, install_path: string, aliases: string[], dev: bool}, versions: array<string, array{pretty_version?: string, version?: string, reference?: string|null, type?: string, install_path?: string, aliases?: string[], dev_requirement: bool, replaced?: string[], provided?: string[]}>} $required */
                    $required = require $vendorDir.'/composer/installed.php';
                    self::$installedByVendor[$vendorDir] = $required;
                    $installed[] = $required;
                    if (self::$installed === null && $vendorDir.'/composer' === $selfDir) {
                        self::$installed = $required;
                        self::$installedIsLocalDir = true;
                    }
                }
                if (self::$installedIsLocalDir && $vendorDir.'/composer' === $selfDir) {
                    $copiedLocalDir = true;
                }
            }
        }

        if (null === self::$installed) {
            // only require the installed.php file if this file is loaded from its dumped location,
            // and not from its source location in the composer/composer package, see https://github.com/composer/composer/issues/9937
            if (substr(__DIR__, -8, 1) !== 'C') {
                /** @var array{root: array{name: string, pretty_version: string, version: string, reference: string|null, type: string, install_path: string, aliases: string[], dev: bool}, versions: array<string, array{pretty_version?: string, version?: string, reference?: string|null, type?: string, install_path?: string, aliases?: string[], dev_requirement: bool, replaced?: string[], provided?: string[]}>} $required */
                $required = require __DIR__ . '/installed.php';
                self::$installed = $required;
            } else {
                self::$installed = array();
            }
        }

        if (self::$installed !== array() && !$copiedLocalDir) {
            $installed[] = self::$installed;
        }

        return $installed;
    }
}

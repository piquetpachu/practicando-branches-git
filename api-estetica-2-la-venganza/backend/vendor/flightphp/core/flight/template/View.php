<?php

declare(strict_types=1);

namespace flight\template;

/**
 * The View class represents output to be displayed. It provides
 * methods for managing view data and inserts the data into
 * view templates upon rendering.
 *
 * @license MIT, http://flightphp.com/license
 * @copyright Copyright (c) 2011, Mike Cao <mike@mikecao.com>
 */
class View
{
    /** Location of view templates. */
    public string $path;

    /** File extension. */
    public string $extension = '.php';

    public bool $preserveVars = true;

    /**
     * View variables.
     *
     * @var array<string, mixed> $vars
     */
    protected array $vars = [];

    /** Template file. */
    private string $template;

    /****
     * Initializes the View instance with the specified templates directory path.
     *
     * @param string $path Directory path where view templates are located. Defaults to the current directory.
     */
    public function __construct(string $path = '.')
    {
        $this->path = $path;
    }

    /**
     * Retrieves the value of a template variable by key.
     *
     * @param string $key The name of the template variable.
     * @return mixed The value of the variable, or null if it is not set.
     */
    public function get(string $key)
    {
        return $this->vars[$key] ?? null;
    }

    /**
     * Assigns one or more variables to the template.
     *
     * Accepts either a single key-value pair or an iterable of key-value pairs to set multiple variables at once. Returns the instance for method chaining.
     *
     * @param string|iterable<string, mixed> $key Variable name or iterable of key-value pairs.
     * @param mixed $value Value to assign if setting a single variable.
     * @return self
     */
    public function set($key, $value = null): self
    {
        if (\is_iterable($key)) {
            foreach ($key as $k => $v) {
                $this->vars[$k] = $v;
            }
        } else {
            $this->vars[$key] = $value;
        }

        return $this;
    }

    /****
     * Determines whether a template variable with the specified key exists.
     *
     * @param string $key The name of the template variable to check.
     * @return bool True if the variable is set; otherwise, false.
     */
    public function has(string $key): bool
    {
        return isset($this->vars[$key]);
    }

    /**
     * Removes a specific template variable or clears all variables if no key is provided.
     *
     * @param string|null $key The variable key to remove, or null to clear all variables.
     * @return self The current instance for method chaining.
     */
    public function clear(?string $key = null): self
    {
        if ($key === null) {
            $this->vars = [];
        } else {
            unset($this->vars[$key]);
        }

        return $this;
    }

    /****
     * Renders a template file, extracting stored and optional data variables into the template scope.
     *
     * @param string $file Name or path of the template file to render.
     * @param array<string, mixed>|null $data Optional associative array of variables to extract into the template.
     *
     * @throws \Exception If the template file does not exist.
     */
    public function render(string $file, ?array $data = null): void
    {
        $this->template = $this->getTemplate($file);

        if (!\file_exists($this->template)) {
            $normalized_path = self::normalizePath($this->template);
            throw new \Exception("Template file not found: {$normalized_path}.");
        }

        \extract($this->vars);

        if (\is_array($data) === true) {
            \extract($data);

            if ($this->preserveVars === true) {
                $this->vars = \array_merge($this->vars, $data);
            }
        }

        include $this->template;
    }

    /**
     * Renders a template and returns its output as a string.
     *
     * Captures the rendered output of the specified template file, optionally using additional data, and returns it instead of sending it directly to the output.
     *
     * @param string $file The template file to render.
     * @param array<string, mixed>|null $data Optional data to be used within the template.
     * @return string The rendered template output.
     */
    public function fetch(string $file, ?array $data = null): string
    {
        \ob_start();

        $this->render($file, $data);

        return \ob_get_clean();
    }

    /**
     * Determines whether the specified template file exists.
     *
     * @param string $file Name or path of the template file to check.
     * @return bool True if the template file exists; otherwise, false.
     */
    public function exists(string $file): bool
    {
        return \file_exists($this->getTemplate($file));
    }

    /**
     * Resolves and returns the absolute path to a template file.
     *
     * Appends the configured extension if missing and handles both absolute and relative paths for Unix and Windows systems.
     *
     * @param string $file The template file name or path.
     * @return string The absolute path to the template file.
     */
    public function getTemplate(string $file): string
    {
        $ext = $this->extension;

        if (!empty($ext) && (\substr($file, -1 * \strlen($ext)) != $ext)) {
            $file .= $ext;
        }

        $is_windows = \strtoupper(\substr(PHP_OS, 0, 3)) === 'WIN';

        if ((\substr($file, 0, 1) === '/') || ($is_windows && \substr($file, 1, 1) === ':')) {
            return $file;
        }

        return $this->path . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * Escapes a string for safe HTML output, echoes it, and returns the escaped value.
     *
     * @param string $str The string to be escaped.
     * @return string The HTML-escaped string.
     */
    public function e(string $str): string
    {
        $value = \htmlentities($str);
        echo $value;
        return $value;
    }

    /**
     * Normalizes a file path by replacing all slashes and backslashes with the specified directory separator.
     *
     * @param string $path The file path to normalize.
     * @param string $separator The directory separator to use. Defaults to the system directory separator.
     * @return string The normalized file path.
     */
    protected static function normalizePath(string $path, string $separator = DIRECTORY_SEPARATOR): string
    {
        return \str_replace(['\\', '/'], $separator, $path);
    }
}

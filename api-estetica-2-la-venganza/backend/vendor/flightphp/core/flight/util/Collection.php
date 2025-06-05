<?php

declare(strict_types=1);

namespace flight\util;

use ArrayAccess;
use Countable;
use Iterator;
use JsonSerializable;

/**
 * The Collection class allows you to access a set of data
 * using both array and object notation.
 *
 * @license MIT, http://flightphp.com/license
 * @copyright Copyright (c) 2011, Mike Cao <mike@mikecao.com>
 * @implements ArrayAccess<string, mixed>
 * @implements Iterator<string, mixed>
 */
class Collection implements ArrayAccess, Iterator, Countable, JsonSerializable
{
    /**
     * Collection data.
     *
     * @var array<string, mixed>
     */
    private array $data;

    /****
     * Initializes the collection with optional initial data.
     *
     * @param array<string, mixed> $data Optional associative array to populate the collection.
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /****
     * Retrieves the value associated with the specified key.
     *
     * Returns the value for the given key if it exists in the collection; otherwise, returns null.
     *
     * @param string $key The key to retrieve from the collection.
     * @return mixed The value associated with the key, or null if the key does not exist.
     */
    public function __get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /****
     * Sets the value for the specified key in the collection.
     *
     * @param string $key The key to set.
     * @param mixed $value The value to assign to the key.
     */
    public function __set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Determines whether the specified key exists in the collection.
     *
     * @param string $key The key to check for existence.
     * @return bool True if the key exists; otherwise, false.
     */
    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Removes the item with the specified key from the collection.
     *
     * @param string $key The key of the item to remove.
     */
    public function __unset(string $key): void
    {
        unset($this->data[$key]);
    }

    /****
     * Retrieves the value associated with the specified offset.
     *
     * Returns the value for the given offset if it exists; otherwise, returns null.
     *
     * @param string $offset The key of the item to retrieve.
     * @return mixed The value at the specified offset, or null if not set.
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    /****
     * Sets a value in the collection at the specified offset.
     *
     * If the offset is null, the value is appended to the collection; otherwise, it is set at the given offset.
     *
     * @param string|null $offset The key at which to set the value, or null to append.
     * @param mixed $value The value to set.
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Determines whether a given key exists in the collection.
     *
     * @param string $offset The key to check for existence.
     * @return bool True if the key exists, false otherwise.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * Removes the item at the specified offset from the collection.
     *
     * @param string $offset The key of the item to remove.
     */
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    /****
     * Rewinds the collection to the first element for iteration.
     */
    public function rewind(): void
    {
        reset($this->data);
    }

    /****
     * Returns the current element in the collection during iteration.
     *
     * @return mixed The current value, or false if the collection is empty or the pointer is invalid.
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->data);
    }

    /****
     * Returns the current key in the collection during iteration.
     *
     * @return string|null The current key, or null if the collection is empty or the pointer is invalid.
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->data);
    }

    /****
     * Advances the internal pointer to the next element in the collection.
     */
    #[\ReturnTypeWillChange]
    public function next(): void
    {
        next($this->data);
    }

    /****
     * Determines whether the current position in the collection is valid during iteration.
     *
     * @return bool True if the current key is valid; otherwise, false.
     */
    public function valid(): bool
    {
        return key($this->data) !== null;
    }

    /**
     * Returns the number of items in the collection.
     *
     * @return int The count of elements stored in the collection.
     */
    public function count(): int
    {
        return \count($this->data);
    }

    /**
     * Returns all keys in the collection.
     *
     * @return array<int, string> An array of all keys present in the collection.
     */
    public function keys(): array
    {
        return array_keys($this->data);
    }

    /**
     * Returns the entire data array stored in the collection.
     *
     * @return array<string, mixed> The collection's data as an associative array.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /****
     * Replaces the entire collection data with the provided array.
     *
     * @param array<string, mixed> $data The new data to set for the collection.
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Returns the collection's data for JSON serialization.
     *
     * @return array The internal data array to be serialized as JSON.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * Empties the collection, removing all stored items.
     */
    public function clear(): void
    {
        $this->data = [];
    }
}

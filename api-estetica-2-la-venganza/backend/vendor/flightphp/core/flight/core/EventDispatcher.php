<?php

declare(strict_types=1);

namespace flight\core;

class EventDispatcher
{
    /** @var self|null Singleton instance of the EventDispatcher */
    private static ?self $instance = null;

    /** @var array<string, array<int, callable>> */
    protected array $listeners = [];

    /**
     * Returns the singleton instance of the EventDispatcher.
     *
     * Creates a new instance if one does not already exist.
     *
     * @return self The singleton EventDispatcher instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /****
     * Registers a callback to be invoked when the specified event is triggered.
     *
     * @param string $event The name of the event to listen for.
     * @param callable $callback The callback function to execute when the event is triggered.
     */
    public function on(string $event, callable $callback): void
    {
        if (isset($this->listeners[$event]) === false) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = $callback;
    }

    /**
     * Invokes all registered listeners for a given event, passing any provided arguments.
     *
     * Calls each listener for the specified event in order, passing the supplied arguments. If any listener returns `false`, further listener execution is halted. Returns the result of the last executed listener, or `null` if no listeners are registered.
     *
     * @param string $event Name of the event to trigger.
     * @param mixed ...$args Arguments to pass to each listener.
     * @return mixed Result of the last executed listener, or `null` if no listeners are registered.
     */
    public function trigger(string $event, ...$args)
    {
        $result = null;
        if (isset($this->listeners[$event]) === true) {
            foreach ($this->listeners[$event] as $callback) {
                $result = call_user_func_array($callback, $args);

                // If you return false, it will break the loop and stop the other event listeners.
                if ($result === false) {
                    break; // Stop executing further listeners
                }
            }
        }
        return $result;
    }

    /****
     * Determines whether any listeners are registered for a given event.
     *
     * @param string $event The name of the event to check.
     * @return bool True if one or more listeners are registered for the event; false otherwise.
     */
    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) === true && count($this->listeners[$event]) > 0;
    }

    /**
     * Returns all listeners registered for a given event.
     *
     * @param string $event The name of the event.
     * @return array<int, callable> List of callbacks registered for the event, or an empty array if none exist.
     */
    public function getListeners(string $event): array
    {
        return $this->listeners[$event] ?? [];
    }

    /**
     * Returns an array of all event names that have registered listeners.
     *
     * @return array<int, string> List of event names with at least one registered listener.
     */
    public function getAllRegisteredEvents(): array
    {
        return array_keys($this->listeners);
    }

    /**
     * Removes a specific listener callback from the list of listeners for a given event.
     *
     * If the callback is registered for the event, it will be removed. If the callback is not found, no action is taken.
     *
     * @param string $event Name of the event.
     * @param callable $callback The listener callback to remove.
     */
    public function removeListener(string $event, callable $callback): void
    {
        if (isset($this->listeners[$event]) === true && count($this->listeners[$event]) > 0) {
            $this->listeners[$event] = array_filter($this->listeners[$event], function ($listener) use ($callback) {
                return $listener !== $callback;
            });
            $this->listeners[$event] = array_values($this->listeners[$event]); // Re-index the array
        }
    }

    /**
     * Removes all listeners registered for the specified event.
     *
     * @param string $event Name of the event whose listeners should be removed.
     */
    public function removeAllListeners(string $event): void
    {
        if (isset($this->listeners[$event]) === true) {
            unset($this->listeners[$event]);
        }
    }

    /****
     * Resets the singleton instance of the EventDispatcher, allowing a new instance to be created.
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}

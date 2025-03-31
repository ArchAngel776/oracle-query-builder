<?php

namespace ArchAngel776\OracleQueryBuilder\Helpers;


/**
 * Trait Conditionals
 *
 * Provides methods for conditional execution of callbacks.
 */
trait Conditionals {
    /**
     * Conditionally executes one of the provided callbacks based on the boolean condition.
     *
     * Both callbacks must accept the current instance ($this) as an argument and return the instance.
     *
     * @param bool $condition The condition to evaluate.
     * @param callable $callbackIf A callback to execute if the condition is true. Signature: function(static $instance): static.
     * @param callable|null $callbackElse An optional callback to execute if the condition is false. Signature: function(static $instance): static.
     * @return static The current instance after processing the callback(s), or the callback result.
     */
    public function makeIf(bool $condition, callable $callbackIf, ?callable $callbackElse = null): static {
        if ($condition) {
            return $callbackIf($this);
        } elseif ($callbackElse !== null) {
            return $callbackElse($this);
        }
        return $this;
    }

    /**
     * Executes a callback from an associative array based on a given value.
     *
     * The keys in the array should be of type string or number. If a key matches the provided value, its corresponding
     * callback is executed with $this as an argument and the result is returned.
     * If no matching key is found, returns $this.
     *
     * @param mixed $value The value to match against the keys.
     * @param array<string|int, callable> $cases An associative array where each key is a string or number and each value is a callback.
     *                                           The callback must accept the current instance (static) and return the instance (static).
     * @return static The result of the matching callback if found, otherwise the current instance.
     */
    public function makeSwitch(mixed $value, array $cases): static {
        if (array_key_exists($value, $cases)) {
            $callback = $cases[$value];
            return $callback($this);
        }
        return $this;
    }
}

?>

<?php

namespace ArchAngel776\OracleQueryBuilder\Components;

use RuntimeException;
use InvalidArgumentException;
use ArchAngel776\OracleQueryBuilder\Data\QueryBuilder;
use ArchAngel776\OracleQueryBuilder\Components\Condition;


class Where implements QueryBuilder {
    /**
     * Array of Condition objects or nested Where objects.
     *
     * @var array<Condition|Where>
     */
    protected array $conditions;

    /**
     * Array of string operators.
     *
     * @var string[]
     */
    protected array $operators;

    /**
     * Global NOT flag for the WHERE clause.
     *
     * @var bool
     */
    protected bool $not;

    /**
     * Constructor.
     * Initializes conditions and operators as empty arrays and sets $not to false.
     */
    public function __construct() {
        $this->conditions = [];
        $this->operators = [];
        $this->not = false;
    }

    /**
     * Checks if there are any conditions present in the WHERE clause.
     *
     * @return bool True if there is at least one condition, false otherwise.
     */
    public function hasConditions(): bool
    {
        return !empty($this->conditions);
    }

    /**
     * Sets the global NOT flag for the WHERE clause to true.
     *
     * @return static
     */
    public function not(): static {
        $this->not = true;
        return $this;
    }

    /**
     * Returns the status of the global NOT flag.
     *
     * @return bool True if the NOT flag is set, false otherwise.
     */
    public function isNot(): bool {
        return $this->not;
    }

    /**
     * Overloaded where method.
     *
     * When called with three arguments:
     *   - string $field: The field name.
     *   - string $operator: The operator.
     *   - mixed $value: The value for the condition (either a raw value or an instance of Param).
     * In this form, if the conditions array is not empty, throws a RuntimeException;
     * otherwise, adds a new Condition with $not set to false.
     *
     * When called with one argument:
     *   - callable $callback: A callback that accepts a Where object and returns a Where object.
     * In this form, if the conditions array is not empty, throws a RuntimeException;
     * otherwise, creates a new Where object, passes it to the callback, and adds the returned Where.
     *
     * @param mixed ...$args Either (string, string, mixed) or a single callable.
     * @return static
     * @throws InvalidArgumentException if the arguments do not match expected patterns.
     * @throws RuntimeException if the conditions array is not empty or if the callback does not return a Where.
     */
    public function where(mixed ...$args): static {
        if (!empty($this->conditions)) {
            throw new RuntimeException('Conditions array is not empty.');
        }
        if (count($args) === 3) {
            [$field, $operator, $value] = $args;
            if (!is_string($field) || !is_string($operator)) {
                throw new InvalidArgumentException('Invalid argument types for where condition. Expected (string, string, mixed).');
            }
            $this->conditions[] = new Condition($field, $operator, $value, false);
            return $this;
        } elseif (count($args) === 1) {
            [$callback] = $args;
            if (!is_callable($callback)) {
                throw new InvalidArgumentException('The single argument must be callable.');
            }
            $result = $callback(new Where());
            if (!$result instanceof Where) {
                throw new RuntimeException('Callback must return an instance of Where.');
            }
            $this->conditions[] = $result;
            return $this;
        } else {
            throw new InvalidArgumentException('Invalid number of arguments for where method.');
        }
    }

    /**
     * Overloaded whereNot method.
     *
     * Works similarly to where(), but in the 3-argument form sets the Condition's $not flag to true.
     * In the callback form, the resulting Where object's global $not flag is set to true.
     *
     * @param mixed ...$args Either (string, string, mixed) or a single callable.
     * @return static
     * @throws InvalidArgumentException if the arguments do not match expected patterns.
     * @throws RuntimeException if the conditions array is not empty or if the callback does not return a Where.
     */
    public function whereNot(mixed ...$args): static {
        if (!empty($this->conditions)) {
            throw new RuntimeException('Conditions array is not empty.');
        }
        if (count($args) === 3) {
            [$field, $operator, $value] = $args;
            if (!is_string($field) || !is_string($operator)) {
                throw new InvalidArgumentException('Invalid argument types for whereNot condition. Expected (string, string, mixed).');
            }
            $this->conditions[] = new Condition($field, $operator, $value, true);
            return $this;
        } elseif (count($args) === 1) {
            [$callback] = $args;
            if (!is_callable($callback)) {
                throw new InvalidArgumentException('The single argument must be callable.');
            }
            $result = $callback(new Where());
            if (!$result instanceof Where) {
                throw new RuntimeException('Callback must return an instance of Where.');
            }
            $result->not();
            $this->conditions[] = $result;
            return $this;
        } else {
            throw new InvalidArgumentException('Invalid number of arguments for whereNot method.');
        }
    }

    /**
     * Overloaded orWhere method.
     *
     * When called with three arguments:
     *   - string $field: The field name.
     *   - string $operator: The operator.
     *   - mixed $value: The value for the condition.
     * In this form, if the conditions array is empty, throws a RuntimeException;
     * otherwise, appends the "OR" operator and adds a new Condition with $not set to false.
     *
     * When called with one argument:
     *   - callable $callback: A callback that accepts a Where object and returns a Where object.
     * In this form, if the conditions array is empty, throws a RuntimeException;
     * otherwise, appends the "OR" operator and adds the Where returned by the callback.
     *
     * @param mixed ...$args Either (string, string, mixed) or a single callable.
     * @return static
     * @throws RuntimeException if no initial condition exists or if the callback does not return a Where.
     * @throws InvalidArgumentException if the arguments do not match expected patterns.
     */
    public function orWhere(mixed ...$args): static {
        if (empty($this->conditions)) {
            throw new RuntimeException('No initial condition exists for OR condition.');
        }
        if (count($args) === 3) {
            [$field, $operator, $value] = $args;
            if (!is_string($field) || !is_string($operator)) {
                throw new InvalidArgumentException('Invalid argument types for orWhere condition. Expected (string, string, mixed).');
            }
            $this->operators[] = "OR";
            $this->conditions[] = new Condition($field, $operator, $value, false);
            return $this;
        } elseif (count($args) === 1) {
            [$callback] = $args;
            if (!is_callable($callback)) {
                throw new InvalidArgumentException('The single argument must be callable.');
            }
            $result = $callback(new Where());
            if (!$result instanceof Where) {
                throw new RuntimeException('Callback must return an instance of Where.');
            }
            $this->operators[] = "OR";
            $this->conditions[] = $result;
            return $this;
        } else {
            throw new InvalidArgumentException('Invalid number of arguments for orWhere method.');
        }
    }

    /**
     * Overloaded orWhereNot method.
     *
     * When called with three arguments:
     *   - string $field: The field name.
     *   - string $operator: The operator.
     *   - mixed $value: The value for the condition.
     * In this form, if the conditions array is empty, throws a RuntimeException;
     * otherwise, appends the "OR" operator and adds a new Condition with $not set to true.
     *
     * When called with one argument:
     *   - callable $callback: A callback that accepts a Where object and returns a Where object.
     * In this form, if the conditions array is empty, throws a RuntimeException;
     * otherwise, appends the "OR" operator and adds the Where returned by the callback after calling not() on it.
     *
     * @param mixed ...$args Either (string, string, mixed) or a single callable.
     * @return static
     * @throws RuntimeException if no initial condition exists or if the callback does not return a Where.
     * @throws InvalidArgumentException if the arguments do not match expected patterns.
     */
    public function orWhereNot(mixed ...$args): static {
        if (empty($this->conditions)) {
            throw new RuntimeException('No initial condition exists for OR condition.');
        }
        if (count($args) === 3) {
            [$field, $operator, $value] = $args;
            if (!is_string($field) || !is_string($operator)) {
                throw new InvalidArgumentException('Invalid argument types for orWhereNot condition. Expected (string, string, mixed).');
            }
            $this->operators[] = "OR";
            $this->conditions[] = new Condition($field, $operator, $value, true);
            return $this;
        } elseif (count($args) === 1) {
            [$callback] = $args;
            if (!is_callable($callback)) {
                throw new InvalidArgumentException('The single argument must be callable.');
            }
            $result = $callback(new Where());
            if (!$result instanceof Where) {
                throw new RuntimeException('Callback must return an instance of Where.');
            }
            $result->not();
            $this->operators[] = "OR";
            $this->conditions[] = $result;
            return $this;
        } else {
            throw new InvalidArgumentException('Invalid number of arguments for orWhereNot method.');
        }
    }

    /**
     * Overloaded andWhere method.
     *
     * When called with three arguments:
     *   - string $field: The field name.
     *   - string $operator: The operator.
     *   - mixed $value: The value for the condition.
     * In this form, if the conditions array is empty, throws a RuntimeException;
     * otherwise, appends the "AND" operator and adds a new Condition with $not set to false.
     *
     * When called with one argument:
     *   - callable $callback: A callback that accepts a Where object and returns a Where object.
     * In this form, if the conditions array is empty, throws a RuntimeException;
     * otherwise, appends the "AND" operator and adds the Where returned by the callback.
     *
     * @param mixed ...$args Either (string, string, mixed) or a single callable.
     * @return static
     * @throws RuntimeException if no initial condition exists or if the callback does not return a Where.
     * @throws InvalidArgumentException if the arguments do not match expected patterns.
     */
    public function andWhere(mixed ...$args): static {
        if (empty($this->conditions)) {
            throw new RuntimeException('No initial condition exists for AND condition.');
        }
        if (count($args) === 3) {
            [$field, $operator, $value] = $args;
            if (!is_string($field) || !is_string($operator)) {
                throw new InvalidArgumentException('Invalid argument types for andWhere condition. Expected (string, string, mixed).');
            }
            $this->operators[] = "AND";
            $this->conditions[] = new Condition($field, $operator, $value, false);
            return $this;
        } elseif (count($args) === 1) {
            [$callback] = $args;
            if (!is_callable($callback)) {
                throw new InvalidArgumentException('The single argument must be callable.');
            }
            $result = $callback(new Where());
            if (!$result instanceof Where) {
                throw new RuntimeException('Callback must return an instance of Where.');
            }
            $this->operators[] = "AND";
            $this->conditions[] = $result;
            return $this;
        } else {
            throw new InvalidArgumentException('Invalid number of arguments for andWhere method.');
        }
    }

    /**
     * Overloaded andWhereNot method.
     *
     * When called with three arguments:
     *   - string $field: The field name.
     *   - string $operator: The operator.
     *   - mixed $value: The value for the condition.
     * In this form, if the conditions array is empty, throws a RuntimeException;
     * otherwise, appends the "AND" operator and adds a new Condition with $not set to true.
     *
     * When called with one argument:
     *   - callable $callback: A callback that accepts a Where object and returns a Where object.
     * In this form, if the conditions array is empty, throws a RuntimeException;
     * otherwise, appends the "AND" operator and adds the Where returned by the callback after calling not() on it.
     *
     * @param mixed ...$args Either (string, string, mixed) or a single callable.
     * @return static
     * @throws RuntimeException if no initial condition exists or if the callback does not return a Where.
     * @throws InvalidArgumentException if the arguments do not match expected patterns.
     */
    public function andWhereNot(mixed ...$args): static {
        if (empty($this->conditions)) {
            throw new RuntimeException('No initial condition exists for AND condition.');
        }
        if (count($args) === 3) {
            [$field, $operator, $value] = $args;
            if (!is_string($field) || !is_string($operator)) {
                throw new InvalidArgumentException('Invalid argument types for andWhereNot condition. Expected (string, string, mixed).');
            }
            $this->operators[] = "AND";
            $this->conditions[] = new Condition($field, $operator, $value, true);
            return $this;
        } elseif (count($args) === 1) {
            [$callback] = $args;
            if (!is_callable($callback)) {
                throw new InvalidArgumentException('The single argument must be callable.');
            }
            $result = $callback(new Where());
            if (!$result instanceof Where) {
                throw new RuntimeException('Callback must return an instance of Where.');
            }
            $result->not();
            $this->operators[] = "AND";
            $this->conditions[] = $result;
            return $this;
        } else {
            throw new InvalidArgumentException('Invalid number of arguments for andWhereNot method.');
        }
    }

    /**
     * Builds the query fragment for the WHERE clause.
     *
     * Ensures that the number of conditions is exactly one greater than the number of operators,
     * then interleaves the conditions and operators. For each condition:
     * - If it's a Condition, its buildQuery() result is used directly.
     * - If it's a Where, its buildQuery() result is wrapped in parentheses, and if its isNot() returns true,
     *   it's prefixed with "NOT ".
     *
     * @return string The built query fragment.
     * @throws RuntimeException if the count of conditions is not exactly one greater than the count of operators.
     */
    public function buildQuery(): string {
        if (count($this->conditions) !== count($this->operators) + 1) {
            throw new RuntimeException("Mismatch between conditions and operators: the conditions array must contain exactly one more element than the operators array.");
        }

        $queryParts = [];
        foreach ($this->conditions as $cond) {
            if ($cond instanceof Condition) {
                $queryParts[] = $cond->buildQuery();
            } elseif ($cond instanceof Where) {
                $subQuery = $cond->buildQuery();
                if ($cond->isNot()) {
                    $queryParts[] = "NOT ({$subQuery})";
                } else {
                    $queryParts[] = "({$subQuery})";
                }
            } else {
                throw new RuntimeException("Invalid condition type encountered in Where.");
            }
        }

        $finalQuery = array_shift($queryParts);
        foreach ($this->operators as $operator) {
            $finalQuery .= " {$operator} " . array_shift($queryParts);
        }

        return $finalQuery;
    }

    /**
     * Gets the parameters for the WHERE clause.
     *
     * Iterates through the list of conditions and collects parameters from each one.
     *
     * @return array<array{0:string, 1:int}> A numeric list of parameters.
     */
    public function getParams(): array {
        $params = [];
        foreach ($this->conditions as $condition) {
            if ($condition instanceof QueryBuilder) {
                $params = array_merge($params, $condition->getParams());
            }
        }
        return $params;
    }
}

?>

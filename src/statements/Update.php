<?php

namespace ArchAngel776\OracleQueryBuilder\Statements;

use RuntimeException;
use InvalidArgumentException;
use ArchAngel776\OracleQueryBuilder\Data\QueryBuilder;
use ArchAngel776\OracleQueryBuilder\Components\Set;
use ArchAngel776\OracleQueryBuilder\Components\Where;
use ArchAngel776\OracleQueryBuilder\Helpers\Conditionals;


class Update implements QueryBuilder {
    use Conditionals;

    /**
     * The table name for the UPDATE statement.
     *
     * @var string
     */
    protected string $table;

    /**
     * An array of Set objects for the UPDATE statement.
     *
     * @var Set[]
     */
    protected array $sets;

    /**
     * WHERE clause for the UPDATE statement.
     *
     * @var Where
     */
    protected Where $where;

    /**
     * Constructor.
     * Initializes $table as an empty string and the sets array as empty.
     */
    public function __construct()
    {
        $this->table = "";
        $this->sets  = [];
        $this->where = new Where();
    }

    /**
     * Sets the table name for the UPDATE statement.
     *
     * @param string $table The table name.
     * @return static
     */
    public function table(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Setter "set" for adding SET clauses.
     *
     * This method works in two variants:
     *
     * 1. If one argument is provided (an associative array), it iterates over the array,
     *    creating a new Set object for each key/value pair and adding it to the $sets array.
     *
     * 2. If two arguments are provided, where the first is a string (field name) and the
     *    second is a mixed value, it creates a single Set object and adds it to the $sets array.
     *
     * @param mixed ...$args Either an associative array or a string and a mixed value.
     * @return static Returns the current instance for method chaining.
     * @throws InvalidArgumentException if the number or type of arguments is invalid.
     */
    public function set(...$args): static
    {
        $argCount = count($args);
        if ($argCount === 1) {
            $assocArray = $args[0];
            if (!is_array($assocArray)) {
                throw new InvalidArgumentException("Expected an associative array as the single argument.");
            }
            foreach ($assocArray as $field => $value) {
                $this->sets[] = new Set($field, $value);
            }
        } elseif ($argCount === 2) {
            [$field, $value] = $args;
            if (!is_string($field)) {
                throw new InvalidArgumentException("First argument must be a string (field name).");
            }
            $this->sets[] = new Set($field, $value);
        } else {
            throw new InvalidArgumentException("Invalid number of arguments for method set().");
        }
        return $this;
    }

    /**
     * Delegates to the where() method of the $where property.
     *
     * @param mixed ...$args Either (string, string, mixed) or a single callable that accepts a Where and returns a Where.
     * @return static
     */
    public function where(mixed ...$args): static
    {
        $this->where->where(...$args);
        return $this;
    }

    /**
     * Delegates to the whereNot() method of the $where property.
     *
     * @param mixed ...$args Either (string, string, mixed) or a single callable that accepts a Where and returns a Where.
     * @return static
     */
    public function whereNot(mixed ...$args): static
    {
        $this->where->whereNot(...$args);
        return $this;
    }

    /**
     * Delegates to the orWhere() method of the $where property.
     *
     * @param mixed ...$args Either (string, string, mixed) or a single callable that accepts a Where and returns a Where.
     * @return static
     */
    public function orWhere(mixed ...$args): static
    {
        $this->where->orWhere(...$args);
        return $this;
    }

    /**
     * Delegates to the orWhereNot() method of the $where property.
     *
     * @param mixed ...$args Either (string, string, mixed) or a single callable that accepts a Where and returns a Where.
     * @return static
     */
    public function orWhereNot(mixed ...$args): static
    {
        $this->where->orWhereNot(...$args);
        return $this;
    }

    /**
     * Delegates to the andWhere() method of the $where property.
     *
     * @param mixed ...$args Either (string, string, mixed) or a single callable that accepts a Where and returns a Where.
     * @return static
     */
    public function andWhere(mixed ...$args): static
    {
        $this->where->andWhere(...$args);
        return $this;
    }

    /**
     * Delegates to the andWhereNot() method of the $where property.
     *
     * @param mixed ...$args Either (string, string, mixed) or a single callable that accepts a Where and returns a Where.
     * @return static
     */
    public function andWhereNot(mixed ...$args): static
    {
        $this->where->andWhereNot(...$args);
        return $this;
    }

    /**
     * Builds the complete UPDATE query.
     *
     * Constructs the query in the following order:
     * 1. UPDATE clause with the table name.
     * 2. SET clause built from the $sets array.
     * 3. WHERE clause, if any conditions exist in $where.
     *
     * @return string The complete built UPDATE query.
     * @throws RuntimeException if no table or no SET clauses are provided.
     */
    public function buildQuery(): string
    {
        if (empty($this->table)) {
            throw new RuntimeException("Table not specified for UPDATE.");
        }
        if (empty($this->sets)) {
            throw new RuntimeException("No SET clauses provided for UPDATE.");
        }
        
        $query = "UPDATE " . $this->table;
        
        $setParts = [];
        foreach ($this->sets as $set) {
            $setParts[] = $set->buildQuery();
        }
        $query .= " SET " . implode(", ", $setParts);
        
        if ($this->where->hasConditions()) {
            $query .= " WHERE " . $this->where->buildQuery();
        }
        
        return $query;
    }

    /**
     * Gets the parameters for the UPDATE query.
     *
     * Aggregates parameters from all SET clauses and the WHERE clause.
     *
     * @return array<array{0:string, 1:int}> A numeric list of parameters.
     */
    public function getParams(): array
    {
        $params = [];
        
        // Parameters from SET clauses.
        foreach ($this->sets as $set) {
            if ($set instanceof QueryBuilder) {
                $params = array_merge($params, $set->getParams());
            }
        }
        
        // Parameters from WHERE clause.
        if ($this->where instanceof QueryBuilder) {
            $params = array_merge($params, $this->where->getParams());
        }
        
        return $params;
    }
}

?>

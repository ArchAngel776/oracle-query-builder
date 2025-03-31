<?php

namespace ArchAngel776\OracleQueryBuilder\Statements;


use RuntimeException;
use ArchAngel776\OracleQueryBuilder\Data\QueryBuilder;
use ArchAngel776\OracleQueryBuilder\Components\Where;
use ArchAngel776\OracleQueryBuilder\Helpers\Conditionals;


class Delete implements QueryBuilder {
    use Conditionals;
    
    /**
     * The table from which rows will be deleted.
     *
     * @var string
     */
    protected string $table;

    /**
     * The WHERE clause for the DELETE statement.
     *
     * @var Where
     */
    protected Where $where;

    /**
     * Constructor.
     * Initializes $table as an empty string and $where as a new Where instance.
     */
    public function __construct()
    {
        $this->table = "";
        $this->where = new Where();
    }

    /**
     * Sets the table name for the DELETE statement.
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
     * Builds the DELETE query.
     *
     * Constructs the query in the following order:
     * 1. DELETE FROM clause with the table name.
     * 2. WHERE clause if conditions exist in $where.
     *
     * @return string The complete built DELETE query.
     * @throws RuntimeException if the table name is not specified.
     */
    public function buildQuery(): string
    {
        if (empty($this->table)) {
            throw new RuntimeException("Table not specified for DELETE.");
        }
        
        $query = "DELETE FROM " . $this->table;
        if ($this->where->hasConditions()) {
            $query .= " WHERE " . $this->where->buildQuery();
        }
        return $query;
    }

    /**
     * Gets the parameters for the DELETE query.
     *
     * Aggregates parameters from the WHERE clause.
     *
     * @return array<array{0:string, 1:int}> A numeric list of parameters.
     */
    public function getParams(): array
    {
        return $this->where->getParams();
    }
}
?>

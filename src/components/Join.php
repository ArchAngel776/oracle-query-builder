<?php

namespace ArchAngel776\OracleQueryBuilder\Components;

use RuntimeException;
use ArchAngel776\OracleQueryBuilder\Data\QueryBuilder;
use ArchAngel776\OracleQueryBuilder\Statements\Select;


class Join implements QueryBuilder {
    /**
     * The join type (e.g. "INNER", "LEFT", "RIGHT", "OUTER").
     *
     * @var string
     */
    protected string $type;

    /**
     * The table to join.
     * This can be either a table name (string) or a callback that accepts a Select object and returns a Select.
     *
     * @var string|callable
     * @psalm-var string|(callable(Select): Select)
     */
    protected mixed $table;

    /**
     * Optional alias for the table.
     *
     * @var string|null
     */
    protected ?string $alias;

    /**
     * The ON condition for the join.
     *
     * @var On|null
     */
    protected ?On $on;

    /**
     * Constructor.
     *
     * @param string $type The join type.
     * @param string|callable $table The table name or a callback (function(Select): Select).
     * @param string|null $alias Optional alias for the table.
     */
    public function __construct(string $type, string|callable $table, ?string $alias = null)
    {
        $this->type  = $type;
        $this->table = $table;
        $this->alias = $alias;
        $this->on    = null;
    }

    /**
     * Sets the ON condition for the join.
     *
     * @param On $on The join condition.
     * @return static
     */
    public function setOn(On $on): static
    {
        $this->on = $on;
        return $this;
    }

    /**
     * Builds the JOIN clause query fragment.
     *
     * If $table is a callback, it is called with new Select() and its result is used as the join table.
     * If $table is a string, it is used directly.
     * If an alias is provided, it is appended without the "AS" keyword.
     * Finally, if an ON condition is set, its buildQuery() result is appended preceded by " ON ".
     *
     * @return string The built JOIN clause.
     * @throws RuntimeException if the table callback does not return an instance of Select.
     */
    public function buildQuery(): string
    {
        if (is_callable($this->table)) {
            $resultSelect = call_user_func($this->table, new Select());
            if (!$resultSelect instanceof Select) {
                throw new RuntimeException("Table callback must return an instance of Select.");
            }
            $tablePart = "(" . $resultSelect->getRoot()->buildQuery() . ")";
        } else {
            // Instead of throwing an exception, we cast the value to string.
            $tablePart = (string)$this->table;
        }

        $aliasPart = $this->alias !== null ? " " . $this->alias : "";
        $query = "{$this->type} JOIN {$tablePart}{$aliasPart}";
        if ($this->on !== null) {
            $query .= " ON " . $this->on->buildQuery();
        }
        return $query;
    }

    /**
     * Gets the parameters for the JOIN clause.
     *
     * If $table is a callback, calls it with new Select() and merges its parameters.
     * Also merges parameters from the ON condition if present.
     *
     * @return array<array{0:string, 1:int}> A numeric list of parameters.
     * @throws RuntimeException if the table callback does not return an instance of Select.
     */
    public function getParams(): array
    {
        $params = [];
        if (is_callable($this->table)) {
            $resultSelect = call_user_func($this->table, new Select());
            if (!$resultSelect instanceof Select) {
                throw new RuntimeException("Table callback must return an instance of Select.");
            }
            $params = array_merge($params, $resultSelect->getRoot()->getParams());
        }
        if ($this->on !== null) {
            $params = array_merge($params, $this->on->getParams());
        }
        return $params;
    }
}
?>

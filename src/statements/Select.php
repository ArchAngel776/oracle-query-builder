<?php

namespace ArchAngel776\OracleQueryBuilder\Statements;

use RuntimeException;
use ArchAngel776\OracleQueryBuilder\Data\QueryBuilder;
use ArchAngel776\OracleQueryBuilder\Components\Field;
use ArchAngel776\OracleQueryBuilder\Components\AggregateField;
use ArchAngel776\OracleQueryBuilder\Components\CaseField;
use ArchAngel776\OracleQueryBuilder\Components\Join;
use ArchAngel776\OracleQueryBuilder\Components\On;
use ArchAngel776\OracleQueryBuilder\Components\Where;
use ArchAngel776\OracleQueryBuilder\Components\Order;
use ArchAngel776\OracleQueryBuilder\Helpers\Conditionals;


/**
 * Class Select
 *
 * Builds a SELECT statement with various aggregate and helper functions.
 */
class Select implements QueryBuilder
{
    use Conditionals;

    /**
     * Parent Select statement of current one.
     *
     * @var Select|null
     */
    protected ?Select $parent;

    /**
     * Array of Field or AggregateField objects for the SELECT statement.
     *
     * @var Field[]
     */
    protected array $fields;

    /**
     * Global distinct flag for the query.
     *
     * @var bool
     */
    protected bool $distinct;

    /**
     * Source for the SELECT statement.
     * It can be either a table name (string) or a nested query (Select).
     *
     * @var string|Select
     */
    protected string|Select $source;

    /**
     * Alias for the source, if any.
     *
     * @var string|null
     */
    protected ?string $sourceAlias;

    /**
     * Array of Join objects.
     *
     * @var Join[]
     */
    protected array $joins;

    /**
     * WHERE clause for the query.
     *
     * @var Where
     */
    protected Where $where;

    /**
     * Array of GROUP BY fields.
     *
     * @var string[]
     */
    protected array $groupBy;

    /**
     * HAVING clause for the query.
     *
     * @var Where|null
     */
    protected ?Where $having;

    /**
     * Array of Order objects for the ORDER BY clause.
     *
     * @var Order[]
     */
    protected array $orderBy;

    /**
     * The LIMIT value for the query.
     *
     * @var int|null
     */
    protected ?int $limit;

    /**
     * The OFFSET value for the query.
     *
     * @var int|null
     */
    protected ?int $offset;

    /**
     * The united SELECT query that should appear after the current query.
     *
     * @var Select|null
     */
    protected ?Select $unitedWith;


    /**
     * Constructor.
     * 
     * @param Select|null $parent [Optional]
     */
    public function __construct(?Select $parent = null)
    {
        $this->parent = $parent;
        $this->fields = [];
        $this->distinct = false;
        $this->source = '';
        $this->sourceAlias = null;
        $this->joins = [];
        $this->where = new Where();
        $this->groupBy = [];
        $this->having = null;
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;
        $this->unitedWith  = null;
    }

    /**
     * Adds fields to the SELECT statement.
     *
     * Each provided string is wrapped in a Field object.
     *
     * @param string ...$fields Fields to be added.
     * @return static
     */
    public function select(string ...$fields): static
    {
        foreach ($fields as $fieldName)
        {
            $this->fields[] = new Field($fieldName);
        }

        return $this;
    }

    /**
     * Sets the distinct flag for the query to true.
     *
     * @return static
     */
    public function distinct(): static
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Adds aliases to the SELECT statement.
     *
     * Aliases are applied in reverse order: if one alias is provided, it is assigned to the last field;
     * if two aliases are provided, the first is assigned to the penultimate field and the second to the last field.
     *
     * @param string ...$aliases Aliases to be added.
     * @return static
     * @throws \RuntimeException if the number of aliases exceeds the number of fields.
     */
    public function as(string ...$aliases): static
    {
        $numAliases = count($aliases);
        $numFields = count($this->fields);

        if ($numAliases > $numFields)
        {
            throw new \RuntimeException('Number of aliases exceeds the number of fields.');
        }

        $startIndex = $numFields - $numAliases;

        for ($i = 0; $i < $numAliases; $i++)
        {
            $this->fields[$startIndex + $i]->setAlias($aliases[$i]);
        }

        return $this;
    }

    /**
     * Adds a COUNT aggregate field to the SELECT statement.
     *
     * @param string $field The field for which to apply the COUNT function.
     * @param bool $distinct Optional flag to apply DISTINCT (default false).
     * @return static
     */
    public function count(string $field, bool $distinct = false): static
    {
        $this->fields[] = new AggregateField($field, "COUNT", $distinct);
        return $this;
    }

    /**
     * Adds an AVG aggregate field to the SELECT statement.
     *
     * @param string $field The field for which to apply the AVG function.
     * @param bool $distinct Optional flag to apply DISTINCT (default false).
     * @return static
     */
    public function avg(string $field, bool $distinct = false): static
    {
        $this->fields[] = new AggregateField($field, "AVG", $distinct);
        return $this;
    }

    /**
     * Adds a SUM aggregate field to the SELECT statement.
     *
     * @param string $field The field for which to apply the SUM function.
     * @param bool $distinct Optional flag to apply DISTINCT (default false).
     * @return static
     */
    public function sum(string $field, bool $distinct = false): static
    {
        $this->fields[] = new AggregateField($field, "SUM", $distinct);
        return $this;
    }

    /**
     * Adds a MIN aggregate field to the SELECT statement.
     *
     * @param string $field The field for which to apply the MIN function.
     * @param bool $distinct Optional flag to apply DISTINCT (default false).
     * @return static
     */
    public function min(string $field, bool $distinct = false): static
    {
        $this->fields[] = new AggregateField($field, "MIN", $distinct);
        return $this;
    }

    /**
     * Adds a MAX aggregate field to the SELECT statement.
     *
     * @param string $field The field for which to apply the MAX function.
     * @param bool $distinct Optional flag to apply DISTINCT (default false).
     * @return static
     */
    public function max(string $field, bool $distinct = false): static
    {
        $this->fields[] = new AggregateField($field, "MAX", $distinct);
        return $this;
    }

    /**
     * Adds a LISTAGG aggregate field to the SELECT statement.
     *
     * This method does not include an ORDER BY clause.
     * Use withinGroup() to add a WITHIN GROUP (ORDER BY ...) expression.
     *
     * If the expression is an array, its elements are concatenated with "||".
     *
     * @param string|string[] $expression The aggregation expression or an array of expressions.
     * @param string $delimiter The delimiter for the aggregation.
     * @param bool $distinct Optional flag to apply DISTINCT (default false).
     * @return static
     */
    public function listAgg(string | array $expression, string $delimiter, bool $distinct = false): static
    {
        if (is_array($expression))
        {
            $expression = implode(" || ", $expression);
        }

        $this->fields[] = new AggregateField("{$expression}, '{$delimiter}'", "LISTAGG", $distinct);
        return $this;
    }

    /**
     * Adds a WITHIN GROUP (ORDER BY ...) clause to the last field.
     *
     * This method retrieves the last field in the fields array, checks if it is an AggregateField
     * with an aggregation function of LISTAGG. If not, it throws a RuntimeException.
     * Otherwise, it sets the additional expression for the field.
     *
     * @param string ...$orderBy Order by fields to be used in the WITHIN GROUP clause.
     * @return static
     * @throws \RuntimeException if the last field is not an AggregateField or its aggregation function is not LISTAGG.
     */
    public function withinGroup(string ...$orderBy): static
    {
        if (empty($this->fields))
        {
            throw new \RuntimeException('No fields available to apply WITHIN GROUP clause.');
        }

        $lastField = end($this->fields);

        if (!$lastField instanceof AggregateField)
        {
            throw new \RuntimeException('The last field is not an aggregate field.');
        }

        if ($lastField->getAggregationFunction() !== "LISTAGG")
        {
            throw new \RuntimeException('The aggregation function of the last field is not LISTAGG.');
        }

        $orderByClause = implode(', ', $orderBy);
        $lastField->setAdditionalExpression("WITHIN GROUP (ORDER BY {$orderByClause})");

        return $this;
    }

    /**
     * Adds a new CASE clause to the query.
     *
     * This method inserts a new instance of CaseField into the $fields array.
     *
     * @return static
     */
    public function case(): static
    {
        $this->fields[] = new CaseField();
        return $this;
    }

    /**
     * Adds a WHEN clause to the last CASE clause in the query.
     *
     * Accepts a callback that receives a new Where object and returns a Where.
     * Retrieves the last element from the $fields array and verifies that it is an instance
     * of CaseField. Then, it calls its when() method with the result of the callback.
     *
     * @param callable $callback A callback that accepts a Where and returns a Where.
     * @return static
     * @throws \RuntimeException if the last field is not a CaseField.
     */
    public function when(callable $callback): static
    {
        $lastField = end($this->fields);
        if (!$lastField instanceof CaseField) {
            throw new \RuntimeException('Last field is not a CaseField.');
        }
        $result = $callback(new Where());
        if (!$result instanceof Where) {
            throw new \RuntimeException('Callback must return an instance of Where.');
        }
        $lastField->when($result);
        return $this;
    }

    /**
     * Adds a THEN clause to the last CASE clause in the query.
     *
     * Accepts a string value and an optional boolean flag for quoting.
     * Retrieves the last element from the $fields array and verifies that it is an instance
     * of CaseField. Then, it calls its then() method with the provided value and $quotes flag.
     *
     * @param string $value The value to use in the THEN clause.
     * @param bool $quotes Whether to wrap the value in quotes if it's not numeric. Default is true.
     * @return static
     * @throws \RuntimeException if the last field is not a CaseField.
     */
    public function then(string $value, bool $quotes = true): static
    {
        $lastField = end($this->fields);
        if (!$lastField instanceof CaseField) {
            throw new \RuntimeException('Last field is not a CaseField.');
        }
        $lastField->then($value, $quotes);
        return $this;
    }

    /**
     * Sets the default value for the last CASE clause in the query.
     *
     * Accepts a string value and an optional boolean flag for quoting.
     * Retrieves the last element from the $fields array and verifies that it is an instance
     * of CaseField. Then, it calls its else() method with the provided value and $quotes flag.
     *
     * @param string $value The default value for the CASE expression.
     * @param bool $quotes Whether to wrap the default in quotes if it's not numeric. Default is true.
     * @return static
     * @throws \RuntimeException if the last field is not a CaseField.
     */
    public function else(string $value, bool $quotes = true): static
    {
        $lastField = end($this->fields);
        if (!$lastField instanceof CaseField) {
            throw new \RuntimeException('Last field is not a CaseField.');
        }
        $lastField->else($value, $quotes);
        return $this;
    }

    /**
     * Sets the source for the SELECT statement.
     *
     * Accepts either a string or a callback that returns a Select.
     * If a callback is provided, it is called with a new Select instance and its result is used.
     *
     * @param string|callable $source The table name or a callback that accepts a Select and returns a Select.
     * @param string|null $sourceAlias Optional alias for the source.
     * @return static
     * @throws RuntimeException if the source callback does not return an instance of Select.
     */
    public function from(string|callable $source, ?string $sourceAlias = null): static
    {
        if (is_callable($source)) {
            $resultSelect = call_user_func($source, new Select());
            if (!$resultSelect instanceof Select) {
                throw new RuntimeException("Source callback must return an instance of Select.");
            }
            $this->source = $resultSelect;
        } else {
            $this->source = $source;
        }
        $this->sourceAlias = $sourceAlias;
        return $this;
    }


    /**
     * Creates and adds an INNER JOIN to the query.
     *
     * @param string|callable $table The table to join, either as a string or as a callback that accepts a Select and returns a Select.
     * @param string|null $alias Optional alias for the table.
     * @return static
     */
    public function innerJoin(string|callable $table, ?string $alias = null): static {
        $this->joins[] = new Join('INNER', $table, $alias);
        return $this;
    }

    /**
     * Creates and adds a LEFT JOIN to the query.
     *
     * @param string|callable $table The table to join, either as a string or as a callback that accepts a Select and returns a Select.
     * @param string|null $alias Optional alias for the table.
     * @return static
     */
    public function leftJoin(string|callable $table, ?string $alias = null): static {
        $this->joins[] = new Join('LEFT', $table, $alias);
        return $this;
    }

    /**
     * Creates and adds a RIGHT JOIN to the query.
     *
     * @param string|callable $table The table to join, either as a string or as a callback that accepts a Select and returns a Select.
     * @param string|null $alias Optional alias for the table.
     * @return static
     */
    public function rightJoin(string|callable $table, ?string $alias = null): static {
        $this->joins[] = new Join('RIGHT', $table, $alias);
        return $this;
    }

    /**
     * Creates and adds an OUTER JOIN to the query.
     *
     * @param string|callable $table The table to join, either as a string or as a callback that accepts a Select and returns a Select.
     * @param string|null $alias Optional alias for the table.
     * @return static
     */
    public function outerJoin(string|callable $table, ?string $alias = null): static {
        $this->joins[] = new Join('OUTER', $table, $alias);
        return $this;
    }

    /**
     * Adds an ON condition to the last join.
     *
     * This method retrieves the last Join from the joins array,
     * and if it exists, sets its ON condition using a new On object.
     *
     * @param string $source The source field for the join condition.
     * @param string $target The target field for the join condition.
     * @return static
     * @throws \RuntimeException if no join exists.
     */
    public function on(string $source, string $target): static
    {
        if (empty($this->joins))
        {
            throw new \RuntimeException('No join exists in the query.');
        }

        $lastJoin = end($this->joins);
        $lastJoin->setOn(new On($source, $target));

        return $this;
    }

    /**
     * Delegates to the where() method of the $where property.
     *
     * @param mixed ...$args Either:
     *   - (string $field, string $operator, mixed $value)
     *   - or a single callable that accepts a Where object and returns a Where object.
     * @return static
     */
    public function where(mixed ...$args): static {
        $this->where->where(...$args);
        return $this;
    }

    /**
     * Delegates to the whereNot() method of the $where property.
     *
     * @param mixed ...$args Either:
     *   - (string $field, string $operator, mixed $value)
     *   - or a single callable that accepts a Where object and returns a Where object.
     * @return static
     */
    public function whereNot(mixed ...$args): static {
        $this->where->whereNot(...$args);
        return $this;
    }

    /**
     * Delegates to the orWhere() method of the $where property.
     *
     * @param mixed ...$args Either:
     *   - (string $field, string $operator, mixed $value)
     *   - or a single callable that accepts a Where object and returns a Where object.
     * @return static
     */
    public function orWhere(mixed ...$args): static {
        $this->where->orWhere(...$args);
        return $this;
    }

    /**
     * Delegates to the orWhereNot() method of the $where property.
     *
     * @param mixed ...$args Either:
     *   - (string $field, string $operator, mixed $value)
     *   - or a single callable that accepts a Where object and returns a Where object.
     * @return static
     */
    public function orWhereNot(mixed ...$args): static {
        $this->where->orWhereNot(...$args);
        return $this;
    }

    /**
     * Delegates to the andWhere() method of the $where property.
     *
     * @param mixed ...$args Either:
     *   - (string $field, string $operator, mixed $value)
     *   - or a single callable that accepts a Where object and returns a Where object.
     * @return static
     */
    public function andWhere(mixed ...$args): static {
        $this->where->andWhere(...$args);
        return $this;
    }

    /**
     * Delegates to the andWhereNot() method of the $where property.
     *
     * @param mixed ...$args Either:
     *   - (string $field, string $operator, mixed $value)
     *   - or a single callable that accepts a Where object and returns a Where object.
     * @return static
     */
    public function andWhereNot(mixed ...$args): static {
        $this->where->andWhereNot(...$args);
        return $this;
    }

    /**
     * Sets the GROUP BY fields for the query.
     *
     * Accepts a variadic list of strings, verifies that at least one field is provided,
     * and adds them to the $groupBy array.
     *
     * @param string ...$fields The GROUP BY fields.
     * @return static
     * @throws \RuntimeException if no fields are provided.
     */
    public function groupBy(string ...$fields): static
    {
        if (count($fields) < 1) {
            throw new \RuntimeException('At least one GROUP BY field must be provided.');
        }
        $this->groupBy = array_merge($this->groupBy, $fields);
        return $this;
    }

    /**
     * Sets the HAVING clause for the query.
     *
     * Accepts a callback that takes a Where object and returns a Where object.
     * Checks if the GROUP BY list is empty; if so, throws a RuntimeException.
     * Then, stores the result of the callback in a variable, verifies its type,
     * and finally assigns it to the $having property.
     *
     * @param callable $callback A callback that accepts a Where and returns a Where.
     * @return static
     * @throws \RuntimeException if the GROUP BY list is empty or if the callback does not return a Where.
     */
    public function having(callable $callback): static
    {
        if (empty($this->groupBy)) {
            throw new \RuntimeException('Cannot set HAVING clause when GROUP BY list is empty.');
        }
        $result = $callback(new Where());
        if (!$result instanceof Where) {
            throw new \RuntimeException('Callback must return an instance of Where.');
        }
        $this->having = $result;
        return $this;
    }

    /**
     * Sets the ORDER BY clause.
     *
     * Accepts an associative array. For each element:
     * - If the key is numeric, the value is treated as the field and the order is set to null.
     * - Otherwise, the key is treated as the field and the value as the order.
     *
     * @param array $orderings The associative array of orderings.
     * @return static
     */
    public function orderBy(array $orderings): static
    {
        foreach ($orderings as $key => $value) {
            if (is_numeric($key)) {
                $this->orderBy[] = new Order($value, null);
            } else {
                $this->orderBy[] = new Order($key, $value);
            }
        }
        return $this;
    }

    /**
     * Sets the LIMIT value for the query.
     *
     * @param int $limit The LIMIT value.
     * @return static
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Sets the OFFSET value for the query.
     *
     * @param int $offset The OFFSET value.
     * @return static
     */
    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Creates a new Select object that is united with the current query.
     *
     * This method creates a new Select instance, passing the current object as the unitedWith parameter,
     * and returns the new instance.
     *
     * @return Select
     */
    public function union(): Select
    {
        $this->unitedWith = new Select($this);
        return $this->unitedWith;
    }

    /**
     * Builds the complete query for the SELECT statement.
     *
     * The query is constructed in the following order:
     * 1. SELECT clause with DISTINCT if applicable.
     * 2. List of fields (or "*" if empty).
     * 3. FROM clause: if source is a string, then "FROM {source}"; if a Select, then "FROM ({source->buildQuery()})".
     *    If sourceAlias is set, it is appended after the source.
     * 4. JOIN clauses from the $joins array.
     * 5. WHERE clause: if $where->hasConditions() is true, then if $where->isNot() is true use "WHERE NOT ({...})",
     *    otherwise "WHERE {...}".
     * 6. GROUP BY clause: if $groupBy is not empty, then "GROUP BY field1, field2, ...".
     * 7. HAVING clause: if $having is not null, then similar to WHERE, with "HAVING" (using NOT if needed).
     * 8. ORDER BY clause: if $orderBy is not empty, then join each Order's buildQuery() result with commas.
     * 9. LIMIT/OFFSET clause for Oracle: if $limit is set, then "OFFSET {offset OR 0} ROWS FETCH NEXT {limit} ROWS ONLY".
     * 10. If $unitedWith is not null, then prepend its buildQuery() result followed by " UNION " and then the current query.
     *
     * @return string The complete built query.
     * @throws RuntimeException if there is any mismatch or error in building the query.
     */
    public function buildQuery(): string
    {
        // 1. SELECT clause.
        $query = "SELECT";
        if ($this->distinct) {
            $query .= " DISTINCT";
        }
        
        // 2. Fields list.
        if (empty($this->fields)) {
            $query .= " *";
        } else {
            $fieldsQuery = [];
            foreach ($this->fields as $field) {
                $fieldsQuery[] = $field->buildQuery();
            }
            $query .= " " . implode(", ", $fieldsQuery);
        }
        
        // 3. FROM clause.
        $query .= " FROM ";
        if ($this->source instanceof Select) {
            $query .= "(" . $this->source->buildQuery() . ")";
        } else {
            $query .= $this->source;
        }
        $query .= $this->sourceAlias !== null ? " " . $this->sourceAlias : "";

        // 4. JOIN clauses.
        foreach ($this->joins as $join) {
            $query .= " " . $join->buildQuery();
        }
        
        // 5. WHERE clause.
        if ($this->where->hasConditions()) {
            if ($this->where->isNot()) {
                $query .= " WHERE NOT (" . $this->where->buildQuery() . ")";
            } else {
                $query .= " WHERE " . $this->where->buildQuery();
            }
        }
        
        // 6. GROUP BY clause.
        if (!empty($this->groupBy)) {
            $query .= " GROUP BY " . implode(", ", $this->groupBy);
        }
        
        // 7. HAVING clause.
        if ($this->having !== null) {
            if ($this->having->isNot()) {
                $query .= " HAVING NOT (" . $this->having->buildQuery() . ")";
            } else {
                $query .= " HAVING " . $this->having->buildQuery();
            }
        }
        
        // 8. ORDER BY clause.
        if (!empty($this->orderBy)) {
            $orders = [];
            foreach ($this->orderBy as $order) {
                $orders[] = $order->buildQuery();
            }
            $query .= " ORDER BY " . implode(", ", $orders);
        }
        
        // 9. LIMIT/OFFSET clause for Oracle.
        if ($this->limit !== null) {
            $offset = $this->offset ?? 0;
            $query .= " OFFSET {$offset} ROWS FETCH NEXT {$this->limit} ROWS ONLY";
        }
        
        // 10. Append UNION clause if $unitedWith is set.
        if ($this->unitedWith !== null) {
            $query .= " UNION " . $this->unitedWith->buildQuery();
        }
        
        return $query;
    }

    /**
     * Gets the parameters for the SELECT query.
     *
     * Aggregates parameters from fields, source (if it's callable), joins, where, and having.
     *
     * @return array<array{0:string, 1:int}> A numeric list of parameters.
     * @throws RuntimeException if a source callback does not return an instance of Select.
     */
    public function getParams(): array
    {
        $params = [];

        // Fields.
        foreach ($this->fields as $field) {
            if ($field instanceof QueryBuilder) {
                $params = array_merge($params, $field->getParams());
            }
        }

        // Source.
        if ($this->source instanceof QueryBuilder) {
            $params = array_merge($params, $this->source->getParams());
        }
        // If source is a string, no parameters are added.

        // Joins.
        foreach ($this->joins as $join) {
            $params = array_merge($params, $join->getParams());
        }

        // Where.
        if ($this->where instanceof QueryBuilder) {
            $params = array_merge($params, $this->where->getParams());
        }

        // Having.
        if ($this->having instanceof QueryBuilder) {
            $params = array_merge($params, $this->having->getParams());
        }

        // Append parameters from $unitedAfter, if set.
        if ($this->unitedWith !== null) {
            $params = array_merge($params, $this->unitedWith->getParams());
        }

        return $params;
    }

    /**
     * Fetch a root element of Select statement.
     * 
     * @return Select
     */
    public function getRoot(): Select
    {
        return is_null($this->parent) ? $this : $this->parent->getRoot();
    }
}

?>

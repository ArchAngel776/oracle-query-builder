<?php

namespace ArchAngel776\OracleQueryBuilder\Components;

use RuntimeException;
use ArchAngel776\OracleQueryBuilder\Components\Field;
use ArchAngel776\OracleQueryBuilder\Components\Where;


class CaseField extends Field {
    /**
     * Array of cases. Each element is a two-element array:
     * - The first element is an instance of Where.
     * - The second element is a string or null.
     *
     * @var array<int, array{0: Where, 1: string|null}>
     */
    protected array $cases;

    /**
     * The default value for the CASE expression.
     *
     * @var string|null
     */
    protected ?string $default;

    /**
     * Constructor.
     * Does not take any argument and passes an empty string to parent::__construct.
     */
    public function __construct()
    {
        parent::__construct('');
        $this->cases = [];
        $this->default = null;
    }

    /**
     * Adds a new WHEN clause.
     *
     * Creates a two-element list with the provided Where object and null,
     * then appends it to the cases array.
     *
     * @param Where $where The condition for the WHEN clause.
     * @return static
     */
    public function when(Where $where): static
    {
        $this->cases[] = [$where, null];
        return $this;
    }

    /**
     * Sets the THEN value for the last added WHEN clause.
     *
     * Retrieves the last element from the cases array and sets its second element to the provided value.
     * If the provided value is not numeric and $quotes is true, it is wrapped in single quotes.
     *
     * @param string $value The result value for the THEN clause.
     * @param bool $quotes Whether to wrap the value in quotes if it's not numeric. Default is true.
     * @return static
     * @throws RuntimeException if no case exists.
     */
    public function then(string $value, bool $quotes = true): static
    {
        if (empty($this->cases)) {
            throw new RuntimeException('No case exists to apply then.');
        }
        if ($quotes && !is_numeric($value)) {
            $value = "'" . addslashes($value) . "'";
        }
        $index = count($this->cases) - 1;
        $this->cases[$index][1] = $value;
        return $this;
    }

    /**
     * Sets the default value for the CASE expression.
     *
     * If the provided default is not numeric and $quotes is true, it is wrapped in single quotes.
     *
     * @param string $default The default result value.
     * @param bool $quotes Whether to wrap the default in quotes if it's not numeric. Default is true.
     * @return static
     */
    public function else(string $default, bool $quotes = true): static
    {
        if ($quotes && !is_numeric($default)) {
            $default = "'" . addslashes($default) . "'";
        }
        $this->default = $default;
        return $this;
    }

    /**
     * Builds the query fragment for the CASE expression.
     *
     * First, it ensures that the $cases array is not empty (throws a RuntimeException if it is).
     * Then, it begins the expression with "CASE" and for each case, appends:
     *   " WHEN " followed by the built query fragment from the Where object.
     *   If the Where object's isNot() method returns true, its query fragment is enclosed in parentheses
     *   and prefixed with "NOT ".
     *   Then, " THEN " followed by the corresponding result value.
     * Finally, if $default is not empty, it appends " ELSE " and the default value, and closes the expression with " END".
     *
     * @return string The built CASE expression.
     * @throws RuntimeException if the $cases array is empty.
     */
    public function buildQuery(): string
    {
        if (empty($this->cases)) {
            throw new RuntimeException("No cases defined in CASE expression.");
        }

        $query = "CASE";
        foreach ($this->cases as $case) {
            /** @var Where $where */
            $where = $case[0];
            $thenValue = $case[1];
            $whenPart = $where->buildQuery();
            if ($where->isNot()) {
                $whenPart = "NOT (" . $whenPart . ")";
            }
            $query .= " WHEN " . $whenPart . " THEN " . $thenValue;
        }
        if (!empty($this->default)) {
            $query .= " ELSE " . $this->default;
        }
        $query .= " END";
        if ($this->alias !== null && $this->alias !== '') {
            $query .= " AS \"{$this->alias}\"";
        }
        return $query;
    }

    /**
     * Gets the parameters from all nested Where objects in the CASE expression.
     *
     * Iterates through the $cases array and merges parameters from each Where object's getParams() result.
     *
     * @return array<array{0:string, 1:int}> The merged parameters from all CASE conditions.
     */
    public function getParams(): array
    {
        $params = [];
        foreach ($this->cases as $case) {
            /** @var Where $where */
            $where = $case[0];
            $params = array_merge($params, $where->getParams());
        }
        return $params;
    }
}

?>

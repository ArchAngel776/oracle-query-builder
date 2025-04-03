<?php

namespace ArchAngel776\OracleQueryBuilder\Components;

use RuntimeException;
use ArchAngel776\OracleQueryBuilder\Data\QueryBuilder;
use ArchAngel776\OracleQueryBuilder\Components\Param;
use ArchAngel776\OracleQueryBuilder\Statements\Select;
use ArchAngel776\OracleQueryBuilder\Helpers\Parametrized;


class Condition implements QueryBuilder {
    use Parametrized;

    /**
     * The field name for the condition.
     *
     * @var string
     */
    protected string $field;

    /**
     * The operator for the condition.
     * Allowed values: =, <, >, <=, >=, !=, LIKE, BETWEEN, IN.
     *
     * @var string
     */
    protected string $operator;

    /**
     * The parameter for the condition, or a raw value.
     *
     * @var mixed
     */
    protected mixed $value;

    /**
     * Indicates whether the condition is negated.
     *
     * @var bool
     */
    protected bool $not;

    /**
     * Constructor.
     *
     * @param string $field The field name.
     * @param string $operator The operator.
     * @param mixed $value The parameter for the condition, either an instance of Param or a raw value.
     * @param bool $not Whether the condition is negated.
     */
    public function __construct(string $field, string $operator, mixed $value, bool $not)
    {
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;

        if ($this->value instanceof Param)
        {
            $this->createParam($this->value);
        }
        else if (is_callable($value))
        {
            $result = $value(new Select());
            if (!$result instanceof Select) {
                throw new RuntimeException('Callback must return an instance of Select.');
            }
            $this->value = $result;
        }

        $this->not = $not;
    }

    /**
     * Builds the query fragment for this condition.
     *
     * If $value is an instance of Param, the behavior is as follows:
     * - For standard operators (=, <, >, <=, >=, !=, LIKE): if the underlying value is not an array, 
     *   a parameter is created and a "?" placeholder is used.
     * - For BETWEEN: the parameter's value must be a two-element array of numerics; otherwise, a RuntimeException is thrown.
     *   The entire array is passed to createParam(), and the placeholder becomes "? AND ?".
     * - For IN: the parameter's value must be an array; if empty, a RuntimeException is thrown.
     *   The number of question marks in the placeholder is equal to the number of elements in the array.
     *
     * If $value is not an instance of Param, then:
     * - For standard operators (=, <, >, <=, >=, !=, LIKE): if the raw value is a string, it is enclosed in single quotes; if numeric, it is used as-is.
     * - For BETWEEN: the raw value must be a two-element array; the placeholder becomes "first AND second", with each element quoted if it's a string.
     * - For IN: the raw value must be a non-empty array; each element is processed via array_map (enclosing strings in quotes) and then imploded with commas inside parentheses.
     *
     * Adjusts the placement of the NOT prefix depending on the operator:
     * - For BETWEEN, IN, and LIKE, NOT is inserted between the field and the operator 
     *   (e.g. "field NOT LIKE ?", "field NOT BETWEEN ? AND ?", "field NOT IN (?, ?, ...)" ).
     * - For all other operators, if negated, the entire condition is prefixed with NOT (e.g. "NOT field = ?").
     *
     * @return string The built query fragment.
     * @throws RuntimeException if the operator is invalid or if the parameter value does not meet the requirements.
     */
    public function buildQuery(): string
    {
        // First, handle null value.
        if ($this->value === null) {
            if ($this->operator === '=') {
                return "{$this->field} IS NULL";
            } elseif ($this->operator === '!=') {
                return "{$this->field} IS NOT NULL";
            } else {
                throw new RuntimeException("Operator {$this->operator} is not allowed with null value.");
            }
        }
        
        $allowedOperators = ['=', '<', '>', '<=', '>=', '!=', 'LIKE', 'BETWEEN', 'IN'];
        if (!in_array($this->operator, $allowedOperators, true)) {
            throw new RuntimeException("Invalid operator: {$this->operator}");
        }

        $placeholder = "";
        // Check if the value is an instance of Param.
        if ($this->value instanceof Param) {
            $val = $this->value->getValue();
            $type = $this->value->getType();
            if (in_array($this->operator, ['=', '<', '>', '<=', '>=', '!=', 'LIKE'], true)) {
                if (is_array($val)) {
                    throw new RuntimeException("Operator {$this->operator} does not support array value.");
                }
                $placeholder = "?";
            } elseif ($this->operator === 'BETWEEN') {
                if (!is_array($val) || count($val) !== 2) {
                    throw new RuntimeException("BETWEEN operator requires a two-element array as value.");
                }
                if (!is_numeric($val[0]) || !is_numeric($val[1])) {
                    throw new RuntimeException("BETWEEN operator requires numeric values.");
                }
                $placeholder = "? AND ?";
            } elseif ($this->operator === 'IN') {
                if (!is_array($val)) {
                    throw new RuntimeException("IN operator requires an array as value.");
                }
                if (empty($val)) {
                    throw new RuntimeException("IN operator requires a non-empty array.");
                }
                // Create a placeholder string with as many question marks as elements in the array.
                $num = count($val);
                $placeholders = "(" . implode(", ", array_fill(0, $num, "?")) . ")";
                $placeholder = $placeholders;
            }
        } else if ($this->value instanceof Select) {
            $val = $this->value;
            if ($this->operator === 'IN') {
                $placeholder = $val->buildQuery();
            }
        } else {
            // Raw value branch.
            $val = $this->value;
            if (in_array($this->operator, ['=', '<', '>', '<=', '>=', '!=', 'LIKE'], true)) {
                if (is_array($val)) {
                    throw new RuntimeException("Operator {$this->operator} does not support array value in raw mode.");
                }
                if (is_string($val)) {
                    $literal = "'" . addslashes($val) . "'";
                } elseif (is_numeric($val)) {
                    $literal = (string)$val;
                } else {
                    throw new RuntimeException("Operator {$this->operator} supports only string or numeric values in raw mode.");
                }
                $placeholder = $literal;
            } elseif ($this->operator === 'BETWEEN') {
                if (!is_array($val) || count($val) !== 2) {
                    throw new RuntimeException("BETWEEN operator requires a two-element array as value in raw mode.");
                }
                $first = $val[0];
                $second = $val[1];
                if (is_string($first)) {
                    $firstLiteral = "'" . addslashes($first) . "'";
                } elseif (is_numeric($first)) {
                    $firstLiteral = (string)$first;
                } else {
                    throw new RuntimeException("BETWEEN operator supports only string or numeric values in raw mode.");
                }
                if (is_string($second)) {
                    $secondLiteral = "'" . addslashes($second) . "'";
                } elseif (is_numeric($second)) {
                    $secondLiteral = (string)$second;
                } else {
                    throw new RuntimeException("BETWEEN operator supports only string or numeric values in raw mode.");
                }
                $placeholder = "{$firstLiteral} AND {$secondLiteral}";
            } elseif ($this->operator === 'IN') {
                if (!is_array($val)) {
                    throw new RuntimeException("IN operator requires an array as value in raw mode.");
                }
                if (empty($val)) {
                    throw new RuntimeException("IN operator requires a non-empty array in raw mode.");
                }
                $mapped = array_map(function ($element) {
                    if (is_string($element)) {
                        return "'" . addslashes($element) . "'";
                    } elseif (is_numeric($element)) {
                        return (string)$element;
                    } else {
                        throw new RuntimeException("IN operator supports only string or numeric values in raw mode.");
                    }
                }, $val);
                $placeholder = "(" . implode(", ", $mapped) . ")";
            }
        }

        // Determine NOT placement.
        if (in_array($this->operator, ['BETWEEN', 'IN', 'LIKE'], true)) {
            $notText = $this->not ? "NOT " : "";
            return "{$this->field} {$notText}{$this->operator} {$placeholder}";
        } else {
            return $this->not
                ? "NOT {$this->field} {$this->operator} {$placeholder}"
                : "{$this->field} {$this->operator} {$placeholder}";
        }
    }
}

?>

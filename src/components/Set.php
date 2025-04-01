<?php

namespace ArchAngel776\OracleQueryBuilder\Components;

use RuntimeException;
use ArchAngel776\OracleQueryBuilder\Data\QueryBuilder;
use ArchAngel776\OracleQueryBuilder\Helpers\Parametrized;


class Set implements QueryBuilder {
    use Parametrized;

    /**
     * The field name for the SET clause.
     *
     * @var string
     */
    protected string $field;

    /**
     * The value to assign to the field.
     *
     * @var mixed
     */
    protected mixed $value;

    /**
     * Constructor.
     *
     * @param string $field The field name.
     * @param mixed $value The value to assign.
     */
    public function __construct(string $field, mixed $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    /**
     * Builds the SET clause fragment.
     *
     * The resulting SQL fragment is in the form:
     *    {field} = {value}
     *
     * If $value is an instance of Param, the method calls createParam($value)
     * and uses a placeholder "?" in the query.
     * If $value is not an array and not an instance of Param, then:
     *   - If it's a string, it is wrapped in single quotes.
     *   - Otherwise, the raw value is used.
     * If $value is an array, a RuntimeException is thrown.
     *
     * @return string The built SET clause fragment.
     * @throws RuntimeException if $value is an array.
     */
    public function buildQuery(): string
    {
        if (is_array($this->value)) {
            throw new RuntimeException("Set clause value cannot be an array.");
        }
        
        if ($this->value instanceof Param) {
            $this->createParam($this->value);
            return $this->field . " = ?";
        }
        
        if (is_string($this->value)) {
            // Wrap string literal in single quotes and escape it.
            return $this->field . " = '" . addslashes($this->value) . "'";
        }
        
        // For numeric or other types, cast to string.
        return $this->field . " = " . $this->value;
    }
}

?>

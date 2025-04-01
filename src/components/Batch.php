<?php

namespace ArchAngel776\OracleQueryBuilder\Components;

use RuntimeException;
use ArchAngel776\OracleQueryBuilder\Data\QueryBuilder;
use ArchAngel776\OracleQueryBuilder\Helpers\Parametrized;


/**
 * Class Batch
 *
 * Represents a batch operation with a set of fields.
 */
class Batch implements QueryBuilder {
    use Parametrized;

    /**
     * Array of field names.
     *
     * @var string[]
     */
    protected array $fields;

    /**
     * Array of values.
     * Each element is an array of mixed values corresponding to a record.
     *
     * @var array<int, array<mixed>>
     */
    protected array $values;


    /**
     * Constructor.
     * Initializes the fields as an empty array.
     */
    public function __construct()
    {
        $this->fields = [];
        $this->values = [];
    }

    /**
     * Adds a new record to the batch.
     *
     * If the $fields property is empty, it is populated with the keys of the provided associative array.
     * Otherwise, it validates that the keys of the provided array match the $fields (order does not matter).
     * Then, the values are ordered according to $fields and appended to the $values array.
     *
     * @param array $data An associative array of field => value pairs.
     * @return static
     * @throws RuntimeException if validation fails or a required field is missing.
     */
    public function add(array $data): static
    {
        $dataKeys = array_keys($data);
        if (empty($this->fields)) {
            $this->fields = $dataKeys;
        } else {
            $sortedDataKeys = $dataKeys;
            $sortedFields   = $this->fields;
            sort($sortedDataKeys);
            sort($sortedFields);
            if ($sortedDataKeys !== $sortedFields) {
                throw new RuntimeException("Provided keys do not match the defined fields.");
            }
        }

        // Order the values according to the order in $fields.
        $orderedValues = [];
        foreach ($this->fields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new RuntimeException("Missing field '$field' in provided data.");
            }
            $value = $data[$field];
            if (is_array($value)) {
                throw new RuntimeException("Value for field '$field' cannot be an array.");
            }
            $orderedValues[] = $value;
        }
        $this->values[] = $orderedValues;
        return $this;
    }

    /**
     * Checks whether the batch is initialized.
     *
     * A batch is considered initialized if the fields array is not empty and the values array contains at least one record.
     *
     * @return bool True if initialized, false otherwise.
     */
    public function isInitialized(): bool
    {
        return !empty($this->fields) && !empty($this->values);
    }

    /**
     * Builds the batch query.
     *
     * Generates a query of the form:
     *   (column1, column2, ...) VALUES (value1, value2, ...), (value1, value2, ...), ...
     *
     * For each value:
     *  - If the value is null, outputs the string "NULL".
     *  - If the value is an array, a RuntimeException is thrown.
     *  - If the value is a string, it is enclosed in single quotes (with proper escaping).
     *  - Otherwise, the value is used as-is.
     *
     * @return string The built batch query.
     * @throws RuntimeException if any value is an array or if no fields are defined.
     */
    public function buildQuery(): string
    {
        if (empty($this->fields)) {
            throw new RuntimeException("No fields defined for batch query.");
        }
        
        $columns = "(" . implode(", ", $this->fields) . ")";
        $valuesParts = [];
        
        foreach ($this->values as $record) {
            $valueStrs = [];
            foreach ($record as $value) {
                if (is_array($value)) {
                    throw new RuntimeException("Value cannot be an array.");
                }
                if ($value === null) {
                    $valueStrs[] = "NULL";
                } elseif ($value instanceof Param) {
                    $this->createParam($value);
                    $valueStrs[] = "?";
                } elseif (is_string($value)) {
                    $valueStrs[] = "'" . addslashes($value) . "'";
                } else {
                    $valueStrs[] = (string)$value;
                }
            }
            $valuesParts[] = "(" . implode(", ", $valueStrs) . ")";
        }
        
        return $columns . " VALUES " . implode(", ", $valuesParts);
    }
}

?>

<?php

namespace ArchAngel776\OracleQueryBuilder\Statements;

use RuntimeException;
use ArchAngel776\OracleQueryBuilder\Data\QueryBuilder;
use ArchAngel776\OracleQueryBuilder\Components\Batch;


class Insert implements QueryBuilder {
    /**
     * The table name for the INSERT statement.
     *
     * @var string
     */
    protected string $table;

    /**
     * Batch data for the INSERT statement.
     *
     * @var Batch
     */
    protected Batch $batch;


    /**
     * Constructor.
     * Initializes $table as an empty string and $batch as a new Batch instance.
     */
    public function __construct()
    {
        $this->table = "";
        $this->batch = new Batch();
    }

    /**
     * Sets the table name for the INSERT statement.
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
     * Adds one or more records to the batch.
     *
     * Accepts a variadic number of associative arrays, each representing a record
     * where the keys are field names and values are the corresponding values.
     * Each record is added to the Batch via its add() method.
     *
     * @param array<string, mixed> ...$records Variadic list of associative arrays.
     * @return static
     */
    public function insert(array ...$records): static
    {
        foreach ($records as $record) {
            $this->batch->add($record);
        }
        return $this;
    }

    /**
     * Builds the complete INSERT query.
     *
     * The query is constructed as:
     *   INSERT INTO {table} {batch query}
     *
     * Before building the query, it verifies that the batch is initialized.
     *
     * @return string The built INSERT query.
     * @throws RuntimeException if the table name is not specified or the batch is not initialized.
     */
    public function buildQuery(): string
    {
        if (empty($this->table)) {
            throw new RuntimeException("Table name not specified for INSERT.");
        }
        if (!$this->batch->isInitialized()) {
            throw new RuntimeException("Batch is not initialized for INSERT query.");
        }
        $batchQuery = $this->batch->buildQuery();
        return "INSERT INTO " . $this->table . " " . $batchQuery;
    }

    /**
     * Gets the parameters for the INSERT query.
     *
     * Delegates to the Batch's getParams() method.
     *
     * @return array<array{0:string, 1:int}> A numeric list of parameters.
     */
    public function getParams(): array
    {
        return $this->batch->getParams();
    }
}
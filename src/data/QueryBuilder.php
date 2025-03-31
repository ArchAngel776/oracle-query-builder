<?php

namespace ArchAngel776\OracleQueryBuilder\Data;


interface QueryBuilder {
    /**
     * Builds the query.
     *
     * @return string The built query.
     */
    public function buildQuery(): string;

    /**
     * Gets the parameters.
     *
     * @return array<array{0:string, 1:int}> The parameters.
     */
    public function getParams(): array;
}

?>

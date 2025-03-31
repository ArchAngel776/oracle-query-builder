<?php

namespace ArchAngel776\OracleQueryBuilder\Components;

use ArchAngel776\OracleQueryBuilder\Data\QueryBuilder;


class On implements QueryBuilder {
    /**
     * The source field.
     *
     * @var string
     */
    protected string $source;

    /**
     * The target field.
     *
     * @var string
     */
    protected string $target;

    /**
     * Constructor.
     *
     * @param string $source The source field.
     * @param string $target The target field.
     */
    public function __construct(string $source, string $target)
    {
        $this->source = $source;
        $this->target = $target;
    }

    /**
     * Gets the source field.
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Gets the target field.
     *
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * Builds the query fragment for the ON clause.
     *
     * Returns a string in the format "$source = $target".
     *
     * @return string The built query fragment.
     */
    public function buildQuery(): string
    {
        return "{$this->source} = {$this->target}";
    }

    /**
     * Gets the parameters for the ON clause.
     *
     * Since the ON clause does not use parameters, this returns an empty array.
     *
     * @return array<array{0:string, 1:int}> An empty array.
     */
    public function getParams(): array
    {
        return [];
    }
}

?>

<?php

namespace ArchAngel776\OracleQueryBuilder\Components;

use ArchAngel776\OracleQueryBuilder\Data\QueryBuilder;


class Field implements QueryBuilder {
    /**
     * The name of the field.
     *
     * @var string
     */
    protected string $name;

    /**
     * The alias for the field, if any.
     *
     * @var string|null
     */
    protected ?string $alias;

    /**
     * Constructor.
     *
     * @param string $name The name of the field.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->alias = null;
    }

    /**
     * Sets the alias of the field.
     *
     * @param string|null $alias
     * @return static
     */
    public function setAlias(?string $alias): static
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * Builds the query fragment for this field.
     *
     * If an alias is set, returns "field AS alias", otherwise returns "field".
     *
     * @return string The built query fragment.
     */
    public function buildQuery(): string
    {
        if ($this->alias !== null && $this->alias !== '') {
            return "{$this->name} AS \"{$this->alias}\"";
        }
        return $this->name;
    }

    /**
     * Returns an empty parameters array.
     *
     * @return array<array{0:string, 1:int}> Empty array.
     */
    public function getParams(): array
    {
        return [];
    }
}

?>

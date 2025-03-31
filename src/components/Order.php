<?php

namespace ArchAngel776\OracleQueryBuilder\Components;

use RuntimeException;
use ArchAngel776\OracleQueryBuilder\Data\QueryBuilder;


class Order implements QueryBuilder {
    /**
     * The field name to order by.
     *
     * @var string
     */
    protected string $field;

    /**
     * The order direction (ASC, DESC) or null.
     *
     * @var string|null
     */
    protected ?string $order;

    /**
     * Constructor.
     *
     * @param string $field The field name.
     * @param string|null $order The order direction (ASC, DESC) or null.
     */
    public function __construct(string $field, ?string $order = null)
    {
        $this->field = $field;
        $this->order = $order;
    }

    /**
     * Builds the ORDER BY clause fragment.
     *
     * If the order value is "ASC", returns "field ASC".
     * If the order value is "DESC", returns "field DESC".
     * If the order value is null, returns just the field name.
     * Otherwise, throws a RuntimeException.
     *
     * @return string The built ORDER BY fragment.
     * @throws RuntimeException if the order value is not ASC, DESC, or null.
     */
    public function buildQuery(): string
    {
        if ($this->order === null) {
            return $this->field;
        }

        $upperOrder = strtoupper($this->order);
        if ($upperOrder === "ASC" || $upperOrder === "DESC") {
            return "{$this->field} {$upperOrder}";
        }

        throw new RuntimeException("Invalid order value: {$this->order}");
    }

    /**
     * Returns an empty parameters array.
     *
     * @return array<array{0:string, 1:int}> An empty array.
     */
    public function getParams(): array
    {
        return [];
    }
}

?>

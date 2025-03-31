<?php

namespace ArchAngel776\OracleQueryBuilder\Components;


class Param {
    // Public constants with numeric values matching PDO, ordered ascending by value.
    public const INTEGER = 1;
    public const STRING  = 2;
    public const LOB     = 3;
    public const BOOL    = 5;

    /**
     * The parameter value.
     *
     * @var mixed
     */
    protected mixed $value;

    /**
     * The parameter type.
     *
     * @var int
     */
    protected int $type;

    /**
     * Constructor.
     *
     * @param mixed $value The parameter value.
     * @param int $type The parameter type.
     */
    public function __construct(mixed $value, int $type)
    {
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * Gets the parameter value.
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Gets the parameter type.
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Creates a new instance of Param.
     *
     * @param mixed $value The parameter value.
     * @param int $type The parameter type.
     * @return static
     */
    public static function make(mixed $value, int $type): static
    {
        return new static($value, $type);
    }
}

?>

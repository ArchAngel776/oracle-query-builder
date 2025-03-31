<?php

namespace ArchAngel776\OracleQueryBuilder\Helpers;

use ArchAngel776\OracleQueryBuilder\Components\Param;


trait Parametrized {
    /**
     * Numeric list of parameters.
     * Each element is a two-element array:
     * - The first element is the parameter value (mixed)
     * - The second element is the parameter type (int)
     *
     * @var array<int, array{0: mixed, 1: int}>
     */
    protected array $params = [];

    /**
     * Creates parameter(s) from a Param object.
     *
     * If the parameter's value is an array, each element is added as a separate parameter.
     * Otherwise, a single parameter is added.
     *
     * @param Param $param The parameter object.
     * @return void
     */
    public function createParam(Param $param): void
    {
        $value = $param->getValue();
        $type = $param->getType();
        if (is_array($value)) {
            foreach ($value as $element) {
                $this->params[] = [$element, $type];
            }
        } else {
            $this->params[] = [$value, $type];
        }
    }

    /**
     * Gets the parameters.
     *
     * @return array<int, array{0: mixed, 1: int}>
     */
    public function getParams(): array
    {
        return $this->params;
    }
}

?>

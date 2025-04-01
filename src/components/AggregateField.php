<?

namespace ArchAngel776\OracleQueryBuilder\Components;


/**
 * Class AggregateField
 *
 * Represents an aggregate field in a SELECT statement.
 */
class AggregateField extends Field
{
    /**
     * The aggregation function (e.g. COUNT, AVG, SUM, etc.).
     *
     * @var string
     */
    protected string $aggregationFunction;

    /**
     * Additional expression, if any.
     *
     * @var string|null
     */
    protected ?string $additionalExpression;

    /**
     * Whether to apply aggregation on distinct values.
     *
     * @var bool
     */
    protected bool $distinct;

    /**
     * Constructor.
     *
     * @param string $name The base expression or field.
     * @param string $aggregationFunction The aggregation function.
     * @param bool $distinct Whether to apply DISTINCT.
     */
    public function __construct(string $name, string $aggregationFunction, bool $distinct)
    {
        parent::__construct($name);
        $this->aggregationFunction = $aggregationFunction;
        $this->distinct = $distinct;
        $this->additionalExpression = null;
    }

    /**
     * Gets the aggregation function.
     *
     * @return string
     */
    public function getAggregationFunction(): string
    {
        return $this->aggregationFunction;
    }

    /**
     * Sets the additional expression.
     *
     * @param string $expression
     * @return static
     */
    public function setAdditionalExpression(string $expression): static
    {
        $this->additionalExpression = $expression;
        return $this;
    }

    /**
     * Builds the query fragment for this aggregate field.
     *
     * It produces an expression in the form:
     * AGG_FUNCTION([DISTINCT] field [ additionalExpression ])
     * and appends an alias if present.
     *
     * @return string The built query fragment.
     */
    public function buildQuery(): string
    {
        $distinctPart = $this->distinct ? 'DISTINCT ' : '';
        $additionalPart = ($this->additionalExpression !== null && $this->additionalExpression !== '')
            ? ' ' . $this->additionalExpression
            : '';
        $query = "{$this->aggregationFunction}({$distinctPart}{$this->name}){$additionalPart}";
        if ($this->alias !== null && $this->alias !== '') {
            $query .= " AS {$this->alias}";
        }
        return $query;
    }
}

?>

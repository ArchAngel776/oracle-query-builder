<?php

use PHPUnit\Framework\TestCase;
use ArchAngel776\OracleQueryBuilder\Statements\Select;
use ArchAngel776\OracleQueryBuilder\Components\Where;
use ArchAngel776\OracleQueryBuilder\Components\Param;


final class Aggregation extends TestCase
{
    public function testGroupByWithSingleAggregateAndHaving(): void
    {
        // Build a query that groups by 'department' and includes an aggregate field
        // using the count() method with distinct flag.
        $select = new Select();
        $select
            ->select("department")
            ->count("orders", true)  // produces "COUNT(DISTINCT orders)"
            ->from("employees")
            ->groupBy("department")
            ->having(fn (Where $w) => $w
                ->where("COUNT(DISTINCT orders)", ">", Param::make(10, Param::INTEGER))
            );

        $expectedQuery = "SELECT department, COUNT(DISTINCT orders) FROM employees GROUP BY department HAVING COUNT(DISTINCT orders) > ?";
        $this->assertEquals($expectedQuery, $select->buildQuery());

        $params = $select->getParams();
        $this->assertCount(1, $params);
        $this->assertEquals([10, Param::INTEGER], $params[0]);
    }

    public function testGroupByWithMultipleAggregatesAndHaving(): void
    {
        // Build a query that groups by 'department' and selects two aggregate functions:
        // COUNT(DISTINCT orders) and AVG(salary). The HAVING clause applies conditions on both.
        $select = new Select();
        $select
            ->select("department")
            ->count("orders", true)  // "COUNT(DISTINCT orders)"
            ->avg("salary", false)     // "AVG(salary)"
            ->from("employees")
            ->groupBy("department")
            ->having(fn (Where $w) => $w
                ->where("COUNT(DISTINCT orders)", ">", Param::make(10, Param::INTEGER))
                ->andWhere("AVG(salary)", ">=", Param::make(50000, Param::INTEGER))
            );

        $expectedQuery = "SELECT department, COUNT(DISTINCT orders), AVG(salary) FROM employees GROUP BY department HAVING COUNT(DISTINCT orders) > ? AND AVG(salary) >= ?";
        $this->assertEquals($expectedQuery, $select->buildQuery());

        $params = $select->getParams();
        $this->assertCount(2, $params);
        $this->assertEquals([10, Param::INTEGER], $params[0]);
        $this->assertEquals([50000, Param::INTEGER], $params[1]);
    }

    public function testHavingWithRawValues(): void
    {
        // Build a query using raw values (not Param objects) in the HAVING clause.
        $select = new Select();
        $select
            ->select("department", "SUM(salary)")
            ->from("employees")
            ->groupBy("department")
            ->having(fn (Where $w) => $w
                ->where("SUM(salary)", ">", 100000)
            );

        $expectedQuery = "SELECT department, SUM(salary) FROM employees GROUP BY department HAVING SUM(salary) > 100000";
        $this->assertEquals($expectedQuery, $select->buildQuery());
        // With raw values, no parameters are expected.
        $this->assertEmpty($select->getParams());
    }
}

?>

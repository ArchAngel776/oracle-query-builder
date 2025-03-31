<?php

use PHPUnit\Framework\TestCase;
use ArchAngel776\OracleQueryBuilder\Statements\Select;
use ArchAngel776\OracleQueryBuilder\Components\Where;
use ArchAngel776\OracleQueryBuilder\Components\Param;


final class Unions extends TestCase
{
    public function testSimpleUnionWithFieldsAndFrom(): void
    {
        // First query: simple fields with FROM clause.
        $select = new Select();
        $select
            ->select("id", "name")
            ->from("table1", "t1")
            ->union()
            ->select("id", "name")
            ->from("table2", "t2");

        $expected = "SELECT id, name FROM table1 t1 UNION SELECT id, name FROM table2 t2";
        $this->assertEquals($expected, $select->buildQuery());
        $this->assertEmpty($select->getParams());
    }

    public function testUnionWithWhereAndJoin(): void
    {
        // First query: includes fields, JOIN, and a WHERE condition.
        $select = new Select();
        $select
            ->select("u.id", "u.name")
            ->from("users", "u")
            ->innerJoin("orders", "o")->on("u.id", "o.user_id")
            ->where("u.active", "=", Param::make(1, Param::INTEGER))
            ->union()
            ->select("u.id", "u.name")
            ->from("users", "u")
            ->innerJoin("orders", "o")->on("u.id", "o.user_id")
            ->where("u.active", "=", Param::make(0, Param::INTEGER));

        $expected = "SELECT u.id, u.name FROM users u INNER JOIN orders o ON u.id = o.user_id WHERE u.active = ? UNION SELECT u.id, u.name FROM users u INNER JOIN orders o ON u.id = o.user_id WHERE u.active = ?";
        $this->assertEquals($expected, $select->buildQuery());

        // Check merged parameters: first query should have [1, INTEGER] and second [0, INTEGER]
        $params = $select->getParams();
        $this->assertCount(2, $params);
        $this->assertEquals([1, Param::INTEGER], $params[0]);
        $this->assertEquals([0, Param::INTEGER], $params[1]);
    }

    public function testUnionWithAggregatesAndGroupByHaving(): void
    {
        // First query: groups by 'department' with an aggregate function (AVG) and a HAVING clause.
        $select = new Select();
        $select
            ->select("department")
            ->avg("salary")
            ->from("employees")
            ->groupBy("department")
            ->having(fn (Where $w) => 
                $w->where("AVG(salary)", ">", Param::make(50000, Param::INTEGER))
            )
            ->union()
            ->select("department")
            ->avg("salary")
            ->from("employees")
            ->groupBy("department")
            ->having(fn (Where $w) => 
                $w->where("AVG(salary)", "<", Param::make(30000, Param::INTEGER))
            );

        $expected = "SELECT department, AVG(salary) FROM employees GROUP BY department HAVING AVG(salary) > ? UNION SELECT department, AVG(salary) FROM employees GROUP BY department HAVING AVG(salary) < ?";
        $this->assertEquals($expected, $select->buildQuery());

        $params = $select->getParams();
        $this->assertCount(2, $params);
        $this->assertEquals([50000, Param::INTEGER], $params[0]);
        $this->assertEquals([30000, Param::INTEGER], $params[1]);
    }

    public function testUnionWithCaseExpressions(): void
    {
        // First query: includes a CASE expression.
        $select = new Select();
        $select
            ->select("id")
            ->case()
            ->when(fn (Where $w) =>
                $w->where("score", ">=", Param::make(70, Param::INTEGER))
            )
            ->then("High", false)
            ->when(fn (Where $w) =>
                $w->where("score", "<", Param::make(70, Param::INTEGER))
            )
            ->then("Low", false)
            ->else("Unknown", false)
            ->from("exams")
            ->union()
            ->select("id")
            ->case()
            ->when(fn (Where $w) =>
                $w->where("score", ">=", Param::make(90, Param::INTEGER))
            )
            ->then("Excellent", false)
            ->when(fn (Where $w) =>
                $w->where("score", "<", Param::make(90, Param::INTEGER))
            )
            ->then("Average", false)
            ->else("Poor", false)
            ->from("exams");

        $expected = "SELECT id, CASE WHEN score >= ? THEN High WHEN score < ? THEN Low ELSE Unknown END FROM exams UNION SELECT id, CASE WHEN score >= ? THEN Excellent WHEN score < ? THEN Average ELSE Poor END FROM exams";
        $this->assertEquals($expected, $select->buildQuery());

        $params = $select->getParams();
        // First query: two parameters, second query: two parameters.
        $this->assertCount(4, $params);
        $this->assertEquals([70, Param::INTEGER], $params[0]);
        $this->assertEquals([70, Param::INTEGER], $params[1]);
        $this->assertEquals([90, Param::INTEGER], $params[2]);
        $this->assertEquals([90, Param::INTEGER], $params[3]);
    }
}

?>

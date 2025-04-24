<?php

use PHPUnit\Framework\TestCase;
use ArchAngel776\OracleQueryBuilder\Statements\Select;
use ArchAngel776\OracleQueryBuilder\Components\Param;


final class Source extends TestCase
{
    public function testFromClauseWithStringSource(): void
    {
        $select = new Select();
        $select
            ->from("users", "u");
        
        // When no fields are specified, our builder returns "*" as default.
        $expected = "SELECT * FROM users u";
        $this->assertEquals($expected, $select->buildQuery());
        $this->assertEmpty($select->getParams());
    }

    public function testFromClauseWithCallbackSource(): void
    {        
        $select = new Select();
        // Provide a callback for source that returns the nested select.
        $select
            ->from(fn (Select $s) => $s
                ->select("id")
                ->from("orders")
            , "o");
        
        $expected = "SELECT * FROM (SELECT id FROM orders) o";
        $this->assertEquals($expected, $select->buildQuery());
    }

    public function testJoinWithStringAndCallbackTable(): void
    {
        $select = new Select();
        $select
            ->from("users", "u")
            ->innerJoin("orders", "o")->on("u.id", "o.user_id")
            ->leftJoin(fn (Select $s) => $s
                ->select("payment_id")
                ->from("payments")
            , "p")->on("u.id", "p.user_id");
        
        $expected = "SELECT * FROM users u INNER JOIN orders o ON u.id = o.user_id LEFT JOIN (SELECT payment_id FROM payments) p ON u.id = p.user_id";
        $this->assertEquals($expected, $select->buildQuery());
    }

    public function testNestedJoin(): void
    {        
        $select = new Select();
        $select
            ->from("users", "u")
            ->innerJoin(fn (Select $s) => $s
                ->select("order_id", "amount")
                ->from("orders")
            , "o")->on("u.id", "o.user_id");
        
        $expected = "SELECT * FROM users u INNER JOIN (SELECT order_id, amount FROM orders) o ON u.id = o.user_id";
        $this->assertEquals($expected, $select->buildQuery());
    }

    public function testGetParamsFromCallbackSourceAndJoin(): void
    {
        // Build a query where both the source and a join use callbacks that produce parameters.
        $select = new Select();
        // Source as a callback: a nested select with a WHERE clause that adds one parameter.
        $select
            ->from(fn (Select $s) => $s
                ->select("id")
                ->from("orders")
                ->where("status", "=", Param::make("completed", Param::STRING))
            , "o")
            ->innerJoin("users", "u")->on("u.order_id", "o.id");

        // Expected SQL:
        // SELECT * FROM (SELECT id FROM orders WHERE status = ?) o INNER JOIN users u ON u.order_id = o.id
        $expectedSql = "SELECT * FROM (SELECT id FROM orders WHERE status = ?) o INNER JOIN users u ON u.order_id = o.id";
        $this->assertEquals($expectedSql, $select->buildQuery());

        // Expect one parameter from the nested WHERE clause.
        $params = $select->getParams();
        $this->assertCount(1, $params);
        $this->assertEquals(["completed", Param::STRING], $params[0]);
    }
}

?>

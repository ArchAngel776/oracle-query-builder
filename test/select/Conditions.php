<?php

use PHPUnit\Framework\TestCase;
use ArchAngel776\OracleQueryBuilder\Statements\Select;
use ArchAngel776\OracleQueryBuilder\Components\Where;
use ArchAngel776\OracleQueryBuilder\Components\Param;


final class Conditions extends TestCase
{
    public function testSimpleWhereWithParam(): void
    {
        $select = new Select();
        $select
            ->select("id", "name")
            ->from("users")
            ->where("age", ">", Param::make(30, Param::INTEGER));

        $expectedQuery = "SELECT id, name FROM users WHERE age > ?";
        $this->assertEquals($expectedQuery, $select->buildQuery());

        $params = $select->getParams();
        $this->assertCount(1, $params);
        $this->assertEquals([30, Param::INTEGER], $params[0]);
    }

    public function testCompoundWhereWithOrAndAnd(): void
    {
        $select = new Select();
        $select
            ->select("id")
            ->from("exams")
            ->where(fn (Where $w) => $w
                ->where("score", ">=", Param::make(50, Param::INTEGER))
                ->andWhere("score", "<", Param::make(90, Param::INTEGER))
            )
            ->orWhere("status", "=", Param::make("pending", Param::STRING));

        $expectedQuery = "SELECT id FROM exams WHERE (score >= ? AND score < ?) OR status = ?";
        $this->assertEquals($expectedQuery, $select->buildQuery());

        $params = $select->getParams();
        $this->assertCount(3, $params);
        $this->assertEquals([50, Param::INTEGER], $params[0]);
        $this->assertEquals([90, Param::INTEGER], $params[1]);
        $this->assertEquals(["pending", Param::STRING], $params[2]);
    }

    public function testBetweenOperatorWithParam(): void
    {
        $select = new Select();
        $select
            ->select("id")
            ->from("orders")
            ->where("price", "BETWEEN", Param::make([100, 200], Param::INTEGER));

        $expectedQuery = "SELECT id FROM orders WHERE price BETWEEN ? AND ?";
        $this->assertEquals($expectedQuery, $select->buildQuery());

        $params = $select->getParams();
        $this->assertCount(2, $params);
        $this->assertEquals([100, Param::INTEGER], $params[0]);
        $this->assertEquals([200, Param::INTEGER], $params[1]);
    }

    public function testInOperatorWithParam(): void
    {
        $select = new Select();
        $select
            ->select("id")
            ->from("products")
            ->where("category", "IN", Param::make(["electronics", "books"], Param::STRING));

        // Expected query: The placeholder should have as many ? as there are elements.
        $expectedQuery = "SELECT id FROM products WHERE category IN (?, ?)";
        $this->assertEquals($expectedQuery, $select->buildQuery());

        $params = $select->getParams();
        // Using the Parametrized trait, if the value is an array,
        // each element is added as a separate parameter.
        $this->assertCount(2, $params);
        $this->assertEquals(["electronics", Param::STRING], $params[0]);
        $this->assertEquals(["books", Param::STRING], $params[1]);
    }

    public function testRawValueOperators(): void
    {
        // Using raw values (not Param objects)
        $select = new Select();
        $select
            ->select("id")
            ->from("employees")
            ->where("salary", ">", 50000);

        $expectedQuery = "SELECT id FROM employees WHERE salary > 50000";
        $this->assertEquals($expectedQuery, $select->buildQuery());
        $this->assertEmpty($select->getParams());

        // Test raw value for LIKE operator (string literal should be quoted).
        $select = new Select();
        $select
            ->select("id")
            ->from("employees")
            ->where("name", "LIKE", "John%");

        $expectedQuery = "SELECT id FROM employees WHERE name LIKE 'John%'";
        $this->assertEquals($expectedQuery, $select->buildQuery());
        $this->assertEmpty($select->getParams());

        // Test raw value for BETWEEN operator.
        $select = new Select();
        $select
            ->select("id")
            ->from("orders")
            ->where("price", "BETWEEN", [100, 200]);

        $expectedQuery = "SELECT id FROM orders WHERE price BETWEEN 100 AND 200";
        $this->assertEquals($expectedQuery, $select->buildQuery());
        $this->assertEmpty($select->getParams());

        // Test raw value for IN operator.
        $select = new Select();
        $select
            ->select("id")
            ->from("products")
            ->where("category", "IN", ["books", "electronics"]);

        // Each element should be quoted.
        $expectedQuery = "SELECT id FROM products WHERE category IN ('books', 'electronics')";
        $this->assertEquals($expectedQuery, $select->buildQuery());
        $this->assertEmpty($select->getParams());
    }

    public function testNestedWhere(): void
    {
        // Test nested where conditions using callbacks.
        $select = new Select();
        $select
            ->select("id")
            ->from("users")
            ->where(fn (Where $w) => $w
                ->where("age", ">", Param::make(18, Param::INTEGER))
                ->andWhere("age", "<", Param::make(65, Param::INTEGER))
            );

        $expectedQuery = "SELECT id FROM users WHERE (age > ? AND age < ?)";
        $this->assertEquals($expectedQuery, $select->buildQuery());

        $params = $select->getParams();
        $this->assertCount(2, $params);
        $this->assertEquals([18, Param::INTEGER], $params[0]);
        $this->assertEquals([65, Param::INTEGER], $params[1]);
    }
}

?>

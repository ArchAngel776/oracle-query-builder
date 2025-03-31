<?php

use PHPUnit\Framework\TestCase;
use ArchAngel776\OracleQueryBuilder\Statements\Select;


final class Reporting extends TestCase
{
    public function testOrderByLimitOffset(): void
    {
        $select = new Select();
        $select
            ->select("id", "name")
            ->from("users")
            ->orderBy(["name" => "ASC", "id" => "DESC"])
            ->limit(10)
            ->offset(5);
        
        $expected = "SELECT id, name FROM users ORDER BY name ASC, id DESC OFFSET 5 ROWS FETCH NEXT 10 ROWS ONLY";
        $this->assertEquals($expected, $select->buildQuery());
        $this->assertEmpty($select->getParams());
    }

    public function testMakeIfFunctionality(): void
    {
        // Test makeIf: if condition is true, apply distinct.
        $select1 = new Select();
        $select1
            ->select("id")
            ->from("products")
            ->makeIf(true, fn (Select $s) => $s
                ->distinct()
            );

        $expected1 = "SELECT DISTINCT id FROM products";
        $this->assertEquals($expected1, $select1->buildQuery());

        // Test makeIf with callbackElse: when condition is false, apply limit(15); otherwise limit(20).
        $select2 = new Select();
        $select2
            ->select("id")
            ->from("products")
            ->makeIf(false, 
                fn (Select $s) => $s->limit(20),
                fn (Select $s) => $s->limit(15)
            );

        // Since offset is not set, default to OFFSET 0 is appended when limit is used.
        $expected2 = "SELECT id FROM products OFFSET 0 ROWS FETCH NEXT 15 ROWS ONLY";
        $this->assertEquals($expected2, $select2->buildQuery());
    }

    public function testMakeSwitchFunctionality(): void
    {
        // Test makeSwitch: if the value matches, apply a callback to set offset.
        $select = new Select();
        $select
            ->select("id")
            ->from("orders")
            ->makeSwitch("test", [
                "test"  => fn (Select $s) => $s->offset(30),
                "other" => fn (Select $s) => $s->offset(40)
            ])
            ->limit(20);

        $expected = "SELECT id FROM orders OFFSET 30 ROWS FETCH NEXT 20 ROWS ONLY";
        $this->assertEquals($expected, $select->buildQuery());
    }
}

?>

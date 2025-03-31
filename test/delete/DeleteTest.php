<?php

use PHPUnit\Framework\TestCase;
use ArchAngel776\OracleQueryBuilder\Statements\Delete;
use ArchAngel776\OracleQueryBuilder\Components\Where;
use ArchAngel776\OracleQueryBuilder\Components\Param;


final class DeleteTest extends TestCase
{
    public function testDeleteWithoutWhere(): void
    {
        // DELETE FROM users (no WHERE clause)
        $delete = new Delete();
        $delete->table("users");

        $expectedQuery = "DELETE FROM users";
        $this->assertEquals($expectedQuery, $delete->buildQuery());
        $this->assertEmpty($delete->getParams());
    }

    public function testDeleteWithRawWhere(): void
    {
        // DELETE FROM orders WHERE status = 'cancelled'
        $delete = new Delete();
        $delete
            ->table("orders")
            ->where("status", "=", "cancelled");

        $expectedQuery = "DELETE FROM orders WHERE status = 'cancelled'";
        $this->assertEquals($expectedQuery, $delete->buildQuery());
        $this->assertEmpty($delete->getParams());
    }

    public function testDeleteWithParamWhere(): void
    {
        // DELETE FROM orders WHERE order_id = ?
        $delete = new Delete();
        $delete
            ->table("orders")
            ->where("order_id", "=", Param::make(456, Param::INTEGER));

        $expectedQuery = "DELETE FROM orders WHERE order_id = ?";
        $this->assertEquals($expectedQuery, $delete->buildQuery());

        $params = $delete->getParams();
        $this->assertCount(1, $params);
        $this->assertEquals([456, Param::INTEGER], $params[0]);
    }

    public function testDeleteWithComplexWhere(): void
    {
        // DELETE FROM products WHERE stock < ? OR discontinued = 'yes'
        $delete = new Delete();
        $delete
            ->table("products")
            ->where(fn (Where $w) => $w
                ->where("stock", "<", Param::make(10, Param::INTEGER))
                ->orWhere("discontinued", "=", "yes")
            );

        $expectedQuery = "DELETE FROM products WHERE (stock < ? OR discontinued = 'yes')";
        $this->assertEquals($expectedQuery, $delete->buildQuery());

        $params = $delete->getParams();
        // Only one parameter from the stock condition.
        $this->assertCount(1, $params);
        $this->assertEquals([10, Param::INTEGER], $params[0]);
    }
}

?>

<?php

use PHPUnit\Framework\TestCase;
use ArchAngel776\OracleQueryBuilder\Statements\Update;
use ArchAngel776\OracleQueryBuilder\Components\Where;
use ArchAngel776\OracleQueryBuilder\Components\Param;


final class UpdateTest extends TestCase
{
    public function testComplexUpdateQuery(): void
    {
        // Build an update query for the "employees" table with multiple SET clauses and a complex WHERE.
        $update = new Update();
        $update
            ->table("employees")
            // Using two-argument form for parameterized value.
            ->set("salary", Param::make(60000, Param::INTEGER))
            // Using associative-array form for raw values.
            ->set([
                "bonus" => 5000,
                "title" => "Senior Developer"
            ])
            // Build a complex WHERE clause with nested conditions.
            ->where(fn (Where $w) => $w
                ->where("department", "=", Param::make("Sales", Param::STRING))
                // Nested condition: (experience > ? OR position = 'Manager')
                ->andWhere(fn (Where $w) => $w
                    ->where("experience", ">", Param::make(5, Param::INTEGER))
                    ->orWhere("position", "=", "Manager")
                )
            );
        
        // Expected query:
        // UPDATE employees SET salary = ?, bonus = 5000, title = 'Senior Developer'
        // WHERE department = ? AND (experience > ? OR position = 'Manager')
        $expectedQuery = "UPDATE employees SET salary = ?, bonus = 5000, title = 'Senior Developer' WHERE (department = ? AND (experience > ? OR position = 'Manager'))";
        $this->assertEquals($expectedQuery, $update->buildQuery());
        
        // Expected parameters: [60000, INTEGER] from SET clause, then [Sales, STRING] and [5, INTEGER] from WHERE clause.
        // Note: Raw values (5000 and 'Senior Developer' and 'Manager') are embedded in the SQL.
        $params = $update->getParams();
        $this->assertCount(3, $params);
        $this->assertEquals([60000, Param::INTEGER], $params[0]);
        $this->assertEquals(["Sales", Param::STRING], $params[1]);
        $this->assertEquals([5, Param::INTEGER], $params[2]);
    }
    
    public function testUpdateWithConditionalMethods(): void
    {
        // Build an update query for the "orders" table using conditional methods from the Conditionals trait.
        $update = new Update();
        $update->table("orders")
            ->set("status", "pending")
            // Add a basic WHERE condition.
            ->where("amount", ">", Param::make(100, Param::INTEGER))
            // Conditionally add a SET clause if condition is true.
            ->makeIf(true, fn (Update $u) =>
                $u->set("priority", "high")
            )
            // Use makeSwitch to conditionally add another SET clause based on a value.
            ->makeSwitch("B", [
                "A" => fn (Update $u) => $u->set("shipping", "express"),
                "B" => fn (Update $u) => $u->set("shipping", "standard")
            ]);
        
        // Expected query:
        // UPDATE orders SET status = 'pending', priority = 'high', shipping = 'standard'
        // WHERE amount > ?
        $expectedQuery = "UPDATE orders SET status = 'pending', priority = 'high', shipping = 'standard' WHERE amount > ?";
        $this->assertEquals($expectedQuery, $update->buildQuery());
        
        // Expected parameters: Only one parameter from the WHERE clause.
        $params = $update->getParams();
        $this->assertCount(1, $params);
        $this->assertEquals([100, Param::INTEGER], $params[0]);
    }

    public function testUpdateWithNullValue(): void
    {
        // Test update query when a column is set to null.
        $update = new Update();
        $update->table("users")
               ->set("name", null)
               ->where("id", "=", Param::make(1, Param::INTEGER));

        $expectedQuery = "UPDATE users SET name = NULL WHERE id = ?";
        $this->assertEquals($expectedQuery, $update->buildQuery());

        $params = $update->getParams();
        $this->assertCount(1, $params);
        $this->assertEquals([1, Param::INTEGER], $params[0]);
    }
}

?>

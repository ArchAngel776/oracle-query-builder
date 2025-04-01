<?php

use PHPUnit\Framework\TestCase;
use ArchAngel776\OracleQueryBuilder\Statements\Insert;
use ArchAngel776\OracleQueryBuilder\Components\Param;


final class InsertTest extends TestCase
{
    public function testSingleInsertWithRawValues(): void
    {
        // Single insert with raw values (no parameters)
        $insert = new Insert();
        $insert
            ->table("users")
            ->insert([
                "name" => "John Doe",
                "age"  => 30
            ]);
        
        $expectedQuery = "INSERT INTO users (name, age) VALUES ('John Doe', 30)";
        $this->assertEquals($expectedQuery, $insert->buildQuery());
        $this->assertEmpty($insert->getParams());
    }
    
    public function testSingleInsertWithParams(): void
    {
        // Single insert with parameterized values
        $insert = new Insert();
        $insert
            ->table("users")
            ->insert([
                "name" => Param::make("Alice", Param::STRING),
                "age"  => Param::make(25, Param::INTEGER)
            ]);
        
        $expectedQuery = "INSERT INTO users (name, age) VALUES (?, ?)";
        $this->assertEquals($expectedQuery, $insert->buildQuery());
        
        $params = $insert->getParams();
        $this->assertCount(2, $params);
        $this->assertEquals(["Alice", Param::STRING], $params[0]);
        $this->assertEquals([25, Param::INTEGER], $params[1]);
    }
    
    public function testMultiBatchInsert(): void
    {
        // Multi batch insert with a mix of raw and parameterized values.
        $insert = new Insert();
        // First record: sets fields and establishes order.
        $insert
            ->table("users")
            ->insert(
                [
                    "name" => "Bob",
                    "age"  => 40
                ],
                [
                    "name" => Param::make("Carol", Param::STRING),
                    "age"  => Param::make(35, Param::INTEGER)
                ]
            );
        
        $expectedQuery = "INSERT INTO users (name, age) VALUES ('Bob', 40), (?, ?)";
        $this->assertEquals($expectedQuery, $insert->buildQuery());
        
        $params = $insert->getParams();
        $this->assertCount(2, $params);
        $this->assertEquals(["Carol", Param::STRING], $params[0]);
        $this->assertEquals([35, Param::INTEGER], $params[1]);
    }
}

?>

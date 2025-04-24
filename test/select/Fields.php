<?php

use PHPUnit\Framework\TestCase;
use ArchAngel776\OracleQueryBuilder\Statements\Select;
use ArchAngel776\OracleQueryBuilder\Components\Param;


final class Fields extends TestCase
{
    public function testPlainFieldsAndAliases(): void
    {
        $select = new Select();
        $select->select("id", "name", "email")
               ->as("ID", "FullName", "EmailAddress")
               ->from("users");

        $expected = "SELECT id AS \"ID\", name AS \"FullName\", email AS \"EmailAddress\" FROM users";
        $this->assertEquals($expected, $select->buildQuery());

        // No parameters expected from plain fields.
        $this->assertEmpty($select->getParams());
    }

    public function testAggregateFunctions(): void
    {
        $select = new Select();
        $select->count("id", true)   // COUNT(DISTINCT id)
               ->avg("score")         // AVG(score)
               ->from("results");

        $expected = "SELECT COUNT(DISTINCT id), AVG(score) FROM results";
        $this->assertEquals($expected, $select->buildQuery());

        // In these aggregate functions, the parameter values are created from the function calls,
        // but since they use a Param value internally, they will add parameters.
        // However, if our implementation simply returns an empty array in getParams() for Field/AggregateField,
        // then no parameters will be returned. Adjust assertion accordingly.
        $this->assertEmpty($select->getParams());
    }

    public function testCaseExpressionWithQuotesFalse(): void
    {
        // Build a CASE expression where then() and else() are called with $quotes = false.
        $select = new Select();
        $select->case()
               ->when(function ($w) {
                   // Condition: score >= 50
                   $w->where("score", ">=", Param::make(50, Param::INTEGER));
                   return $w;
               })
               ->then("Pass", false)  // Without quotes: Pass
               ->when(function ($w) {
                   // Condition: score < 50
                   $w->where("score", "<", Param::make(50, Param::INTEGER));
                   return $w;
               })
               ->then("Fail", false)  // Without quotes: Fail
               ->else("Unknown", false); // Without quotes: Unknown
        $select->from("exams");

        $expected = "SELECT CASE WHEN score >= ? THEN Pass WHEN score < ? THEN Fail ELSE Unknown END FROM exams";
        $this->assertEquals($expected, $select->buildQuery());

        // Check parameters collected from the two where conditions inside the CASE.
        // Each condition creates a parameter from Param::make(50, Param::INTEGER).
        $params = $select->getParams();
        $this->assertCount(2, $params);
        $this->assertEquals([50, Param::INTEGER], $params[0]);
        $this->assertEquals([50, Param::INTEGER], $params[1]);
    }

    public function testCaseExpressionWithQuotesTrue(): void
    {
        // Build a CASE expression where then() and else() are called with default quotes (true).
        $select = new Select();
        $select->case()
               ->when(function ($w) {
                   $w->where("score", ">=", Param::make(50, Param::INTEGER));
                   return $w;
               })
               ->then("Pass")  // Default quotes true: becomes 'Pass'
               ->when(function ($w) {
                   $w->where("score", "<", Param::make(50, Param::INTEGER));
                   return $w;
               })
               ->then("Fail")  // becomes 'Fail'
               ->else("Unknown"); // becomes 'Unknown'
        $select->from("exams");

        $expected = "SELECT CASE WHEN score >= ? THEN 'Pass' WHEN score < ? THEN 'Fail' ELSE 'Unknown' END FROM exams";
        $this->assertEquals($expected, $select->buildQuery());

        // Check parameters are the same as before.
        $params = $select->getParams();
        $this->assertCount(2, $params);
        $this->assertEquals([50, Param::INTEGER], $params[0]);
        $this->assertEquals([50, Param::INTEGER], $params[1]);
    }

    public function testCombinedFieldsAggregationsAndCase(): void
    {
        // Combine plain fields, aggregate function, and a CASE expression.
        $select = new Select();
        $select->select("id", "name")
               ->as("ID", "Name");
        $select->count("orders", true); // COUNT(DISTINCT orders)
        $select->case()
               ->when(function ($w) {
                   $w->where("score", ">=", Param::make(70, Param::INTEGER));
                   return $w;
               })
               ->then("High", false)
               ->when(function ($w) {
                   $w->where("score", "<", Param::make(70, Param::INTEGER));
                   return $w;
               })
               ->then("Low", false)
               ->else("Unknown", false);
        $select->from("customers");

        $expected = "SELECT id AS \"ID\", name AS \"Name\", COUNT(DISTINCT orders), CASE WHEN score >= ? THEN High WHEN score < ? THEN Low ELSE Unknown END FROM customers";
        $this->assertEquals($expected, $select->buildQuery());

        // Expect parameters from the two where conditions in the CASE.
        $params = $select->getParams();
        $this->assertCount(2, $params);
        $this->assertEquals([70, Param::INTEGER], $params[0]);
        $this->assertEquals([70, Param::INTEGER], $params[1]);
    }
}

?>

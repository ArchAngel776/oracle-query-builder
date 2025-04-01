# Oracle - Query Builder
Standalone SQL Query Builder dedicated for Oracle Database.

Oracle - Query Builder is a robust, standalone PHP library designed to build dynamic SQL queries specifically tailored for Oracle databases. It provides an object-oriented, modular approach to constructing complex SQL statements such as SELECT, INSERT, UPDATE, and DELETE queries. The library emphasizes security and flexibility by supporting parameterized queries, dynamic conditions, and Oracle-specific syntax (such as the Oracle-style LIMIT/OFFSET clause).

## Overview

Oracle - Query Builder is built with a strong focus on:

- **Modular Design:** Separate classes for different query types (Select, Insert, Update, Delete) and auxiliary classes (Batch, Set, Param, etc.) ensure clear responsibilities and easy maintenance.
- **Dynamic Query Construction:** Easily build complex queries with nested conditions, joins, unions, and conditional logic.
- **Parameter Binding:** Securely bind parameters to avoid SQL injection vulnerabilities.
- **Oracle-Specific Features:** Incorporates Oracle-specific SQL syntax and features, such as the OFFSET/FETCH clause.
- **Extensibility:** Designed with interfaces and traits (e.g., `QueryBuilder`, `Conditionals`, `Parametrized`) to allow further customization and extension.
- **Comprehensive Testing:** An extensive PHPUnit test suite validates functionality across a wide range of query scenarios.

## Features

- **SELECT Queries:** Build complex SELECT statements with support for nested queries, joins, grouping, having, ordering, and union operations.
- **INSERT Queries:** Support both single and batch inserts with robust field and value validation.
- **UPDATE Queries:** Dynamically construct UPDATE statements with parameterized SET clauses and complex WHERE conditions.
- **DELETE Queries:** Build DELETE statements with flexible and nested WHERE conditions.
- **Batch Operations:** Easily manage multi-record insert operations through the Batch class.
- **Conditional Logic:** Utilize helper methods (`makeIf` and `makeSwitch`) to add conditional logic to your queries.
- **Parameter Handling:** Securely manage query parameters using the Param class, which aligns with PDO parameter types.
- **Factory Pattern:** Use the QueryBuilderFactory to quickly instantiate the type of query builder you need.

## Installation

Clone the repository and install dependencies via Composer:

```bash
git clone https://github.com/your-repo/oracle-query-builder.git
cd oracle-query-builder
composer install
```

Ensure that your project is set up to autoload classes via Composer's autoloader.

## Usage

### SELECT Query Example

```php
use QueryBuilder\QueryBuilderFactory;

$select = QueryBuilderFactory::select();
$select->select("id", "name")
       ->from("users", "u")
       ->where("active", "=", 1)
       ->orderBy(["name" => "ASC"])
       ->limit(10)
       ->offset(0);

$sql = $select->buildQuery();
$params = $select->getParams();

echo $sql; // SELECT id, name FROM users u WHERE active = 1 ORDER BY name ASC OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY
print_r($params);
```

### INSERT Query Example

```php
use QueryBuilder\QueryBuilderFactory;

$insert = QueryBuilderFactory::insert();
$insert->table("users")
       ->insert([
           "name" => "John Doe",
           "age"  => 30
       ]);

$sql = $insert->buildQuery();
$params = $insert->getParams();

echo $sql; // INSERT INTO users (name, age) VALUES ('John Doe', 30)
```

### UPDATE Query Example

```php
use QueryBuilder\QueryBuilderFactory;
use QueryBuilder\Param;

$update = QueryBuilderFactory::update();
$update->table("users")
       ->set("name", "Jane Doe")
       ->set("age", Param::make(32, Param::INTEGER))
       ->where("id", "=", Param::make(1, Param::INTEGER));

$sql = $update->buildQuery();
$params = $update->getParams();

echo $sql; // UPDATE users SET name = 'Jane Doe', age = ? WHERE id = ?
print_r($params);
```

### DELETE Query Example

```php
use QueryBuilder\QueryBuilderFactory;
use QueryBuilder\Param;

$delete = QueryBuilderFactory::delete();
$delete->table("users")
       ->where("id", "=", Param::make(1, Param::INTEGER));

$sql = $delete->buildQuery();
$params = $delete->getParams();

echo $sql; // DELETE FROM users WHERE id = ?
print_r($params);
```

### Batch Insert Example

```php
use QueryBuilder\Batch;

$batch = new Batch();
$batch->add([
    "name" => "Alice",
    "age"  => 28
]);
$batch->add([
    "name" => "Bob",
    "age"  => 35
]);

$sql = $batch->buildQuery();
$params = $batch->getParams();

echo $sql; // (name, age) VALUES ('Alice', 28), ('Bob', 35)
print_r($params); // Parameters will be present if values were wrapped in Param objects.
```

## Classes Overview

- **QueryBuilder Interface:**  
  Defines the contract for building SQL queries with methods `buildQuery()` and `getParams()`.

- **Select Class:**  
  Builds complex SELECT queries with support for nested queries, joins, WHERE, GROUP BY, HAVING, ORDER BY, LIMIT/OFFSET, and UNION.

- **Insert Class:**  
  Constructs INSERT queries using the Batch class for managing multiple records.

- **Update Class:**  
  Dynamically constructs UPDATE statements with parameterized SET clauses and flexible WHERE conditions.

- **Delete Class:**  
  Builds DELETE queries with support for nested WHERE clauses.

- **Batch Class:**  
  Manages batch insert operations by validating fields and ordering values consistently.

- **Set Class:**  
  Represents individual field-value assignments in INSERT and UPDATE queries, supporting raw values and parameters.

- **Param Class:**  
  Represents query parameters with constants aligned to PDO parameter types.

- **Condition and Where Classes:**  
  Provide a powerful system for constructing dynamic WHERE clauses, including support for nested conditions and various operators (with special handling for `NULL` values).

- **Join Class:**  
  Manages JOIN clauses, including nested queries and dynamic join conditions.

- **Conditionals Trait:**  
  Offers utility methods `makeIf()` and `makeSwitch()` for conditional query modifications.

- **QueryBuilderFactory Class:**  
  A factory for quickly instantiating query builder objects (Select, Insert, Update, Delete).

## Testing

A comprehensive PHPUnit test suite covers the functionality of all components. To run the tests, execute:

```bash
vendor/bin/phpunit test
```

## Contributing

Contributions are welcome! If you'd like to improve the project, please fork the repository and submit a pull request. For major changes, please open an issue to discuss your ideas first.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

Oracle - Query Builder offers a powerful yet flexible solution for building SQL queries dynamically in PHP. Its modular architecture, combined with comprehensive testing and clear documentation, makes it an excellent choice for any Oracle database project. Enjoy building your queries!

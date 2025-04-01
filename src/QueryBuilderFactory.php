<?php

namespace ArchAngel776\OracleQueryBuilder;

use ArchAngel776\OracleQueryBuilder\Statements\Insert;
use ArchAngel776\OracleQueryBuilder\Statements\Select;
use ArchAngel776\OracleQueryBuilder\Statements\Update;
use ArchAngel776\OracleQueryBuilder\Statements\Delete;


class QueryBuilderFactory
{
    /**
     * Creates a new Insert query builder.
     *
     * @return Insert
     */
    public static function insert(): Insert {
        return new Insert();
    }

    /**
     * Creates a new Select query builder.
     *
     * @return Select
     */
    public static function select(): Select {
        return new Select();
    }

    /**
     * Creates a new Update query builder.
     *
     * @return Update
     */
    public static function update(): Update {
        return new Update();
    }

    /**
     * Creates a new Delete query builder.
     *
     * @return Delete
     */
    public static function delete(): Delete {
        return new Delete();
    }
}

?>

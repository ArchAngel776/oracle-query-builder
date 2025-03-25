<?php

/**
 * This is an autoloader file used to including library classes.
 * 
 * @category Core
 * @package OracleQueryBuilder
 * @author ArchAngel776
 * @license MIT License https://github.com/ArchAngel776/oracle-query-builder?tab=MIT-1-ov-file
 */

spl_autoload_register(function (string $className): void
{
    if (!preg_match("/^ArchAngel776\\\\OracleQueryBuilder(?P<structure>[a-zA-Z0-9_\\\\]*)\\\\(?P<name>[a-zA-Z0-9_]+)$/", $className, $segments))
    {
        return;
    }

    $path = __DIR__ . "/src" .  str_replace("\\", "/", $segments["structure"]);

    require_once strtolower($path) . "/" . $segments["name"] . ".php";
});

?>

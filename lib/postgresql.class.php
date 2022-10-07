<?php

class Postgresql extends Database implements DatabaseType
{

  function getTableStructure(string $tableName, string $schemaName = "public"): array
  {
    $content = array("tableName" => $tableName, $schemaName => $schemaName);

    /**
     * Get then comment on the table
     */
    $sql = "select description
    from pg_catalog.pg_statio_all_tables st
    left outer join pg_catalog.pg_description on (relid = objoid and objsubid = 0)
    where relname = :tablename and schemaname = :schemaname";
    $res = $this->execute($sql, array("tablename" => $tableName, "schemaname" => $schemaName));
    $content["tableComment"] = $res[0]["description"];

    /**
     * Get the list of columns
     */
    $sql = 'SELECT  pg_attribute.attname AS "columnName",
    pg_catalog.format_type(pg_attribute.atttypid,pg_attribute.atttypmod) AS "type",
    (SELECT col_description(pg_attribute.attrelid,pg_attribute.attnum)) AS "columnComment",
    CASE pg_attribute.attnotnull WHEN FALSE THEN 0 ELSE 1 END AS "mandatory",
    CASE WHEN pg_constraint.conname is not null then 1 ELSE 0 END AS "primaryKey",
    , CASE WHEN pg_get_serial_sequence(schemaname||\'.\'||pg_tables.tablename::text, pg_attribute.attname::text) is not null THEN 1 ELSE 0 END as "autoIncrement"
    FROM pg_tables
    JOIN pg_namespace ON (pg_namespace.nspname = pg_tables.schemaname)
    JOIN pg_class
      ON (pg_class.relname = pg_tables.tablename
    AND pg_class.relnamespace = pg_namespace.oid)
    JOIN pg_attribute ON (pg_class.oid = pg_attribute.attrelid AND pg_attribute.atttypid <> 0::OID AND pg_attribute.attnum > 0)
    LEFT JOIN pg_constraint
    ON pg_constraint.contype = \'p\'::char
    AND pg_constraint.conrelid = pg_class.oid
    AND (pg_attribute.attnum = ANY (pg_constraint.conkey))
    WHERE tablename = :tablename AND schemaname = :schemaname
    ORDER BY attnum ASC';
    $content ["columns"] = $this->execute($sql, array ("tablename" => $tableName, "schemaname" => $schemaName));
    return $content;
  }
}

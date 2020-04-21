<?php
class ExportException extends Exception
{ };


class ExportModelProcessing
{
  private $model = array();
  private $bdd;
  private $lastResultExec;
  public $modeDebug = false;
  private $lastSql = "";
  private $stmt;
  public $isWeb = true;
  public $structure = array();
  public $quote = '"';
  public $binaryFolder = "binary";
  /**
   * Constructor
   *
   * @param PDO $bdd: database connection object
   * @param bool $isWeb: false if run from command line
   */
  function __construct(PDO $bdd, bool $isWeb = true)
  {
    $this->bdd = $bdd;
    $this->isWeb = $isWeb;
  }
  /**
   * Display the content of a variable
   *
   * @param any $tableau
   * @param integer $mode_dump
   * @param bool $force
   * @return void
   */
  private function printr($tableau, $mode_dump = 0, $force = false)
  {
    global $APPLI_modeDeveloppement;
    if ($APPLI_modeDeveloppement || $force) {
      if ($mode_dump == 1) {
        var_dump($tableau);
      } else {
        if (is_array($tableau)) {
          print_r($tableau);
        } else {
          echo $tableau;
        }
      }
      if ($this->isWeb) {
        echo "<br>";
      } else {
        echo PHP_EOL;
      }
    }
  }

  /**
   * Set the used model
   *
   * @param array $model: JSON field contents the description of the tables
   * @return void
   */
  function initModel(array $model)
  {
    /**
     * Generate the model with tableName as identifier
     */
    foreach ($model as $m) {
      /**
       * Set the tableAlias if not defined
       */
      if (strlen($m["tableAlias"]) == 0) {
        $m["tableAlias"] = $m["tableName"];
      }
      $this->model[$m["tableAlias"]] = $m;
    }
  }

  /**
   * Load the structure of the database
   *
   * @param array $structure
   * @return void
   */
  function initStructure(array $structure = array())
  {
    if (count($structure) == 0) {
      throw new ExportException("The structure of the export in empty");
    }
    $this->structure = $structure;
  }

  /**
   * Generate the structure of database for all tables in the model
   *
   * @param array $model
   * @return array
   */
  function generateStructure(array $model = array()): array
  {
    if (count($model) == 0) {
      $model = $this->model;
    }
    $this->structure = array();
    foreach ($model as $table) {
      $this->structure[$table["tableName"]] = $this->getFieldsFromTable($table["tableName"]);
    }
    return ($this->structure);
  }
  /**
   * Get the list of columns of the table
   *
   * @param string $tablename
   * @return array|null
   */
  function getFieldsFromTable(string $tablename): ?array
  {
    $schematable = explode(".", $tablename);
    $schemaname = "";
    $hasSchema = false;
    if (count($schematable) == 2) {
      $schemaname = $schematable[0];
      $tablename = $schematable[1];
      $hasSchema = true;
    }
    $data = array("tablename" => $tablename);
    $select = "SELECT attnum,  pg_attribute.attname AS field,
                pg_catalog.format_type(pg_attribute.atttypid,pg_attribute.atttypmod) AS type,
                (SELECT col_description(pg_attribute.attrelid,pg_attribute.attnum)) AS COMMENT,
                CASE pg_attribute.attnotnull WHEN FALSE THEN 0 ELSE 1 END AS notnull,
                pg_constraint.conname AS key,
                (SELECT pg_attrdef.adsrc FROM pg_attrdef WHERE pg_attrdef.adrelid = pg_class.oid AND pg_attrdef.adnum = pg_attribute.attnum) AS def
                FROM pg_tables
                JOIN pg_class on (pg_class.relname = pg_tables.tablename)
                JOIN pg_attribute ON (pg_class.oid = pg_attribute.attrelid AND pg_attribute.atttypid <> 0::OID AND pg_attribute.attnum > 0)
                LEFT JOIN pg_constraint
                ON pg_constraint.contype = 'p'::char
                AND pg_constraint.conrelid = pg_class.oid
                AND (pg_attribute.attnum = ANY (pg_constraint.conkey))
                ";
    $where = ' WHERE tablename = :tablename';
    if ($hasSchema) {
      $where .= ' AND schemaname = :schemaname';
      $data["schemaname"] = $schemaname;
    }
    $order = ' ORDER BY attnum ASC';
    $result = $this->execute($select . $where . $order, $data);
    /**
     * translate sequence field to serial
     */
    foreach ($result as $key=>$field) {
      if ($field["type"] == 'integer' && substr($field["def"], 0, 7) == "nextval") {
        $result[$key]["type"] = "serial";
      }
      unset ($result[$key]["def"]);
    }
    return ($result);
  }

  /**
   * Get the list of the tables witch are not children
   *
   * @return array
   */
  function getListPrimaryTables(): array
  {
    $list = array();
    foreach ($this->model as $table) {
      if (strlen($table["parentKey"]) == 0 && !$table["isEmpty"]) {
        $list[] = $table["tableAlias"];
      }
    }
    return $list;
  }
  /**
   * Prepare the list of columns for sql clause
   *
   * @param string $tableName
   * @return string
   */
  function generateListColumns(string $tableName): string
  {
    $cols = "";
    $comma = "";
    foreach ($this->structure[$tableName] as $col) {
      if ($col["type"] != "bytea") {
        $cols .= $comma . $this->quote . $col["field"] . $this->quote;
        $comma = ",";
      }
    }
    return $cols;
  }
  /**
   * Get the list of binary fields for a table
   *
   * @param string $tableName
   * @return array
   */
  function getBinaryFields(string $tableName): array
  {
    $fields = array();
    foreach ($this->structure[$tableName] as $col) {
      if ($col["type"] == "bytea") {
        $fields[] = $col["field"];
      }
    }
    return $fields;
  }

  /**
   * Get the content of a table
   *
   * @param string $tableName: alias of the table
   * @param array $keys: list of the keys to extract
   * @param integer $parentKey: value of the technicalKey of the parent (foreign key)
   * @return array
   */
  function getTableContent(string $tableAlias, array $keys = array(), int $parentKey = 0): array
  {
    $model = $this->model[$tableAlias];
    if (count($model) == 0) {
      throw new ExportException("The alias $tableAlias was not described in the model");
    }
    $tableName = $model["tableName"];

    $content = array();
    $args = array();
    if (!$model["isEmpty"] || count($keys) > 0) {
      $cols = $this->generateListColumns($model["tableName"]);
      $sql = "select $cols from " . $this->quote . $tableName . $this->quote;
      if (count($keys) > 0) {
        $where = " where " . $this->quote . $model["technicalKey"] . $this->quote . " in (";
        $comma = "";
        foreach ($keys as $k) {
          if (is_numeric($k)) {
            $where .= $comma . $k;
            $comma = ",";
          }
        }
        $where .= ")";
      } else if (strlen($model["parentKey"]) > 0 && $parentKey > 0) {
        /**
         * Search by parent
         */
        $where = " where " . $this->quote . $model["parentKey"] . $this->quote . " = :parentKey";
        $args["parentKey"] = $parentKey;
      } else {
        $where = "";
      }
      if (strlen($model["technicalKey"]) > 0) {
        $order = " order by " . $this->quote . $model["technicalKey"] . $this->quote;
      } else {
        $order = " order by 1";
      }
      $content = $this->execute($sql . $where . $order, $args);

      /**
       * export the binary data in files
       */
      $binaryFields = $this->getBinaryFields($model["tableName"]);
      if (count($binaryFields) > 0) {
        /**
         * Verify if binary folder exists
         */
        if (!is_dir($this->binaryFolder)) {
          if (!mkdir($this->binaryFolder, 0700)) {
            throw new ExportException("The folder $this->binaryFolder can't be created");
          }
        }
        foreach ($binaryFields as $fieldName) {
          foreach ($content as $row) {
            if (strlen($row[$model["technicalKey"]]) > 0) {
              $ref = $this->getBlobReference($tableName, $model["technicalKey"], $row[$model["technicalKey"]], $fieldName);
              if ($ref) {
                $filename=$model["tableName"]."-".$fieldName."-".$row[$model["technicalKey"]].".bin";
                $fb = fopen($this->binaryFolder."/".$filename,"wb");
                fwrite($fb,fread($ref, 0));
                fclose ($fb);
              }
            }
          }
        }
      }


      if ($model["istablenn"] == 1) {
        /**
         * get the description of the secondary table
         */
        $model2 = $this->model[$model["tablenn"]["tableAlias"]];
      }
      /**
       * Search the data from the children
       */
      if (count($model["children"]) > 0) {
        foreach ($content as $k => $row) {
          foreach ($model["children"] as $child) {
            $content[$k]["children"][$child["aliasName"]] = $this->getTableContent($child["aliasName"], array(), $row[$model["technicalKey"]]);
          }
        }
      }
      /**
       * Search the parameters
       */
      if (count($model["parameters"]) > 0) {
        foreach ($content as $k => $row) {
          foreach ($model["parameters"] as $parameter) {
            $id = $row[$parameter["fieldName"]];
            if ($id > 0) {
              $content[$k]["parameters"][$parameter["aliasName"]] = $this->getTableContent($parameter["aliasName"], array($id))[0];
            }
          }
        }
      }
      if ($model["istablenn"] == 1) {
        foreach ($content as $k => $row) {
          /**
           * Get the record associated with the current record
           */
          $sql = "select * from $this->quote" . $model2["tableName"] . "$this->quote where $this->quote" . $model["tablenn"]["secondaryParentKey"] . "$this->quote = :secKey";
          $data = $this->execute($sql, array("secKey" => $row[$model["tablenn"]["secondaryParentKey"]]));
          $content[$k][$model["tablenn"]["tableAlias"]] = $data[0];
        }
      }
    }
    return $content;
  }

  /**
   * Execute a SQL command
   *
   * @param string $sql: request to execute
   * @param array $data: data associated with the request
   * @return array
   */
  private function execute(string $sql, array $data = array()): array
  {
    if ($this->modeDebug) {
      $this->printr($sql);
      $this->printr($data);
    }
    try {
      if ($this->lastSql != $sql) {
        $this->stmt = $this->bdd->prepare($sql);
        $this->lastSql = $sql;
      }
      $this->lastResultExec = $this->stmt->execute($data);
      if ($this->lastResultExec) {
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
      }
    } catch (PDOException $e) {
      $this->lastResultExec = false;
      throw new ExportException($e->getMessage());
    }
  }
/**
 * Read a binary object in the database and returns the resource file
 *
 * @param string $tableName
 * @param string $keyName
 * @param integer $id
 * @param string $fieldName
 * @return resource|null
 */
  function getBlobReference(string $tableName, string $keyName, int $id, string $fieldName): ?resource
  {
    if ($id > 0) {
      $blobRef = null;
      $sql = "select " . $this->quote . $fieldName . $this->quote . "
          from " . $this->quote . $tableName . $this->quote . "
			    where " . $this->quote . $keyName . $this->quote  . " = ?";
      $query = $this->connection->prepare($sql);
      $query->execute(array($id));
      if ($query->rowCount() == 1) {
        $query->bindColumn(1, $blobRef, PDO::PARAM_LOB);
        $query->fetch(PDO::FETCH_BOUND);
        return $blobRef;
      }
    }
    return null;
  }

  /**
   * Import data from a table
   *
   * @param string $tableName: name of the table
   * @param array $data: all data to be recorded
   * @param integer $parentKey: key of the parent from the table
   * @param array $setValues: list of values to insert into each row. Used for set a parent key
   * @param bool $deleteBeforeInsert: delete all records linked to the parent before insert new records
   * @return void
   */
  function importDataTable(string $tableAlias, array $data, int $parentKey = 0, array $setValues = array(), $deleteBeforeInsert = false)
  {
    if (!isset($this->model[$tableAlias])) {
      throw new ExportException(sprintf(_("Aucune description trouvée pour l'alias de table %s dans le fichier de paramètres"), $tableAlias));
    }
    /**
     * prepare sql request for search key
     */
    $model = $this->model[$tableAlias];
    $tableName = $model["tableName"];
    $bkeyName = $model["businessKey"];
    $tkeyName = $model["technicalKey"];
    $pkeyName = $model["parentKey"];
    if (strlen($bkeyName) > 0) {
      $sqlSearchKey = "select $this->quote$tkeyName$this->quote as key
                    from $this->quote$tableName$this->quote
                    where $this->quote$bkeyName$this->quote = :businessKey";
      $isBusiness = true;
    } else {
      $isBusiness = false;
    }
    if ($deleteBeforeInsert && $parentKey > 0) {
      $sqlDeleteFromParent = "delete $this->quote$tableName$this->quote where $this->quote$pkeyName$this->quote = :parent";
      $this->execute($sqlDeleteFromParent, array("parent" => $parentKey));
    }
    if ($model["istablenn"] == 1) {
      $tableAlias2 = $model["tablenn"]["tableAlias"];
      $model2 = $this->model[$tableAlias2];
      if (count($model2) == 0) {
        throw new ExportException(sprintf(_("Le modèle ne contient pas la description de la table %s"), $model["tablenn"]["tableAlias"]));
      }
      $tableName2 = $model2["tableName"];
      $tkeyName2 = $model2["technicalKey"];
      $bkey2 = $model2["businessKey"];
      /**
       * delete pre-existent rows
       */
      $sqlDelete = "delete from $this->quote$tableName$this->quote
            where $this->quote$pkeyName$this->quote = :parentKey";
      $this->execute($sqlDelete, array("parentKey" => $parentKey));
    }
    foreach ($data as $row) {
      if (strlen($row[$tkeyName]) > 0 || ($model["istablenn"] == 1 && strlen($row[$pkeyName]) > 0)) {
        /**
         * search for preexisting record
         */
        if ($this->modeDebug) {
          test($tableName . " key:" . $row[$tkeyName]);
          test($tableAlias . " tablename:" . $tableName . " businessKey:" . $bkeyName . " technicalKey:" . $tkeyName . " parentKey:" . $pkeyName);
        }
        if ($isBusiness && strlen($row[$bkeyName]) > 0) {
          $previousData = $this->execute($sqlSearchKey, array("businessKey" => $row[$bkeyName]));
          if ($previousData[0]["key"] > 0) {
            $row[$tkeyName] = $previousData[0]["key"];
          } else {
            unset($row[$tkeyName]);
          }
        } else {
          unset($row[$tkeyName]);
        }
        if ($parentKey > 0 && strlen($pkeyName) > 0) {
          $row[$pkeyName] = $parentKey;
        }
        if ($model["istable11"] == 1 && $parentKey > 0) {
          $row[$tkeyName] = $parentKey;
        }
        if ($model["istablenn"] == 1) {
          /**
           * Search id of secondary table
           */
          $sqlSearchSecondary = "select $this->quote$tkeyName2$this->quote as key
                    from $this->quote$tableName2$this->quote
                    where $this->quote$bkey2$this->quote = :businessKey";
          $sdata = $this->execute($sqlSearchSecondary, array("businessKey" => $row[$tableAlias2][$model2["businessKey"]]));
          $skey = $sdata[0]["key"];
          if (!$skey > 0) {
            /**
             * write the secondary parent
             */
            $skey = $this->writeData($tableAlias2, $row[$tableAlias2]);
          }
          $row[$model["tablenn"]["secondaryParentKey"]] = $skey;
        }
        /**
         * Get the real values for parameters
         */
        foreach ($row["parameters"] as $parameterName => $parameter) {
          $modelParam = $this->model[$parameterName];
          if (count($modelParam) == 0) {
            throw new ExportException(sprintf(_("L'alias %s n'a pas été décrit dans le modèle"), $parameterName));
          }
          /**
           * Search the id from the parameter
           */
          $paramKey = $modelParam["technicalKey"];
          $paramBusinessKey = $modelParam["businessKey"];
          $paramTablename = $modelParam["tableName"];
          $sqlSearchParam = "select $this->quote$paramKey$this->quote as key
                    from $this->quote$paramTablename$this->quote
                    where $this->quote$paramBusinessKey$this->quote = :businessKey";
          $pdata = $this->execute($sqlSearchParam, array("businessKey" => $parameter[$modelParam["businessKey"]]));
          $pkey = $pdata[0]["key"];
          if (!$pkey > 0) {
            /**
             * write the parameter
             */
            unset($parameter[$modelParam["technicalKey"]]);
            try {
              $pkey = $this->writeData($parameterName, $parameter);
            } catch (Exception $e) {
              throw new ExportException(sprintf(_('Erreur d\'enregistrement dans la table de paramètres %1$s pour la valeur %2$s'), $parameterName, $parameter[$modelParam["businessKey"]]));
            }
          }
          if ($this->modeDebug) {
            $this->printr("Parameter " . $parameterName . ": key for " . $parameter[$modelParam["businessKey"]] . " is " . $pkey);
          }
          if (!$pkey > 0) {
            throw new ExportException(sprintf(_("Aucune clé n'a pu être trouvée ou générée pour la table de paramètres %s"), $parameterName));
          }
          /**
           * Search the name of the attribute corresponding in the row
           */
          $fieldName = "";
          foreach ($model["parameters"] as $modParam) {
            if ($modParam["aliasName"] == $parameterName) {
              $fieldName = $modParam["fieldName"];
              break;
            }
          }
          if (strlen($fieldName) == 0) {
            throw new ExportException(sprintf(_("Erreur inattendue : impossible de trouver le nom du champ correspondant à la table de paramètres %s"), $parameterName));
          }
          $row[$fieldName] = $pkey;
        }
        /**
         * Set values
         */
        if (count($setValues) > 0) {
          foreach ($setValues as $kv => $dv) {
            if (strlen($dv) == 0) {
              throw new ExportException(sprintf(_("Une valeur vide a été trouvée pour l'attribut ajouté %s"), $kv));
            }
            $row[$kv] = $dv;
          }
        }
        /**
         * Write data
         */
        $children = $row["children"];
        unset($row["children"]);
        unset($row["parameters"]);
        $id = $this->writeData($tableAlias, $row);
        /**
         * Record the children
         */
        if ($id > 0) {
          foreach ($children as $tableChield => $child) {
            if (count($child) > 0) {
              if (!isset($child["isStrict"])) {
                $child["isStrict"] = false;
              }
              $this->importDataTable($tableChield, $child, $id, array(), $child["isStrict"]);
            }
          }
        }
      }
    }
  }

  /**
   * insert or update a record
   *
   * @param string $tableName: name of the table
   * @param array $data: data of the record
   * @return integer: technical key generated or updated
   */
  function writeData(string $tableAlias, array $data): ?int
  {
    $model = $this->model[$tableAlias];
    $tableName = $model["tableName"];
    $tkeyName = $model["technicalKey"];
    $pkeyName = $model["parentKey"];
    $skeyName = $model["tablenn"]["secondaryParentKey"];
    $dataSql = array();
    $comma = "";
    $mode = "insert";
    if ($data[$tkeyName] > 0) {
      $mode = "update";
    }
    $model["istablenn"] == 1 ? $returning = "" : $returning = " RETURNING $tkeyName";
    /**
     * update
     */
    if ($mode == "update") {
      $sql = "update $this->quote$tableName$this->quote set ";
      foreach ($data as $field => $value) {
        if (in_array($field, $model["booleanFields"]) && !$value) {
          $value = "false";
        }
        if ($field != $tkeyName) {
          $sql .= "$comma$this->quote$field$this->quote = :$field";
          $comma = ", ";
          $dataSql[$field] = $value;
        }
      }
      if (strlen($pkeyName) > 0 && strlen($skeyName) > 0) {
        $where = " where $this->quote$pkeyName$this->quote = :$pkeyName and $this->quote$skeyName$this->quote = :$skeyName";
      } else {
        $where = " where $this->quote$tkeyName$this->quote = :$tkeyName";
        $dataSql[$tkeyName] = $data[$tkeyName];
      }
      if (!isset($where)) {
        throw new ExportException(sprintf(_("la clause where n'a pu être construite pour la table %s"), $tableName));
      }
      $sql .= $where;
    } else {
      /**
       * insert
       */
      $mode = "insert";
      $cols = "(";
      $values = "(";
      foreach ($data as $field => $value) {
        if (in_array($field, $model["booleanFields"]) && !$value) {
          $value = "false";
        }
        if (!($model["istablenn"] == 1 && $field == $model["tablenn"]["tableAlias"])) {
          $cols .= $comma . $this->quote . $field . $this->quote;
          $values .= $comma . ":$field";
          $dataSql[$field] = $value;
          $comma = ", ";
        }
      }
      $cols .= ")";
      $values .= ")";
      $sql = "insert into $this->quote$tableName$this->quote $cols values $values $returning";
    }
    $result = $this->execute($sql, $dataSql);
    if ($model["istablenn"] == 1) {
      $newkey = null;
    } else if ($mode == "insert") {
      $newKey = $result[0][$tkeyName];
    } else {
      $newKey = $data[$tkeyName];
    }
    return $newKey;
  }
}

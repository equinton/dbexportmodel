<?php

class DatabaseException extends Exception
{
}

interface DatabaseType {
  function getTableStructure(string $tableName, string $schemaName = ""):array ;
}

class Database
{

  private $lastResultExec;
  public $modeDebug = false;
  private $messageDebug = array();
  private $lastSql = "";
  private PDOStatement $stmt;
  private $eol = PHP_EOL;
  private PDO $db;

  function __construct()
  {
    if (PHP_SAPI != "cli") {
      $this->eol = "<br>";
    }
  }

  /**
   * Connection to the database
   *
   * @param string $dsn
   * @param string $login
   * @param string $password
   * @return void
   */
  function connection(string $dsn, string $login, string $password) {
    try {
      $this->db = new PDO($dsn, $login, $password);
    } catch (PDOException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }

  /**
   * Execute a SQL command
   *
   * @param string $sql: request to execute
   * @param array $data: data associated with the request
   * @return array|null
   */
  public function execute(string $sql, array $data = array()): ?array
  {
    if ($this->modeDebug) {
      $this->setDebug($sql);
      $this->setDebug($data);
    }
    $result = null;
    try {
      $this->prepare($sql);
      $this->lastResultExec = $this->stmt->execute($data);
      if ($this->lastResultExec) {
        $result = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
      } else {
        $sdata = "";
        foreach ($data as $key => $value) {
          $sdata .= "$key:$value" . $this->eol;
        }
        throw new ExportException("Error when execute the request" . $this->eol
          . $sql . $this->eol
          . $sdata
          . $this->stmt->errorInfo()[2]);
      }
    } catch (PDOException $e) {
      $this->lastResultExec = false;
      throw new ExportException($e->getMessage());
    }
    return $result;
  }

  /**
   * Prepare the statement of PDO connection
   * only if the sql value change
   *
   * @param string $sql
   * @return void
   */
  private function prepare(string $sql)
  {
    if ($this->lastSql != $sql) {
      $this->stmt = $this->db->prepare($sql);
      if (!$this->stmt) {
        throw new ExportException("This request can't be prepared:" . $this->eol . $sql);
      }
      $this->lastSql = $sql;
    }
  }

  /**
   * Add a debug message
   *
   * @param string|array $content
   * @return void
   */
  private function setDebug($content)
  {
    $this->messageDebug[] = $content;
  }

  /**
   * Get the content of the debug messages
   *
   * @return string
   */
  public function getDebug(): string
  {
    $content = "";
    foreach ($this->messageDebug as $item) {
      if (is_array($item)) {
        $content .= $this->getDebugArray($item);
      } else {
        $content .= $item . $this->eol;
      }
    }
    return $content;
  }
  /**
   * Get the debug messages for array
   *
   * @param array $itemArray
   * @return string
   */
  private function getDebugArray(array $itemArray): string
  {
    $content = "";
    foreach ($itemArray as $k => $i) {
      if (is_array($i)) {
        $content .= $k . ": " . $this->getDebugArray($i, $this->eol);
      } else {
        $content .= $k . ": " . $i . $this->eol;
      }
    }
    return $content;
  }
}

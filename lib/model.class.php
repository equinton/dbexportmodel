<?php
class ModelException extends Exception
{
}

class Model
{

  private array $data;
  public array $errors;
  private $version = "2.0";
  private array $aliases;
  private array $tables;

  function __construct($json, $version = "2.0")
  {
    $this->data = json_decode($json, true);
    $this->version = $version;
    foreach ($this->data["aliases"] as $alias) {
      $this->aliases[$alias["aliasName"]] = $alias;
      if (!array_key_exists($alias["tableName"], $this->tables)) {
        $this->tables[$alias["tableName"]] = array(
          "schemaName" => $alias["schemaName"],
          "primaryKeys" => $alias["primaryKeys"],
          "businessKeys" => $alias["businessKeys"],
          "parentKeys" => $alias["parentKeys"]
        );
      }
    }
  }

  public function getDatabasetype():string {
    return $this->data["databaseType"];
  }

  function verify(): bool
  {
    $ok = true;
    /**
     * Verify the version
     */
    if ($this->data["version"] != $this->version) {
      $errors[] = "The version of the file is not compatible with the version of the program. Required: " . $this->version . ", supplied version: " . $this->data["version"];
    }
    /**
     * search if each alias is described
     */
    $fields = array("parents", "children");
    foreach ($this->aliases as $alias) {
      foreach ($fields as $field) {
        foreach ($alias[$field] as $element) {
          if (!array_key_exists($element["aliasName"], $this->aliases)) {
            $errors[] = "The alias " . $element["aliasName"] . " is not described";
          } else {
            if (empty($element["foreignKeys"])) {
              $errors[] = "The foreign keys are not provided into the alias " . $alias["aliasName"] . "for the $field " . $element["aliasName"];
            }
          }
        }
      }
    }
    empty($this->$errors) ? $ok = true : $ok = false;
    return $ok;
  }
}

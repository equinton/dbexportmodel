<?php

/**
 * Software used to exchange data between postgresql databases
 * Copyright © 2020, Eric Quinton
 * Distributed under license MIT (https://mit-license.org/)
 *
 * Usage:
 * rename param.ini.dist in param.ini
 * change params in param.ini to specify the parameters to connect the database,
 * and specify the list of schemas to analyze, separated by a comma
 */

error_reporting(E_ERROR | E_WARNING | E_PARSE);
require_once 'lib/exportmodel.class.php';
require_once 'lib/message.php';
require_once 'lib/functions.php';


$paramfile = "param.ini";
$sectionName = "database";

$message = new Message();
try {
  if ($argv[1] == "-h" || $argv[1] == "--help" || count($argv) == 1) {
    $message->set("dbExportModel : exchange data between postgresql databases, with json files");
    $message->set("Licence : MIT. Copyright © 2020 - Éric Quinton");
    $message->set("Options :");
    $message->set("-h ou --help: this help message");
    $message->set("--export: generate the export of the data");
    $message->set("--import: generate the import of the data");
    $message->set("--create: generate the sql script of creation of the tables");
    $message->set("--structure: generate the structure of the tables involved in the export");
    $message->set("--keyfile=dbexportkeys.json: list of the keys to be treated for the export operation");
    $message->set("--structurename=dbexportstructure.json: name of the file which contents the database structure");
    $message->set("--description=dbexportdescription.json: name of the file which contents the description of the export/import");
    $message->set("--data=dbexportdata.json: file contents data");
    $message->set("--binaryfolder=dbexport: name of the folder which contents the binary files exported from database");
    $message->set("--filezip=dbexport.zip: name of zip file");
    $message->set("--zip: create or use a zip file witch contents structure, description, data and binary files");
    $message->set("--sqlfile:dbcreate.sql name of the file that will contain the database tables generation sql script");
    $message->set("Change params in param.ini to specify the parameters to connect the database, and specify the list of schemas to analyze, separated by a comma");
    throw new ExportException();
  } else {
    $dbparam = parse_ini_file($paramfile, true);

    $isConnected = false;
    $structurename = "dbexportstructure.json";
    $description = "dbexportdescription.json";
    $datafile = "dbexportdata.json";
    $keyfile = "dbexportkeys.json";
    $zipfile = "dbexport.zip";
    $binaryfolder = "binary";
    $sqlfile = "dbcreate.sql";
    $readmefile = "lib/readme.md";
    $schemas = $dbparam[$sectionName]["schema"];
    $zipped = false;
    $action = "";
    $modeDebug = false;
    $tempDir = "tmp";
    $root = "";
    $structure = "";



    /**
     * Processing args
     */
    for ($i = 1; $i <= count($argv); $i++) {
      $a_arg = explode("=", $argv[$i]);
      $arg = substr($a_arg[0], 2);
      if (strlen($a_arg[1]) > 0) {
        $$arg = $a_arg[1];
      } else {
        if ($arg == "zip") {
          $zipped = true;
        } else if (in_array($arg, array("export", "import", "create", "structure"))) {
          $action = $arg;
        } else if ($arg == "debug") {
          $modeDebug = true;
        }
      }
    }


    /**
     * Database connection
     */
    try {
      $db = new PDO($dbparam[$sectionName]["dsn"], $dbparam[$sectionName]["login"], $dbparam[$sectionName]["passwd"]);
      $isConnected = true;
    } catch (PDOException $e) {
      $message->set($e->getMessage());
    }

    if ($isConnected) {
      $export = new ExportModelProcessing($db);
      $export->modeDebug = $modeDebug;
      if ($modeDebug) {
        printr("dsn:" . $dbparam[$sectionName]["dsn"]);
        printr("login:" . $dbparam[$sectionName]["login"]);
        printr("schemas:".$dbparam[$sectionName]["schema"]);
      }
      /**
       * Set the default path
       */
      if (strlen($dbparam[$sectionName]["schema"]) > 0) {
        $export->setDefaultPath($dbparam[$sectionName]["schema"]);
      }
    }
  }

  /**
   * Set the binary folder
   */
  $export->binaryFolder = $binaryfolder;
  /**
   * Prepare the zip process
   */
  if ($zipped) {
    if (!is_dir($tempDir)) {
      if (!mkdir($tempDir, 0700)) {
        throw new ExportException("The folder $tempDir can't be created");
      }
    }
    if ($action == "import") {
      /**
       * Extract the content of the archive
       */
      $zipImport = new ZipArchive();
      $zi = $zipImport->open($zipfile);
      if ($zi === true) {
        $zipImport->extractTo($tempDir);
      } else {
        throw new ExportException("The zip file can't to be opened");
      }
    }
    $root = $tempDir . "/";
  }
  /**
   * Open the structure and the description
   */

  if (!file_exists($description)) {
    throw new ExportException("The file $description don't exists");
  }
  $fd = file_get_contents($description);
  if (!$fd) {
    throw new ExportException("The file $description can't be read");
  }
  $export->initModel(json_decode($fd, true));
  if ($action != "structure") {
    if (!file_exists($structurename)) {
      /**
       * Generate the structure of the database before export
       */
      file_put_contents($structurename, $export->generateStructure());
    } else {
      $fs = file_get_contents($structurename);
      if (!$fs) {
        throw new ExportException("The file $structurename can't be read");
      }
      $structure = json_decode($fs, true);
      if (!is_array($structure) && count($structure) == 0) {
        throw new ExportException("$structurename is empty");
      }
      $export->initStructure($structure);
    }
  }

  switch ($action) {
    case "export":
      /**
       * Get the keys to treat them
       */
      if (file_exists($keyfile)) {
        $fk = json_decode(file_get_contents($keyfile), true);
      }
      $primaryTables = $export->getListPrimaryTables();
      $dexport = array();
      foreach ($export->getListPrimaryTables() as $key => $table) {
        if ($key == 0 && count($fk) > 0) {
          /**
           * set the list of records for the first item
           */
          $dexport[$table] = $export->getTableContent($table, $fk);
        } else {
          $dexport[$table] = $export->getTableContent($table);
        }
      }
      /**
       * Write datafile
       */
      if (!$zipped) {
        file_put_contents($datafile, json_encode($dexport));
        $message->set("Data are been recorded in the file $datafile. Where applicable, the binary data are stored in the folder $binaryfolder");
      } else {
        file_put_contents($root . $datafile, json_encode($dexport));
        $zip = new ZipArchive;
        $zip->open($zipfile, ZipArchive::CREATE);
        $zip->addFile($structurename, basename($structurename));
        $zip->addFile($description, basename($description));
        $zip->addFile($root . $datafile, basename($datafile));
        if (file_exists($readmefile)) {
          $zip->addFile($readmefile, basename($readmefile));
        }
        /**
         * Add binary files
         */
        if (is_dir($binaryfolder)) {
          foreach (scandir($binaryfolder) as $bf) {
            if (is_file($binaryfolder . "/" . $bf) && substr($bf, -3) == "bin") {
              $zip->addFile($binaryfolder . "/" . $bf, "binary/" . $bf);
            }
          }
        }
        $zip->close();
        /**
         * Purge of files
         */
        unlink($root . $datafile);
        if (is_dir($binaryfolder)) {
          foreach (scandir($binaryfolder) as $bf) {
            if (is_file($binaryfolder . "/" . $bf) && substr($bf, -3) == "bin") {
              unlink($binaryfolder . "/" . $bf);
            }
          }
          rmdir($binaryfolder);
        }
        $message->set("Data are available in the zip file $zipfile");
      }
      break;
    case "import":
      if ($zipped) {
        $zip = new ZipArchive();
        if (!$zip->open($zipfile)) {
          throw new ExportException("The file $zipfile can't be opened");
        }
        $umask = umask();
        umask(027);
        if (!$zip->extractTo($tempDir)) {
          throw new ExportException("The files into $zipfile can't be extracted to the folder $tempDir");
        };
        umask($umask);
        $zip->close();

        /**
         * Treatment of the import
         */
        if (!is_file($root . $datafile)) {
          throw new ExportException("The file $datafile don't exists");
        }
        $data = json_decode(file_get_contents($root . $datafile), true);
        if (!is_array($data)) {
          throw new ExportException("The file $root" . "$datafile don't contains data");
        }
        $export->importData($data);
        $message->set("Import done");
        if ($zipped) {
          /**
           * clean temp folder
           */
          cleanFolder($tempDir);
        }
      }
      break;
    case "structure":
      file_put_contents($root . $structurename, json_encode($export->generateStructure()));
      $message->set("Database structure generated in $root$structurename");
      break;
    case "create":
      file_put_contents($root . $sqlfile, $export->generateCreateSql());
      $message->set("Script of creation of the tables in the database generated in $root$sqlfile");
      break;
    default:
      throw new ExportException("No action defined. Run with -h option to see the available parameters");
  }
} catch (ExportException $ee) {
  $message->set("An error occurred during the treatment");
  $message->set($ee->getMessage());
} catch (Exception $e) {
  $message->set("An unattented error occurred during the treatment");
  $message->set($e->getMessage());
}

/**
 * Display messages
 */
foreach ($message->get() as $line) {
  echo ($line . PHP_EOL);
}
echo (PHP_EOL);

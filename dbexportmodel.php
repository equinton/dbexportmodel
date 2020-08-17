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

error_reporting(E_ERROR | E_PARSE);
require_once 'lib/exportmodel.class.php';
require_once 'lib/message.php';
require_once 'lib/functions.php';

$version = "v1.0.2 - 2020-08-17";
$paramfile = "param.ini";
$sectionName = "database";

$message = new Message();
$isHelp = false;
try {
  if ($argv[1] == "-h" || $argv[1] == "--help" || count($argv) == 1) {
    $message->set("dbExportModel : exchange data between postgresql databases, with json files");
    $message->set("Licence : MIT. Copyright © 2020 - Éric Quinton");
    $message->set("Version $version");
    $message->set("Options :");
    $message->set("-h ou --help: this help message");
    $message->set("--export: generate the export of the data");
    $message->set("--import: generate the import of the data");
    $message->set("--create: generate the sql script of creation of the tables");
    $message->set("--structure: generate the structure of the tables involved in the export");
    $message->set("--keyfile=dbexportkeys.json: list of the keys to be treated for the export operation");
    $message->set("--structurename=dbexportstructure.json: name of the file which contents the database structure");
    $message->set("--descriptionname=dbexportdescription.json: name of the file which contents the description of the export/import");
    $message->set("--dataname=dbexportdata.json: file contents data");
    $message->set("--binaryfolder=dbexport: name of the folder which contents the binary files exported from database");
    $message->set("--zipfile=dbexport.zip: name of zip file");
    $message->set("--zip: create or use a zip file witch contents structure, description, data and binary files");
    $message->set("--sqlfile:dbcreate.sql name of the file that will contain the database tables generation sql script");
    $message->set("Change params in param.ini to specify the parameters to connect the database, and specify the list of schemas to analyze, separated by a comma");
    $isHelp = true;
    throw new ExportException();
  } else {
    $dbparam = parse_ini_file($paramfile, true);

    $isConnected = false;
    $structurename = "dbexportstructure.json";
    $descriptionname = "dbexportdescription.json";
    $dataname = "dbexportdata.json";
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
        printr("schemas:" . $dbparam[$sectionName]["schema"]);
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
    if ($modeDebug) {
      echo "Initialisation of zip file done" . phpeol();
    }
  }
  /**
   * Open the structure and the description
   */

  if (!file_exists($root . $descriptionname)) {
    throw new ExportException("The file $root.$descriptionname don't exists");
  }
  $fd = file_get_contents($root . $descriptionname);
  if (!$fd) {
    throw new ExportException("The file $root.$descriptionname can't be read");
  }
  if ($modeDebug) {
    echo "File $root.$descriptionname loaded" . phpeol();
  }
  $export->initModel(json_decode($fd, true));
  if ($action != "structure") {
    if (!file_exists($root . $structurename)) {
      /**
       * Generate the structure of the database before export
       */
      file_put_contents($root . $structurename, $export->generateStructure());
    } else {
      $fs = file_get_contents($root . $structurename);
      if (!$fs) {
        throw new ExportException("The file $root.$structurename can't be read");
      }
      $structure = json_decode($fs, true);
      if (!is_array($structure) && count($structure) == 0) {
        throw new ExportException("$root.$structurename is empty");
      }
      $export->initStructure($structure);
    }
    if ($modeDebug) {
      echo "File $root.$structurename loaded" . phpeol();
    }
  }

  switch ($action) {
    case "export":
      /**
       * Get the keys to treat them
       */
      if (file_exists($keyfile)) {
        $fk = json_decode(file_get_contents($keyfile), true);
        if ($modeDebug) {
          echo "File $keyfile loaded" . phpeol();
        }
      }
      $primaryTables = $export->getListPrimaryTables();
      $dexport = array();
      foreach ($export->getListPrimaryTables() as $key => $table) {
        if ($modeDebug) {
          echo "Treatment of $table..." . phpeol();
        }
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
        file_put_contents($dataname, json_encode($dexport));
        $message->set("Data are been recorded in the file $dataname. Where applicable, the binary data are stored in the folder $binaryfolder");
      } else {
        if ($modeDebug) {
          echo "Create the zipfile" . phpeol();
        }
        file_put_contents($root . $dataname, json_encode($dexport));
        $zip = new ZipArchive;
        $zip->open($zipfile, ZipArchive::CREATE);
        $zip->addFile($structurename, basename($structurename));
        $zip->addFile($descriptionname, basename($descriptionname));
        $zip->addFile($root . $dataname, basename($dataname));
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
        unlink($root . $dataname);
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
        if ($modeDebug) {
          echo "Opening the file $zipfile" . phpeol();
        }
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
         * Set the binary folder to the class
         */
        $export->binaryFolder = $root . $binaryfolder;
      }
      /**
       * Treatment of the import
       */
      if (!is_file($root . $dataname)) {
        throw new ExportException("The file $dataname don't exists");
      }
      $data = json_decode(file_get_contents($root . $dataname), true);
      if (!is_array($data)) {
        throw new ExportException("The file $root" . "$dataname don't contains data");
      }
      if ($modeDebug) {
        echo "Importing data..." . phpeol();
      }
      $export->importData($data);
      $message->set("Import done");
      if ($zipped) {
        /**
         * clean temp folder
         */
        if ($modeDebug) {
          echo "Cleaning temporary files create from zipfile..." . phpeol();
        }
        cleanFolder($tempDir);
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
  if (!$isHelp) {
    $message->set("An error occurred during the treatment");
    $message->set($ee->getMessage());
  }
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

<?php
/**
 * Delete recursively all files and directories into a directory
 *
 * @param string $foldername
 * @return void
 */
function cleanFolder(string $foldername) {
  foreach (scandir($foldername) as $name) {
    if ($name != "." && $name != "..") {
      if (is_dir($name)) {
        cleanFolder($foldername."/".$name);
        if (!rmdir($name)) {
          throw new ExportException("The folder $name can't be deleted");
        }
      } else {
        if (! unlink($name)) {
          throw new ExportException("The file $foldername/$name can't be deleted");
        }
      }
    }
  }
}
  /**
   * Display the content of a variable
   *
   * @param any $tableau
   * @param integer $mode_dump
   * @param bool $force
   * @return void
   */
  function printr($tableau, $mode_dump = false)
  {

      if ($mode_dump) {
        var_dump($tableau);
      } else {
        if (is_array($tableau)) {
          print_r($tableau);
        } else {
          echo $tableau;
        }
      }
      phpeol();
  }
  /**
   * Generate a line return with <br> or PHP_EOL
   *
   * @return void
   */
  function phpeol () {
    if (PHP_SAPI == "cli") {
      echo PHP_EOL;
    } else {
      echo "<br>";
    }
  }
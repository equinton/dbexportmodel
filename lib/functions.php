<?php
$testOccurrence = 0;
/**
 * Delete recursively all files and directories into a directory
 *
 * @param string $foldername
 * @return void
 */
function cleanFolder(string $foldername)
{
  if (is_dir($foldername) && $foldername != "." && $foldername != "..") {
    foreach (scandir($foldername) as $name) {
      if ($name != "." && $name != "..") {
        if (is_dir($foldername . "/" . $name)) {
          cleanFolder($foldername . "/" . $name);
        } else {
          if (!unlink($foldername . "/" . $name)) {
            throw new ExportException("The file $foldername/$name can't be deleted");
          }
        }
      }
    }
    if (!rmdir($foldername)) {
      throw new ExportException("The folder $foldername can't be deleted");
    }
  } else {
    throw new ExportException("$foldername is not a folder that can be erased");
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
  echo phpeol();
}
/**
 * Generate a line return with <br> or PHP_EOL
 *
 * @return void
 */
function phpeol()
{
  if (PHP_SAPI == "cli") {
    return PHP_EOL;
  } else {
    return "<br>";
  }
}

function test($content = "")
{
  global $testOccurrence;
  echo "test $testOccurrence: $content " . phpeol();

  $testOccurrence++;
}

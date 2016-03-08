<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

 require_once("Logger.php");
# $localPrefix="lol";
# $cmd = "./enableElasticSearch $localPrefix > /var/log/es/out.log 2>/var/log/es/err";
# $out = shell_exec($cmd);
  $HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." ES: " );

?>

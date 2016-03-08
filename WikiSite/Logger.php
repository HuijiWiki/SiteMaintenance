<?php
require_once("/var/www/services/log4php-2.3.0/Logger.php");
Logger::configure("log-config.xml");
$HJLogger = Logger::getLogger("myLogger");
$ProjectName = "WikiSite";
?>


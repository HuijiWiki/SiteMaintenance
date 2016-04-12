<?php
require_once("/var/www/services/log4php-2.3.0/Logger.php");
Logger::configure(__DIR__."/log-config.xml");
$HJLogger = Logger::getLogger("myLogger");
$ProjectName = "WikiSiteCreation";
?>

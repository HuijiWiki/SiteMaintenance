<?php
/**
 * Remove Site From Huiji
 */
require_once('WikiSite.php');
if (php_sapi_name() == "cli") {
    // In cli-mode
    if(!isset($argv)){
    	$HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: missing arguments" );
    }
	echo "Are you sure you want to destroy $argv[1]?  Type 'yes' to continue: ";
	$handle = fopen ("php://stdin","r");
	$line = fgets($handle);
	if(trim($line) != 'yes'){
	    echo "ABORTING!\n";
	    exit;
	}
	echo "\n"; 
	echo "Thank you, continuing...\n";
    $site = new WikiSite($argv[1],'','','','');
    $site->remove();
    $HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." ##### Finish destructing ".$argv[1]." wikisite" );

} else {
    // Not in cli-mode
    $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: Attempt to access site destructor from web" );
}
?>
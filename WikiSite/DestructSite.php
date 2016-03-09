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
	$i = 1;
	$sites = '';
	for( $i = 1; $i < count($argv)-1;  $i++){
	    $sites .= $argv[$i].' ';
	}
	echo "Are you sure you want to destroy $sites?  Type 'yes' to continue: ";
	$handle = fopen ("php://stdin","r");
	$line = fgets($handle);
	if(trim($line) != 'yes'){
	    echo "ABORTING!\n";
	    exit;
	}
	echo "\n"; 
	echo "Thank you, continuing...\n";
	$i = 1;
	for( $i = 1; $i < count($argv)-1;  $i++){
		$HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ."destructing ".$argv[$i]."" );
	    $site = new WikiSite($argv[$i],'','','','');
	    $site->remove();		
	}
    // $site = new WikiSite($argv[1],'','','','');
    // $site->remove();
    $HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." ##### Finish destructing ".$sites."" );

} else {
    // Not in cli-mode
    $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: Attempt to access site destructor from web" );
}
?>
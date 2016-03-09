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

    $site = new WikiSite($argv[1]);
    $site->remove();
    $HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." ##### Finish destructing ".$this->wikiname."(".$this->domainprefix.") wikisite" );

} else {
    // Not in cli-mode
    $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: Attempt to access site destructor from web" );
}
?>
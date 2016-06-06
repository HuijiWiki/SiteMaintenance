<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/DBUtility.php');
require_once(__DIR__.'/ErrorMessage.php');
require_once(__DIR__.'/../BaseSite.php');
require_once(__DIR__.'/../WebSocket.php');
require_once(__DIR__.'/Logger.php');
class WikiSite extends BaseSite implements WebSocket{
    
    public $domainprefix ;
    public $dbprefix;
    public $wikiname;
    public $domaintype;
    public $domaindsp; 
    private $founderid;
    private $foundername;
    private $manifestName;
    private $steps = array(
        1 => '点火准备',
        2 => '请检查氧气阀压力',
        3 => '请系好安全带',
        4 => '主发动机点火中……',
        5 => '请不要恐慌',
        6 => '灰机已经起飞！重复一遍，灰机已经起飞！',
    );


    /** Constructor
     *
     * 
     * @param type $prefix domain prefix
     * @param type $name domain name
     * @param type $type wiki type
     * @param type $dsp wiki description
     */
    public function __construct($prefix, $name, $type, $dsp , $manifestName, $userId, $userName){
        $this->domainprefix = $prefix;
        $this->dbprefix = $prefix.'_';
        $this->wikiname = $name;
        $this->domaintype = $type;
        $this->domaindsp = $dsp;
        $this->manifestName = $manifestName;
	   $this->founderid = $userId;
	   $this->foundername = $userName;
    }
   


    public function sendMessage($conn,$data){
	   $conn->send(json_encode($data));
    
    }
    public function receiveMessage($conn,$data){


    }

    public function creationStepProgress($connection,$status,$action,$step,$extra,$percent){
	if($connection == null){
		$this->showProgress($step);
	}else{
		$m = (object)[
			'step' => $step,
			'extra' => $extra,
			'percent' => $percent,
		];
	
		$data = (object)[
			'status' => $status,
			'action' => $action,
			'message' => $m,
		];
		$this->sendMessage($connection,$data);
	}
    }


   public function mSleep(){
	sleep(3);
	return 0;
   } 

    /** Create a complete working sub wiki
     * 
     * @return Int; if not sucessful return the error code else 0;
     */
    public function create($connection){
        global $HJLogger, $ProjectName;
        //----------------------------------------
        // Total processes
	
	$returnCode = 0; 
	$HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." ##### Start building ".$this->wikiname."(".$this->domainprefix.") wikisite" );
       
	
        $HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." Start Setp 1: check user session" );
        $sessionRet = $this->setFounderInfo($connection);
        if($sessionRet != 0){
           $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail at Setp 1: check user session" );
	   $this->creationStepProgress($connection, "fail", "create", 1, "用户未登陆", 10);
           return ErrorMessage::ERROR_NOT_LOG_IN;
        }
 	$HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." Pass Setp 1: check user session" );
        $this->creationStepProgress($connection, "success", "create", 1, "用户已登录", 10);


	$HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." Start Setp 2: install site" );
        $installRet = $this->install();
        if($installRet != 0){
	    $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail at Setp 2: install site" );
          $this->remove();
	    $this->creationStepProgress($connection, "fail", "create", 2, "安装站点失败", 40);
            return ErrorMessage::ERROR_FAIL_INSTALL_SITE;
        }
        $HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." Pass Setp 2: install site" );
	$this->creationStepProgress($connection, "success", "create", 2, "安装站点成功", 40);

	$HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." Start Setp 3: update site" );
	$updateRet = $this->update();
        if($updateRet != 0){
 	   $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail at Setp 3: update site" ); 
         $this->remove();
	   $this->creationStepProgress($connection, "fail", "create", 3, "更新站点失败", 70);
           return ErrorMessage::ERROR_FAIL_UPDATE_SITE;
        }
        $HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." Pass Setp 3: update site" ); 
        $this->creationStepProgress($connection, "success", "create", 3, "更新站点成功", 70);


	$HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." Start Setp 4: promote user privilege" );
        $promoteRet = $this->promote();
        if($promoteRet != 0){
           $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail at Setp 4: promote user privilege" );
	   $this->creationStepProgress($connection, "fail", "create", 4, "提升权限失败", 80);
           $returnCode = ErrorMessage::ERROR_FAIL_PROMOTE_USER_PRIVILEGE;
        }else{
           $HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." Pass Setp 4: promote user privilege" );	
           $this->creationStepProgress($connection, "success", "create", 4, "提升权限成功", 80);
	}

	$HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." Start Setp 5: migrate" );
        $migrateRet = $this->migrate();
	if($migrateRet != 0){
           $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail at Setp 5: migrate" );
	   $this->remove();
	   $this->creationStepProgress($connection, "fail", "create", 5, "搬运模版失败", 90);
           return ErrorMessage::ERROR_FAIL_MIGRATE;
        }
        $HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." Pass Setp 5: migrate" );
        $this->creationStepProgress($connection, "success", "create", 5, "搬运模版成功", 90);


	$HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." Start Setp 6: enable ES service" );
        if(self::createESIndex($this->domainprefix) != 0){
           $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail at Setp 6: enable ES service" );
	   $this->creationStepProgress($connection, "fail", "create", 6, "搜索功能失败", 100);
           $returnCode = ErrorMessage::ERROR_FAIL_ENABLE_ES;
        }else{
	   $HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." Pass Setp 6: enable ES service" );         
           $this->creationStepProgress($connection, "success", "create", 6, "搜索功能成功", 100);
	}
       	$HJLogger->info("$ProjectName ". __FILE__ ." ". __LINE__ ." ##### Finish building ".$this->wikiname."(".$this->domainprefix.") wikisite" );
        return $returnCode; 
    }
       
    public static function checkRule($name, $domain){
#       global $HJLogger, $ProjectName;
        $status = 0;
        $reg = "/^[A-Za-z0-9][A-Za-z0-9-]*$/i";

        if( strlen( $domain ) === 0 || empty($domain) || empty($name) ) {
            // empty fielad
#    	   $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Check: site domain is empty" );
            $status = ErrorMessage::ERROR_DOMAIN_IS_EMPTY;
        }
        elseif ( strlen( $domain ) < 4 ) {
            // too short
 #   	   $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Check: site domain is too short" );
            $status = ErrorMessage::ERROR_DOMAIN_TOO_SHORT;
        }
        elseif ( strlen( $domain ) > 30 ) {
            // too long
#           	$HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Check: site domain is too long" );
    	   $status = ErrorMessage::ERROR_DOMAIN_TOO_LONG;
        }
        elseif( preg_match($reg, $domain) !== 1 ){
#    	   $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Check: site domain is invalid" );
            $status = ErrorMessage::ERROR_DOMAIN_INVALID_CHAR;
        }
        elseif ( strpos ($domain, 'fuck') !== false || strpos ($domain, 'sex') !== false || strpos ($domain, 'porn') !== false) {
            //no dot allowed in production server
#    	   $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Check: site domain is bad name" );
            $status = ErrorMessage::ERROR_DOMAIN_BAD_NAME;
        }
            
        else {
            if( DBUtility::domainExists( $domain) ) {
#               $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Check: site domain already exits" );
                $status = ErrorMessage::ERROR_DOMAIN_NAME_TAKEN;
            }
        }
       
        return $status;
    }
    
    
    /**
     * 
     * @return Int, return error code if not successful, 0 if successful
     */
    public static function createSiteFileDir($domainprefix){
        $name = $domainprefix;
        global $HJLogger, $ProjectName;
  
        $structure = "/var/www/virtual/".$name;
        
            // To create the nested structure, the $recursive parameter 
            // to mkdir() must be specified.
        $oldmask = umask(0);
        if (!mkdir($structure, 0777,true)) {
	    $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: create dir" ); 
            return ErrorMessage::ERROR_FAIL_FOLDER;
        }
        if(!mkdir($structure."/uploads",0777,true)) {
	    $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: create dir" );
            return ErrorMessage::ERROR_FAIL_CREATE_UPLOAD;
        }
        if(!mkdir($structure."/cache",0777,true)) {
            $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: create dir" );
            return ErrorMessage::ERROR_FAIL_CREATE_CACHE;
        }
	if(!mkdir($structure."/style",0777,true)) {
            $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: create dir" );
            return ErrorMessage::ERROR_FAIL_FOLDER;
        }

        //the source link from the linked folder
        // if($srcDir == null){
        //  $srcDir = "/var/www/src/extensions/SocialProfile";   
        // }
        
        // self::xcopy($srcDir."/avatars", $structure."/uploads/avatars");
        // self::xcopy($srcDir."/awards", $structure."/uploads/awards");    
        umask($oldmask);
        // use shared avatars and awards from the main site.
        exec('ln -s /var/www/html/uploads/avatars '.$structure."/uploads/avatars",$output,$return_code);
    	if($return_code > 0) {
    		$HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: link dir" );
    		return ErrorMessage::ERROR_FAIL_LINK_FOLDER;
    	}
            exec('ln -s /var/www/html/uploads/awards '.$structure."/uploads/awards",$output,$return_code);
    	if($return_code > 0) {
    		$HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: link dir" );
    		return ErrorMessage::ERROR_FAIL_LINK_FOLDER;
    	}

        exec('ln -s /var/www/src/* '.$structure,$output,$return_code);

        return 0;
    }
    
    /** remove a created wiki directory. 
     * 
     * @return 0 if successful, errorcode if not. 
      */
    public static function removeSiteFileDir($domainprefix){
        global $HJLogger, $ProjectName;
	$domainprefix = trim($domainprefix);
        $structure = "/var/www/virtual/".$domainprefix;
	$target = "/var/backups/".$domainprefix.'-'.time();
        $cmd = "mv ".$structure." ".$target;
        exec($cmd, $output, $return_var);
        if($return_var){
            $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: remove dir " );
            return ErrorMessage::ERROR_REMOVE_DIR;
        }

        return 0;
   
    }
    
    
    
    
    
    /**install a new wiki by running the install.php script. 
     * 
     * @param type $domainprefix
     * @param type $wikiname
     * @return int error code if fails, 0 if successful
     */
    
   public function installSiteBycScript(){
         global $HJLogger, $ProjectName;
      //create wll the script params
	$domainprefix = $this->domainprefix;
	$wikiname = $this->wikiname;
	$domaintype = $this->domaintype;
	$domaindsp = $this->domaindsp;

        $domainDir = str_replace(".","_",$domainprefix);
        $name = "huiji_".$domainDir;
        $structure = '/var/www/virtual/'.$domainprefix;
        $install_cmd = "php ".$structure."/maintenance/install.php --dbuser=".Confidential::$username." --dbpass=".Confidential::$pwd;
        $name_admin = " ".$wikiname." ".$wikiname."_admin";
        $confpath = " --confpath=".$structure;
        $pass = " --pass=123123 ";
        $install_db = " --installdbuser=".Confidential::$username." --installdbpass=".Confidential::$pwd;
        $db_info= " --dbserver=".Confidential::$servername." --dbname=huiji_sites --dbprefix=".$domainDir.'_';
        $script_path = " --scriptpath=";
        $lang = " --lang=zh-cn";
	$out = " >/var/log/site-maintenance/wikisite/install.log 2> /var/log/site-maintenance/wikisite/install.err";
        $install_cmd = $install_cmd.$name_admin.$confpath.$pass.$install_db.$db_info.$script_path.$lang.$out;
        exec($install_cmd,$out,$return_code);
	if($return_code > 0)
	{
	    $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: exec php script" );
            return ErrorMessage::ERROR_FAIL_EXEC_CALL;
        }
	return 0;
   }


     /**insert domain prefix into global and inter wiki DB 
     * 
     * @return int error code if fails, 0 if successful
     */
    
   public  function insertWikiSitePrefixIntoDB(){
          global $HJLogger, $ProjectName;
 
	$db_key = DBUtility::insertGlobalDomainPrefix($this->domainprefix, $this->wikiname, $this->domaintype, $this->domaindsp, $this->founderid, $this->foundername);
	if($db_key != FALSE){
	   $this->id = $db_key;
	}else{
	   $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: insert global domain prefix into DB" );
	   return ErrorMessage::ERROR_FAIL_DATABASE_INSERT;
	}
	
	if(DBUtility::insertInterwikiPrefix($this->domainprefix, $db_key) == false){
	   $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: insert inter domain prefix into DB" );
	   return ErrorMessage::ERROR_FAIL_DATABASE_INSERT;

	}
        return 0;
    }

   /**
   * install new wikisite
   *
   * @return int
   **/

  public function install(){
    global $HJLogger, $ProjectName;

    if(self::createSiteFileDir($this->domainprefix) > 0){
	   $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: create wikisite file directions and files " );
	   return ErrorMessage::ERROR_FAIL_CREATE_DIR;
    }

    self::createDefaultStyleFile($this->domainprefix);

    if($this->installSiteByMWScript() > 0){
       $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: run mediawiki install.php script to install new wikisite" );        
       return ErrorMessage::ERROR_FAIL_EXE_INSTALL_CMD;
    }
    $ret = $this->insertWikiSitePrefixIntoDB();
    if($ret > 0){
       $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: insert site prefix in domain and interwiki db " );
       return ErrorMessage::ERROR_FAIL_INSERT_DOMAIN_PREFIX;
    }
    return 0;
   
  }

   /**
   * remove new wikisite
   *
   * @return int
   **/

  public function remove(){
       global $HJLogger, $ProjectName;
       self::removeSiteFileDir($this->domainprefix);
       self::dropSiteDB($this->domainprefix);
       self::clearWikiSitePrefixInDB($this->domainprefix); 
       self::removeESIndex($this->domainprefix);
  }



    
    /** drop one site DatBase
     * 
     * @param  string : wikisite domainprefix
     * @return int : ERROR CODE if fail, 0 if success
     */
    public static function dropSiteDB($domainprefix){ 
	    global $HJLogger, $ProjectName;
            if(DBUtility::dropDB($domainprefix) == false){
	           $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: drop ".$domainprefix." DB");
	           return ErrorMessage::ERROR_FAIL_DATABASE_DROP;
        }
	return 0;
        
    }

    /**
     * remove site record from domain & interwiki table in huiji DB
     * @param string : wikisite domainprefix
     * @return int :ERROR CODE if fail, 0 if success
     *
     */
    public static function clearWikiSitePrefixInDB($domainprefix){
    	global $HJLogger, $ProjectName;
        if(DBUtility::deletePrefixFromDomainTable($domainprefix) == false){
	 $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: delete ".$domainprefix." from domain table");
         return ErrorMessage::ERROR_FAIL_CLEAR_DOMAIN_TABLE;
        }

        if(DBUtility::deletePrefixFromInterWikiTable($domainprefix) == false){
          $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: delete ".$domainprefix." from interwiki table");
          return ErrorMessage::ERROR_FAIL_CLEAR_INTERWIKI_TABLE;
        }

        if(DBUtility::deleteSiteFromUserSiteFollowTable($domainprefix) == false){
	   $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: delete ".$domainprefix." from user_site_follow table");
           return ErrorMessage::ERROR_FAIL_CLEAR_INTERWIKI_TABLE;
        }
        return 0;
    }


    /**
     * remove elasticsearch index for wikisite
     * @return int :ERROR CODE if fail, 0 if success
     *
     */

   public static function removeESIndex($domainprefix){
	global $HJLogger, $ProjectName;
        $localPrefix = strtolower($domainprefix);
	$cmd = __DIR__."/removeESIndex $localPrefix ";
	exec($cmd,$output,$return_code);
	if($return_code > 0){
	  $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: call exec" );
	  return ErrorMessage::ERROR_FAIL_EXEC_CALL;
	}
	return 0;
   }


 

    /**
     * create elasticsearch index for wikisite
     * @return int :ERROR CODE if fail, 0 if success
     *
     */

    public static function createESIndex($domainprefix){
	global $HJLogger, $ProjectName;
        $localPrefix = strtolower($domainprefix);
	$cmd = __DIR__."/createESIndex $localPrefix >/var/log/site-maintenance/wikisite/es.log 2> /var/log/site-maintenance/wikisite/es.err";
	exec($cmd,$output,$return_code);
	if($return_code > 0){
	  $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: call exec" );
	  return ErrorMessage::ERROR_FAIL_EXEC_CALL;
	}
	return 0;
  }
   
   public function setFounderInfo($conn){
	if($conn == null){
		return $this->checkUserSession();
	}
	sleep(3);
	return 0;
   }
   

 
    /**Check the current user session
     * 
     * @return int : 0 if success, ERROR_CODE if fail
     */
    public function checkUserSession(){
        global $HJLogger, $ProjectName;
        $session_cookie = 'huiji_session';
        if(!isset($_COOKIE[$session_cookie]))
        {
	    $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: get user cookie" );
            return ErrorMessage::ERROR_NO_USR_SESSION;
        }
        else
        {
            $ch = curl_init();
            $api_end = 'http://www.huiji.wiki/api.php?action=query&format=xml&meta=userinfo';
            curl_setopt($ch, CURLOPT_URL, $api_end);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_COOKIE, $session_cookie . '=' . $_COOKIE[$session_cookie]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $ret = curl_exec ($ch);
            curl_close ($ch);
	    if($ret == false){
		$HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: get curl call" );
		return ErrorMessage::ERROR_FAIL_CURL_CALL;
	    }
            if((preg_match('/id="(\d+)"/',$ret,$userId) && $userId[1]) && (preg_match('/name="(.*?)"/u',$ret,$userName))){
                #pop a simple window for user to wait 
                #   return $id;
                $this->founderid = $userId[1];
                $this->foundername = $userName[1];
                return 0;
            }
            else{
		$HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: fectch found_id and foundname " );

                return ErrorMessage::ERROR_FAIL_GET_USER_SESSION;
            }
        }
    }


   /**
   * migrate content or template into site
   *
   * @return int
   **/
  
   public function migrate(){
	global $HJLogger, $ProjectName;
	if($this->manifestName === "empty"){
	   return 0;
        }
        else if($this->manifestName === "internal"){
           // $manifestChoice = "Manifest:灰机基础包";
          if( self::migrateInitialManifest($this->domainprefix) > 0){
		$HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: migrate from initial mainfest " );
		return ErrorMessage::ERROR_FAIL_MIGRATE_INITIAL_MANIFEST;
           }
    
        }
        else if($manifest === "external"){
            $fromDomain = $_POST["fromDomain"]; //get the wikia site to get the nav bar informaiton
            $toDomain = $this->domainprefix.".huiji.wiki";
           if(self::migrateWikia($fromDomain, $toDomain) > 0){
		$HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: migrate from wiki" );
		return ErrorMessage::ERROR_FAIL_MIGRATE_FROM_WIKIA;
    	   }
        } 
       return 0;
   }




    /** 
    * When Create Wiki, copy the initial templates into the newly created wiki site
    *
    * @param $domainprefix : the domain prefix of the inital template
    * @param $iniTemplateName: the choice from user about which template user wants to install.
    *
    * @return int : 0 if success, ERROR_CODE if fail
    **/
    public static function migrateWikia($fromDomain, $toDomain){
        global $HJLogger, $ProjectName;
        $params = array('fromDomain'=>$fromDomain, 'targetDomain'=>$toDomain);
        $ch = curl_init();
        $param_url = http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, 'http://www.huiji.wiki:3000/service/mm?'.$param_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $ret = curl_exec($ch);
        curl_close($ch);
	if($ret == false){
	   $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: get curl call" );
	   return ErrorMessage::ERROR_FAIL_CURL_CALL;
	}
        return 0;
    }
    /** 
    * When Create Wiki, copy the initial templates into the newly created wiki site
    *
    * @param $domainprefix : the domain prefix of the inital template
    * @param $iniTemplateName: the choice from user about which template user wants to install.
    *
    * @return int:  0 if the curl call is sucessful, false ERROR_CODE.
    **/
    public static function migrateInitialManifest($domainprefix){ 
        global $HJLogger, $ProjectName;
        $targetDomain = $domainprefix.".huiji.wiki";
        $fromDomain = "templatemanager.huiji.wiki";
        $manifestName = "Manifest:灰机基础包";
        $params = array('fromDomain' => $fromDomain, 'targetDomain' => $targetDomain,'skeletonName' => $manifestName);
        $ch = curl_init();
        $param_url = http_build_query($params);
       
	curl_setopt($ch, CURLOPT_URL, 'http://www.huiji.wiki:3000/service/smp?'.$param_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

	$ret = false;
	$count = 0;
	
	while(($ret == false || json_decode($ret)->status == 'fail') && $count <=2){	
        	$ret = curl_exec($ch);
		$count++;
	}
    	if($ret == false){
    	       $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: get curl call" );
               curl_close($ch);
               return ErrorMessage::ERROR_FAIL_CURL_CALL;
    	}
//	var_dump($ret);
	if(json_decode($ret)->status == 'fail'){
	       $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: migrate service error" );
               curl_close($ch);
               return ErrorMessage::ERROR_FAIL_MIGRATE;

	}
        curl_close($ch);
        return 0;
    }


    public static function createDefaultStyleFile($sitePrefix){
	$target = '/var/www/virtual/'.$sitePrefix.'/style/SiteColor.less';
	if((copy('/var/www/src/style/SiteColor.less', $target) == false) || (chmod($target, 0777) == false)){
		$HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: copy site style color file" );
           	 return ErrorMessage::ERROR_FAIL_COPY_FILE;
	}else{
		return 0;
	}
    }






        /** Replace the current LocalSettings.php after it is generated
         * 
         * @param string $srcDir source directroy to copy template from
         * @param string $targetDir target directory to copy template to
	 * @return Boolean 
         */
    public static function copyTemplateLocalSetting($srcDir=null, $targetDir=null){
        global $HJLogger, $ProjectName;
        if($srcDir == null){
            $srcDir = '/var/www/src/LocalSettings.php.example';
        }
        if($targetDir == null){
            $targetDir = './LocalSettings.php';
        }
        return copy($srcDir,$targetDir);
    }
    /**update the localsetting.s.php
         * 
         * @param type $domainprefix
         * @param type $wikiname
         * @param string $fileName
         * @return int 0 if suceessfu.
         */
    public static function updateLocalSettings($wikiname,$wikiid,$domainprefix){
	    global $HJLogger, $ProjectName;
        #$domainDir = str_replace(".","_",$domainprefix);
        $fileName = '/var/www/virtual/'.$domainprefix.'/LocalSettings.php';
        $templateName = '/var/www/src/LocalSettings.php.example';
        if(self::copyTemplateLocalSetting($templateName,$fileName) == false){
    	    $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: copy template local setting file" );
    	    return ErrorMessage::ERROR_FAIL_COPY_FILE;
        }
        $file_contents = file_get_contents($fileName);
        $file_contents = str_replace("%wikiname%",$wikiname,$file_contents);
        $file_contents = str_replace("%domainprefix%",$domainprefix,$file_contents);
        $file_contents = str_replace("%wikiid%",$wikiid,$file_contents);
        file_put_contents($fileName,$file_contents);        
        return 0; 
    }
    
    /**
    * Run the update.php in /maintenance.php to create and register necessary dbs for extensions 
    * $domainprefix : the domain prefix for the new wiki
    */
    public static function updateSiteByMWScript($domainprefix){
        global $HJLogger, $ProjectName;
        $command = "php /var/www/virtual/".$domainprefix."/maintenance/update.php  --conf=/var/www/virtual/".$domainprefix."/LocalSettings.php --quick >/var/log/site-maintenance/wikisite/update.log 2> /var/log/site-maintenance/wikisite/update.err;";
    	$con = 1;
    	$count = 0;
    	while($con > 0 && $count <= 4){
    	   exec($command,$out,$return_code);
    	   $con = $return_code;
    	   $count++;
    	}
    	if($con > 0){
            $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: run exec call" );
            return ErrorMessage::ERROR_FAIL_EXEC_CALL;
    	}
        $command = " php /var/www/virtual/".$domainprefix."/maintenance/rebuildLocalisationCache.php  --conf=/var/www/virtual/".$domainprefix."/LocalSettings.php --lang=en,zh-cn,zh,zh-hans,zh-hant >/var/log/site-maintenance/wikisite/update.log 2> /var/log/site-maintenance/wikisite/update.err";
                $con = 1;
        $count = 0;
        
        exec($command,$out,$return_code);

        if($return_code > 0){
            $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: run rebuildLocalisationCache exec call" );
            return ErrorMessage::ERROR_FAIL_EXEC_CALL;
        }
    	return 0;
    }

    /**
    * update site
    *
    * @return int
    */
   
    public function update(){
        global $HJLogger, $ProjectName;
        if(self::updateLocalSettings($this->wikiname,$this->id,$this->domainprefix) > 0){
        	$HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: update LocalSetting.php " );
        	return ErrorMessage::ERROR_FAIL_UPDATE_LOCALSETTING;
        }

        if($ret = self::updateSiteByMWScript($this->domainprefix) > 0){
            $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: run mediawiki update.php to maintenance extra dbs for extensions " );
            return ErrorMessage::ERROR_FAIL_EXE_UPDATE_CMD;
        }

        return 0;
   }

   

    
    /**
    * promote a user to admin stage of the wiki
    * @param:$domainprefix : the domain prefix for the new wiki
    * @param:$userid : the user id of the user in glabal table.
    * @return int : 0 success, ERROR_CODE fail;
    */
    public static function promoteUserWikiSiteStageToAdmin($domainprefix, $userid){
        global $HJLogger, $ProjectName;
        $command = "php /var/www/virtual/".$domainprefix."/maintenance/createAndPromoteFromId.php --conf=/var/www/virtual/".$domainprefix."/LocalSettings.php --force --bureaucrat --sysop ".$userid." >/var/log/site-maintenance/wikisite/promote.log 2> /var/log/site-maintenance/wikisite/promote.err" ;
        exec($command,$out,$return_code);
        if($return_code >0){
            $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: run exec");
            return ErrorMessage::ERROR_FAIL_EXEC_CALL;
        }
        return 0;
    }

    
    public function promote(){
        global $HJLogger, $ProjectName;
        if(self::promoteUserWikiSiteStageToAdmin($this->domainprefix,$this->founderid) > 0){
            $HJLogger->error("$ProjectName ". __FILE__ ." ". __LINE__ ." Fail: promote user:".$userid." to the admin stage of wiki:".$domainprefix);
            return ErrorMessage::ERROR_FAIL_PROMOTE_USER_PRIVILEGE;
        }
        return 0;

    }


    /** 
    * recursively copy all files from one folder to another.
    */
    public static function xcopy($source, $dest, $permissions = 0777)
    {
        // Check for symlinks
        if (is_link($source)) {
            return symlink(readlink($source), $dest);
        }
        // Simple copy for a file
        if (is_file($source)) {
            return copy($source, $dest);
        }
        // Make destination directory
        if (!is_dir($dest)) {
            mkdir($dest, $permissions);
        }
        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            // Deep copy directories
            self::xcopy("$source/$entry", "$dest/$entry", $permissions);
        }
        // Clean up
        $dir->close();
        return true;
    }
    /**
    * Make use of javascript to show the progress percentage.
    * @param $total: int
    * @param $current: int
    *
    */
    public function showProgress($current){
    	set_time_limit ( 120 );
        $percent = intval($current/count($this->steps) * 100)."%";
        echo '<script type="text/javascript">document.getElementById("progress").innerHTML="<div class=\"progress-bar\" style=\"width:'.$percent.';\">&nbsp;</div>";
                $("#information").prepend("<h3 id=\"step_'.$current.'\">'.$this->steps[$current].'</h3>");
                $("#step_'.$current.'").textillate("{loop: false, initialDelay: 0, in:{effect: \'flip\', delayScale: 1, delay: 150, reverse: true}}");
            </script>';
        echo str_repeat(' ',1024*64);
        flush();
    }

    public static function generate_send_object($status, $data, $action){
		
	return (object)[
		'status' => $status,
		'message' => $data,
		'action' => $action,
	];
       // return "{\"status\":\"$status\", \"message\":\"$data\", \"action\":\"$action\"}";
    }
  
}   
?>

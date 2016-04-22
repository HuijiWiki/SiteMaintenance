<?php
require_once('Confidential.php');
require_once('Logger.php');
class DBUtility
{
   /** 
   *Version 0.0.1 check in the data base whether the domain user wants to take
   *$name : domain name 
   *$language, $type: ignored atm
   *@return bool
   */
   public static function domainExists($name, $language=null, $type=null){
      global $HJLogger, $ProjectName;	
      $db_name = "huiji";
      $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd,$db_name);
      if($conn->connect_error)
      {
	       $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ." db connect fail:".$conn->error );	
        # die("Connection Failed");
	       return FALSE;
      }
      // statement to execute
      $name = mysqli_real_escape_string($conn, $name);
      $sql = 'SELECT `domain_id` AS `exists` FROM domain WHERE domain_prefix=\''.$name.'\'';
      # echo  $sql;
      // execute the statement
      $query = $conn->query($sql);
      if ($query === false) 
      {
	 $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ." db query fail:".$conn->error );	
 #       die('Query Error');
         $conn->close();
	       return FALSE;
      }
      // extract the value
      $dbExists = ($query->num_rows <= 0) ? false : true;
      $conn->close();
#      echo "value :".$dbExists;
      return $dbExists;
   }

  

   /**
   *This function inserts the newly created domain prefix into the global domain_prefix database table  
   *
   *Database : huiji_domain_all
   *table : domains {domain_prefix : VARCHAR, domain_name : VARCHAR}
   *
   *$domainprefix : the new domain prefix
   *$domainname : the new domain name 
   *
   * @return mixed. FALSE if failed to insert, id if successfully inserted.
   *
   */

   public static function insertGlobalDomainPrefix($domainprefix, $domainname, $domaintype, $domaindsp, $founderid, $foundername){
      //huiji_domain_all is the database to store the huiji_domain_all . 
        global $HJLogger, $ProjectName;
      $db_name = 'huiji';
      $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, $db_name);
      if($conn->connect_error)
      {
        $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ." db connect fail:".$conn->error);
             #die("Connection Failed");
        return FALSE;
      }
      $domainprefix = mysqli_real_escape_string($conn, $domainprefix);
      $domainname = mysqli_real_escape_string($conn, $domainname);
      $domaintype = mysqli_real_escape_string($conn, $domaintype);
      $domaindsp = mysqli_real_escape_string($conn, $domaindsp);
      $foundername = mysqli_real_escape_string($conn, $foundername);
      $founderid = mysqli_real_escape_string($conn, $founderid);
      $date = date( 'Y-m-d H:i:s' );
      $sql = "INSERT INTO domain (domain_prefix, domain_name, domain_type, domain_dsp, domain_status, domain_founder_id, domain_founder_name, domain_date)
        VALUES ('{$domainprefix}', '{$domainname}', '{$domaintype}', '{$domaindsp}', 'TRUE', '{$founderid}', '{$foundername}', '{$date}')";
      if ($conn->query($sql) === TRUE) {
         $last_id = $conn->insert_id;
         $conn->close();
         return $last_id;
      } else {
        $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ." db query fail:".$conn->error );	
         echo "Error: " . $sql . "<br>" . $conn->error;
         $conn->close();
         return FALSE;
      }
      $conn->close();
   }

   /**
   *This function inserts Interwiki Prefix into global interwiki table  
   *
   *Database : huiji.interwiki
   *table : domains {domain_prefix : BLOB, domain_name : BLOB}
   *
   *$domainprefix : the new domain prefix
   *$domainid : the new domain name 
   *
   * @return bool
   *
   */

   public static function insertInterwikiPrefix($domainprefix, $domainid){
      //huiji.interwiki is the database to store the global interwiki links. 
        global $HJLogger, $ProjectName;
      $db_name = 'huiji';
      $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, $db_name);
      if($conn->connect_error)
      {
        $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ." db connect fail:".$conn->error );	
          # die("Connection Failed");
        return FALSE;
      }
      $url = 'http://'.$domainprefix.'.huiji.wiki/wiki/$1';
      $api = 'http://'.$domainprefix.'.huiji.wiki/api.php';

      $domainprefix = mysqli_real_escape_string($conn, $domainprefix);
      $domainid = mysqli_real_escape_string($conn, $domainid);
      $sql = "INSERT INTO interwiki (iw_prefix, iw_url, iw_api, iw_wikiid, iw_local, iw_trans) VALUES ('{$domainprefix}', '{$url}', '{$api}', '{$domainid}', '1', '1')";
     
      if ($conn->query($sql) === TRUE) {
         $conn->close();
         return TRUE;
      } else {
         echo "Error: " . $sql . "<br>" . $conn->error;
         $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ." db query fail:".$conn->error );	
         $conn->close();
         return FALSE;
      }
      $conn->close();
   }
  
   /**drop a DB. 
    * 
    * @param type $name the domain prefix
    * @return Boolean. True if sucessful False if not. 
    */
   public static function dropDB($name, $id = null){
         return self::dropTablesWithPrefix($name, $id); 
      }

      
  /**drop a table with given prefix.
  *
  * @param type $prefix the domain prefix
  * @return Boolean. True if sucessful False if not. 
  */

  public static function dropTablesWithPrefix($prefix, $id = 150){
    global $HJLogger, $ProjectName;
    $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd);
    if($conn->connect_error)
    {
    # die("Connection Failed");
     $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ."db connection fail:".$conn->error );
     return false;
    }
      if ( $id > 131 ){
        $dbPrefix = $prefix.'_';

      }
      else{
        $dbPrefix = $prefix;
      }
      $conn->query("SET group_concat_max_len = 10000");
      $db_name = "huiji_sites";
      $prefix = mysqli_real_escape_string($conn, $prefix);
      $sql = "SELECT CONCAT( 'DROP TABLE ', GROUP_CONCAT(table_name) , ';' ) \n"
    . " AS statement FROM information_schema.tables \n"
    . " WHERE table_schema = '".$db_name."' AND table_name LIKE '".$dbPrefix."%'";
      $result = $conn->query($sql);
      $row = $result->fetch_row();
      $result->close();
      mysqli_select_db($conn, $db_name);
      if( $conn->query($row[0])){
             $conn->close();
	     self::dropRelatedTables($prefix);
             return true;
      }
      self::dropRelatedTables($prefix);
      #echo "Error:" . $conn->error;
      $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ."db query fail:".$conn->error );
      $conn->close();
      return false;

   }
  /**
   * Drop entries in interwiki/domain/user_site_follow tables
   *
   */
  public static function dropRelatedTables($prefix){
        global $HJLogger, $ProjectName;
	$db_name = "huiji";
        $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, $db_name);
            if($conn->connect_error)
            {
	       $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ."db connection fail:".$conn->error );
               #die("Connection Failed");
	       return false;
            }
        $prefix = mysqli_real_escape_string($conn, $prefix);
        $arr = array (array(
                    'tbname' =>'interwiki',
                    'param' => 'iw_prefix',
                    'condition' => $prefix
                ),
                array(
                    'tbname' =>'domain',
                    'param' => 'domain_prefix',
                    'condition' => $prefix
                ),
                array(
                    'tbname' =>'user_site_follow',
                    'param' => 'f_wiki_domain',
                    'condition' => $prefix
                ));
        foreach ($arr as $key => $value) {
            
            $sql = "DELETE from ".$value['tbname']." WHERE '".$value['param']."' = '".$value['condition']. "'";
            $res = $conn->query($sql);
            if( $res ){
                $conn->close();
                return true;
            }else{
                echo 'error:'.$conn->error;
		$HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ."db query fail:".$conn->error );
                $conn->close();
                return false;
            }
            
        }

  }
  
  public static function deletePrefixFromInterWikiTable($prefix){
        global $HJLogger, $ProjectName;
        $db_name = "huiji";
        $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, $db_name);
            if($conn->connect_error)
            {
               $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ."db connection fail:".$conn->error );
               #die("Connection Failed");
               return false;
            }
 	
       $sql = "DELETE from interwiki WHERE iw_prefix ='".$prefix."'";
       $res = $conn->query($sql);
       if( $res ){
             $conn->close();
             return true;
        }else{
              echo 'error:'.$conn->error;
              $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ."db query fail:".$conn->error );
              $conn->close();
              return false;
        }
   }

  
   public static function deletePrefixFromDomainTable($prefix){
        global $HJLogger, $ProjectName;
        $db_name = "huiji";
        $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, $db_name);
            if($conn->connect_error)
            {
               $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ."db connection fail:".$conn->error );
               #die("Connection Failed");
               return false;
            }

       $sql = "DELETE from domain WHERE domain_prefix ='".$prefix."'";
       $res = $conn->query($sql);
       if( $res ){
             $conn->close();
             return true;
        }else{
              echo 'error:'.$conn->error;
              $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ."db query fail:".$conn->error );
              $conn->close();
              return false;
        }
   }

   public static function deleteSiteFromUserSiteFollowTable($prefix){
        global $HJLogger, $ProjectName;
        $db_name = "huiji";
        $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, $db_name);
            if($conn->connect_error)
            {
               $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ."db connection fail:".$conn->error );
               #die("Connection Failed");
               return false;
            }

       $sql = "DELETE from user_site_follow WHERE f_wiki_domain ='".$prefix."'";
       $res = $conn->query($sql);
       if( $res ){
             $conn->close();
             return true;
        }else{
              echo 'error:'.$conn->error;
              $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ."db query fail:".$conn->error );
              $conn->close();
              return false;
        }
   }


   /**
    * check domain name
    * @param   $[domainName] [<domain name>]
    * @return bollean [exist return true, null return false]
    */
   static function checkDomainName( $domainName ){
      global $HJLogger;
      $db_name = "huiji";
      $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, $db_name);
          if($conn->connect_error)
          {
             $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ."db connection fail:".$conn->error );
             #die("Connection Failed");
             return false;
          }

     $sql = "SELECT * from domain WHERE domain_name ='".$domainName."'";
     $res = $conn->query($sql);
     if( $res->num_rows > 0  ){
           $conn->close();
           return true;
      }else if($res->num_rows <=0) {
	   $conn->close();
	   return false;
      }else{
            // echo 'error:'.$conn->error;
            $HJLogger->error("SiteMaintenance ". __FILE__ ." ". __LINE__ ."db query fail:".$conn->error );
            $conn->close();
            return false;
      }
   }

}

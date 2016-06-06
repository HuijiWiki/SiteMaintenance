<?php
class ErrorMessage{
	//domain check error messages
	const ERROR_DOMAIN_NAME_TAKEN                      = 1;
	const ERROR_DOMAIN_BAD_NAME                        = 2;
	const ERROR_DOMAIN_IS_EMPTY                        = 3;
	const ERROR_DOMAIN_TOO_LONG                        = 4;
	const ERROR_DOMAIN_TOO_SHORT	                   = 5;


	//create directory error messages
	const ERROR_FAIL_FOLDER                            = 6;
	const ERROR_FAIL_CREATE_UPLOAD                     = 7;
	const ERROR_FAIL_CREATE_CACHE                      = 8;
	const ERROR_FAIL_LINK_FOLDER                       = 9;

	//install new wiki error messages 
	const ERROR_FAIL_EXE_INSTALL_CMD                   = 10;

	//update database error message 
	const ERROR_DATABASE_SCRIPT_ERROR                  = 11;
        
    //User Not Login 
    const ERROR_NOT_LOG_IN                             = 12;
    
    //Revoke errors
    const ERROR_REMOVE_DIR                             = 13;
    const ERROR_REVOKE_INSTALL                         = 14;
    
    //DB errors;
    //to-do: add more db errors and modify the db class 
    //using pdd. http://www.binpress.com/tutorial/using-php-with-mysql-the-right-way/17
    const ERROR_DB_CONNECT                             = 15;
    const ERROR_DB_QUERY                               = 16;    
    
    //INVITATION CODE ERROR
    const INV_NOT_FOUND                                = 17;
    const INV_USED                                     = 18;
    const ERROR_DOMAIN_INVALID_CHAR                    = 19;
        
    const ERROR_FAIL_DATABASE_INSERT                   = 20;        
    const ERROR_FAIL_DATABASE_DROP                     = 21;         
    const ERROR_FAIL_ENABLE_ES                         = 22;
    const ERROR_FAIL_CURL_CALL                         = 23;
    const ERROR_NO_USER_SESSION                        = 24;
    const ERROR_FAIL_EXE_UPDATE_CMD                    = 25;
    const ERROR_FAIL_COPY_FILE                         = 26;
    const ERROR_FAIL_EXEC_CALL                         = 27;
    const ERROR_FAIL_INSERT_DOMAIN_PREFIX              = 28;
    const ERROR_FAIL_MIGRATE_INITIAL_MANIFEST          = 29;
    const ERROR_FAIL_MIGRATE_FROM_WIKIA                = 30;
    const ERROR_FAIL_UPDATE_LOCALSETTING               = 31;
    const ERROR_FAIL_PROMOTE_USER_PRIVILEGE            = 32;
    const ERROR_FAIL_CHECK_RULE                        = 33;
    const ERROR_FAIL_INSTALL_SITE                      = 34;
    const ERROR_FAIL_UPDATE_SITE                       = 35;
    const ERROR_FAIL_GET_USER_SESSION                  = 36;
    const ERROR_FAIL_CREATE_DIR                        = 37;
    const ERROR_FAIL_CLEAR_DOMAIN_TABLE                = 38;
    const ERROR_FAIL_CLEAR_INTERWIKI_TABLE             = 39;
    const ERROR_FAIL_MIGRATE                           = 40;
    const ERROR_NO_USR_SESSION                         = 41; 
    const ERROR_FAIL_ES_AND_PROMOTE_PRI                = 42;
    const ERROR_FAIL_EXE_REBUID_LOCALISATION_CACHE     = 43;
}

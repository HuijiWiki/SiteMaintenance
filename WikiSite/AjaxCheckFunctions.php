<?php
/**
 * [ajax check input]
 * @return json
 */
require_once('DBUtility.php');
require_once('Invitation.php');
//check domain name
if ( $_POST['action'] == 'checkDomainName' ) {
    $name = $_POST['name'];
    $reg = "/[@\/\\]+/";
    if ( empty($name) ) {
        echo '{"result": "false","message": "名称不能为空"}';
    }elseif ( strlen($name) > 30 ) {
        echo '{"result": "false","message": "名称过长"}';
    }elseif ( DBUtility::checkDomainName( $name ) ) {
        echo '{"result": "false","message": "名称已存在"}';
    }elseif ( preg_match($reg, $name) != 0 ){
        echo '{"result": "false","message": "名称不能含有特殊字符"}';
    }else{
        echo '{"result": "success","message": "success"}';
    }
}
//check prefix url
if ( $_POST['action'] == 'checkPrefixUrl' ) {
    $prefix = $_POST['prefix'];
    $reg = "/^[A-Za-z0-9][A-Za-z0-9-]*$/i";
    if ( empty($prefix) ) {
        echo '{"result": "false","message": "url不能为空"}';
    }elseif ( strlen($prefix) < 3 || strlen($prefix) > 20 ) {
        echo '{"result": "false","message": "url长度与规定不符"}';
    }elseif( preg_match($reg, $prefix) !== 1 ){
        echo '{"result": "false","message": "url不能包含特殊字符"}';
    }elseif ( DBUtility::domainExists( $prefix) ) {
        echo '{"result": "false","message": "url已经存在"}';
    }elseif ( strpos ($prefix, 'fuck') !== false || strpos ($prefix, 'sex') !== false || strpos ($prefix, 'porn') !== false) {
        echo '{"result": "false","message": "url不能包含非法字符"}';
    }else{
        echo '{"result": "success","message": "success"}';
    }
}
//check invite code
if( $_POST['action'] == 'inviteCode' ){
    $inviteCode = $_POST['inviteCode'];
    if ( empty($inviteCode) ) {
        echo '{"result": "false","message": "邀请码不能为空"}';
    }else if ( Invitation::checkInvitation( $inviteCode ) == 0 ) {
        echo '{"result": "success","message": "success"}';
    }else{
        echo '{"result": "false","message": "邀请码错误"}';
    }
}

//check user login
if( $_POST['action'] == 'checkUserLogin' ){
        $session_cookie = 'huiji_session';
        if(!isset($_COOKIE[$session_cookie])){
            echo '{"result": "false","message": "未登录"}';
        }else{
            $ch = curl_init();
            $api_end = 'http://www.huiji.wiki/api.php?action=query&format=xml&meta=userinfo';
            curl_setopt($ch, CURLOPT_URL, $api_end);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_COOKIE, $session_cookie . '=' . $_COOKIE[$session_cookie]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $ret = curl_exec ($ch);
            curl_close ($ch);
        if($ret == false){
            echo '{"result": "false","message": "未登录"}';
        }
        if((preg_match('/id="(\d+)"/',$ret,$id) && $id[1]) && (preg_match('/name="(.*?)"/u',$ret,$name))){
            #pop a simple window for user to wait 
            // $this->founderid = $id[1];
            // $this->foundername = $name[1];
            echo '{"result": "success","message": "{"founderid":"'.$id[1].'","foundername":"'.$name[1].'"}"}';
        }else{
            echo '{"result": "false","message": "未登录"}';
        }
        }
    }

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo '<!DOCTYPE html">
<html lang="zh-cn" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>灰机准备起飞中</title>
    <script src="http://libs.baidu.com/jquery/1.9.0/jquery.min.js"></script>
    <link href="textillate/assets/animate.css" rel="stylesheet">
    <link href="textillate/assets/style.css" rel="stylesheet">
</head>
<body>
<script src="textillate/assets/jquery.fittext.js"></script>
<script src="textillate/assets/jquery.lettering.js"></script>
<script src="textillate/jquery.textillate.js"></script>

<div class="container">
    <!-- Progress bar holder -->
    <div id="progress" class="progress"></div>
    <!-- Progress information -->
    <div id="information"></div>
</div>';

// require_once('WikiSite.php');
require_once('WikiSite2.php');
require_once('ErrorMessage.php');
require_once('Invitation.php');
//require_once('ImportMW.php');

// error_reporting(E_ALL);
// ini_set('display_errors', '1');
header('Content-type: text/html; charset=utf-8');
$domainprefix = $_POST["domainprefix"]; // Starting from 2016.3.17, all prefixes moving forward end with "_".
$domainprefix = strtolower ( $domainprefix ); //domain name should be case insensitive here.
$wikiname = $_POST["wikiname"];
$dsp = $_POST["description"];
$type = $_POST["type"];
$manifest = $_POST["manifest"];
$invcode = $_POST["inv"];


$ruleCheck = WikiSite::checkRule($wikiname, $domainprefix);
if($ruleCheck != 0){
    echo "<p style='text-align:center'>站点名称网址有误或站点已存在</p>"; 
    echo "\n\n\n";
    echo '<a href="http://www.huiji.wiki/wiki/%E5%88%9B%E5%BB%BA%E6%96%B0wiki" style="text-align:center;display:block">返回创建页面</a>';
    die();
}


$invCheck = Invitation::checkInvitation($invcode);
//$invCheck = 0;
if($invCheck == ErrorMessage::INV_NOT_FOUND){
	
    echo "<p style='text-align:center'>无效的激活码</p>"; 
    echo "\n\n\n";
    echo '<a href="http://www.huiji.wiki/wiki/%E5%88%9B%E5%BB%BA%E6%96%B0wiki" style="text-align:center;display:block">返回创建页面</a>';
    die();
}
if($invCheck == ErrorMessage::INV_USED){

    echo "<p style='text-align:center'>激活码已过期</p>";
    echo "\n\n\n";
    echo '<a href="http://www.huiji.wiki/wiki/%E5%88%9B%E5%BB%BA%E6%96%B0wiki" style="text-align:center;display:block">返回创建页面</a>';
    die();    
}


$wiki = new WikiSite2($domainprefix, $wikiname, $type, $dsp, $manifest, null, null);
$ret = $wiki->create(null);
if($ret == ErrorMessage::ERROR_NOT_LOG_IN){
    echo "<p style='text-align:center'>建站终止</p>";
    echo "<p style='text-align:center'>用户未登录</p>";
    echo "\n\n\n";
    echo '<a href="http://www.huiji.wiki/wiki/%E7%89%B9%E6%AE%8A:%E7%94%A8%E6%88%B7%E7%99%BB%E5%BD%95" style="text-align:center;display:block">跳转至登录页面</a>';
    die();
}
elseif ($ret == 0){
//    Invitation::expireInvitation($invcode);
    echo "<p style='text-align:center'>创建站点 $wikiname 成功</p>";
    echo "\n\n\n";
    echo "<a href='http://$domainprefix.huiji.wiki/?action=purge' style='text-align:center;display:block'>访问新站点:$wikiname</a>";
    die();
}
elseif ($ret == ErrorMessage::ERROR_FAIL_PROMOTE_USER_PRIVILEGE){
    Invitation::expireInvitation($invcode);
    echo "<p style='text-align:center'>创建站点 $wikiname 成功, 但出现如下小问题:</p>";
    echo "\n\n\n";
    echo "<p style='text-align:center'>提升创建者站点权限失败，但不影响站点使用，请创建者联系support@huiji.wiki帮助</p>";
    echo "\n\n\n";
    echo "<a href='http://$domainprefix.huiji.wiki/?action=purge' style='text-align:center;display:block'>访问新站点:$wikiname</a>";
    die();
}
elseif ($ret == ErrorMessage::ERROR_FAIL_ENABLE_ES){

    Invitation::expireInvitation($invcode);
    echo "<p style='text-align:center'>创建站点 $wikiname 成功, 但出现如下小问题:</p>";
    echo "\n\n\n";
    echo "<p style='text-align:center'>搜索功能开启失败，但不影响站点使用，请创建者联系support@huiji.wiki帮助</p>";
    echo "\n\n\n";
    echo "<a href='http://$domainprefix.huiji.wiki/?action=purge' style='text-align:center;display:block'>访问新站点:$wikiname</a>";
    die();
}
else{

    echo "<p style='text-align:center'>建站失败</p>";
    echo "<p style='text-align:center'>请重试创建(激活码未失效), 或请创建者联系support@huiji.wiki帮助</p>";
    echo "\n\n\n";
    echo '<a href="http://www.huiji.wiki/wiki/%E5%88%9B%E5%BB%BA%E6%96%B0wiki" style="text-align:center;display:block">返回创建页面</a>';
    die();
}

echo '</body>
</html>';
?>

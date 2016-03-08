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

require_once('WikiSite.php');
require_once('ErrorMessage.php');
require_once('Invitation.php');
//require_once('ImportMW.php');

// error_reporting(E_ALL);
// ini_set('display_errors', '1');
header('Content-type: text/html; charset=utf-8');
$domainprefix = $_POST["domainprefix"];
$domainprefix = strtolower ( $domainprefix ); //domain name should be case in sensitive here.
$wikiname = $_POST["wikiname"];
$dsp = $_POST["description"];
$type = $_POST["type"];
$manifest = $_POST["manifest"];
$invcode = $_POST["inv"];


$invCheck = Invitation::checkInvitation($invcode);
if($invCheck == ErrorMessage::INV_NOT_FOUND){
    die("The invitation code is not valid") ;
}
if($invCheck == ErrorMessage::INV_USED){
    die("The invitation code has expired");
}
$wiki = new WikiSite($domainprefix, $wikiname, $type, $dsp, $manifest);
$ret = $wiki->create();
if($ret == ErrorMessage::ERROR_NOT_LOG_IN){
		echo '<script type="text/javascript">window.location="http://www.huiji.wiki/wiki/%E7%89%B9%E6%AE%8A:%E7%94%A8%E6%88%B7%E7%99%BB%E5%BD%95";</script>';
}
elseif ($ret == 0){
    Invitation::expireInvitation($invcode);
    echo '<script type="text/javascript">window.location="http://'.$domainprefix.'.huiji.wiki";</script>';
}
else{
    echo $ret;
}

echo '</body>
</html>';
?>

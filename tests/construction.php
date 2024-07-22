<?php 
set_time_limit(0);
require_once("../src/facebookSender.php");
require_once("../src/instagramSender.php");
require_once("../src/xSender.php");
require_once("../src/googleSender.php");

require_once("lib.php");

use WAASender\FacebookSender;
use WAASender\InstagramSender;
use WAASender\XSender;
use WAASender\GoogleSender;

	
$raw=[];
$socialResponses=[];

$articleInfo=GetArticleInfo();

$fbSender=new FacebookSender();
$instaSender=new InstagramSender();
$xSender=new XSender();
$googleSender=new GoogleSender();

?>
OK<br><br>
<a href="googleAuthorize.php"><button>Google Authorize</button></a>
<br><br>
<a href="upload.php"><button>Upload</button></a>
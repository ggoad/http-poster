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



$result=$fbSender->Upload($articleInfo);

	$result['allResponses']=$fbSender->GetAllResponses();
	$raw['fb']=$result;
	$socialResponses['fb']=$result['resp'];

$result=$instaSender->Upload($articleInfo);

	$result['allResponses']=$instaSender->GetAllResponses();
	$raw['insta']=$result;
	$socialResponses['insta']=$result['resp'];

$result=$xSender->Upload($articleInfo);

	$result['allResponses']=$xSender->GetAllResponses();
	$raw['x']=$result;
	$socialResponses['x']=$result['resp'];
	
	
$rawJsn=json_encode($raw,JSON_PRETTY_PRINT);
$jsnError=json_last_error_msg();
$socialResponsesJsn=json_encode($socialResponses,JSON_PRETTY_PRINT);

	file_put_contents("uploadRawResults.json", $rawJsn);
	file_put_contents("uploadSocialResults.json", $socialResponsesJsn);
?><!DOCTYPE html>
<html>
<head>
<style>
	button{
		margin:10px;
	}
</style>
</head>
<body>
<div>
	<h2>Social Results</h2>
	<pre><?php echo htmlspecialchars($socialResponsesJsn);?></pre>
</div>

<details>
	<summary>Raw Results</summary>
	<pre><?php echo htmlspecialchars($rawJsn);?></pre>
</details>
<?php echo $jsnError;?>
<br><br>
<div>
	<a href="javascript:location.reload(true)"><button>Again</button></a>
	<a href="publish.php"><button>On to Publish</button></a>
	<a href="edit.php"><button>On to Edit</button></a>
</div>
</body></html>

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

$fbSender=new FacebookSender();
$instaSender=new InstagramSender();
$xSender=new XSender();
$googleSender=new GoogleSender();

$uploadPostResults=json_decode(file_get_contents('output/uploadSocialResults.json'), true);
$editPostResults=json_decode(file_get_contents('output/updateSocialResults.json'), true);


$result=$fbSender->Remove([
	'postId'=>$uploadPostResults['fb']['id'] ?? ''
]);

	$result['allResponses']=$fbSender->GetAllResponses();
	$raw['fb']=$result;
	$socialResponses['fb']=$result['resp'];
	
$result=$xSender->Remove([
	'postId'=>$editPostResults['x']['data']['id'] ?? ''
]);

	$result['allResponses']=$xSender->GetAllResponses();
	$raw['x']=$result;
	$socialResponses['x']=$result['resp'];
	
$result=$googleSender->Remove([
	'postId'=>$uploadPostResults['google']['name'] ?? ''
]);

	$result['allResponses']=$googleSender->GetAllResponses();
	$raw['google']=$result;
	$socialResponses['google']=$result['resp'];


$rawJsn=json_encode($raw,JSON_PRETTY_PRINT);
$socialResponsesJsn=json_encode($socialResponses,JSON_PRETTY_PRINT);


	OutputTestResults("deleteRawResults.json", $rawJsn);
	OutputTestResults("deleteSocialResults.json", $socialResponsesJsn);
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
<br><br>
<div>
	<a href="javascript:location.reload(true)"><button>Again</button></a>
	<a href="upload.php"><button>Back to Upload</button></a>
</div>
</body></html>
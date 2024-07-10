<?php 
set_time_limit(0);
require_once("../src/instagramSender.php");
require_once("lib.php");

use WAASender\FacebookSender;
use WAASender\InstagramSender;

	
$raw=[];
$socialResponses=[];

$instaSender=new InstagramSender();

$uploadResults=json_decode(file_get_contents("uploadSocialResults.json"), true);
	

$result=$instaSender->Publish([
	'postId'=>$uploadResults['insta']['id'] ?? ''
]);

	$result['allResponses']=$instaSender->GetAllResponses();
	$raw['insta']=$result;
	$socialResponses['insta']=$result['resp'];
	
	
$rawJsn=json_encode($raw,JSON_PRETTY_PRINT);
$socialResponsesJsn=json_encode($socialResponses,JSON_PRETTY_PRINT);

	file_put_contents("publisRawResults.json", $rawJsn);
	file_put_contents("publishSocialResults.json", $socialResponsesJsn);
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
	<a href="edit.php"><button>On to Edit</button></a>
</div>
</body></html>

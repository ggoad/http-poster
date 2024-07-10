<?php 

function GetRequestIndex(){
	
	$ind=intval(@file_get_contents("requestIndex.int") ?: 0);
	
	file_put_contents("requestIndex.int", "".($ind+1));
	
	return $ind;
	
}
function GetArticleInfo($edit=false){
	$articleInfo=json_decode(file_get_contents('samplePost.json'),true);
	$articleInfo['content'].=GetRequestIndex();
	if($edit){
		$articleInfo['content']="Here is an edit: ".$articleInfo['content'];
	}
	return $articleInfo;
}
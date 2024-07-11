<?php 
namespace WAASender;
require_once("socialPoster.php");

class GoogleSender extends SocialPoster{
	
	function __construct($credFile=__DIR__.'/conf/google.json'){
		parent::__construct($credFile,[
			'retType'=>'json',
			'authorization'=>'basic'
		],[],[
			'upload'=>"https://mybusiness.googleapis.com/v4/accounts/{#TOKEN-accountId}/locations/{#TOKEN-locationId}/localPosts",
			'update'=>"https://mybusiness.googleapis.com/v4/accounts/{#TOKEN-accountId}/locations/{#TOKEN-locationId}/localPosts/{#0-postId}",
			'delete'=>"https://mybusiness.googleapis.com/v4/accounts/{#TOKEN-accountId}/locations/{#TOKEN-locationId}/localPosts/{#0-postId}"
		]);
	}
	
	protected function _Upload($postData){
		
		return $this->Post(
			$this->Endpoint('upload'),
			$this->GenerateRequestBody($postData, true)
		);

		
	}
	
	protected function _Update($postData){
		
		return $this->ParamArray([
			'updateMask'=>'summary,callToAction,media'
		])->Patch(
			$this->Endpoint('update',$postData['postId']),
			$this->GenerateRequestBody($postData)
		);
		
	}
	protected function _Remove($postData){
		
		return $this->Delete(
			$this->Endpoint('delete',$postData['postId'])
		);
		
	}
	
	protected function GenerateRequestBody($postData, $init=false){
		$body=[
			"summary" => $postData['content'],
			"callToAction" => [
				"actionType" => "LEARN_MORE",
				"url" => $this->Config('site').'/'.$postData['slug'],
			],
			"media" => [
				[
				  "mediaFormat" => "PHOTO",
				  "sourceUrl" => $postData['imgUrl'],
				]
			]
			
		];
		if($init){
			$body["topicType"]="STANDARD";
			
			$body["languageCode"] = "en-US";
			
		}
		return $body;
	}
}
?>
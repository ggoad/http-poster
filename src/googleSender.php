<?php 
namespace WAASender;
require_once("socialPoster.php");

class GoogleSender extends SocialPoster{
	
	function __construct($credFile=__DIR__.'/conf/google.json'){
		parent::__construct($credFile,[
			'retType'=>'json',
			'authorization'=>'basic'
		]);
	}
	
	protected function _Upload($postData){
		$accountId=$this->Token('accountId');
		$locationId=$this->Token('locationId');
		
		
		
		return $this->Post(
			"https://mybusiness.googleapis.com/v4/accounts/$accountId/locations/$locationId/localPosts",
			$this->GenerateRequestBody($postData, true)
		);

		
	}
	
	protected function _Update($postData){
		$postId=$postData['postId'];
		$accountId=$this->Token('accountId');
		$locationId=$this->Token('locationId');
		
		
		
		return $this->Patch(
			"https://mybusiness.googleapis.com/v4/accounts/$accountId/locations/$locationId/localPosts/$postId?updateMask=summary,callToAction,media",
			$this->GenerateRequestBody($postData)
		);
	}
	protected function _Remove($postData){
		$postId=$postData['postId'];
		$accountId=$this->Token('accountId');
		$locationId=$this->Token('locationId');
		
		return $this->Delete(
			"https://mybusiness.googleapis.com/v4/accounts/$accountId/locations/$locationId/localPosts/$postId"
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
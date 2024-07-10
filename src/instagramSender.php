<?php 
namespace WAASender;
require_once("socialPoster.php");

class InstagramSender extends SocialPoster{
	/* instagramCredz.json
		"config":{
			"active": bool
		},
		"tokens":{
			"pageId"        : string : The id to the page you'll be posting to, 
			"systemUserTok" : string : the system user authorized to post on the page
		}
	*/
	function __construct($credFile=__DIR__.'/conf/instagram.json'){
		// add Publish to the methods expecting to return a response object
		array_push($this->retRespArr, "Publish");
		
		parent::__construct($credFile,[
			'retType'=>'json'
		]);
	}
	
	
	protected function _Upload($postData){
		$pId=$this->Token('pageId');
		$body=[
			'image_url'=>$postData['imageUrl'],
			'caption'=>$postData['content']."\n\n".$this->Config('siteBlog'),
			'access_token'=>$this->Token('systemUserToken')
		];
		
		return $this->Post("https://graph.facebook.com/v20.0/$pId/media",$body);
	}
	protected function _Publish($postData){
		$pId=$this->Token('pageId');
		
		$resp=$this->Get("https://graph.facebook.com/v20.0/$postData[postId]/",[
			"fields"=>"status_code",
			"access_token"=>$this->Token("systemUserToken")
		]);
		
		if(!($resp['resp']['status_code'] ?? false)){
			return $this->Eject('No status code');
		}
		
		if($resp['resp']['status_code'] === 'EXPIRED'){
			return $this->Eject("Expired post id");
		}else if($resp['resp']['status_code'] !== "FINISHED"){
			return $this->Eject("Post not finished processing");
		}
		
		return $this->Post("https://graph.facebook.com/v20.0/$pId/media_publish",[
			'creation_id'=>$postData['postId'],
			'access_token'=>$this->Token('systemUserToken')
		]);
	}
	
	protected function _Update($postData){
		// instagram does not have an update endpoint
		return ['success'=>false, 'resp'=>'No Updates from Insta', 'specialMessage'=>'No Updates from Insta'];
	}
	protected function _Remove($postData){
		// instagram does not provide a remove endpoint
		return ['success'=>false, 'resp'=>'No Deletes from Insta', 'specialMessage'=>'No Deletes from Insta'];
	}

}
?>
<?php 
namespace WAASender;
require_once(__DIR__."/socialPoster.php");

class GoogleSender extends SocialPoster{
	
	function __construct($credFile=__DIR__.'/conf/google.json'){
		parent::__construct($credFile,[
			'retType'=>'json',
			'authorization'=>'bearer'
		],[
			
		],[
			'auth'=>'https://oath2.googleapis.com/token',
			'upload'=>"https://mybusiness.googleapis.com/v4/accounts/{#TOKEN-accountId}/locations/{#TOKEN-locationId}/localPosts",
			'update'=>"https://mybusiness.googleapis.com/v4/accounts/{#TOKEN-accountId}/locations/{#TOKEN-locationId}/localPosts/{#0-postId}",
			'delete'=>"https://mybusiness.googleapis.com/v4/accounts/{#TOKEN-accountId}/locations/{#TOKEN-locationId}/localPosts/{#0-postId}"
		],[
			'aud'=>'https://oauth2.googleapis.com/token', 
			"scope"=>"https://www.googleapis.com/auth/business.manage"
		]);
	}
	
	
	protected function GrabOauthToken(){
		if(!is_file(self::googleAuthFile)){
			$this->Eject("No Auth File");
			return false;
		}
		$tokInfo=json_decode(file_get_contents(self::googleAuthFile),true);
		if(time() > $tokInfo['exp']){
			@unlink(self::googleAuthFile);
			$this->Eject('expired token');
			return false;
		}
		$this->Token([
			'bearer'=>$tokInfo['token']
		]);
		return true;
		
	}
	const googleAuthFile=__DIR__.'/conf/googleAuth.json';
	protected function GrabServiceUserToken(){
		$resp=$this->None()->Post($this->Endpoint('auth'),[
			"grant_type"=>"urn:ietf:params:oauth:grant-type:jwt-bearer",
			"assertion"=>$this->JWTStr()
		]);
		
		if($resp['success']){
			$this->Token(['bearer'=>$resp['resp']['access_token']])['bearer'];
			return true;
		}
		$this->Eject('Unable to aquire bearer token');
		return false;
	}
	protected function _GetBearerToken(){
		if($this->Token('bearer')){
			return true;
		}else if($this->Config('authMode') === "oauth2"){
			return $this->GrabOauthToken();
		}else if($this->Config('authMode') === "serviceAccount"){
			return $this->GrabServiceUserToken();
		
		}
		
		$this->Eject("Unsupported authMode: ".$authMode);
		return false;
		
		
	}
	
	protected function _Upload($postData){

		$this->GetBearerToken();
		
		return $this->Post(
			$this->Endpoint('upload'),
			$this->GenerateRequestBody($postData, true)
		);

		
	}
	
	protected function _Update($postData){

		$this->GetBearerToken();

		return $this->ParamArray([
			'updateMask'=>'summary,callToAction,media'
		])->Patch(
			$this->Endpoint('update',$postData['postId']),
			$this->GenerateRequestBody($postData)
		);
		
	}
	protected function _Remove($postData){

		$this->GetBearerToken();
		
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
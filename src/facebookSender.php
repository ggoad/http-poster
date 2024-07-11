<?php
namespace WAASender;

require_once("socialPoster.php");



class FacebookSender extends SocialPoster{
	/* fbCredz.json
		"config":{
			"active": bool
		},
		"tokens":{
			"pageId"        : string : The id to the page you'll be posting to, 
			"systemUserTok" : string : the system user authorized to post on the page
		}
	*/
	function __construct($credFile=__DIR__.'/conf/fb.json'){
		parent::__construct($credFile,[
			'retType'=>'json'
		]);
	}
	protected function _Upload($postData,$callback=null){
		return $this->Post(
			"https://graph.facebook.com/v20.0/{$this->Token('pageId')}/feed", 
			$this->ComposeRequestBody($postData)
		);
		
	}
	protected function _Update($postData,$callback=null){
		$pageId=$this->Token('pageId');
		$requestBody=$this->ComposeRequestBody($postData);
		
		
		if($postData['needsScrape'] ?? false){
			$this->Get("https://graph.facebook.com",[
				'id'=>$requestBody['link'],
				'scrape'=>'true',
				'access_token'=>$this->GetAccessToken()
			]);
		}
		
		
		return $this->Post(
			"https://graph.facebook.com/v20.0/$postData[postId]",
			$requestBody
		);
		
	}
	protected function _Remove($postData,$callback=null){
		$resp=$this->Delete(
			"https://graph.facebook.com/v20.0/$postData[postId]",[
				'access_token'=>$this->GetAccessToken()
			]
		);
		return $resp;
	}
	
	protected function _GetAccessToken(){
		if($this->Token('access_token')){
			return $this->Token('access_token');
		}
		$resp=$this->Get("https://graph.facebook.com/v20.0/me/accounts",[
			'access_token'=>$this->Token('systemUserToken')
		]);
		if(!$resp['success']){
			return $this->Eject('Bad Repsonse');
		}
		if(!$resp['resp']['data']){
			return $this->Eject('Unusual Response');
		}
		foreach($resp['resp']['data'] as $d)
		{
			if($d['id'] === $this->Token('pageId')){
				return $this->Token(['access_token'=>$d['access_token']])['access_token'];
			}
		}
		
		return $this->Eject('Access token not found in the page list for the system user');
		
	}
	protected function ComposeRequestBody($postData){
		$tok=$this->GetAccessToken();
		
		$lnk=$this->Config('siteBlogViewer')."$postData[slug]";
		
		$ret=[
			'access_token'=>$tok,
			'published'=>'true',
			'message'=>''
		];
		
		$ret['message']=$postData['content'];
		
		
		$ret['message'].="\n\nTo read more visit:\n$lnk";
		$ret['link']=$lnk;
		
		return $ret;
	}
	
	
	
	
	
}

?>
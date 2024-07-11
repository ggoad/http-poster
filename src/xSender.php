<?php 
namespace WAASender;
require_once("socialPoster.php");

class XSender extends SocialPoster{
	/* xCredz:
			"config":{
				"active"    - bool
				"startDate" - date
			},
			"tokens":{
				"accessToken"       - string,
				"accessTokenSecret" - string,

				"consumerKey"       - string (x apiKey),
				"consumerSecret"    - string (x apiSecret)
			}
				
		*/
	function __construct($credFile=__DIR__.'/conf/x.json'){
		
		parent::__construct($credFile,[
			'authorization'=>'oauth1',
			'retType'=>'json'
		],[],[
			'imageUpload'=>"https://upload.twitter.com/1.1/media/upload.json",
			'upload'=>"https://api.twitter.com/2/tweets",
			'delete'=>"https://api.twitter.com/2/tweets/{#0-postId}" 
		]);
	}
	protected function _Update($postData){
		$this->Remove($postData);
		return $this->Upload($postData);
	}
	protected function _Upload($postData){
		$postData['xMediaId']=false;
		if($postData['imageAbs']){
			$bodyFiles=[
				'media'=>[
					'imgLoc'=>$this->Config('imageFolder').$postData['imageAbs']
				]
			];
			$resp=$this->MultiPart()->Post(
				$this->Endpoint('imageUpload'),
				[],
				$bodyFiles
			);
			
			if(!($resp['resp']['media_id'] ?? false)){
				return $this->Eject('Media upload fail');
			}
			$postData['xMediaId']=$resp['resp']['media_id'];
		}
		$body=$this->CalculateRequestBody($postData);
		
		return $this->Json()->Post($this->Endpoint('upload'),$body);
		
	}
	protected function _Remove($postData){
		return $this->Delete($this->Endpoint('delete', $postData['postId']));
	}
	protected function CalculateRequestBody($postData){
		$ret= [
			'text'=>$postData['content']
				."\n\nTo read more, Visit: \n\n"
				.$this->Config('siteBlogViewer').$postData['slug'],
		];
		
		if($postData['xMediaId']){
			$ret['media']=['media_ids'=>["$postData[xMediaId]"]];
		}
		
		return $ret;
	}
}

?>
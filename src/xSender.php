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
		]);
	}
	protected function _Update($postData,$callback=null){
		$this->Remove($postData);
		return $this->Upload($postData);
	}
	protected function _Upload($postData,$callback=null){
		$postData['xMediaId']=false;
		if($postData['imageAbs']){
			$bodyFiles=[
				'media'=>[
					'imgLoc'=>$this->Config('imageFolder').$postData['imageAbs']
				]
			];
			$resp=$this->MultiPart()->Post(
				"https://upload.twitter.com/1.1/media/upload.json",
				[],
				$bodyFiles
			);
			
			if(!($resp['resp']['media_id'] ?? false)){
				return(($this->Eject('Media upload fail')));
			}
			$postData['xMediaId']=$resp['resp']['media_id'];
		}
		$body=$this->CalculateRequestBody($postData);
		
		return $this->Json()->Post("https://api.twitter.com/2/tweets",$body);
		
	}
	protected function _Remove($postData,$callback=null){
		return $this->Delete("https://api.twitter.com/2/tweets/$postData[postId]");
	}
	protected function CalculateRequestBody($postData,$callback=null){
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
<?php 
namespace WAASender;
require_once(__DIR__."/socialPoster.php");

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
	
	protected function UploadMediaRef(&$postData, $noEjectOnFail=false){
		
		$resp=$this->UploadMedia($postData);
		if(!($resp['resp']['media_id'] ?? false)){
			if($noEjectOnFail){
				return $resp;
			}
			return $this->Eject('Media upload fail');
		}
		$postData['xMediaId']=$resp['resp']['media_id'];
		return $resp;
	}
	protected function _UploadMedia($postData){
		$bodyFiles=[
			'media'=>[
				'imgLoc'=>$this->Config('imageFolder').$postData['imageAbs']
			]
		];
		return $this->MultiPart()->Post(
			$this->Endpoint('imageUpload'),
			[],
			$bodyFiles
		);
		
	}
	protected function _Upload($postData){
		$postData['xMediaId']=false;
		if($postData['imageAbs'] && !$this->eject){
			$this->UploadMediaRef($postData, true);
			
		}
		$body=$this->CalculateRequestBody($postData);
		
		return $this->Json()->Post($this->Endpoint('upload'),$body);
		
	}
	protected function _Remove($postData){
		return $this->Delete($this->Endpoint('delete', $postData['postId']));
	}
	protected function CalculateRequestBody($postData){
		$app="\n\nTo read more, Visit: \n\n".$this->Config('siteBlogViewer').$postData['slug'];
		$appLen=strlen($app);
		$bod=substr($postData['content'],0,280-$appLen);

		$bod=preg_replace('/[^\.\?]*$/s',"", $bod);
		
		
		
		$ret= [
			'text'=>$bod.$app,
		];
		
		if($postData['xMediaId']){
			$ret['media']=['media_ids'=>["$postData[xMediaId]"]];
		}
		
		return $ret;
	}
}

?>
<?php 
namespace WAASender;

class HttpSender{
	
	public function __construct($config=[], $tokens=[]){
		$this->SetConfig($config);
		$this->SetTokens($tokens);
	}
	
	
	// Sending Methods (the bread and butter 'Go' is last function in class)
	public function Post($url, $body,$bodyFiles=[]){
		return $this->Go("POST",$url,$body,$bodyFiles);
	}
	public function Get($url, $body=[]){
		if($body){
			$arr=[];
			foreach($body as $k=>$v)
			{
				$arr[]=urlencode($k)."=".urlencode($v);
			}
			$body="";
			$url.="?".join('&',$arr);
		}
		return $this->Go("GET",$url);
		
	}
	public function Delete($url, $body=[]){
		return $this->Go("DELETE",$url,$body);
		
	}
	public function Put($url, $body=[],$bodyFiles=[]){
		return $this->Go("PUT",$url,$body,$bodyFiles);
			
	}
	public function Patch($url, $body=[],$bodyFiles=[]){
		return $this->Go("PATCH",$url,$body,$bodyFiles);
			
	}
	
	
	// Content Types
	public function Urlencoded(){
		$this->Config(["contentType"=>"urlencoded"]);
		return $this;
	}
	public function Json(){
		$this->Config(["contentType"=>"json"]);
		return $this;
		
	}
	public function MultiPart(){
		$this->Config(["contentType"=>"multipart"]);
		return $this;
	}
	
	// Response Types
	public function RetText(){
		$this->Config(["retType"=>"text"]);
		return $this;
	}
	public function RetJson(){
		$this->Config(["retType"=>"json"]);
		return $this;
	}


	// Authorizations
	public function None(){
		$this->Config(['authorization'=>"none"]);
		return $this;
	}
	public function Bearer($tok=''){
		$this->TruthyToken([
			'bearer'=>$tok
		]);
		
		$this->Config(['authorization'=>"bearer"]);
		return $this;
	}
	public function Basic($u='',$p=''){
		$this->TruthyToken([
			'user'=>$u,
			'pass'=>$p
		]);
		
		$this->Config(['authorization'=>"basic"]);
		return $this;
	}
	public function Oauth1($consumerKey='', $consumerSecret='', $accessToken='',$accessSecret=''){
		$this->TruthyToken([
			'consumerKey'=>$consumerKey,
			'consumerSecret'=>$consumerSecret,
			'accessToken'=>$accessToken,
			'accessSecret'=>$accessSecret,
		]);
		
		$this->Config(['authorization'=>"oauth1"]);
		return $this;
	}
	
	
		
		// Oauth1 helpers
		protected function Oauth1_sig(&$authArr){
			$allArr=$authArr;
			if($this->Config("contentType") === "urlencoded"){
				$allArr=array_merge($allArr, $this->Config("body"));
			}
			
			ksort($allArr);
			
			$base=strtoupper($this->Config("method"))."&".rawurlencode(strtolower($this->Config("url")))."&";
			$baseArr=[];
			foreach($allArr as $k=>$v)
			{
				$baseArr[]=rawurlencode($k)."=".rawurlencode($v);
			}
			$base.=rawurlencode(join('&',$baseArr));
			
			$key=rawurlencode($this->Token('consumerSecret')) . '&' . rawurlencode($this->Token('accessSecret'));
			
			if($this->Config('signatureMethod') === "HMAC-SHA1"){
				$sig=rawurlencode(base64_encode(hash_hmac('sha1', $base, $key, true)));
			}else{
				throw new Exception("Unsupported signature method: ".$this->Config("signatureMethod"));
			}
			$authArr['oauth_signature']=$sig;
		}
		protected function Oauth1_header(){
			$this->GenerateNonce();
			$arr=[
				'oauth_consumer_key'=>$this->Token("consumerKey"),
				"oauth_nonce"=>$this->Config("nonce"),
				"oauth_signature_method"=>$this->Config("signatureMethod"),
				"oauth_timestamp"=>$this->Config("time"),
				"oauth_token"=>$this->Token("accessToken"),
				"oauth_version"=>"1.0"
			];
			$this->Oauth1_sig($arr);
			$mappedArr=array_map(
				function($k, $v){return "$k=\"$v\"";},
				array_keys($arr),
				array_values($arr)
			);
			return "Authorization: Oauth ".join(',',$mappedArr)."\r\n";
		}
		// nonce helper
		protected function GenerateNonce(){
			$this->Config(['nonce'=>"".bin2hex(random_bytes(16))]);
		}

	// gets the auth header when sending
	protected function AuthHeader(){
		$meth=$this->Config("authorization");
		switch($meth){
			case "oauth1":
				return $this->Oauth1_header();
				break;
			case "bearer":
				return "Authorization: Bearer ".$this->Token("bearer")."\r\n";
				break;
			case "basic":
				return "Authorization: Basic ".base64_encode($this->Token("user").":".$this->Token("pass"))."\r\n";
				break;
			case "none":
				return '';
			default:
				throw new Exception("Unsupported authorization: $meth");
		}
	}

	
	
	//////////////////////////////////////////////////
	// tokens and config
	//////////////////////////////////////////////////
	
	public function Config($oper=null){
		switch(gettype($oper)){
			case "string":
				return $this->conf[$oper];
				break;
			case "array":
				foreach($oper as $k=>$v)
				{
					$this->conf[$k]=$v;
				}
				break;
		}
		return $this->conf;
	}
	
	protected function TruthyToken($arr){
		$this->Token(array_filter($arr, function($a){return $a;}));
	}
	public function Token($oper=null){
		switch(gettype($oper)){
			case "string":
				return $this->tok[$oper] ??0?:'';
				break;
			case "array":
				foreach($oper as $k=>$v)
				{
					$this->tok[$k]=$v;
				}
				break;
		}
		return $this->tok;
		
	}
	
	private $conf=[
		'timeout'=>120,
		'userAgent'=>'genericUA',
		'contentType'=>'urlencoded',
		'authorization'=>'none',
		'signatureMethod'=>'HMAC-SHA1',
		'url'=>'',
		'body'=>[],
		'method'=>'GET',
		'time'=>'',
		'retType'=>'text',
		'defaultConfig'=>[
			'timeout'=>120,
			'userAgent'=>'genericUA',
			'contentType'=>'urlencoded',
			'authorization'=>'none',
			'signatureMethod'=>'HMAC-SHA1',
			'url'=>'',
			'body'=>[],
			'method'=>'GET',
			'time'=>'',
			'retType'=>'text',
			
		]
	];
	private $tok=[
		'bearer'=>'',
		'consumerKey'=>'',
		'consumerSecret'=>'',
		'accessToken'=>'',
		'accessSecret'=>'',
		'user'=>'',
		'pass'=>'',
		'defaultTokens'=>[
			'bearer'=>'',
			'consumerKey'=>'',
			'consumerSecret'=>'',
			'accessToken'=>'',
			'accessSecret'=>'',
			'user'=>'',
			'pass'=>'',
		]
	];
	
	public function SetConfig($arr){
		$arr['defaultConfig']=array_merge($this->Config('defaultConfig'), $arr);
		
		$this->Config($arr); 
	}
	public function SetTokens($arr){
		$arr['defaultTokens']=array_merge($this->Token('defaultTokens'), $arr);
		$this->Token($arr);
	}
	
	public function Reset($responses=false){
		$this->Config($this->Config('defaultConfig'));
		$this->Token($this->Token('defaultTokens'));
		if($responses){
			$this->responseSaves=[];
		}
	}
	
	private $responseSaves=[];
	public function LastResponse(){
		return end($this->responseSaves);
	}
	public function &LastResponseRef(){
		end($this->responseSaves);
		return $this->responseSaves[key($this->responseSaves)];
	}
	public function PushResponse($resp){
		array_push($this->responseSaves, $resp);
	}
	public function GetAllResponses(){
		return $this->responseSaves;
	}
	
	//////////////////////////////////////////////////
	// actual sender
	//////////////////////////////////////////////////
	static $multiBoundary="WAASenderBoundary-----------------------------------------";
	public function Go($method, $url, $body=[], $bodyFiles=[]){
		$this->Config([
			'url'=>$url,
			"body"=>$body,
			"method"=>$method,
			"time"=>time()
		]);
		
		$options=[
			'http'=>[
				'ignore_errors'=>true,
				'method'=>$method,
				'timeout'=>$this->Config("timeout"),
				'header'=>"User-Agent:{$this->Config("userAgent")}\r\n",
			]
		];
		
		if($body || $bodyFiles){
			
			switch($this->Config("contentType")){
				case "urlencoded":
					$contentType="application/x-www-form-urlencoded";
					$content=http_build_query($body);
					break;
				case "json":
					$contentType="application/json";
					$content=json_encode($body);
					break;
				case "multipart":
					$boundary=self::$multiBoundary.microtime(true);
					$contentType="multipart/form-data; boundary=$boundary";
					$content="";
					foreach($body as $k=>$v)
					{
						$content.="--$boundary\r\n"
							."Content-Disposition: form-data; name=\"$k\"\r\n\r\n"
							.$v."\r\n";
					}
					foreach($bodyFiles as $k=>$v)
					{
						if($v['imgLoc']){
							$v['mime']=mime_content_type($v['imgLoc']);
							$v['filename']=basename($v['imgLoc']);
							$v['contents']=file_get_contents($v['imgLoc']);
						}
						
						$content.="--$boundary\r\n"
							."Content-Disposition: form-data; name=\"$k\"; filename=\"$v[filename]\"\r\n"
							."Content-Type: $v[mime]\r\n\r\n"
							.$v['contents']."\r\n";
					}
					$content.="--$boundary--\r\n";
					break;
				default:
					throw new \Exception("Unsupported content type: ".$this->Config("contentType"));
			}
			$options['http']['header'].="Content-Type: $contentType\r\n";
			$options['http']['header'].="Content-Length: ".strlen($content)."\r\n";
			$options['http']['content']=$content;
		}
		$options['http']['header'].=$this->AuthHeader();
		
		$context=stream_context_create($options);
		
		
		$resp=@file_get_contents($this->Config("url"), false, $context);
		
		if($bodyFiles){
			unset ($options['http']['content']);
		}
		$ret=[
			'rawResp'=>$resp,
			'config'=>$this->Config(),
			'options'=>$options
		];
		if($bodyFiles){
			$ret['config']['bodyFiles']=array_map(function($bf){
				unset($bf['contents']);
				return $bf;
			},$bodyFiles);;
			
		}
		
		$statusLine=$http_response_header[0];
			preg_match('{HTTP\/\S*\s(\d{3})}', $statusLine, $match);
			$status = $match[1];
		
		$ret['status']=$status;
		$ret['headers']=$http_response_header;
		$ret['success']=($status === '200');
		$ret['child_responses']=[];
		
		switch($this->Config("retType")){
			case "json":
				$ret['resp']=json_decode($resp,true);
				break;
			default:
				$ret['resp']=$resp;
				break;
			
		}
		
		$this->PushResponse($ret);
		
		$this->Reset();
		return $ret;
	}
	
	
	
	
}


?>
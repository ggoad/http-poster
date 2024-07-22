<?php 
namespace WAASender;

class HttpSender{
	
	public function __construct($conf=[], $tok=[], $endp=[], $jwt=[]){
		$this->SetConfig($conf);
		$this->SetTokens($tok);
		$this->SetEndpoints($endp);
		$this->SetJWTClaims($jwt);
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
	
	// Get Parameteres
	public function ParamArray($arr){
		$this->Config(['paramArray'=>$arr]);
		return $this;
	}
	public function ParamAppend($str){
		$this->Config(['paramAppend'=>$str]);
		return $this;
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
		
		protected function JWTHeaderOb(){
			$ret=[
				'alg'=>$this->Config('jwtSigAlgo'),
				'typ'=>$this->Config('jwtTyp')
			];
			$tok=$this->Token('jwtKeyId');
			if($tok){
				$ret['kid']=$tok;
			}
			return $this->base64url_encode(
				json_encode([
					$ret
				])
			);
		}
		protected function JWTClaimOb($perOb=[]){
			$t=time();
			return $this->base64url_encode(
				json_encode(
					array_filter(
						array_merge(
							$this->Config('jwtClaims'),
							[
								'iat'=>$t,
								'exp'=>$t+$this->Config('jwtValidLength')
							],
							$perOb
						), 
						function($m){return !is_null($m);}
					)
				)
			);
		}
		protected function JWTSignature($prep){
			switch($this->Config('jwtSigAlgo')){
				case "RS256":
					openssl_sign($prep, $signature, $this->Token('jwtPrivateKey'),OPENSSL_ALGO_SHA256);
					break;
			}
			return $this->base64url_encode($signature);
		}
		protected function JWTStr($perOb=[]){
			$header=$this->JWTHeaderOb();
			$claim=$this->JWTClaimOb($perOb);
			
			$prep="$header.$claim";
			
			$signature=$this->JWTSignature($prep);
			return "$prep.$signature";
			
		}
		protected function base64url_encode($str){
			return rtrim(strtr(base64_encode($str),'+/','-_'),'=');
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
	
	private function EndpointInterpolation($endpoint, $args){
		$tok=$this->Token();
		$conf=$this->Config();
			
		$sArr=[];
		$rArr=[];
		foreach($tok as $k=>$v)
		{
			if(!is_string($v)){
				continue;
			}
			$sArr[]="{#TOKEN-$k}";
			$rArr[]=$v;
		}
		foreach($conf as $k=>$v)
		{
			if(!is_string($v)){
				continue;
			}
			$sArr[]="{#CONFIG-$k}";
			$rArr[]=$v;
		}
		
		$psArr=[];
		$prArr=[];
		
		foreach($args as $k=>$v)
		{
			if(filter_var($k, FILTER_VALIDATE_INT) !== false){
				$psArr[]="/{#$k-[A-Za-z0-9\-_]*}/";
			}else{
				$psArr[]="/{#[0-9]+-$k}/";
			}
			$prArr[]=$v;
		}
		
		
		return preg_replace($psArr,$prArr,str_replace($sArr, $rArr,$endpoint));
	}
	public function Endpoint($oper=null, ...$args){
		switch(gettype($oper)){
			case "string":
				if($oper === 'defaultEndpoints'){return $this->endp[$oper];}
				return $this->EndpointInterpolation(
					$this->endp[$oper],
					(gettype($args[0] ?? '') === 'array' ? $args[0] : $args)
				);
				break;
			case "array":
				foreach($oper as $k=>$v)
				{
					$this->endp[$k]=$v;
				}
				break;
		}
		return $this->endp;
		
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
	public function JWTClaim($oper){
		switch(gettype($oper)){
			case "string":
				return $this->jwtClm[$oper] ?? null;
				break;
			case "array":
				foreach($oper as $k=>$v)
				{
					$this->jwtClm[$k]=$v;
				}
				break;
		}
		return $this->jwtClm;
	}
	
	private $conf=[
		'timeout'=>120,
		'userAgent'=>'genericUA',
		'contentType'=>'urlencoded',
		'authorization'=>'none',
		'signatureMethod'=>'HMAC-SHA1',
		'jwtSigAlgo'=>'RS256',
		'jwtTyp'=>'JWT',
		'jwtValidLength'=>3600000,
		'url'=>'',
		'body'=>[],
		'method'=>'GET',
		'time'=>'',
		'retType'=>'text',
		'paramAppend'=>'',
		'paramArray'=>[],
		'defaultConfig'=>[
			'timeout'=>120,
			'userAgent'=>'genericUA',
			'contentType'=>'urlencoded',
			'authorization'=>'none',
			'signatureMethod'=>'HMAC-SHA1',
			'jwtSigAlgo'=>'RS256',
			'jwtTyp'=>'JWT',
			'jwtValidLength'=>3600000,
			'url'=>'',
			'body'=>[],
			'method'=>'GET',
			'time'=>'',
			'retType'=>'text',
			'paramAppend'=>'',
			'paramArray'=>[],

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
		'jwtKeyId'=>'',
		'jwtPrivateKey'=>'',
		'defaultTokens'=>[
			'bearer'=>'',
			'consumerKey'=>'',
			'consumerSecret'=>'',
			'accessToken'=>'',
			'accessSecret'=>'',
			'user'=>'',
			'pass'=>'',
			'jwtKeyId'=>'',
			'jwtPrivateKey'=>'',
		]
	];
	private $endp=[
		'defaultEndpoints'=>[]
	];
	private $jwtClm=[
		'iss'=>null,	//	issuer
		'sub'=>null,	//	subject 
		'aud'=>null,	//	audienc
		'exp'=>null,	//	expirationTime
		'nbf'=>null,	//	not before time
		'iat'=>null,	//	issued at time
		'jti'=>null		//	unique identifier
		'defaultClaims'=>[
			'iss'=>null,	//	issuer
			'sub'=>null,	//	subject 
			'aud'=>null,	//	audienc
			'exp'=>null,	//	expirationTime
			'nbf'=>null,	//	not before time
			'iat'=>null,	//	issued at time
			'jti'=>null		//	unique identifier
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
	public function SetEndpoints($arr){
		$arr['defaultEndpoints']=array_merge($this->Endpoint('defaultEndpoints'), $arr);
		$this->Endpoint($arr);
	}
	public function SetJWTClaims($arr){
		$arr['defaultClaims']=array_merge($this->JWTClaim('defaultClaims');
		$this->JWTClaim($arr);
	}
	
	public function Reset($responses=false){
		$this->Config($this->Config('defaultConfig'));
		$this->Token($this->Token('defaultTokens'));
		$this->Endpoint($this->Endpoint('defaultEndpoints'));
		$this->JWTClaim($this->JWTClaim('defaultClaims'));
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
	
	protected function ParamParser(&$url, $paramAppend,$paramArray){
		if($paramAppend){
			if(!preg_match('/\?/',$url)){
				$url.="?";
			}
			$url.=$paramAppend;
		}
		if($paramArray){
			if(!preg_match('/\?/',$url)){
				$url.="?";
			}
			$arr=[];
			foreach($paramArray as $k=>$v)
			{
				$arr[]=urlencode($k).'='.urlencode($v);
			}
			if(!preg_match('/\?$/',$url)){
				$url.="&";
			}
			$url.=join('&',$arr);
		}
	}
	
	public $responseCallback;
	static $multiBoundary="WAASenderBoundary-----------------------------------------";
	public function Go($method, $url, $body=[], $bodyFiles=[]){
		
		$this->ParamParser($url, $this->Config('paramAppend'), $this->Config('paramArray'));
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
		if($this->responseCallback){
			call_user_func($this->responseCallback,$this, $method, $url, $body, $bodyFiles,$ret);
		}
		
		$this->Reset();
		
		return $ret;
	}
	
	
	
	
}



?>

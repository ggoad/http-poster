<?php 

namespace WAASender;

require_once(__DIR__."/httpSender.php");
class SenderBlocker{
	
	function __construct($conf=[], $tok=[],$endp=[]){
		$this->nullClass=new NullClass($this);
		
		$this->sender=new HttpSender($conf,$tok,$endp);
	}
	
	use Phased;
	
	protected $nullClass;
	protected $sender;
	protected $eject=false;
		
	protected function Eject($msg, $retOb=false){
		$this->eject=true;
		$ret=$this->sender->LastResponseRef();
		$ret['specialMessage']=$msg;
		return $ret;
	}


	// these are the methods that return responses
		protected $retRespArr=["Go","Post","Get","Patch","Delete","Put","Delete"];
	
	function __call($method, $args){
		
		// return a response if necesary, or the null class
		if(!$this->sender->Config('active') || $this->eject){
			if(array_search($method, $this->retRespArr) > -1){
				return $this->sender->LastResponse() ?: ['success'=>false, 'resp'=>null,'specialMessage'=>'No Responses'];
			}return $this->nullClass;
		}
		
		// checks for underscore methods first
		if(method_exists($this, "_$method")){
			this->Phase($method);
			return call_user_func_array([$this, "_$method"],$args);
		}
		
		// checks for sender methods
		if(method_exists($this->sender, $method)){
			return call_user_func_array([$this->sender, $method], $args);
		}
		
		// oops
		throw new Exception("Bad Method $method");
		
	}
	
		
	function Reset(){
		$this->eject=false;
		$this->sender->Reset();
	}
}

// this is here for the Eject functionality. 
// 	To return an object if necessary, 
//	or a failed response when expecting a response

class NullClass{
	private $par;
	function __construct(&$par){
		$this->par=&$par;
	}
	function __get($str){
		return $this;
	}
	function __call($methods, $args){
		return call_user_func_array([$this->par, $method],$args);
	}
}
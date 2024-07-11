<?php 
namespace WAASender;

require_once("senderBlocker.php");


abstract class SocialPoster extends SenderBlocker{
	/* blogConfig.json
		"site"           - string - site root,
		"siteBlog"       - string - blog lister,
		"siteBlogViewer" - string - blog article viewer,	
		"imageFolder"    - string - file-system path to blog image parent folder
	*/
	function __construct($confFile, $conf=[], $tok=[]){
		// add these to the list of methods expecting a response
		array_push($this->retRespArr, "Update","Upload","Remove");
		
		
		// parse configurations
		$sConf=json_decode(file_get_contents($confFile),true);
		$blogConfig=json_decode(file_get_contents(self::$blogConfig),true);
		
		$conf=array_merge($blogConfig, $sConf['config'],$conf);
		$tok=array_merge($sConf['tokens'], $tok);
		
		parent::__construct($conf, $tok);
	}
		
		static $blogConfig=__DIR__."/conf/blog.json";
		
	/* postData is an associative array
		content  : body of post 
		slug     : article slug (will be appended to blogConfig.siteBlogViewer
		imageUrl : url to image
		imageAbs : path to local image (for sharing images, appended to blogConfig.imageFolder)
		postId   : id of post (for Update and Remove) 
		
	*/
	abstract protected function _Upload($postData,$callback=null);
	abstract protected function _Remove($postData,$callback=null);
	abstract protected function _Update($postData,$callback=null);

	
}



?>
<?php 
require_once(__DIR__.'/../src/googleSender.php');
$googlie=new WAASender\GoogleSender();


?><!DOCTYPE html>
<html>
<head></head>
<body>
<script>
var _el={
	CREATE:function(tp, id, className, otherMemOb, append){
		var ret=document.createElement(tp);
		if(id){
			ret.id=id;
		}
		if(className){
			ret.className=className;
		}
		if(otherMemOb){
			for(var mem in otherMemOb)
			{
				if(mem === "style"){
					for(var s in otherMemOb[mem])
					{
						ret.style[s]=otherMemOb[mem][s];
					}
				}else if(mem === 'attributes'){
					for(var a in otherMemOb[mem]){ret.setAttribute(a, otherMemOb[mem][a]);}
				}else{
					ret[mem]=otherMemOb[mem];
				}
			}
		}
		if(append){
		   this.APPEND(ret, append);
		}
		return ret;
	},
	APPEND:function(p,c){
		if(Array.isArray(c)){
			for(var i=0; i<c.length; i++)
			{
				p.appendChild(this.PARSE_element(c[i]));
			}
		}else{
			p.appendChild(this.PARSE_element(c));
		}
		return p;
	},
	PARSE_element:function(a){
       if(typeof a === "string"){return this.TEXT(a);}
       return a;
    },
};
function GoogleAuthRedirect(){
	var frm;
	_el.APPEND(document.body, frm= _el.CREATE('form','','',{
		action:'https://accounts.google.com/o/oauth2/v2/auth',
		method:'GET'
	},[
		_el.CREATE('input','','',{type:'hidden', name:'client_id',value:'<?php echo $googlie->Token('oauthClientId');?>'}),
		_el.CREATE('input','','',{type:'hidden', name:'redirect_uri',value:'http://localhost/ggoadGit/http-poster/tests/googleAuthorizeHandler.php'}),
		_el.CREATE('input','','',{type:'hidden', name:'response_type',value:'token'}),
		_el.CREATE('input','','',{type:'hidden', name:'scope',value:'<?php echo $googlie->JWTClaim('scope');?>'}),
		_el.CREATE('input','','',{type:'hidden', name:'state',value:'Test'}),
		_el.CREATE('input','','',{type:'hidden', name:'include_granted_scopes',value:'true'}),
		//_el.CREATE('input','','',{type:'hidden', name:'access_type',value:''}),
		//_el.CREATE('input','','',{type:'hidden', name:'enable_granular_consent',value:''}),
		//_el.CREATE('input','','',{type:'hidden', name:'login_hint',value:''}),
		//_el.CREATE('input','','',{type:'hidden', name:'prompt',value:''})
	]));
	frm.submit();
}
</script>
<h1>Google Authroize</h1>
<br>
<button onclick="GoogleAuthRedirect()">Authorize</button><br><br>
<a href="upload.php"><button>On to Upload</button></a>
</body>
</html>
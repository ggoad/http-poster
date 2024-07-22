<?php 

if($_SERVER['QUERY_STRING'] ?? false){
	$_GET['exp']=time()+intval($_GET['expires_in'])*1000;
	$jsn=json_encode($_GET);
	file_put_contents(__DIR__."/../src/conf/googleAuth.json", $jsn);
	?>
	<h1>Success</h1><br>
	<a href="upload.php"><button>On to Upload</button></a>
	<?php
	die();
}

?>
<script>
	if(location.href.match(/#.+/)){
		location.href=location.href.replace('#','?')+"&rearranged=true";
	}else{
		alert("Failed");
	}
	
	
	
</script>

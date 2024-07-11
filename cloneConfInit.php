<?php 
$baseDir="";
$dir=$baseDir."src/sampleConf";
$tdir=$baseDir."src/conf";
@mkdir($tdir);
$sd=scandir($dir);
$sd=array_filter($sd, function($s){
	return preg_match('/\.json$/',$s);
});
foreach($sd as $s)
{
	copy("$dir/$s", "$tdir/$s");
}
echo "Copy Complete";

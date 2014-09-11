<?php
/*
 *@description   文件上传方法
 *@author widuu  http://www.widuu.com
 *@mktime 08/01/2014
 */
	header("Content-type:text/html;charset=utf-8");
	require("conf.php");
	
	$accessKey = $QINIU_ACCESS_KEY;
	$secretKey = $QINIU_SECRET_KEY;
	
	$bucket = $BUCKET;

	$host  = $HOST;
	
	$time = time()+3600;
	
	if(empty($_GET["key"])){
		exit('param error');
	}else{
		$data =  array(
				"scope"=>$bucket.":".$_GET['key'],
				"deadline"=>$time,
				"returnBody"=>"{\"url\":\"{$host}$(key)\", \"state\": \"SUCCESS\", \"name\": $(fname),\"size\": \"$(fsize)\",\"w\": \"$(imageInfo.width)\",\"h\": \"$(imageInfo.height)\"}"
			);
	}

	$data = json_encode($data);
	$find = array('+', '/');
	$replace = array('-', '_');
	$data = str_replace($find, $replace, base64_encode($data));
	$sign = hash_hmac('sha1', $data, $secretKey, true);
	$result = $accessKey . ':' . str_replace($find, $replace, base64_encode($sign)).':'.$data ;
	echo $result;
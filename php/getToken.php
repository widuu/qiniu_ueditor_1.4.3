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
	
	$time = time()+$TIMEOUT;
	
	if(empty($_GET["key"])){
		exit('param error');
	}else{
		
		if($USEWATER && empty($_GET['type'])){
			$waterBase = urlsafe_base64_encode($WATERIMAGEURL);
			$returnBody = "{\"url\":\"{$host}/$(key)?watermark/1/image/{$waterBase}/dissolve/{$DISSOLVE}/gravity/{$GRAVITY}/dx/{$DX}/dy/{$DY}\", \"state\": \"SUCCESS\", \"name\": $(fname),\"size\": \"$(fsize)\",\"w\": \"$(imageInfo.width)\",\"h\": \"$(imageInfo.height)\"}";
		}else{
			$returnBody = "{\"url\":\"{$host}/$(key)\", \"state\": \"SUCCESS\", \"name\": $(fname),\"size\": \"$(fsize)\",\"w\": \"$(imageInfo.width)\",\"h\": \"$(imageInfo.height)\"}";
		}

		$data =  array(
				"scope"=>$bucket.":".$_GET['key'],
				"deadline"=>$time,
				"returnBody"=> $returnBody
			);
	}

	$data = json_encode($data);
	$dataSafe = urlsafe_base64_encode($data);
	$sign = hash_hmac('sha1',$dataSafe, $secretKey, true);
	$result = $accessKey . ':' . urlsafe_base64_encode($sign).':'.$dataSafe ;
	echo $result;

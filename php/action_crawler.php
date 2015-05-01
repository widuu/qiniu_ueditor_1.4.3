<?php
/**
 * 抓取远程图片
 * User: rainemnt93
 * Date: 2015-05-01
 * Time: 下午16:18
 */
set_time_limit(0);

include "Qiniu_upload.php";
include "conf.php";
/* 上传配置 */
$fieldName = $CONFIG['catcherFieldName'];


/* 生成上传实例对象并完成上传 */
$config = array(
    'secrectKey'     => $QINIU_SECRET_KEY, 
    'accessKey'      => $QINIU_ACCESS_KEY, 
    'domain'         => $HOST, 
    'bucket'         => $BUCKET, 
    'timeout'        => $TIMEOUT, 
);

$qiniu = new Qiniu($config);
$list = array();

if (isset($_POST[$fieldName])) {
    $source = $_POST[$fieldName];
} else {
    $source = $_GET[$fieldName];
}

$context = stream_context_create(
    array('http' => array(
        'follow_location' => false // don't follow redirects
    ))
);

foreach ($source as $imgUrl) {
    //命名规则
    $key = time() . rand(0,10) .'.png';  
    ob_start();
    readfile($imgUrl, false, $context);
    $img = ob_get_contents();
    ob_end_clean();

    $upfile = array(
        'name'=>'file',
        'fileName'=>$key,
        'fileBody'=>$img
    );
    
    $result = $qiniu->upload(array(), $upfile);

    if(!empty($result['hash'])){
        //加水印判断
        if($USEWATER){
            $waterBase = urlsafe_base64_encode($WATERIMAGEURL);
            $url  =  $qiniu->downlink($result['key'])."?watermark/1/image/{$waterBase}/dissolve/{$DISSOLVE}/gravity/{$GRAVITY}/dx/{$DX}/dy/{$DY}";
        }else{
            $url  =  $qiniu->downlink($result['key']);
        }
        /*构建返回数据格式*/
        $FileInfo = array(
              "url"   => $url,         
              "title" => $result['key'],
              "state" => 'SUCCESS',            
              "source" => htmlspecialchars($imgUrl)     
        );
        array_push($list, $FileInfo);
    }

    unset($img);
    unset($result);
}

/* 返回数据 */
return json_encode(array(
    'state'=> count($list) ? 'SUCCESS':'ERROR',
    'list'=> $list
));

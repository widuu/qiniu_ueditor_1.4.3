<?php

/*
 * +----------------------------------------------------------------------
 * | 七牛列出文件
 * +----------------------------------------------------------------------
 * | Author: widuu <admin@widuu.com>
 * +----------------------------------------------------------------------
 * | Time  : 09/11/2014
 * +----------------------------------------------------------------------
 *
**/

require('conf.php');
define('AKEY', $QINIU_ACCESS_KEY);
define('SKEY', $QINIU_SECRET_KEY);
define('BUCKET',$BUCKET);

class Qiniu_List{
	
	private $QINIU_RSF_HOST = 'http://rsf.qbox.me';
	private $header = array();
	private $url = '';
	private static $instance;

	private  function __construct(){}

	//单态模式实例化
	public static function getInstance() {
			
		if(!isset(self::$instance)){
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	/**
	  * @param prefix 前缀
	  * @param marker 标记
	  * @param limit  限制出现的个数
	  *
	 **/

	public  function getUrl($prefix='', $marker='', $limit = 0){
		
		$query = @array('bucket' => BUCKET);		
		
		if (!empty($prefix)) {
			$query['prefix'] = $prefix;
		}
		if (!empty($marker)) {
			$query['marker'] = $marker;
		}
		if (!empty($limit)) {
			$query['limit'] = $limit;
		}
		$url = '/list?' . http_build_query($query);
	    $this->url = $url;
	}

	
	//获取token
	private function getToken($url){
		$find = array('+', '/');
		$replace = array('-', '_');
		$sign = hash_hmac('sha1', $this->url."\n", SKEY, true);
		$result = AKEY . ':' . str_replace($find, $replace, base64_encode($sign));
		return $result; 
	}

	/**
	  * @Description 列出文件
	  * @return array(
	  *					['marker'] => 标记，
	  *					['item']   => 文件列表数组
	  *				)
	 **/

	public function listFiles(){
    	 
        $_post_url = trim($this->QINIU_RSF_HOST.$this->url,'\n'); 
        $curl = curl_init ();
        curl_setopt($curl, CURLOPT_URL, $_post_url);
        $this->header[] = 'Host: rsf.qbox.me';
		$this->header[] = 'Content-Type:application/x-www-form-urlencoded';
		$this->header[] = 'Authorization: QBox '.$this->getToken($this->url);   
        curl_setopt ( $curl, CURLOPT_HEADER, false);
		curl_setopt ( $curl, CURLOPT_HTTPHEADER, $this->header );
        curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, false); 
        curl_setopt ( $curl, CURLOPT_POST, true);
        curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, true);  
        curl_setopt ( $curl, CURLOPT_POSTFIELDS, "");   
        $result = curl_exec ( $curl );  
        curl_close ( $curl );  
     	return json_decode($result,true);  
	}

}

//演示方法
// $Qiniu_List = Qiniu_List::getInstance();
// $Qiniu_List -> getUrl('','',20);
// echo "<pre>";
// print_r($Qiniu_List -> listFiles());
// echo "</pre>";
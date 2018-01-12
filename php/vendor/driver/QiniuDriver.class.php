<?php

/**
 * 七牛处理驱动
 * 
 * @author   widuu <admin@widuu.com>
 * @document https://github.com/widuu/qiniu_ueditor_1.4.3
 */

class QiniuDriver{

	private  $qiniu_rsf_host = 'http://rsf.qbox.me';
	private  $qiniu_rs_host  = 'http://rs.qbox.me';
	private  $qiniu_up_host  = 'http://up.qiniu.com';
	private  $qiniu_io_host  = 'http://iovip.qbox.me';
	private  $status_code 	= '';


	public function __construct($config){
		foreach ($config as $k => $v) {
			$this->$k = $v;
		}
	}

	/**
	 * 用于直传的时候获取token的方法
	 * 
	 * @author   widuu <admin@widuu.com>
	 */


	public function getToken(){
		$token = $this->getUploadToken($_POST['key']);
		return array(
			'state' => 'SUCCESS',
			'token' => $token
		);
	}


	/**
	 * 七牛删除文件方法
	 * 
	 * @author   widuu <admin@widuu.com>
	 */

	public function removeFile($file){
		$file  = trim($this->escapeQuotes($file));
		$scope = $this->SafeBase64Encode("{$this->bucket}:{$file}");
		$url   = $this->qiniu_rs_host . "/delete/" . $scope;
		$token = $this->getSign("/delete/".$scope."\n");
		$response = $this->request($url, 'POST', array('Authorization'=>"QBox $token"));
		if( isset($response['error']) ){
			return array( 'code'=> 1 ,'error' => $response['error'] );
		}
		return array( 'code'=> 0 );
	}

	/**
	 * 七牛远程获取文件
	 * @param    string $image_url  抓取的url
	 * @param    array  $ue_config  ueditor 配置信息
	 * @return   array
	 * @author   widuu <admin@widuu.com>
	 */

	public function fetchFile( $image_url,$ue_config ){
		// 要抓取的URL
		$image_url   = trim($image_url);
		// 解析Url中的Path然后根据Path获取文件名称
		$file_path   = parse_url($image_url,PHP_URL_PATH);
		// 获取存储文件名的field_name
		$field_name  = pathinfo($file_path,PATHINFO_FILENAME).'.'.pathinfo($file_path,PATHINFO_EXTENSION);
		// 文件名称
		$file_name   = $this->getFileName($field_name,$ue_config,true); 
		// 七牛抓取存储文件的 EncodedEntryURI
		$encoded_entry_uri = $this->SafeBase64Encode("{$this->bucket}:{$file_name}");
		// 七牛抓取存储文件的 EncodedURL
		$encoded_uri = $this->SafeBase64Encode($image_url);
		// 七牛POST的PATH地址
		$post_path   = "/fetch/". $encoded_uri ."/to/". $encoded_entry_uri;
		// POST URL
		$url   = $this->qiniu_io_host . $post_path;
		// 抓取Token
		$token = $this->getSign($post_path."\n");
		// 返回数据
		$response = $this->request($url, 'POST', array('Authorization'=>"QBox $token"));
		// 如果有错误信息
		if( !empty($result['error']) ){
			return array(
				'state' => $result['error']
			);
		}
		// 拼接上传到七牛的地址
		$url = trim($this->host , "/" ). "/" . trim($response['key'], "/" );
		// 返回抓取结果
		return array(
          "state" 	 => "SUCCESS",         
          "url"   	 => $url,                       
          "size" 	 => $response['fsize'],           
        );
	}
	
	/**
	 * 七牛合成文件的方法
	 * @param    array  合成文件信息
	 * @return   array
	 * @author   widuu <admin@widuu.com>
	 */

	public function Synthesis( $params = array(),$ue_config ){
		$ctx_list   = rtrim($_POST['ctx'],",");
		$file_type  = trim($_POST['type']);
		$file_size  = trim($_POST['size']);
		$field_name = trim($_POST['name']);
		$upload_url = trim($_POST['host']);

		$file_name  = $this->getFileName($field_name,$ue_config,true);

		$path = '/mkfile/'.intval($file_size).'/key/'.$this->SafeBase64Encode($file_name);
		$path .= '/mimeType/'.$this->SafeBase64Encode($file_type);

		$headers = array(
			'Content-Type'   => "text/plain",
			'Authorization'  => "UpToken ".$this->getUploadToken($file_name),
			);

		$response = $this->request($upload_url.$path, 'POST', $headers, $ctx_list);
	
		return array(
          "state" 	 => $response['state'],         
          "url"   	 => $response['url'],  
          "type"  	 => $_POST['type'],                     
          "size"  	 => $response['size'],   
          "original" => $_POST['name']       
        );
		
	}

	/**
	 * 设置上传信息
	 *
	 * @param 	array  $config ueditor    php配置信息
	 * @param 	array  $ue_config ueditor json配置信息
	 * @return 	array  
	 * @author  widuu <admin@widuu.com>
	 */

	public function getFileInfo( $config ,$ue_config ){
		$file_info  = $this->setUploadInfo($config, $ue_config );
		$result     =  $this->uploadFile($file_info);
		// 如果有错误信息
		if( !empty($result['error']) ){
			return array(
				'state' => $result['error']
			);
		}
		// 返回上传信息
		$field_name = $config['fieldName'];
		return array(
          "state" 	 => "SUCCESS",         
          "url"   	 => $result['url'],           
          "title" 	 => $result['name'],         
          "original" => $_FILES[$field_name]['name'],       
          "type" 	 => $_FILES[$field_name]['type'],            
          "size" 	 => $_FILES[$field_name]['size'],           
        );
	}

	/**
	 * 七牛列出文件列表
	 *
	 * @param 	prefix 前缀
	 * @param 	marker 标记
	 * @param 	limit  限制出现的个数
	 * @author  widuu <admin@widuu.com>
	 */

	public function getList( $prefix, $marker, $limit){
		$request_info = $this->getListRequestInfo( $prefix, $marker, $limit );
		$request_url   = $request_info['path'];
		$request_token = $request_info['token'];

		$response 	   = $this->request($request_url, 'POST', array('Authorization'=>"QBox {$request_token}"));
		return $response;
	}

	/**
	 * 设置上传信息
	 *
	 * @param 	array  $config ueditor    php配置信息
	 * @param 	array  $ue_config ueditor json配置信息
	 * @return 	array  
	 * @author  widuu <admin@widuu.com>
	 */

	private function setUploadInfo( $config, $ue_config ){
		$field_name  = $config['fieldName'];
		$file_name   = $this->getFileName($field_name,$ue_config); 
		$upload_info = array(
	        "name"		=> 'file',
	        'file_name'	=> $file_name,
	        'file_body' => file_get_contents($_FILES[$field_name]['tmp_name'])
	    );

		if( $config['base64'] == "base64" ){
		    $upload_info['file_name'] = $file_name.'png';
		    $upload_info['file_body'] = base64_decode( $_POST[$field_name] );
		}

		return $upload_info;
		
	}

	/**
	 * 获取文件名称
	 *
	 * @param   string $field_name      文件的filedname或者远程url地址
	 * @param 	array  $ue_config 		ueditor json配置信息
	 * @param 	array  $flag    	    false 上传文件|true 远程抓取
	 * @return 	string  
	 * @author  widuu <admin@widuu.com>
	 */

	private function getFileName( $field_name ,$ue_config , $flag = false ){
		$prefix = trim($ue_config['qiniuUploadPath'] , "/" ) . "/" ;
		$format = $ue_config['qiniuDatePath'];
		$time   = explode('-', date("Y-y-m-d",time()));
		$file_name = "";
		if( !empty($format) ){
			if( strpos($format, 'y') !== false ){
				$format = str_replace("yyyy", $time[0], $format);
        		$format = str_replace("yy"  , $time[1], $format);
			}
			$format = str_replace("mm", $time[2], $format);
        	$format = str_replace("dd", $time[3], $format);
			
		}
		if( isset($this->save_type) && trim($this->save_type) == 'date' ){
			// 不是远程抓取的时候
			if( !$flag ){
				$save_name = pathinfo($_FILES[$field_name]["name"], PATHINFO_EXTENSION);
			}else{
				$save_name = pathinfo($field_name, PATHINFO_EXTENSION);
			}
			$file_name = $prefix . $format.'/'.time().mt_rand(0,215909581).'.'.$save_name;
		}else{
			// 不是远程抓取
			if( !$flag ){
				$save_name = $_FILES[$field_name]["name"];
			}else{
				$file_name = $field_name;
			}
			$file_name = $prefix . $format . '/' . $save_name;
		}

		return $file_name;
	}

	/**
	 * 上传文件到七牛的方法
	 *
	 * @param 	array  $upload_info 上传文件信息
	 * @return 	array  
	 * @author  widuu <admin@widuu.com>
	 */

	private function uploadFile( $upload_info ){
		$token  = $this->getUploadToken($upload_info['file_name']);
		$mimeBoundary = md5(microtime());
		$header = 	array('Content-Type'=>'multipart/form-data;boundary='.$mimeBoundary);
		$data 	= 	array();

		$fields = array(
			'token'	=>	$token,
			'key'	=>	$upload_info['file_name'],
		);

		foreach ($fields as $name => $val) {
			array_push($data, '--' . $mimeBoundary);
			array_push($data, "Content-Disposition: form-data; name=\"$name\"");
			array_push($data, '');
			array_push($data, $val);
		}

		//文件
		array_push($data, '--' . $mimeBoundary);
		$name 		= 	$upload_info['name'];
		$fileName 	= 	$upload_info['file_name'];
		$fileBody 	= 	$upload_info['file_body'];
		$fileName 	= 	$this->escapeQuotes($fileName);

		array_push($data, "Content-Disposition: form-data; name=\"$name\"; filename=\"$fileName\"");
		array_push($data, 'Content-Type: application/octet-stream');
		array_push($data, '');
		array_push($data, $fileBody);

		array_push($data, '--' . $mimeBoundary . '--');
		array_push($data, '');

		$body 		= 	implode("\r\n", $data);
		$response 	= 	$this->request($this->qiniu_up_host, 'POST', $header, $body);
		return $response;
	}


	/**
	  * 设置七牛列出文件的请求信息
	  *
	  * @param string  $prefix 前缀
	  * @param string  $marker 标记
	  * @param int     $limit  限制出现的个数
	  * @author widuu <admin@widuu.com>
	  */

	private  function getListRequestInfo($prefix='', $marker='', $limit = 0){
		
		$query = array( 'bucket' => $this->bucket );		
		
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
	    return array(
	    	'path'  => $this->qiniu_rsf_host . $url,
	    	'token' => $this->getSign($url."\n")
	    );
	}

	/**
	 * 七牛Sign方法
	 * 
	 * @param    string $sign_data 加密字符串
	 * @author   widuu <admin@widuu.com>
	 */

	private function getSign( $sign_data ){
		$sign 	   = hash_hmac('sha1',$sign_data, $this->secret_key, true);
		$result    = $this->access_key . ':' . $this->SafeBase64Encode($sign);
		return $result;
	}

	/**
	 * 直传水印url拼接方式
	 * 
	 * @author   widuu <admin@widuu.com>
	 */

	private function getDirectReturnBody(){
		//使用水印和非使用水印拼接方法
		if( !$this->use_water ){
			$url = trim($this->host , "/")."/$(key)";
		}else{
			$water_image  = $this->SafeBase64Encode($this->water_url);
			$url  = trim($this->host , "/")."/$(key)?watermark/1/image/{$water_image}";
			$url .= "/dissolve/{$this->dissolve}/gravity/{$this->gravity}/dx/{$this->dx}/dy/{$this->dy}";
		}
		
		$return_body = array(
				"url" 	=> $url,
				"state" => "SUCCESS",
				"name"  => "$(fname)",
				"size"  => "$(fsize)",
				"w"  	=> "$(imageInfo.width)",
				"h"	    => "$(imageInfo.height)",
			);

		return json_encode($return_body);
	}


	/**
	 * 七牛base64方法
	 *
	 * @param    string $infomation base64的字符串
	 * @author   widuu <admin@widuu.com>
	 */

	private function SafeBase64Encode( $infomation ){
		$find = array('+', '/');
		$replace = array('-', '_');
		return str_replace($find, $replace, base64_encode($infomation));
	}


	/**
     * 获取上传的文件token
     *
     * @param  string   $key     文件名称
     * @return string
     * @author widuu    <admin@widuu.com>
     */

	private function getUploadToken($key){
		$time  = time() + $this->timeout;
		$scope = $this->bucket.":".$this->escapeQuotes($key);
		$put_policy = array(
			'returnBody' => $this->getDirectReturnBody(),
			'deadline'   => $time,
			'scope'      => $scope
			);

		$safe_data = $this->SafeBase64Encode(json_encode($put_policy));
		return $this->getSign($safe_data).':'.$safe_data;
	}	

	/**
     * 请求云服务器
     * @param  string   $path    请求的PATH
     * @param  string   $method  请求方法
     * @param  array    $headers 请求header
     * @param  resource $body    上传文件资源
     * @return boolean
     */

    private function request($path, $method, $headers = null, $body = null){
        $ch  = curl_init($path);

        $_headers = array('Expect:');
        if (!is_null($headers) && is_array($headers)){
            foreach($headers as $k => $v) {
                array_push($_headers, "{$k}: {$v}");
            }
        }

        $length = 0;
		$date   = gmdate('D, d M Y H:i:s \G\M\T');

        if (!is_null($body)) {
            if(is_resource($body)){
                fseek($body, 0, SEEK_END);
                $length = ftell($body);
                fseek($body, 0);

                array_push($_headers, "Content-Length: {$length}");
                curl_setopt($ch, CURLOPT_INFILE, $body);
                curl_setopt($ch, CURLOPT_INFILESIZE, $length);
            } else {
                $length = @strlen($body);
                array_push($_headers, "Content-Length: {$length}");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        } else {
            array_push($_headers, "Content-Length: {$length}");
        }

        array_push($_headers, "Date: {$date}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method == 'PUT' || $method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
        } else {
			curl_setopt($ch, CURLOPT_POST, 0);
        }

        if ($method == 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $response = curl_exec($ch);

        if( !$response ){
        	return false;
        }
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        list($header, $body) = explode("\r\n\r\n", $response, 2);
        return $this->response($body);
    }

    /**
     * 获取响应数据
     * @param  string $text 响应头字符串
     * @return array        响应数据列表
     */

    private function response($text){
        $headers = explode(PHP_EOL, $text);
        $items = array();
        foreach($headers as $header) {
            $header = trim($header);
            if(strpos($header, '{') !== False){
                $items = json_decode($header, 1);
                break;
            }
        }
        return $items;
    }

    /**
	 * 特殊字符串处理
	 * @param    string $str 
	 * @author   widuu <admin@widuu.com>
	 */

    private function escapeQuotes($str){
		$find = array("\\", "\"");
		$replace = array("\\\\", "\\\"");
		return str_replace($find, $replace, $str);
	}
	
}

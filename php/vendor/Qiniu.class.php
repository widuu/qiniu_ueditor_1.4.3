<?php

/**
 * 七牛上传方法
 * 
 * @author   widuu <admin@widuu.com>
 * @document https://github.com/widuu/qiniu_ueditor_1.4.3
 */

class Qiniu extends Base{

	private $qiniu;

	/**
	 * 继承Base并且实列化QiniuDirver
	 *
	 * @author   widuu <admin@widuu.com>
	 */

	public function __construct($config){
		parent::__construct($config);
		// fix 上传区域错误
		if( isset($this->ue_config['uploadQiniuUrl']) && !empty($this->ue_config['uploadQiniuUrl']) ){
			$this->config['qiniu_up_host'] = $this->ue_config['uploadQiniuUrl'];
		}
		$this->qiniu = new QiniuDriver($this->config);
	}

	/**
	 * 直接七牛URL直传的时候获取token的方法
	 * 
	 * @return  array
	 * @author   widuu <admin@widuu.com>
	 */

	public function getToken(){
		return $this->qiniu->getToken();
	}

	/**
	 * 上传文件的方法
	 * 
	 * @param    string $method 根据method来配置传输参数
	 * @return  array
	 * @author   widuu <admin@widuu.com>
	 */

	public function upload($method){
		$upload_config = $this->setUploadConfig($method);
		$ue_config	   = $this->getUeConfig();
		return $this->qiniu->getFileInfo( $upload_config ,$ue_config );
	}

	/**
	 * 删除文件的方法，七牛远程删除单个文件的方法
	 * 
	 * @return  array
	 * @author   widuu <admin@widuu.com>
	 */

	public function remove(){
		$file_key = trim($_POST['key']);
		$result   = $this->qiniu->removeFile($file_key);
	    if( $result['code'] == 0 ){
	        $return_info = array('state'=>'SUCCESS');
	    }else{
	        $return_info = array('state'=>$result['error']);
	    }
	    return $return_info;
	}

	/**
	 * Ueditor的七牛在线列表
	 * 
	 * @param   string $method 列出文件的类型
	 * @return  array
	 * @author  widuu <admin@widuu.com>
	 */

	public function listFile( $method ){

		$ue_config = $this->getUeConfig();
		
		if( $method == 'listimage'){
			$config_prefix = 'image';
		}else{
			$config_prefix = 'file';
		}

		$config = array(
 			"allowFiles" => $ue_config[$config_prefix.'ManagerAllowFiles'],
        	"listSize"   => $ue_config[$config_prefix.'ManagerListSize'],
        	'prefix'	 => $ue_config['qiniuUploadPath']
		);

		$allow_files = substr(str_replace(".", "|", join("", $config['allowFiles'])), 1);
	
		$size   = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $config['listSize'];
		$start  = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
		$marker = isset($_GET['marker']) ? trim($_GET['marker']) : '';

		$end = $start + $size;

		$files = $this->qiniu->getList($config['prefix'] , $marker, $size );
		// 通过marker来判断文件是否还有文件
		if ( empty($_GET['marker']) && $start != 0  ) {
		    return array(
		        "state" => "no match file",
		        "list" => array(),
		        "start" => $start,
		        "total" => $start
		    );
		}

		/* 获取指定范围的列表 */
		$len = count($files['items']);

		for ($i = 0; $i < $len; $i++ ){
		    if ( preg_match( "/\.($allow_files)$/i" , $files['items'][$i]['key'] ) ) {
	            $list[] = array(
	                "url" => $this->qiniu->host."/".$files['items'][$i]['key'],
	                "key" => $files['items'][$i]['key']
	            );
		    }
		}
		
		$marker = '';
		
		if( $files['marker'] != null ){
			$marker = $files['marker'];
		}

		/* 返回数据 */
		$result = array(
		    "state"  => "SUCCESS",
		    "list"   => $list,
		    "start"  => $start,
		    "marker" => $marker,
		    "total"  => $start + $config['listSize']*2  // 保持最大数保证文件能够完全列出
		);

		return $result;
	}

	/**
	 * 分片上传合成文件的方法
	 * 
	 * @author widuu <admin@widuu.com>
	 */

	public function makeFile(){
		$ue_config = $this->getUeConfig();
		return $this->qiniu->Synthesis($_POST,$ue_config);
	}

	/**
	 * 远程图片抓取 [采用原有ueditor方法]
	 * 
	 * @author widuu <admin@widuu.com>
	 */

	public function catchimage(){
		// Ueditor 配置文件
		$ue_config  = $this->getUeConfig();
		$field_name = $ue_config['catcherFieldName'];

		/* 抓取远程图片 */
		$list = array();
		if (isset($_POST[$field_name])) {
		    $source = $_POST[$field_name];
		} else {
		    $source = $_GET[$field_name];
		}

		foreach ( $source as $img_url ) {
		    $info = $this->qiniu->fetchFile( $img_url ,$ue_config );
		    array_push($list, array(
		        "state"    => $info["state"],
		        "url"      => $info["url"],
		        "size"     => $info["size"],
		        "title"    => htmlspecialchars($info["title"]),
		        "original" => htmlspecialchars($info["original"]),
		        "source"   => htmlspecialchars($img_url)
		    ));
		}

		return array(
		    'state'=> count($list) ? 'SUCCESS':'ERROR',
		    'list'=> $list
		);
	}
}
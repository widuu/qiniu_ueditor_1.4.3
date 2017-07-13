<?php

/**
 * 基类处理方法
 * 
 * @author   widuu <admin@widuu.com>
 * @document https://github.com/widuu/qiniu_ueditor_1.4.3
 */

class Base{

	// 配置文件信息
	protected $config;

	// Ueditor配置信息
	protected $ue_config;

	public function __construct($config){
		$this->config    = $config;
		$this->ue_config = $this->config();
	}

	/**
	 * Ueditor 获取配置信息的返回方法
	 *
	 * @return array
	 * @author widuu <admin@widuu.com>
	 */

	public function config(){
		$ueditor_config = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents( UEDITOR_PATH . "config.json") ), true);
   		return $ueditor_config;
	}

	/**
	 * 判断方法是否存在并调用方法
	 *
	 * @return array
	 * @author widuu <admin@widuu.com>
	 */

	public function __call( $method , $params ){
		if( method_exists($this, $method) ){
			$this->$method();
		}
		
		if( $this->hasUploadMethod( $method ) ){
			if( strpos(strtolower($method),"upload") !== false ){
				return $this->upload($method);
			}else if( strpos(strtolower($method),"list") !== false ){
				return $this->listFile($method);
			}
		}

		return array(
			'state' => 'ERROR',
			'error' => $method.' upload method not exists'
		);
	}

	/**
	 * 获取json配置里边的上传参数
	 *
	 * @return array
	 * @author widuu <admin@widuu.com>
	 */

	public function setUploadConfig($method){
		$ue_config = $this->getUeConfig();
		$config    = $this->config;
		$root_path = $config['root_path'];
		$config_prefix = ltrim($method,"upload");
		$config = array(
			"pathFormat" => $ue_config[$config_prefix.'PathFormat'],
            "maxSize"    => $ue_config[$config_prefix.'MaxSize'],
            "allowFiles" => $ue_config[$config_prefix.'AllowFiles'],
            "fieldName"  => $ue_config[$config_prefix.'FieldName'],
            "base64"     => 'upload',
            "rootPath"   => $root_path
		);
		// scrawl 上传参数
		if( $config_prefix == 'scrawl' ){
			$config['oriName'] = 'scrawl.png';
			$config['base64']  = 'base64';
		}
		
		return $config;
	}

	/**
	 * 判断json中是否定义了方法
	 *
	 * @return boolean
	 * @author widuu <admin@widuu.com>
	 */

	public function hasUploadMethod( $action_name ){
		$ue_config = $this->getUeConfig();
		$action = strtolower($action_name);
		if( in_array( $action , $ue_config )  ){
			return true;
		}
		return false;
	}

	/**
	 * 获取Ueditor配置文件信息
	 *
	 * @return array
	 * @author widuu <admin@widuu.com>
	 */

	public function getUeConfig(){
		$config = array();
		if( count($this->ue_config) > 1 ){
			$config = $this->ue_config;
		}else{
			$config = $this->config();
		}
		return $config;
	}

}

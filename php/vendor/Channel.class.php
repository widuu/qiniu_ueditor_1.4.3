<?php

/**
 * 处理方法
 * 
 * @author   widuu <admin@widuu.com>
 * @document https://github.com/widuu/qiniu_ueditor_1.4.3
 */

class Channel{

	private $config;

	private $handle = null;
	
	public function __construct($config){
		// 上传类型
		$type = strtolower(trim($config['upload_type']));
		// 类名称
		$class_name = ucfirst($type);
		// 判断是否存在
		if( !class_exists($class_name) ){
			return array(
				'state' => 'ERROR',
				'error' => $class_name.' class not exists'
			);
		}else{ 
			$this->handle = new $class_name($config);
		}

	}

	public function dispatcher($action){
		return call_user_func(
			array(
				$this->handle,
				htmlspecialchars($action)
			)
		);
	}

}
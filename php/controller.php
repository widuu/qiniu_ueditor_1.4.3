<?php

/**
 * Ueditor 事件处理方法
 * 
 * @author   widuu <admin@widuu.com>
 * @document https://github.com/widuu/qiniu_ueditor_1.4.3
 */

/**
 * 设置http://www.widuu.com允许跨域访问
 * header('Access-Control-Allow-Origin: http://www.baidu.com'); 
 * header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With'); 
 */

date_default_timezone_set("Asia/chongqing");
error_reporting(E_ERROR);
header("Content-Type: text/html; charset=utf-8");

define('DS', DIRECTORY_SEPARATOR);
define('UEDITOR_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . DS);


// 注册函数方法
spl_autoload_register(function($class){
    if( strpos(strtolower($class), "driver") ){
        $class_path = UEDITOR_PATH . 'vendor'. DS .'driver'. DS . $class. '.class.php';
    }else{
        $class_path = UEDITOR_PATH . 'vendor'. DS . $class. '.class.php';
    }

    if( file_exists($class_path) ){
        include_once($class_path);
    }else{
        return array(
            'state' => 'ERROR',
            'error' => $class.' not exists'
        );
    }
});

// php 配置信息
$config = require_once( UEDITOR_PATH.'config.php' );

// 获取方法
$action = !empty($_GET['action']) ? trim($_GET['action']) : '';

// 实例化处理方法
$handle = new Channel($config);

// 运行
$response = $handle->dispatcher($action);

$result = json_encode($response);

/* 输出结果 */
if (isset($_GET["callback"])) {
    if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
        echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
    } else {
        echo json_encode(array(
            'state'=> 'callback参数不合法'
        ));
    }
} else {
    echo $result;
}

exit();

<?php
/**
 * Constant.php 常量配置文件
 *
 * @author yangbiao
 * @date 16-5-18 15:23
 */

/* Common */
defined('DS') || define('DS', DIRECTORY_SEPARATOR); // 目录分隔符简写

//网站名字
define('SITENAME', Yaf\Registry::get("config")->sitename);
//网址url
define('BASEURL', Yaf\Registry::get("config")->baseurl);

define('IMGURL', Yaf\Registry::get("config")->imgurl);

//阿里云图片url
define('ALIIMGURL', Yaf\Registry::get("config")->get("oss")->imageurl);
//长连接url
define('PULLURL', Yaf\Registry::get("config")->pullurl);
//代码路径
define('PATH_ROOT', Yaf\Registry::get("config")->application->directory);

//代码路径
define('AUTHKEY',Yaf\Registry::get("config")->auth_key);

//静态资源路径
define('STATIC_FILE_BASE_URL', Yaf\Registry::get("config")->static_file_base_url);
//上传路径
if(ENV == 'develop'){
    define('UPLOADPATH', dirname(APP_PATH) . DS.'upload'.DS);
}else{
    define('UPLOADPATH', dirname(APP_PATH) .  DS.'upload'.DS);
}

//写日志目录
define('LOG_PATH', APP_PATH . 'logs/');
//开发和测试环境开启调试模式
ENV == 'develop' && define('DEBUG', 1);
ENV == 'product' && define('DEBUG', 0);


if (DEBUG) {
    error_reporting(E_ALL);
    //error_reporting(E_ALL & ~(E_NOTICE | E_STRICT));
    ini_set('display_errors', 'ON');
} else {
    error_reporting(0);
    ini_set('display_errors', 'Off');
}

//视图层的位置
define('PATH_VIEW', PATH_ROOT . 'views');
define('APP_ADMIN_PATH',PATH_ROOT.'modules'.DS.'Admin');

/* Databases Table */
defined('TABLE_YUETAN_ADMIN_ACCESS') || define('TABLE_YUETAN_ADMIN_ACCESS', 'admin_access');                      // 产品授权表
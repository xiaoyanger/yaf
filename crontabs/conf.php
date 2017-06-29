<?php
header("Content-type: text/html; charset=utf-8");
set_time_limit(0);
ini_set('memory_limit', '2048M');
$lib = 'lib';

//cli模式下运行
if (defined('STDIN')) chdir(dirname(__FILE__));

if (realpath($lib) !== FALSE) $lib = realpath($lib) . '/';
// 站点根目录
define("APP_PATH", dirname(__DIR__));
//确保有一个斜杠
$lib = rtrim($lib, '/') . '/';


if (!is_dir($lib)) exit("lib路径不存在");


//lib绝对路径
define('LIBPATH', str_replace("\\", "/", $lib));

define('BASEPATH', str_replace('lib/', '', LIBPATH));

//抓取类
//require(LIBPATH . 'CURL.php');

//通用方法
//require(LIBPATH . 'common.php');

//html分析类
//require(LIBPATH . 'phpQuery.php');
require(LIBPATH . 'medoo.php');
require(LIBPATH . 'SimpleSSDB.php');
// 生产者环境主机
$production_servers = ['yb-pc-host'];

// 根据主机名设置当前的运行环境
$env = 'develop';
if(in_array($hostname, $production_servers)) { // 生产环境
    $env = 'product';
} else { // 测试环境
    $env = 'develop';
}

$redis =  new redis();

if ($env == 'product') {

    $db =  new medoo(['database_type'=>'mysql','server' => '127.0.0.1', 'username' => 'root', 'password' => '123456', 'database_name' => 'my_yaf', 'port' => 3306]);
} else {
    define('BASEURL', 'http://119.40.0.232/');
    $db = new medoo(['database_type'=>'mysql','server' => '127.0.0.1', 'username' => 'root', 'password' => '123456', 'database_name' => 'my_yaf', 'port' => 3306]);
}

//使用php内置的serialize/unserialize 方法对数据进行处理
$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
//rediskey加前缀
$redis->setOption(Redis::OPT_PREFIX, 'yangbiao@163:');
<?php
// 获取当前主机名
$hostname = gethostname();
// 生产者环境主机
$production_servers = ['yb-pc-host'];

// 根据主机名设置当前的运行环境
$env = 'develop';
if(in_array($hostname, $production_servers)) { // 生产环境
    $env = 'product';
} else { // 测试环境
    $env = 'develop';
}

// 项目根目录
define('APP_PATH', realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);

// 项目运行环境
define('ENV', $env);

//启动应用
$app = new Yaf_Application(APP_PATH . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . $env . '.ini', 'base');
$app->bootstrap()->run();
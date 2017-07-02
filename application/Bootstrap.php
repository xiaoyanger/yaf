<?php

/**
 * Bootstrap.php 引导文件
 */


/**
 * Class Bootstrap
 */
class Bootstrap extends Yaf\Bootstrap_Abstract
{
    /**
     * _init_set
     * 注册配置信息
     * @author yangbiao
     * @date
     */
    public function _init_set()
    {
        //配置文件
        Yaf\Session::getInstance()->start();
        Yaf\Registry::set("config", Yaf\Application::app()->getConfig());
    }
    /**
     * _initConstant 引入常量配置文件
     *
     * @author
     * @date 2016-05-17 17:42:42
     */
    public function _initConstant()
    {
        Yaf\Loader::import(APP_PATH . 'conf/Constant.php');
    }

    /**
     * _initView 对视图的一些初始化设置
     *
     * @param Dispatcher $dispatcher 调度器
     *
     * @author
     * @date 2016-05-18 16:42:07
     */
    public function _initView()
    {
        // 关闭自动渲染
        Yaf\Dispatcher::getInstance()->disableView();
    }

    /**
     * _initSeasLog 初始化设置SeasLog日志
     *
     * @author
     * @date 2016-06-20 17:12:52
     */
    public function _initSeasLog()
    {
        SeasLog::setBasePath(LOG_PATH);
    }

    /**
     * _initRoute 路由的一些初始化设置
     *
     * @param Dispatcher $dispatcher 调度器
     *
     * @author
     * @date 2016-05-20 17:07:27
     */
    public function _initRoute(Yaf\Dispatcher $dispatcher)
    {

    }

    /**
     * _initException 集中处理项目中出现的异常
     *
     * @author yangbao &nbsp;&nbsp; <a href="mailto:yangbaophp@163.com">yangbaophp@163.com</a>
     * @date 2016-06-20 10:44:29
     */
    function _initBase(Yaf\Dispatcher $dispatcher)
    {
        set_exception_handler(function (\Throwable $e) {
            $log_msg = $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
            if ($e instanceof \PDOException) {
                $log_msg = '\PDOException: ' . $e->getMessage() . ' ';
                $trace = $e->getTrace();
                if (! empty($trace[0]['args'][0])) {
                    $log_msg .= ' SQL: ' . $trace[0]['args'][0];
                }
                SeasLog::error($log_msg, [], 'exception/mysql/pdo_exception');
            } elseif ($e instanceof \MongoDB\Driver\Exception\Exception) {
                $log_msg = '\MongoDB\Driver\Exception\Exception: ' . $log_msg;
                SeasLog::error($log_msg, [], 'exception/mongo/mongodb_driver_exception');
            } elseif ($e instanceof \ErrorException) {
                $log_msg = '\ErrorException: ' . $log_msg;
                SeasLog::error($log_msg, [], 'exception/error_exception');
            } elseif ($e instanceof \TypeError) {
                $log_msg = '\TypeError: ' . $log_msg;
                SeasLog::error($log_msg, [], 'exception/type_error');
            } elseif ($e instanceof \Exception) {
                $log_msg = '\Exception: ' . $log_msg;
                SeasLog::error($log_msg, [], 'exception/exception');
            }

            // 记录总日志
            SeasLog::error($log_msg, [], 'exception/all');

            // 非生产环境下输出异常信息
            if('product' != ENV) {
                echo $log_msg . "<br><br>\n\n";
                echo str_replace("\n", "<br>\n", $e->getTraceAsString());
            }
        });
    }


    /**
     * _initAutoload 初始化自动加载vendor
     *
     * @author yangbao &nbsp;&nbsp; <a href="mailto:yangbaophp@163.com">yangbaophp@163.com</a>
     * @date 2016-06-14 09:18:26
     */
    public function _initAutoload()
    {
        Yaf\Loader::import(APP_PATH . 'vendor/autoload.php');
    }

    /**
     * _initTimeZone 初始化时区
     *
     * @author haokaiyang
     * @date 2016-06-28 16:45:23
     */
    public function _initTimeZone()
    {
        date_default_timezone_set('Asia/Shanghai');
    }

    /**
     * 插件注册
     * @param \Yaf\Dispatcher $dispatcher
     */
    public function _initPlugin(Yaf\Dispatcher $dispatcher)
    {
        $dispatcher->registerPlugin(new RoutePlugin ());
    }

    /**
     * 引入部分需要引入的class类
     * @param \Yaf\Dispatcher $dispatcher
     */
    public function _initService(Yaf\Dispatcher $dispatcher)
    {
        $loader = Yaf\Loader::getInstance(APP_PATH.'Services');
        $loader->registerLocalNamespace(array('Testas'));
    }
}

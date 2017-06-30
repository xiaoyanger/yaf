<?php
//namespace Base;
if (!defined('APP_PATH')) exit('No direct script access allowed');
class BaseController extends Yaf\Controller_Abstract
{
    //__FILE__ __LINE__ __METHOD__ __FUNCTION__ __CLASS__
    //$this->module_name = $this->getRequest()->getModuleName();
    //$this->controller_name = $this->getRequest()->getControllerName();
    //$this->action_name = $this->getRequest()->getActionName();
    protected $config;
    //保存类内部实例化对象，方便下次直接调用
    private static $_instance = array();
    //存放实例化类名字
    private static $_instanceName=['db','redis','im_redis','ssdb','mongodb','db2'];
    //传递给模板的数据
    protected $data = [];
    // 接口响应成功
    const STATUS_SUCCESS = 1;
    // 接口响应错误
    const STATUS_ERROR = 0;
    //全局user
    protected $_user = ['uid' => 0, 'username' => 'username'];
    public $_module_name;
    public $_controller_name;
    public $_action_name;
    public $_data = array();

    public function init()
    {
        //平台
        $platform = $this->getRequest()->getPost('platform', 'ios');
        //版本
        $version = $this->getRequest()->getPost('version', '10');
        //配置文件
        $this->config = Yaf\Registry::get("config");
        //cookie初始化
        $this->_cookie = ['cookie_pre' => Yaf\Registry::get("config")->get('cookie')->pre, 'cookie_path' => Yaf\Registry::get("config")->get('cookie')->path, 'cookie_domain' => Yaf\Registry::get("config")->get('cookie')->domain];
        //用户登录判断
        if (isset($_COOKIE[$this->cookie['cookie_pre'] . 'user_auth']) && !empty($_COOKIE[$this->cookie['cookie_pre'] . 'user_auth'])) {
            $arr = explode("\t", core::authcode($_COOKIE[$this->cookie['cookie_pre'] . 'user_auth'], 'DECODE'));
            if(is_array($arr) && !empty($arr) && count($arr) >= 2) {
                list($this->_user['uid'], $this->_user['username']) = $arr;
            }
        }
        $this->_module_name = $this->getRequest()->getModuleName();
        $this->_controller_name = $this->getRequest()->getControllerName();
        $this->_action_name = $this->getRequest()->getActionName();

    }

    /*
     * 魔术方法__GET实现数据库，缓存连接操作
     * __get
     * @param $key
     * @return object|null 返回连接对象
     * @date 2016-4-1
     */
    public  function __get($key)
    {
        $instance_key=self::$_instanceName;
        //判断变量是否在连接数组中
        if (! in_array($key,$instance_key)) {
            return false;
        }
        //如果对象实例过，直接返回对象
        if (isset(self::$_instance[$key])) {
            return self::$_instance[$key];
        }
        switch ($key) {
            case 'db':
                //实例化数据库
                $db=$this->db();
                if ($db) self::$_instance[$key]=$db;
                return $db;
                break;
            case 'redis':
                //实例化redis
                $redis=$this->redis();
                if ($redis) self::$_instance[$key]=$redis;
                return $redis;
                break;
            case 'ssdb':
                //实例化ssdb
                $ssdb=$this->ssdb();
                if ($ssdb) self::$_instance[$key]=$ssdb;
                return $ssdb;
                break;
            case 'mongodb':
                //实例化mongodb
                $mongodb=$this->mongodb();
                if ($mongodb) self::$_instance[$key]=$mongodb;
                return $mongodb;
                break;
        }
        return false;
    }

    /*
     * 实例化数据库
     * db
     * @return medoo 返回medoo数据库操作句柄
     */
    private  function db()
    {
        return new medoo($this->config->database->medoo->yaf->toArray());
    }

    /**
     * @var string 信息提示模板
     */
    public $defaultMsgTemplate = 'error/showmsg.phtml';
    /**
     * @var string 布局文件
     */
    public $layout;

    /**
     * 设置布局
     * @param string $layout 布局文件
     */
    public function setLayout($layout)
    {
        $this->getResPonse()->layout = function($content) use ($layout){
            //$content子视图的内容
            return $this->getView()->render($layout, ['content'=>$content]);
        };
    }

    /**
     * 带布局渲染页面
     * @param $tpl
     */
    public function displayWithLayout($tpl)
    {
        $this->getResponse()->setBody( $this->render($tpl) );
        return false;
    }

    /**
     * 路由重定向
     * @param $c
     * @param string $a
     * @param string $m
     * @param array $params
     * @param string $routerType
     */
    public function redirectTo($c, $a = 'index', $m = 'default', array $params = [],  $routerType = 'default')
    {
        if($m == 'default'){
            $m = $this->_request->getModuleName();
        }
        $url = \Util_Helper::url($c, $a, $m, $params, $routerType);
        $this->redirect($url);
        die;
    }

    /**
     * ajax请求响应内容
     * @param int $code
     * @param string $msg
     * @param string $content
     */
    public function ajaxResponse($content = '', $code = 1, $msg = 'success'){
        echo json_encode([
            'code' => $code,
            'msg'  => $msg,
            'content' => $content,
        ]);
        return false;
    }

    /**
     * 显示消息提示信息
     * @param $msg
     * @param null $toUrl
     * @param int $timeOut
     * @return bool
     */
    public function showMsg($msg, $toUrl = null, $timeOut = 3 ,$template = null){
        if(!$template) $template = $this->defaultMsgTemplate;
        $this->getView()->display($template,
            [
                'message'=>$msg,
                'toUrl'=>$toUrl,
                'time'=>$timeOut
            ]
        );
        //response body equal ''
        return false;
    }

    /**
     * @param null $name
     * @return mixed|null
     */
    public function get($name = null)
    {
        //静态路由没有$_GET
        if(!$_GET){
            $_GET = $this->getRequest()->getParams();
        }

        if(!$name){
            $data = $_GET;
        }elseif(isset($_GET[$name])){
            $data = $_GET[$name];
        }else{
            return false;
        }

        return $this->xssClean($data);
    }

    /**
     * @param null $name
     * @return mixed|null
     */
    public function post($name = null)
    {

        if(!$name){
            $data = $_POST;
        }elseif(isset($_POST[$name])){
            $data = $_POST[$name];
        }else{
            return false;
        }

        return $this->xssClean($data);
    }

    /**
     * @param null $name
     * @return bool|mixed
     */
    public function cookie($name = null)
    {
        if(!$name){
            $data = $_COOKIE;
        }elseif(isset($_COOKIE[$name])){
            $data = $_COOKIE[$name];
        }else{
            return false;
        }

        return $this->xssClean($data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function xssClean($data)
    {
        if(is_array($data)){
            return filter_var_array($data, FILTER_SANITIZE_STRING);
        }else{
            return filter_var($data, FILTER_SANITIZE_STRING);
        }
    }

}

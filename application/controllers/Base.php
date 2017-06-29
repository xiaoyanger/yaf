<?php
if (!defined('APP_PATH')) exit('No direct script access allowed');
session_start();
class BaseController extends Yaf_Controller_Abstract
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
        $this->config = Yaf_Registry::get("config");
        //cookie初始化
        $this->cookie = ['cookie_pre' => Yaf_Registry::get("config")->get('cookie')->pre, 'cookie_path' => Yaf_Registry::get("config")->get('cookie')->path, 'cookie_domain' => Yaf_Registry::get("config")->get('cookie')->domain];
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
        if(in_array($this->_module_name,['Admin'])){
            $this->_data['__title__'] = $this->_module_name.'->'.$this->_action_name;
            $this->getView()->display(APP_ADMIN_PATH .DS.'views/menu.phtml', ['_data' => $this->_data]);
            $this->getView()->display(APP_ADMIN_PATH .DS.'views/footer.phtml', ['_data' => $this->_data]);
        }
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
            case 'db2':
                //实例化数据库
                $db=$this->db2();
                if ($db) self::$_instance[$key]=$db;
                return $db;
                break;
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

    private  function db2()
    {
        return new medoo($this->config->database2->medoo->yaf->toArray());
    }


}

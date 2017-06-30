<?php
/**
 * 
 * Author: zhangxudong@lianchuagbrothers.com
 * Date: 15-3-3
 * Time: 9:29
 */
if (!defined('APP_PATH')) exit('No direct script access allowed');

class  BaseModel
{
    /**
     * 所有配置信息.
     * @var Yaf_Config_Ini
     */
    protected $config;

    /**
     * 平台和版本号信息
     * @author panxiaoliang
     * @date 2016-06-06
     */
    public $platform; //平台
    public $version;  //版本

    private static $_instance = array();//保存类内部实例化对象，方便下次直接调用
    private static $_instanceName=['db','redis','im_redis','ssdb','mongodb'];//存放实例化类名字

    function __construct()
    {
        //配置文件
        $this->config = Yaf\Registry::get('config');
        //cookie初始化
        $this->cookie = ['cookie_pre' => $this->config->get('cookie')->pre, 'cookie_path' => $this->config->get('cookie')->path, 'cookie_domain' => $this->config->get('cookie')->domain];
        //删除实例化的属性,防止出错
        $this->delInstanceProperty();
        //把请求的值付给属性
        if ( !empty($_REQUEST) ) {
            foreach( array_keys($_REQUEST) as $key ) {

                if ( $this->hasAttributes( $key ) ) {
                    // 是否开启自动转义
                    if (!get_magic_quotes_gpc()) {
                        $this->$key = isset($_REQUEST[$key]) ? $this->addslashes($_REQUEST[$key]) : $this->$key;
                    } else {
                        $this->$key = isset($_REQUEST[$key]) ? $_REQUEST[$key] : $this->$key;
                    }
                }
            }
        }

    }
    
    /**
     * sql查询
     * 如果查询失败，返回false
     * @param type $sql
     * @return type
     */
    public function query($sql) {
        $result = $this->db->query($sql);
        return !empty($result) && is_object($result) ? $result->fetchAll(PDO::FETCH_ASSOC) : false;        
    }
    
    /**
     * 获取最后查询的sql
     * @return type
     * @author wangjiacheng
     */
    public function last_query() {
        return str_replace('"', '', $this->db->last_query()).'<br/>';
    }

    /**
     * 删除类中懒加载定义变量，防止加载不到
     * delInstanceProperty
     * @author:cajianwei
     */
    public function delInstanceProperty()
    {
        $instance_key=self::$_instanceName;
        foreach ($instance_key as $value) {
            if (property_exists($this, $value)) {
                unset($this->$value);
            }
        }
    }
    /**
     * 魔术方法__GET实现数据库，缓存连接操作
     * __get
     * @param $key
     * @return object|null 返回连接对象
     * @author:cajianwei
     * @date 2016-4-1
     */
    public function __get($key)
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
            default:
                break;
        }
        return false;
    }

    /**
     * 实例化数据库
     * db
     * @return medoo 返回medoo数据库操作句柄
     * @author:cajianwei
     * @date 2016-4-1
     */
    private  function db()
    {
        return new medoo($this->config->database->medoo->yaf->toArray());
    }

    /**
     * 实例话redis操作
     * redis
     * @return object|null redis操作句柄
     * @author:cajianwei
     * @date 2016-4-1
     */
    private function redis()
    {
        //redis
        $redis = new redis();
        //连接redis
        $redis->pconnect($this->config->get('redis')->default->host, Yaf_Registry::get("config")->get('redis')->default->port, 2.5);
        //redis密码
        $redis->auth($this->config->get('redis')->default->password);
        //使用php内置的serialize/unserialize 方法对数据进行处理
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        //rediskey加前缀
        $redis->setOption(Redis::OPT_PREFIX, 'yangbiao@163:');
        return $redis;
    }

    /**
     * 实例化ssdb
     * ssdb
     * @return object|null ssdb句柄
     * @author:cajianwei
     * @date 2016-4-1
     */
    private function ssdb()
    {
        return new SimpleSSDB($this->config->get('ssdb')->default->host, $this->config->get('ssdb')->default->port);
    }

    /**
     * mongodb操作句柄
     * mongodb
     * @return object|null mongodb句柄操作
     * @author:cajianwei
     * @date 2016-4-1
     */
    private function mongodb()
    {
        $mongodbconf = $this->config->get('mongodb')->default;
        return new MongoClient("mongodb://".$mongodbconf->server.":".$mongodbconf->port, array("username" => $mongodbconf->username, "password" => $mongodbconf->password, 'db'=>$mongodbconf->database_name));
    }
    /**
     * 当前访问对象是否具有该属性
     * @param string $key
     * @return boolean
     */
    public function hasAttributes( $key ) {
        if ( property_exists( $this , $key ) )
            return true;
        else
            return false;
    }
    /**
     * 转义特殊字符
     * @param string $var
     * @return string
     */
    public static function addslashes(&$var){
        if (is_array($var)) {
            foreach ($var as $k => &$v)
                self::addslashes($v);
        } else
            $var = addslashes($var);
        return $var;
    }

}

<?php
/**
 * Created by PhpStorm.
 * User: yangbao
 * Date: 2015/11/10
 * Time: 9:32
 */

/**
 * Trait ControllerTrait
 * 控制器的公共 Trait
 * @author yangbao
 * @date 2015-11-10 09:36
 */
trait ControllerTrait
{
    /**
     * @var null APP平台
     */
    private $_platform = null;
    /**
     * @var null APP版本
     */
    private $_version = null;
    /**
     * @var int 请求的API版本
     */
    private $_api_version = 1;

    /**
     * getPlatform
     *
     * @return null|string 平台
     * @author yangbao
     * @date 2015-11-10 11:03
     */
    public function getPlatform()
    {
        return $this->_platform;
    }

    /**
     * setPlatform
     *
     * @param string $platform O|平台
     *
     * @return $this
     * @author yangbao
     * @date 2015-11-10 10:50
     */
    public function setPlatform($platform = null)
    {
        $platform = empty($platform) ? $this->getRequest()->getPost('platform', 'ios') : $platform;
        // set平台
        $this->_platform = $platform;
        return $this;
    }

    /**
     * getVersion
     *
     * @return null|string 版本号
     * @author yangbao
     * @date 2015-11-10 11:09
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * setVersion
     *
     * @param string $version O|版本号
     *
     * @return $this
     * @author yangbao
     * @date 2015-11-10 10:50
     */
    public function setVersion($version = null)
    {
        $version = empty($version) ? $this->getRequest()->getPost('version', '1.3') : $version;
        // set版本
        $this->_version = $version;
        return $this;
    }

    /**
     * getApiVersion
     *
     * @return int API版本号
     * @author yangbao
     * @date 2016-02-19 11:56:56
     */
    public function getApiVersion()
    {
        return $this->_api_version;
    }

    /**
     * setApiVersion
     *
     * @param int $api_version O|API版本号
     *
     * @return $this
     * @author yangbao
     * @date 2016-02-19 11:58:58
     */
    public function setApiVersion($api_version = 1)
    {
        $api_version = (int)$api_version;
        $this->_api_version = (0 < $api_version) ? $api_version : (int)$this->getRequest()->getPost('api_version', 1);
        return $this;
    }
}
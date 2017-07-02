<?php
namespace Admin;
/**
 * 后台通用控制器 加了rbac
 * Class AdminCommonController
 * @package Admin
 */
class AdminCommonController extends \BaseController {

    public $layout = 'layout/adminLayout.phtml';
    public $defaultMsgTemplate = 'common/showmsg.phtml';
    /**
     * @var boolean 是否开启用户验证 authenticate
     */
    public $doAuth = true;
    /**
     * @var bool 是否检查权限
     */
    public $checkAuthorization = true;
    /**
     * @var array 用户登录信息
     */
    public $userInfo = [];

    /**
     * @var string 当前请求的权限节点
     */
    public $currentRequest = '';

    public function init(){
        parent::init();
        //权限验证模块
        if($this->doAuth){
            $this->auth();
            if(!$this->userInfo && !in_array($this->_request->controller,['Login'])){
                $this->redirectTo('login', 'index');
            }
            $this->getView()->userInfo = $this->userInfo;
        }

        $currentRequest = '/' . strtolower( implode('/', [
                $this->_request->module,
                $this->_request->controller,
                $this->_request->action,
            ]) );
        $this->getView()->currentRequest = $currentRequest;
        if($this->checkAuthorization && $this->userInfo){
            //$rbacManage = \Core\ServiceLocator::getInstance()->get('rbacManage');
            $rbacManage = new \Rbac_Manage();
            if( ! $rbacManage->isAdmin( $this->userInfo['name'] ) ) {
                $authInfo = $rbacManage->checkAuthorization($this->userInfo['id'], $currentRequest);
                if ($authInfo == false) {
                    throw new \Exception('没有节点访问权限');
                }
            }
        }

        if(in_array($this->_module_name,['Admin']) ){
            if(!in_array($this->_controller_name,['Login'])){
                $this->_data['__title__'] = $this->_module_name.'->'.$this->_action_name;
                $this->_data['__login_out_url__'] = \Util_Helper::url('Login', 'logOut');
                $this->_data['__nav_cat__']['_top_'] = [
                    '0'=>[
                        'title'=>'首页',
                        'url'=>'/admin/index/index',
                        'is_checked'=>1,
                        'list'=>[
                                0=>[
                                    'title'=>'首页',
                                    'url'=>'/admin/index/index',
                                    'is_checked'=>1,
                                ],
                            ],
                    ],
                    '1'=>[
                        'title'=>'系统设置',
                        'url'=>'/admin/system/index',
                        'is_checked'=>0,
                        'list'=>[
                            0=>[
                                'title'=>'权限管理',
                                'url'=>'/admin/system/rbac',
                                'is_checked'=>1,
                            ],
                        ],
                    ],
                ];
                $this->getView()->display(APP_ADMIN_PATH .DS.'views/menu.phtml', ['_data' => $this->_data]);
                $this->getView()->display(APP_ADMIN_PATH .DS.'views/footer.phtml', ['_data' => $this->_data]);
            }
        }
    }

    /**
     * 用户认证
     * @return bool
     */
    protected function auth(){
        $userInfo = \Service\user\DbUserAuth::getUserBySession();
        if( $userInfo ){
            $this->userInfo = $userInfo;
            return true;
        }

        $cookieToken = $this->cookie(\Service\user\DbUserAuth::TOKEN_NAME);
        if( $cookieToken ){
            $result = (new \Service\user\DbUserAuth)->getUserByToken($cookieToken);
            if(!$result){
                return $result;
            }
            $this->userInfo = $result;
            return true;
        }

        return false;
    }

}
<?php

class Rbac_Manage
{

    /**
     * @var \RbacItemModel
     */
    public $itemDao;
    /**
     * @var \UserModel
     */
    public $userDao;
    /**
     * @var \RbacAssigmentModel
     */
    public $assigmentDao;

    /**
     * @var array 用户的权限列表
     */
    public $userPermissions = [];

    public function __construct()
    {
        $this->itemDao =  new \RbacItemModel();
        $this->userDao = new \UserModel();
        $this->assigmentDao = new \RbacAssigmentModel();
    }

    /**
     * 判断用户是否是管理员
     * @param $userName
     */
    public static function isAdmin($userName)
    {
        $adminConfig = \Util_Config::getConfig('admin');
        if($userName == $adminConfig->administrator->name) {
            return true;
        }
        return false;
    }

    /**
     * 检查用户权限
     * @param $userId
     * @param $permissionTitle
     * @return bool
     */
    public function checkAuthorization($userId, $permissionTitle)
    {
        //TODO:: 可以根据业务实现缓存用户权限
        $this->userPermissions = $this->getUserPermissionsMap($userId);
        $authStatus = false;
        foreach( $this->userPermissions as $pItem){
            if(strtolower($pItem['title']) == $permissionTitle){
                $authStatus = true;
                break;
            }
        }
        return $authStatus;
    }

    /**
     * 添加用户
     * @param $name
     * @param $email
     * @param $pwd
     */
    public function addUser($name, $email, $pwd)
    {
        //TODO:datacheck
        $where = [
            'OR' => [
                'name'  => $name,
                'email' => $email,
            ],
            'LIMIT' => [1],
        ];
        $result = $this->userDao->select(['name','email'], $where);
        foreach($result as $item){
            if($item['name'] == $name){
                throw new \InvalidArgumentException('该用户名已被注册');
            }
            if($item['email'] == $email){
                throw new \InvalidArgumentException('该邮箱已被注册');
            }
        }
        $result = $this->userDao->insert([
            'name'  =>  $name,
            'email' => $email,
            'pwd'   => md5($pwd),
            'ctime' => time(),
        ]);
        if($result === false){
            throw new \Exception('无法创建用户');
        }

        return true;
    }

    /**
     * 获取用户权限字典
     * @param $userId
     * @return array|bool
     */
    public function getUserPermissionsMap($userId)
    {
        return $this->assigmentDao->getUserPermissions($userId);
    }

    public function getUserRole($userId)
    {
        return $this->assigmentDao->getUserRoles($userId);
    }

    /** 获取用户列表
     * @param string $field
     * @param $where
     * @return array
     */
    public function getUserList($field = "*", $where)
    {
        $list  = $this->userDao->select($field, $where);
        unset($where['LIMIT']);
        $count = $this->userDao->count($field, $where);
        return [
            'list' => $list,
            'count' => $count,
        ];
    }

    public function getItemList($field = "*", $where)
    {
        $result = $this->itemDao->select($field, $where);
        return $result;
    }

    private function generatorItems($items)
    {
        foreach($items as $item){
            yield $item;
        }
    }

    public function getItemsGroup($field = "*",$where)
    {
        $result = $this->itemDao->select($field, $where);
        //TODO: 需要测试生成器性能
        $itemsGroup = [];
        foreach($this->generatorItems($result) as $item){
            if($item['fid'] == 0){
                $itemsGroup[$item['id']] = $item;
            }else{
                $itemsGroup[$item['fid']]['sub'][] = $item;
            }
        }

        return $itemsGroup;
    }

    /**
     * 添加节点
     * @param $data
     * @return array
     */
    public function addItem($data)
    {
        //TODO:datacheck
        $existInfo = $this->itemDao->get('id', ['title' => $data['title']]);
        if($existInfo){
            throw new \InvalidArgumentException('该节点名称已存在');
        }

        if( $data['type'] == \RbacItemModel::ITEM_TYPE_FPERMISSION
            || $data['type'] == \RbacItemModel::ITEM_TYPE_ROLE ){
            $data['fid']  = 0;
            $data['show'] = 0;
        }

        $insertData = [
            'fid'   => $data['fid'],
            'title' => $data['title'],
            'desc'  => $data['desc'],
            'type'  => $data['type'],
            'show'  => $data['show'],
            'ctime' => time(),
        ];
        $result =  $this->itemDao->insert($insertData);
        if($result === flase){
            throw new \Exception('添加节点失败');
        }

        return $result;
    }

    public function updateItem($data, $where)
    {
        return $this->itemDao->update($data, $where);
    }

    public function delItem($where)
    {
        return $this->itemDao->delete($where);
    }

    public function getItems(\Closure $itemFormat)
    {
        $where = [
                'ORDER' => ['fid ASC'],
        ];
        $result =  $this->itemDao->select("*", $where);
        $permissionList = [];
        $roleList = [];
        foreach ($result as $k => $item) {
            //role
            if($item['type'] == \RbacItemModel::ITEM_TYPE_ROLE){
                $roleList[] = [
                    'title' => $item['title'],
                    'desc' => $item['desc'],
                    'id' => $item['id'],
                ];
                continue;
            }
            //permission
            $permissionList[] = $itemFormat($item);
        }
        return [
            'roleList' => $roleList,
            'permissionList' => $permissionList,
        ];
    }

    public function itemFormatForZTree()
    {
        return function($itemData){
            return [
                'id'   => $itemData['id'],
                'pId'  => $itemData['fid'],
                'name' => $itemData['desc'],
                'open' => true,
            ];
        };
    }

    public function roleGetPermissions($roleId)
    {
        $where = [
            'AND' => [
                'user_id' => 0,
                'role_id' => $roleId,
            ],
        ];
        return $this->assigmentDao->select(['item_id'], $where);
    }

    /**
     * 角色分配权限
     * @param $roleId
     * @param array $items
     * @return bool
     */
    public function roleAssignPermission($roleId, array $items)
    {
        //TODO:datacheck
        return $this->assigmentDao->action(function($db) use ($roleId, $items){

            $where = [
                'AND' => [
                    'role_id' => $roleId,
                    'user_id' => 0,
                ],
            ];
            $result = $this->assigmentDao->delete($where);
            if($result === false){
                return false;
            }

            $datas = [];
            $_time = time();
            foreach ($items as $k => $item) {
                $datas[] = [
                        'user_id' => 0,
                        'role_id' => $roleId,
                        'item_id' => $item,
                        'ctime'   => $_time,
                ];
            }
            if(!empty($datas)){
                $result = $this->assigmentDao->insert($datas);
                if($result === false){
                    return false;
                }
            }
            return true;
        });

    }

    public function getUserDic()
    {
        return $this->userDao->select(['name', 'id']);
    }

    public function userAssignRole($userId, $roleIds)
    {
        //TODO:datacheck
        return $this->assigmentDao->action(function($db) use($userId, $roleIds){
            $where = [
                'user_id' => $userId,
            ];
            $result = $this->assigmentDao->delete($where);
            if($result === false){
                return false;
            }
            $datas = [];
            foreach ($roleIds as $roleId) {
                $datas[] = [
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'item_id' => $roleId,
                    'ctime'   => time(),
                ];
            }
            if($datas){
                return $this->assigmentDao->insert($datas);
            }

            return true;
        });
    }

    public function getItemParentNodes()
    {
        $result = $this->itemDao->select('*', [
           'type' => \RbacItemModel::ITEM_TYPE_FPERMISSION,
        ]);

        if($result === false){
            return false;
        }

        $return = [];
        foreach($result as $item){
            $return[$item['id']] = $item;
        }

        return $return;
    }

    public function delUser($id)
    {
        return $this->userDao->delete(['id' => $id]);
    }

    public function getUserInfo($id)
    {
        return $this->userDao->get('*', ['id' => $id]);
    }

    public function updateUser($inputData)
    {
        //TODO::datacheck
        $data = [
          'name'  => $inputData['name'],
          'email' => $inputData['email'],
        ];
        $where = [
          'id' => $inputData['id'],
        ];
        return $this->userDao->update($data, $where);
    }

}
<?php
/**
 * 权限分配模型
 *
 * Class RbacAssigmentModel
 */
class RbacAssigmentModel extends DbsqlModel
{
    public $tableName = 'rbac_assigment';

    /**
     * 获取用户权限列表
     * @param $userId
     * @return array|bool
     */
    public function getUserPermissions($userId)
    {
        $tablePrefix = $this->getTablePrefix();
        $sql = <<<SQL
select user.id,role.role_id,item.id as item_id, item.fid,item.title,item.desc
from {$tablePrefix}user as user
inner join {$tablePrefix}rbac_assigment as role on user.id = role.user_id and role.user_id = {$userId}
inner join {$tablePrefix}rbac_assigment as permission on role.role_id = permission.role_id and permission.role_id <> permission.item_id
inner join {$tablePrefix}rbac_item as item on permission.item_id = item.id
SQL;
        $stm = $this->getDb()->query($sql);
        if(!$stm){
            return false;
        }
        $result = $stm->fetchAll(PDO::FETCH_ASSOC);
        if(!$result){
            return false;
        }
        return $result;
    }

    /**
     * 获取角色权限字典
     * @return array|bool
     */
    public function getRolePermissionMap()
    {
        $tablePrefix = $this->getTablePrefix();
        $stm = $this->getDb()->query("SELECT role_id,GROUP_CONCAT(item_id) AS permission_id
                                      FROM {$tablePrefix}rbac_assigment
                                      WHERE user_id = 0 GROUP BY role_id");
        return $stm ? $stm->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP) : false;
    }

    /**
     * 获取用户角色
     * @param $userId
     * @return string
     */
    public function getUserRoles($userId)
    {
        return $this->select('role_id', ['user_id'=>$userId]);
    }

    /**
     * 获取用户角色字典
     * @return array|bool
     */
    public function getUserRoleMap()
    {
        $tablePrefix = $this->getTablePrefix();
        $stm = $this->getDb()->query("SELECT user_id,GROUP_CONCAT(role_id) AS role_id
                                      FROM {$tablePrefix}rbac_assigment
                                      WHERE user_id > 0 GROUP BY user_id");
        return $stm ? $stm->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP) : false;
    }

}
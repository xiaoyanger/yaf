<?php
/**
 * 节点模型
 * Class RbacItemModel
 */
class RbacItemModel extends DbsqlModel
{
    public $tableName = 'rbac_item';
    const ITEM_TYPE_ROLE =2;
    const ITEM_TYPE_PERMISSION = 1;
    const ITEM_TYPE_FPERMISSION = 3;
    const ITEM_SHOW_YES = 1;
    const ITEM_SHOW_NOT = 2;

    /**
     * 节点类型常量
     * @param null $s
     * @param bool|false $revert
     * @return array
     */
    public static function M($s = null, $revert = false)
    {
        $m = [
            self::ITEM_TYPE_ROLE        => '角色',
            self::ITEM_TYPE_PERMISSION  => '权限',
            self::ITEM_TYPE_FPERMISSION => '权限父节点',
        ];

        if($revert == true){
             $m = array_reverse($m);
        }

        if($s !== null){
            return $m[$s];
        }

        return $m;
    }

    /**
     * 是否可以在菜单栏显示常量。
     * @param null $s
     * @param bool|false $revert
     * @return array
     */
    public static function MSHOW($s = null, $revert = false)
    {
        $m = [
            self::ITEM_SHOW_YES => '是',
            self::ITEM_SHOW_NOT => '否',
        ];

        if($revert == true){
            $m = array_reverse($m);
        }

        if($s !== null){
            return $m[$s];
        }

        return $m;
    }
}
<?php
/**
 * @desc 角色菜单表
 * @author Fun
 */
namespace app\modules\users\models;
class RoleMenu extends UsersModel
{
    public static function tableName()
    {
        return '{{%role_menu}}';
    }
    
    public static function addRoleMenu($roleId, $menuIds, $type = 1)
    {
        $columns = ['role_id', 'menu_id', 'type'];
        $datas = [];
        foreach ($menuIds as $id)
            $datas[] = [$roleId, $id, $type];
        $db = self::getDb();
        return $db->createCommand()
            ->batchInsert(self::tableName(), $columns, $datas)
            ->execute();
    }
    
    /**
     * @desc 获取用户，角色对应的菜单IDs
     * @param unknown $roleIds
     * @param number $type
     */
    public static function getRoleMenuIds($roleIds, $type = 1)
    {
        if (!is_array($roleIds))
            $roleIds = [$roleIds];
        return self::find()->select("menu_id")
            ->from(self::tableName())
            ->where(['role_id' => $roleIds, 'type' => $type])
            ->column();
    }
}
<?php
/**
 * @desc user model
 * @author Fun
 */
namespace app\modules\users\models;
class UserRole extends UsersModel
{
    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%user_role}}';
    }
    
    /**
     * @desc添加用户角色
     * @param unknown $userId
     * @param array $roleIds
     * @return boolean|\yii\db\$this
     */
    public static function addUserRoles($userId, Array $roleIds)
    {
        if (empty($userId))
            return false;
        $data = [];
        foreach ($roleIds as $roleId)
            $data[] = [$userId, $roleId];
        return self::getDb()->createCommand()
            ->batchInsert(self::tableName(), ['user_id', 'role_id'], $data)
            ->execute();
    }
    
    /**
     * @desc 获取用户对应的所有角色名
     * @param unknown $userId
     */
    public static function getUserRoleName($userId)
    {
        return self::find()->select(['role_name' => 'GROUP_CONCAT(b.role_name SEPARATOR ",")'])
            ->from(['a' => self::tableName()])
            ->where(['user_id' => $userId])
            ->leftJoin(['b' => Role::tableName()], "a.role_id = b.id")
            ->scalar();
    }
    
    /**
     * @desc 获取用户对应的角色IDs
     * @param unknown $userId
     */
    public static function getUserRoleIds($userId)
    {
        return self::find()->select("role_id")
            ->where(['user_id' => $userId])
            ->column();
    }
    
    /**
     * @desc 删除用户对应的角色关系
     * @param unknown $userId
     * @return \yii\db\int
     */
    public static function deleteByUserId($userIds)
    {
        $count = self::find()->where(['user_id' => $userIds])->count();
        if($count){
            return self::deleteAll(['user_id' => $userIds]); 
        }
        return TRUE;
    }
    
    /**
     * @desc 获取用户对应的角色
     * @param unknown $userId
     */
    public static function getUserRoles($userId)
    {
        return self::find()->select("*")
            ->from(['a' =>self::tableName()])
            ->leftJoin(['b' => Role::tableName()], "a.role_id = b.id")
            ->where(['a.user_id' => $userId])
            ->asArray()
            ->all();
    }

    /**
     * 判断用户是否是主管及以上角色
     */
    public static function checkManage($userId)
    {
        if (empty($userId)) {
            return false;
        }

        //主管及以上角色的ID数组
        $manageRoleIds = [1, 2, 3, 5, 6, 7, 8, 9, 12, 14, 18, 20, 26, 30, 33, 34];

        $roleIds = self::find()
            ->select('role_id')
            ->where(['user_id' => $userId])
            ->column();

        if (empty($roleIds)) {
            return false;
        }

        //如果用户的角色中有主管的角色
        $inter = array_intersect($manageRoleIds, $roleIds);
        if (!empty($inter)) {
            return true;
        } else {
            return false;
        }
    }
}
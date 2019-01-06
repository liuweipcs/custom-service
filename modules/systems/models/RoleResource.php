<?php
/**
 * @desc 角色资源表
 * @author Fun
 */
namespace app\modules\systems\models;
class RoleResource extends SystemsModel
{
    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%role_resource}}';
    }
    
    /**
     * @desc 批量添加角色资源
     * @param unknown $roleId
     * @param unknown $resourceIds
     */
    public static function batchAddRoleResource($roleId, $resourceIds, $type)
    {
        $columns = ['role_id', 'resource_id', 'type'];
        $datas = [];
        foreach ($resourceIds as $id)
            $datas[] = [$roleId, $id, $type];
        $db = self::getDb();
        return $db->createCommand()
            ->batchInsert(self::tableName(), $columns, $datas)
            ->execute();
    }
}
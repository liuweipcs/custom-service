<?php
/**
 * @desc 菜单资源模型
 * @author Fun
 */
namespace app\modules\systems\models;
class MenuResource extends SystemsModel
{
    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%menu_resource}}';
    }
    
    /**
     * @desc 获取菜单对应资源ID
     * @param unknown $menuId
     * @return \yii\db\array
     */
    public static function getMenuResourceIds($menuId)
    {
        $menuIds = [];
        if (!is_array($menuId))
            $menuIds[] = $menuId;
        else
            $menuIds = $menuId; 
        $query = new \yii\db\Query();
        $query->from(self::tableName())
            ->select('resource_id')
            ->where(['menu_id' => $menuIds]);
        return $query->column();
    }
    
    /**
     * @desc 插入菜单资源
     * @param unknown $menuId
     * @param unknown $resourceIds
     */
    public function insertMenuResource($menuId, $resourceIds)
    {
        $insertData = [];
        $columns = ['menu_id', 'resource_id'];
        foreach ($resourceIds as $resourceId)
            $insertData[] = [$menuId, $resourceId];
        return $this->getDb()->createCommand()
            ->batchInsert(self::tableName(), $columns, $insertData)
            ->execute();
    }
    
    /**
     * @desc 插入菜单资源
     * @param unknown $menuId
     * @param unknown $resourceIds
     */
    public function deleteMenuResource($menuId, $resourceIds)
    {
        return $this->getDb()->createCommand()->delete(self::tableName(), ['menu_id' => $menuId, 'resource_id' => $resourceIds])
            ->execute();
    }
}
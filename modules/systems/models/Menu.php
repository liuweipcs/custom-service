<?php
namespace app\modules\systems\models;
use app\modules\users\models\Role;
class Menu extends SystemsModel
{
    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%menu}}';
    }
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\ActiveRecord::attributes()
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = ['status_text'];              //状态
        return array_merge($attributes, $extraAttributes);
    }
    
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\base\Model::rules()
     */
    public function rules()
    {
        return [
            [['menu_name', 'is_show', 'parent_id', 'is_show'], 'required'],
            [['route', 'menu_icon'], 'safe'],
            [['sort_order', 'is_new'], 'integer']
        ];
    }
    
    /**
     * @desc search list
     * @param unknown $params
     * @param string $query
     */
    public function searchList($params = [])
    {
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'id' => SORT_ASC
        );
        $dataProvider = parent::search(null, $sort, $params);
        $models = $dataProvider->getModels();
        foreach ($models as  $key => $model)
        {
            //$models[$key]->setAttribute('status_text', self::getStatusList($model->status));
        }
        $dataProvider->setModels($models);
        return $dataProvider;
    }
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\base\Model::attributeLabels()
     */
    public function attributeLabels()
    {
        return [
            'id'                        => \Yii::t('menu', 'Id'),
            'menu_name'                 => \Yii::t('menu', 'Menu Name'),
            'route'                     => \Yii::t('menu', 'Route'),
            'sort_order'                => \Yii::t('menu', 'Sort Order'),
            'is_external'               => \Yii::t('menu', 'Is External'),
            'is_new'                    => \Yii::t('menu', 'Is New'),
            'menu_icon'                 => \Yii::t('menu', 'Menu Icon'),
            'is_show'                   => \Yii::t('menu', 'Is Show'),
            'create_by'                 => \Yii::t('system', 'Create By'),
            'create_time'               => \Yii::t('system', 'Create Time'),
            'modify_by'                 => \Yii::t('system', 'Modify By'),
            'modify_time'               => \Yii::t('system', 'Modify Time'),
            'status_text'               => \Yii::t('system', 'Status Text'),
            'parent_id'                 => \Yii::t('menu', 'Parent Id'),
        ];
    }
    
    /**
     * @desc 搜索过滤项
     * @return multitype:multitype:string multitype:  multitype:string multitype:string
     */
    public function filterOptions()
    {
        return [
            [
                'name' => 'platform_code',
                'type' => 'text',
                'htmlOptions' => [],
                'search' => 'LIKE'
            ],
        ];
    }
    
    /**
     * @desc 获取所以菜单树形结构
     * @param unknown $parentId
     * @param integer $level 菜单层级
     * @param unknown $status
     */
    public function getMenuTree($parentId = 0, $level = 1, $status = null)
    {
        $datas = [];
        $menus = $this->getChildMenus($parentId, $status);

        //获取具体菜单绑定资源的按钮资源
        if($level != 1)
        {
            // 获取菜单绑定资源
            foreach($menus as &$menu)
            {
                $p_sources = MenuResource::getMenuResourceIds($menu['id']);
                // 根据绑定资源查询所有按钮资源
                $menu['botton'] = array();
                $b_sources = Resource::find()->where(['in','parent_id',$p_sources])->andWhere(['resource_type'=>Resource::RESOURCE_TYPE_BUTTON])->asArray()->all();
                $menu['button'] = $b_sources;
            }

        }
        if (!empty($menus))
        {
            foreach ($menus as $row)
            {
                $menuId = $row['id'];
                $parentId = $menuId;
                $datas[$menuId] = $row;
                $datas[$menuId]['level'] = $level;
                $datas[$menuId]['children'] = [];
                $childrenMenus = $this->getMenuTree($parentId, $level+1, $status);
                $datas[$menuId]['children'] = $childrenMenus;
            }            
        }
        return $datas;
    }
    
    /**
     * @desc 获取子菜单
     * @param number $parentId
     * @param string $status
     * @return \yii\db\array
     */
    public static function getChildMenus($parentId = 0, $isShow = null, $roleId = null, $type = 1)
    {
        $query = new \yii\db\Query();
        $query->from(self::tableName())
        ->select('*')
        ->where('parent_id = :parent_id', ['parent_id' => $parentId]);
        if ($isShow !== null)
            $query->andWhere('is_show = :is_show', ['is_show' => (int)$isShow]);
        if ($roleId != null)
        {
            if (!is_array($roleId))
                $roleId = [$roleId];
            $query->innerJoin(\app\modules\users\models\RoleMenu::tableName() , 'id = menu_id');
            $query->andWhere(['role_id' => $roleId]);
            $query->andWhere(['type' => $type]);
        }
        $query->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_DESC]);
        //echo $query->createCommand()->getRawSql();
        $res = $query->all();
        
        if(!empty($res)){
            foreach ($res as &$value) {
                $value['lev3'] = self::getThreeLevel($value['id']);
            }
        }
        return $res;
    }
    
    
    public static function getThreeLevel($parentId = 0, $isShow = null, $roleId = null){
        $query = new \yii\db\Query();
        $query->from(self::tableName())
        ->select('*')
        ->where('parent_id = :parent_id', ['parent_id' => $parentId]);
        if ($isShow !== null)
            $query->andWhere('is_show = :is_show', ['is_show' => (int)$isShow]);
        if ($roleId != null)
        {
            if (!is_array($roleId))
                $roleId = [$roleId];
            $query->innerJoin(\app\modules\users\models\RoleMenu::tableName() , 'id = menu_id');
            $query->andWhere(['role_id' => $roleId]);
        }
        $query->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_DESC]);
        return $query->all();
    }


    /**
     * @desc 获取菜单列表
     * @param number $parentId
     * @param number $level 菜单层级
     * @param string $status 菜单状态
     * @param unknown $list
     * @param string $indent
     * @return \yii\db\array
     */
    public function getMenuTreeList($parentId = 0, $level = 1, $status = null, &$list, $indent = ' ')
    {
        $menus = [];
        $menus = $this->getChildMenus($parentId, $status);
        if (!empty($menus))
        {
            $total = sizeof($menus);
            $count = 1;
            foreach ($menus as $key => $row)
            {
                $menuLevel = $level;
                $decoration = $total == $count ? '|_' : '|-';
                $count++;
                $menuId = $row['id'];
                $parentId = $menuId;
                $menuName = $row['menu_name'];
                //$level = (int)$row['level'];
                $repeatNum = $level * 4;
                $list[$menuId] = str_repeat($indent, $repeatNum) . $decoration .$menuName;
                $this->getMenuTreeList($parentId, $level+1, $status, $list, $indent);
            }
        }
        return $menus;
    }
    
    /**
     * @desc 删除菜单
     * @param unknown $id
     * @throws \yii\base\Exception
     * @return boolean
     */
    public function deleteMenu($id)
    {
        $dbTransaction = $this->getDb()->beginTransaction();
        try
        {
            //获取菜单的所有子菜单，将子菜单的parent id改为0
            $childrenMenus = $this->getChildMenus($id);
            if (!empty($childrenMenus))
            {
                $childrenIds = [];
                foreach ($childrenMenus as $row)
                {
                    $childrenIds[] = $row['id'];
                }
                $flag = $this->updateAll(['parent_id' => 0], ['id' => $childrenIds]);
                if (!$flag)
                    throw new \yii\base\Exception(\Yii::t('system', 'Update Failed'));
            }
            $flag = $this->deleteById($id);
            if (!$flag)
                throw new \yii\base\Exception(\Yii::t('system', 'Delete Failed'));
            $dbTransaction->commit();
            return true;
        }
        catch (\yii\base\Exception $e)
        {
            $dbTransaction->rollBack();
            return false;
        }
    }
    
    /**
     * @desc 更新菜单信息
     * @throws \yii\base\Exception
     * @return boolean
     */
    public function updateMenu()
    {
        $id = $this->id;
        if (empty($id))
            return false;
        $dbTransaction = $this->getDb()->beginTransaction();
        try
        {
            if ($this->parent_id != 0) {
                //如果指定菜单父菜单不是顶级菜单，获取菜单的所有子菜单，将子菜单的parent id改为0
                $childrenMenus = $this->getChildMenus($id);
                if (!empty($childrenMenus))
                {
                    $childrenIds = [];
                    foreach ($childrenMenus as $row)
                    {
                        $childrenIds[] = $row['id'];
                    }
                    $flag = $this->updateAll(['parent_id' => 0], ['id' => $childrenIds]);
                    if (!$flag)
                        throw new \yii\base\Exception(\Yii::t('system', 'Update Failed'));
                }
            }
            $flag = $this->save();
            if (!$flag)
                throw new \yii\base\Exception(\Yii::t('system', 'Update Failed'));
            $dbTransaction->commit();
            return true;
        }
        catch (\yii\base\Exception $e)
        {
            $dbTransaction->rollBack();
            return false;
        }        
    }
    
    /**
     * @desc 获取角色对应的菜单列表
     * @param number $parentId
     * @param unknown $roleId
     * @param number $level
     * @return multitype:|Ambigous <multitype:\yii\db\array , multitype:>
     */
    public static function getRoleMenuList($parentId = 0, $roleId = null, $level = 1, $type = 1)
    {
        static $childRoleIds;
        if ($roleId !== null && $type == Role::ROLE_TYPE_ROLE) {
            if (!is_array($roleId))
                $roleIds = [$roleId];
            else
                $roleIds = $roleId;
            if (empty($childRoleIds))
            {
                $childRoleIds = \app\modules\users\models\Role::getChildRoleIds($roleId, true);
                $childRoleIds = array_merge($childRoleIds, $roleIds);
            }
            $roleId = $childRoleIds;
        }
        $datas = [];
        $menus = self::getChildMenus($parentId, true, $roleId, $type);
        if (!empty($menus))
        {
            foreach ($menus as $row)
            {
                $menuId = $row['id'];
                $parentId = $menuId;
                $datas[$menuId] = $row;
                $datas[$menuId]['level'] = $level;
                $datas[$menuId]['children'] = [];
                $childrenMenus = self::getRoleMenuList($parentId, $roleId, $level+1, $type);
                $datas[$menuId]['children'] = $childrenMenus;
            }            
        }
        return $datas;
    }
    
    /**
     * @desc 获取角色对应的菜单列表
     * @param number $parentId
     * @param number $level
     * @return multitype:|Ambigous <multitype:\yii\db\array , multitype:>
     */
    public static function getMenuList($parentId = 0, $menuIds = [], $isShow = null, $level = 1)
    {
        $datas = [];
        if (empty($menuIds))
            return $datas;
        $query = self::find()->select("*")
            ->where(['parent_id' => $parentId])
            ->andWhere(['id' => $menuIds]);
        if (!is_null($isShow))
            $query->andWhere(['is_show' => $isShow]);
        $query->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_DESC]);
        $menuInfos = $query->asArray()
            ->all();
        if (!empty($menuInfos))
        {
            foreach ($menuInfos as $row)
            {
                $menuId = $row['id'];
                $key = array_search($menuId, $menuIds);
                unset($menuIds[$key]);
                $parentId = $menuId;
                $datas[$menuId] = $row;
                $datas[$menuId]['level'] = $level;
                $datas[$menuId]['children'] = [];
                $childrenMenus = self::getMenuList($parentId, $menuIds, $isShow, $level+1);
                $datas[$menuId]['children'] = $childrenMenus;
            }
        }
        return $datas;
    }
}
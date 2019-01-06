<?php

namespace app\modules\users\models;

use app\modules\systems\models\Menu;
use app\modules\users\models\RoleMenu;
use app\modules\systems\models\MenuResource;
use app\modules\systems\models\RoleResource;

class Role extends UsersModel
{
    /**
     * @desc 管理员CODE
     * @var unknown
     */
    const ROLE_CODE_ADMIN = 'admin';

    /**
     * @desc 游客CODE
     * @var unknown
     */
    const ROLE_CODE_GUEST = 'guest';

    const ROLE_CODE_ID = 0;

    const ROLE_TYPE_ROLE = 1;       //角色
    const ROLE_TYPE_USER = 2;       //用户

    /**
     * @desc　设置表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%role}}';
    }

    public function attributeLabels()
    {
        return [
            'role_name' => \Yii::t('role', 'Role Name'),
            'role_code' => \Yii::t('role', 'Role Code'),
            'status' => \Yii::t('system', 'Status'),
            'description' => \Yii::t('role', 'Description'),
            'parent_id' => \Yii::t('role', 'Parent Name'),
            'create_by' => \Yii::t('system', 'Create By'),
            'create_time' => \Yii::t('system', 'Create Time'),
            'modify_by' => \Yii::t('system', 'Modify By'),
            'modify_time' => \Yii::t('system', 'Modify Time'),
            'status_text' => \Yii::t('system', 'Status Text'),
            'platform_code' => \Yii::t('platform', 'Platform Code'),
        ];
    }

    public function rules()
    {
        return [
            [['role_name', 'role_code', 'status', 'parent_id'], 'required'],
            ['role_code', 'checkRoleCode'],
            [['platform_code'], 'safe'],
        ];
    }

    public function checkRoleCode($attribute, $params = [])
    {
        $value = $this->$attribute;
        $oldValue = $this->getOldAttribute($attribute);
        if ($value != $oldValue && $this->findByCode($value)) {
            $this->addError($attribute, \Yii::t('role', 'Role Code {roleCode} Has Exists', ['roleCode' => $value]));
        }
    }

    /**
     * @desc 根据角色CODE获取记录
     * @param unknown $roleCode
     * @return \yii\db\static
     */
    public function findByCode($roleCode)
    {
        return $this->findOne(['role_code' => $roleCode]);
    }

    /**
     * @desc 获取所以菜单树形结构
     * @param unknown $parentId
     * @param integer $level 菜单层级
     * @param unknown $status
     */
    public function getRoleTree($parentId = 0, $level = 1, $status = 1)
    {
        $datas = [];
        $roles = $this->getChildRoles($parentId, $status);
        if (!empty($roles)) {
            foreach ($roles as $row) {
                $roleId = $row['id'];
                $parentId = $roleId;
                $datas[$roleId] = $row;
                $datas[$roleId]['level'] = $level;
                $datas[$roleId]['children'] = [];
                $childrenRoles = $this->getRoleTree($parentId, $level + 1, $status);
                $datas[$roleId]['children'] = $childrenRoles;
            }
        }
        return $datas;
    }

    /**
     * @desc 获取角色列表
     * @param number $parentId
     * @param number $level 层级
     * @param string $status 状态
     * @param unknown $list
     * @param string $indent
     * @return \yii\db\array
     */
    public function getRoleTreeList($parentId = 0, $level = 1, $status = null, &$list, $indent = ' ')
    {
        $roles = [];
        $roles = $this->getChildRoles($parentId, $status);
        if (!empty($roles)) {
            $total = sizeof($roles);
            $count = 1;
            foreach ($roles as $key => $row) {
                $roleLevel = $level;
                $decoration = $total == $count ? '|_' : '|-';
                $count++;
                $roleId = $row['id'];
                $parentId = $roleId;
                $roleName = $row['role_name'];
                //$level = (int)$row['level'];
                $repeatNum = $level * 4;
                $list[$roleId] = str_repeat($indent, $repeatNum) . $decoration . $roleName;
                $this->getRoleTreeList($parentId, $level + 1, $status, $list, $indent);
            }
        }
        return $roles;
    }

    /**
     * @desc 获取子菜单
     * @param number $parentId
     * @param string $status
     * @return \yii\db\array
     */
    public function getChildRoles($parentId = 0, $status = null)
    {
        $query = new \yii\db\Query();
        $query->from(self::tableName())
            ->select('*')
            ->where('parent_id = :parent_id', ['parent_id' => $parentId]);
        if ($status !== null) {
            $query->andWhere('status = :status', ['status' => (int)$status]);
        }
        return $query->all();
    }

    /**
     * @desc 获取子菜单
     * @param number $parentId
     * @param string $status
     * @return \yii\db\array
     */
    public static function getChildRolesList($parentId = 0, $status = null, $platform_code)
    {
        $query = new \yii\db\Query();
        $query->from(self::tableName())
            ->select('id')
            ->where('parent_id = :parent_id', ['parent_id' => $parentId])
            ->andWhere(['like', 'platform_code', $platform_code]);
        if ($status !== null) {
            $query->andWhere('status = :status', ['status' => (int)$status]);
        }
        $data = $query->all();
        $info = [];
        foreach ($data as $k => $v) {
            $info[$k] = $v['id'];
        }
        return $info;
    }

    public static function getChildList($status = null, $platform_code)
    {
        $query = new \yii\db\Query();
        $query->from(self::tableName())
            ->select('id')
            ->where(['like', 'platform_code', $platform_code]);
        if ($status !== null) {
            $query->andWhere('status = :status', ['status' => (int)$status]);
        }
        $data = $query->all();
        $info = [];
        foreach ($data as $k => $v) {
            $info[$k] = $v['id'];
        }
        return $info;
    }

    /**
     * @desc 获取角色对应菜单列表
     * @param unknown $roleId
     * @param string $isShow
     * @return \yii\db\array
     */
    public static function getRoleMenuList($roleId, $isShow = null, $type = 1)
    {
        //$childIds = self::getChildRoleIds($roleId, true);
        $roleIds = [$roleId];
        //$roleIds = array_merge($roleIds, $childIds);
        $query = new \yii\db\Query();
        $query->from(Menu::tableName() . ' as t')
            ->innerJoin(RoleMenu::tableName() . ' as t1', 't.id = t1.menu_id')
            ->select('t.*')
            ->where(['role_id' => $roleIds])
            ->andWhere(['type' => $type]);
        $res = $query->all();
        //echo $query->createCommand()->getRawSql();
        return $res;
    }

    /**
     * @desc 获取角色子角色Ids
     * @param unknown $roleId
     * @param string $status
     * @return multitype:|Ambigous <array, multitype:\yii\db\array >
     */
    public static function getChildRoleIds($roleId, $status = null)
    {
        $childIds = [];
        $query = new \yii\db\Query();
        $query->from(self::tableName())
            ->select('id')
            ->where('parent_id = :parent_id', ['parent_id' => $roleId]);
        if (!is_null($status)) {
            $query->andWhere('status = :status', ['status' => (int)$status]);
        }
        $ids = $query->column();
        if (empty($ids)) {
            return $childIds;
        }

        foreach ($ids as $id) {
            $childIds[] = $id;
            $chdIds = self::getChildRoleIds($id, $status);
            if (!empty($chdIds)) {
                $childIds = array_merge($childIds, $chdIds);
            }
        }
        return $childIds;
    }

    /**
     * @desc 刷新角色菜单
     * @param unknown $roleId
     * @param unknown $menuIds
     * @throws \Exception
     * @return boolean
     */
    public static function refreshRoleMenu($roleId, $menuIds, $sourceIds, $type = 1)
    {
        if (empty($roleId)) return false;
        $db = self::getDb();
        $dbTransacton = $db->beginTransaction();
        try {
            //删除角色对应的菜单
            if (RoleMenu::findOne(['role_id' => $roleId, 'type' => $type])) {
                $flag = RoleMenu::deleteAll('role_id = :role_id and type = :type', [
                    'role_id' => $roleId,
                    'type' => $type
                ]);
                if (!$flag) {
                    throw new \Exception('Operate Failed');
                }
            }
            //删除角色对应的资源
            if (RoleResource::findOne(['role_id' => $roleId, 'type' => $type])) {
                $flag = RoleResource::deleteAll('role_id = :role_id and type = :type', [
                    'role_id' => $roleId,
                    'type' => $type
                ]);
                if (!$flag) {
                    throw new \Exception('Operate Failed');
                }
            }
            if (!empty($menuIds)) {
                //新增角色菜单
                $flag = RoleMenu::addRoleMenu($roleId, $menuIds, $type);
                if (!$flag) {
                    throw new \Exception('Operate Failed');
                }
                //将菜单关联的资源分配给角色
                $resourceIds = [];
                //获取菜单关联的资源ID
                $resourceIds = MenuResource::getMenuResourceIds($menuIds);
                if ($sourceIds) {
                    $resourceIds = array_merge($resourceIds, $sourceIds);
                }
                $resourceIds = array_unique($resourceIds);
                if (!empty($resourceIds)) {
                    $flag = RoleResource::batchAddRoleResource($roleId, $resourceIds, $type);
                    if (!$flag) {
                        throw new \Exception('Operate Failed');
                    }
                }
            }
            $dbTransacton->commit();
            return true;
        } catch (\Exception $e) {
            $dbTransacton->rollBack();
            return false;
        }
    }

    /**
     * @desc 获取角色对应资源
     * @param unknown $roleIds
     * @param string $includeChild
     * @return \yii\db\array
     */
    public static function getRoleResources($roleIds, $includeChild = false, $type = 1)
    {
        if (!is_array($roleIds)) {
            $roleIds = [$roleIds];
        }
        if ($includeChild) {
            foreach ($roleIds as $roleId) {
                $childIds = self::getChildRoleIds($roleId, true);
                $roleIds = array_merge($roleIds, $childIds);
            }
        }
        $roleIds = array_unique($roleIds);
        return \app\modules\systems\models\Resource::getRoleResources($roleIds, $type);
    }

    /**
     * @desc 更新角色信息
     * @throws \yii\base\Exception
     * @return boolean
     */
    public function updateRole()
    {
        $id = $this->id;
        if (empty($id)) {
            return false;
        }
        $dbTransaction = $this->getDb()->beginTransaction();
        try {
            if ($this->parent_id != 0) {
                //如果指定角色父角色不是顶级角色，获取角色的所有子角色，将子角色的parent id改为0
                //$childrenRoles = $this->getChildRoles($id);
                //if (!empty($childrenRoles)) {
                //    $childrenIds = [];
                //    foreach ($childrenRoles as $row) {
                //        $childrenIds[] = $row['id'];
                //    }
                //    $flag = $this->updateAll(['parent_id' => 0], ['id' => $childrenIds]);
                //    if (!$flag) {
                //        throw new \yii\base\Exception(\Yii::t('system', 'Update Failed'));
                //    }
                //}
            }
            $flag = $this->save();
            if (!$flag) {
                throw new \yii\base\Exception(\Yii::t('system', 'Update Failed'));
            }
            $dbTransaction->commit();
            return true;
        } catch (\yii\base\Exception $e) {
            $dbTransaction->rollBack();
            return false;
        }
    }
}
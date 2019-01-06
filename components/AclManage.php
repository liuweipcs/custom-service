<?php
/**
 * @desc 访问控制列表类
 * @author Fun
 */
namespace app\components;
use app\modules\users\models\Role;
class AclManage
{
    protected $_roles = null;
    
    protected $_acl = null;
    
    protected $_roleKey = null;
    
    protected $_userId = null;
    
    const ROLE_KEY_PREFIX = 'ROLE-';
    
    public function __construct()
    {
        if (\Yii::$app->user->getIsGuest())
        {
            //未登录
            $this->_roles = ['0' => 'guest'];
            $this->_roleKey = self::ROLE_KEY_PREFIX . '0';
        }
        else
        {
            $identity = \Yii::$app->user->getIdentity();
            if (empty($identity->role))
            {
                $this->_roles = ['0' => 'guest'];
                $this->_roleKey = self::ROLE_KEY_PREFIX . '0';
            }
            else
            {
                $this->_roles = $identity->roles;
                $this->_roleKey = self::ROLE_KEY_PREFIX . $identity->id;
                $this->_userId = $identity->id;
            }
        }
        $this->_acl = new \app\components\Acl();
    }
    
    public function setRoles()
    {
        $this->_acl->addRole($this->_roleKey);
        return $this;
    }
    
    public function setResources()
    {
        //获取所有资源
        $modelResource = new \app\modules\systems\models\Resource();
        $resourceList = $modelResource->getAllResources();
        unset($resourceList[374]);
        unset($resourceList[375]);
        if (!empty($resourceList))
        {
            foreach ($resourceList as $row)
            {
                $resourceName = $row['resource_name'];
                $this->_acl->addResource($resourceName);
            }
        }
        return $this;
    }
    
    public function setPrivileges()
    {
        //将不需要权限验证的路由加入到权限列表
        $resourcesName = 'Users_UserController_actionLogin';
        if (!$this->_acl->hasResource($resourcesName))
            $resources = $this->_acl->addResource($resourcesName);
        $this->_acl->allow($this->_roleKey, $resourcesName, null);
        $resourcesName = 'Users_UserController_actionLogout';
        if (!$this->_acl->hasResource($resourcesName))
            $resources = $this->_acl->addResource($resourcesName);
        $this->_acl->allow($this->_roleKey, $resourcesName, null);
        $resourcesName = 'Systems_IndexController_actionIndex';
        if (!$this->_acl->hasResource($resourcesName))
            $resources = $this->_acl->addResource($resourcesName);
        $this->_acl->allow($this->_roleKey, $resourcesName, null);
        //管理员有所有权限
        if (in_array(\app\modules\users\models\Role::ROLE_CODE_ADMIN, $this->_roles)){
            $this->_acl->allow($this->_roleKey);
            return $this;
        }
        //获取用户资源列表
        $resources = \app\modules\users\models\Role::getRoleResources($this->_userId, true, Role::ROLE_TYPE_USER);
        //print_r($resources);exit;
        if (!empty($resources))
        {
            foreach ($resources as $row)
            {
                $this->_acl->allow($this->_roleKey, $row['resource_name'], null);
            }
        }
        //获取角色资源列表
        foreach ($this->_roles as $roleId => $roleCode)
        {
            $resources = \app\modules\users\models\Role::getRoleResources($roleId, true);
            if (!empty($resources))
            {
                foreach ($resources as $row)
                {
                    $this->_acl->allow($this->_roleKey, $row['resource_name'], null);
                }
            }
            //获取用户资源列表
        }
        return $this;
    }
    
    /**
     * @desc 获取Acl对象
     * @return \app\components\Acl
     */
    public function getAcl()
    {
        return $this->_acl;
    }
    
    /**
     * @desc 获取角色IDs
     * @return Ambigous <number, unknown>
     */
    public function getRoles()
    {
        return $this->_roles;
    }
    
    /**
     * @desc 检查角色是否有该资源权限
     * @param string $resource
     * @param string $privilege
     */
    public function checkAccess($resource = null, $privilege = null)
    {//var_dump($resource);exit;
        if (!$this->_acl->hasResource($resource))
            $this->_acl->addResource($resource);
        return $this->_acl->isAllowed($this->_roleKey, $resource, $privilege);
    }
}
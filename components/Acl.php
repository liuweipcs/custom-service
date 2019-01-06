<?php
/**
 * @desc 访问控制列表类
 * @author Fun
 */
namespace app\components;
use yii\base\Object;
class Acl extends Object
{
    /**
     * @desc 角色对象数组
     * @var unknown
     */
    protected $_roles = [];
    
    /**
     * @desc　资源对象数组
     * @var unknown
     */
    protected $_resources = [];
    
    /**
     * @desc 规则
     * @var unknown
     */
    protected $_rules = [
        'allResources' => [
            'allRoles' => [
                'allPrivileges' => [
                    'type' => self::TYPE_DENY
                ],
                'byPrivilegeId' => [],
            ],
            'byRoleId' => [],
        ],
        'byResourceId' => [],
    ];
    
    const TYPE_ALLOW = 'TYPE_ALLOW';                //权限拒绝
    const TYPE_DENY = 'TYPE_DENY';                  //权限允许
    
    const OP_ADD = 'OP_ADD';                        //添加权限操作
    const OP_REMOVE = 'OP_REMOVE';                  //删除权限操作
    
    /**
     * @desc 设置访问权限
     * @param string $roles
     * @param string $resources
     * @param string $privileges
     */
    public function allow($roles = null, $resources = null, $privileges = null)
    {
        return $this->setRule(self::OP_ADD, self::TYPE_ALLOW, $roles, $resources, $privileges);
    }
    
    /**
     * @desc 设置权限规则
     * @param unknown $operate
     * @param unknown $type
     * @param string $roles
     * @param string $resources
     * @param string $privileges
     * @throws \Exception
     */
    protected function setRule($operate, $type, $roles = null, $resources = null, $privileges = null)
    {
        if (!in_array($type, [self::TYPE_ALLOW, self::TYPE_DENY]))
            throw new \Exception('Invalid Operate Type');
        if ($roles === null)
            $roles = [null];
        if (!is_array($roles))
            $roles = [$roles];
        $tmpRoles = $roles;
        $roles = [];
        foreach ($tmpRoles as $role)
        {
            $role = $this->getRole($role);
            $roles[] = $role;
        }
        unset($tmpRoles);
        if ($resources != null)
        {
            if (!is_array($resources))
                $resources = [$resources];
            else if (sizeof($resources) === 0)
                $resources = [null];
            $tmpResources = $resources;
            $resources = [];
            foreach ($tmpResources as $resource)
            {
                $resource = $this->getResource($resource);
                $resources[] = $resource;
            }
            unset($tmpResources);           
        }

        if (is_null($privileges))
            $privileges = [];
        if (!is_array($privileges))
            $privileges = [$privileges];
        
        switch ($operate)
        {   
            //添加权限
            case self::OP_ADD:
                if ($resources !== null)
                {
                    foreach ($resources as $resource)
                    {
                        foreach ($roles as $role)
                        {
                            $rules = &$this->_getRules($resource, $role, true);
                            if (sizeof($privileges) == 0)
                                $rules['allPrivileges']['type'] = $type;
                            else
                                foreach ($privileges as $privilege)
                                {
                                    $rules['byPrivilegeId'][$privilege]['type'] = $type;
                                }           //end privileges foreach
                        }   //end roles foreach
                    }   //end resources foreach
                }
                else
                {
                    foreach ($roles as $role)
                    {
                        $rules = &$this->_getRules(null, $role, true);
                        if (sizeof($privileges) == 0)
                            $rules['allPrivileges']['type'] = $type;
                        else
                            foreach ($privileges as $privilege)
                            {
                                $rules['byPrivilegeId'][$privilege]['type'] = $type;
                            }           //end privileges foreach
                    }   //end roles foreach
                }   //end if
                break;
            default:
                throw new \Exception('Invalid Operate Type');
        }
    }
    
    /**
     * @desc 获取规则
     * @param string $resource
     * @param string $role
     * @param string $create
     * @return NULL|Ambigous <>
     */
    public function &_getRules($resource = null, $role = null, $create = false)
    {
        $rules = [];
        $null = null;
        if ($resource === null)
            $rules = &$this->_rules['allResources'];
        else
        {
            $resource = $this->getResource($resource);
            $resourceId = $resource->getResourceId();
            if (!isset($this->_rules['byResourceId'][$resourceId]))
            {
                if (!$create)
                    return $null;
                $this->_rules['byResourceId'][$resourceId] = [];
            }
            
            $rules = &$this->_rules['byResourceId'][$resourceId];
        }
        if ($role === null)
        {
            if (!isset($rules['allRoles'])) {
                if (!$create) {
                    return $null;
                }
                $visitor['allRoles']['byPrivilegeId'] = array();
            }
            return $visitor['allRoles'];            
        }
        
        $roleId = $role->getRoleId();
        if (!isset($rules['byRoleId'][$roleId]))
        {
            if (!$create)
                return $null;
            $rules['byRoleId'][$roleId] = [
                'allPrivileges' => ['type' => null],
                'byPrivilegeId' => [],
            ];
        }
        return $rules['byRoleId'][$roleId];
    }
    
    /**
     * @desc 拒绝访问
     * @param string $role
     * @param string $resource
     * @param string $privileges
     */
    public function deny($role = null, $resource = null, $privileges = null)
    {
        
    }
    
    /**
     * @desc 检查是否可以访问资源权限
     * @param string $role
     * @param string $resource
     * @param string $privilege
     * @return Ambigous <NULL, boolean>|boolean
     */
    public function isAllowed($role = null, $resource = null, $privilege = null)
    {   
        if ($role !== null)
        {
            $role = $this->getRole($role);
        }
        if ($resource !== null)
        {
            $resource = $this->getResource($resource);
        }
        
        if ($privilege === null)
        {
            do
            {
                if ($role !== null && null !== ($result = $this->_checkInAllPrivileges($role, $resource)))
                    return $result;
                
                $rules = $this->_getRules($resource, $role);
                if ($rules !== null)
                {
                    foreach ($rules['byPrivilegeId'] as $rule)
                    {
                        if (self::TYPE_DENY == $rule['type'])
                            return false;
                    }
                    if (isset($rules['allPrivileges']) && isset($rules['allPrivileges']['type']))
                        return self::TYPE_ALLOW === $rules['allPrivileges']['type'];
                }
                if ($resource === null && !isset($rules['byRoleId']))
                    return self::TYPE_ALLOW === $this->_rules['allResources']['allRoles']['allPrivileges']['type'];
                //继续检查父资源的权限
                $resource = $this->getParentResource($resource);
            }
            while (true);
        }
        else
        {
            do {
                if ($role !== null && null !== ($result = $this->_checkInOnePrivileges($role, $resource, $privilege)))
                    return $result;
                $rules = $this->_getRules($resource, null);
                if (isset($rules['allRoles']['byPrivilegeId'][$privilege]))
                    return self::TYPE_ALLOW === $rules['allRoles']['byPrivilegeId'][$privilege]['type'];
                if ($resource === null)
                    return self::TYPE_ALLOW === $this->_rules['allResources']['allRoles']['allPrivileges']['type'];
            
                //继续检查父资源的权限
                $resource = $this->getParentResource($resource);
            
            } while (true);
        }
        
    }
    
    /**
     * @desc 获取父资源
     * @param unknown $resource
     * @return NULL
     */
    public function getParentResource($resource)
    {
        if (!$this->hasResource($resource))
            return null;
        $resource = $this->getResource($resource);
        return $this->_resources[$resource->getResourceId()]['parent'];
    }
    
    /**
     * @desc 获取父角色
     * @param unknown $role
     * @return multitype:
     */
    public function getParentRoles($role)
    {
        if (!$this->hasRole($role))
            return [];
        $role = $this->getRole($role);
        return $this->_roles[$role->getRoleId()]['parents'];
    }
    
    /**
     * @desc 在所有权限里面检查是否有权限
     * @param unknown $role
     * @param unknown $resource
     * @return Ambigous <boolean, NULL>|NULL
     */
    protected function _checkInAllPrivileges($role, $resource)
    {
        $stack = [
            'checkedRoles' => [],
            'parents' => [],
        ];
        if (null !== ($result = $this->_checkInAllPrivilegesRecurisive($role, $resource, $stack)))
            return $result;
        while (null != ($role = array_pop($stack['parents'])))
        {
            $roleId = $role->getRoleId();
            if (!isset($stack['parents'][$roleId]) && null !== ($result = $this->_checkInAllPrivilegesRecurisive($role, $resource, $stack)))
                return $result;
        }
        return null;
    }
    
    /**
     * @desc 递归检查所有权限
     * @param unknown $role
     * @param unknown $resource
     * @param unknown $stack
     * @return boolean|NULL
     */
    protected function _checkInAllPrivilegesRecurisive($role, $resource, &$stack)
    {
        //先检查deny规则
        $rules = $this->_getRules($resource, $role);
        if ($rules !== null)
        {
            foreach ($rules['byPrivilegeId'] as $privilegeId => $rule)
            {
                if (isset($rule['type']) && $rule['type'] == self::TYPE_DENY)
                    return false;
            }
            if (in_array($rules['allPrivileges']['type'], [self::TYPE_ALLOW, self::TYPE_DENY]))
                return self::TYPE_ALLOW === $rules['allPrivileges']['type'];
        }
        $roleId = $role->getRoleId();
        $stack['checkedRoles'][$roleId] = true;
        $parents = $this->getParentRoles($role);
        foreach ($parents as $roleId => $parentRole)
            $stack['parents'][] = $parentRole;
        return null;
    }

    /**
     * @desc 在指定权限里面检查
     * @param unknown $role
     * @param unknown $resource
     * @param unknown $privilege
     * @return Ambigous <boolean, NULL>|NULL
     */
    protected function _checkInOnePrivileges($role, $resource, $privilege)
    {
        $stack = [
            'checkedRoles' => [],
            'parents' => [],
        ];
        if (null !== ($result = $this->_checkInOnePrivilegesRecurisive($role, $resource, $privilege, $stack)))
            return $result;
        while (null != ($role = array_pop($stack['parents'])))
        {
            $roleId = $role->getRoleId();
            if (!isset($stack['parents'][$roleId]) && null !== ($result = $this->_checkInOnePrivilegesRecurisive($role, $resource, $privilege, $stack)))
                return $result;
        }
        return null;
    }
    
    /**
     * @desc 递归检查单个权限
     * @param unknown $role
     * @param unknown $resource
     * @param unknown $privilege
     * @param unknown $stack
     * @return boolean|NULL
     */
    protected function _checkInOnePrivilegesRecurisive($role, $resource, $privilege, &$stack)
    {
        //先检查deny规则
        $rules = $this->_getRules($resource, $role);
        if ($rules !== null)
        {
            if (isset($rules['byPrivilegeId'][$privilege]))
            {
                return self::TYPE_ALLOW === $rules['byPrivilegeId'][$privilege]['type'];
            }
        }
        $roleId = $role->getRoleId();
        $stack['checkedRoles'][$roleId] = true;
        $parents = $this->getParentRoles($role);
        foreach ($parents as $roleId => $parentRole)
            $stack['parents'][] = $parentRole;
        return null;
    }
    
    /**
     * @desc 添加角色
     * @param unknown $role
     * @param unknown $parents
     * @throws \Exception
     * @return \app\components\Role
     */
    public function addRole($role, $parents = [])
    {
        if (is_string($role))
            $role = new \app\components\Role($role);
        if (!$role instanceof \app\components\Role)
            throw new \Exception('Role must instance of \app\components\Role');
        $roleId = $role->getRoleId();
        if ($this->hasRole($roleId))
            throw new \Exception('Role {' . $roleId . '} Had Added');
        $parentRoles = [];
        if (!is_array($parents))
            $parents = [$parents];
        foreach ($parents as $parent)
        {
            $parentRole = $this->getRole($parent);
            $parentRoleId = $parentRole->getRoleId();
            $parentRoles[$parentRoleId] = $parentRole;
            $this->_roles[$parentRoleId]['children'][$roleId] = $role;
        }
        $this->_roles[$roleId] = [
            'instance' => $role,
            'parents' => $parentRoles,
            'children' => []
        ];
        return $role;
    }
    
    /**
     * @desc 获取角色
     * @param unknown $role
     * @throws \Exception
     */
    public function getRole($role)
    {
        if ($role === null)
            return $role;
        if ($role instanceof \app\components\Role)
            $roleId = $role->getRoleId();
        else
            $roleId = $role;
        if (!$this->hasRole($roleId))
            throw new \Exception('Role {' . $roleId . '} Not Found');
        return $this->_roles[$roleId]['instance'];
    }
    
    /**
     * @desc 检查角色是否添加
     * @param unknown $role
     */
    public function hasRole($role)
    {
        if ($role instanceof \app\components\Role)
            $roleId = $role->getRoleId();
        else
            $roleId = $role;
        return isset($this->_roles[$roleId]);
    }
    
    /**
     * @desc 检查资源是否添加
     * @param unknown $resource
     */
    public function hasResource($resource)
    {
        if ($resource instanceof \app\components\Resource)
            $resourceId = $resource->getResourceId();
        else
            $resourceId = $resource;
        return isset($this->_resources[$resourceId]);
    }
    
    /**
     * @desc 添加资源
     * @param unknown $resource
     * @param string $parent
     * @throws \Exception
     * @return \app\components\Resource
     */
    public function addResource($resource, $parent = null)
    {
        if (is_string($resource))
            $resource = new \app\components\Resource($resource);
        if (!$resource instanceof \app\components\Resource)
            throw new \Exception('Resource must instance of \app\components\Resource');
        $resourceId = $resource->getResourceId();
        if ($this->hasResource($resourceId))
            throw new \Exception('Resource {' . $resourceId . '} Had Added');
        $resourceParent = null;
        if ($parent !== null)
        {
            $parentResource = $this->getResource($parent);
            $parentResourceId = $parentResource->getResourceId();
            $resourceParent = $parentResource;
            $this->_resources[$parentResourceId]['children'][$resourceId] = $resource;
        }
        $this->_resources[$resourceId] = [
            'instance' => $resource,
            'parent' => $resourceParent,
            'children' => [],
        ];
        return $resource;
    }
    
    /**
     * @desc 获取资源
     * @param unknown $resource
     * @throws \Exception
     */
    public function getResource($resource)
    {
        if ($resource instanceof \app\components\Resource)
            $resourceId = $resource->getResourceId();
        else
            $resourceId = $resource;
        if (!$this->hasResource($resourceId))
            throw new \Exception('Resource {' . $resourceId . '} Not Found');
        return $this->_resources[$resourceId]['instance'];
    }
    
    /**
     * @desc 获取添加的角色列表
     * @return \app\components\unknown
     */
    public function getRoles()
    {
        return $this->_roles;
    }
    
    /**
     * @desc 获取规则列表
     * @return \app\components\unknown
     */
    public function getRules()
    {
        return $this->_rules;
    }
}
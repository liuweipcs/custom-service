<?php
/**
 * @desc 角色类
 */
namespace app\components;
use yii\base\Object;
class Role extends Object
{
    protected $_roleId = null;      //角色标示
    
    public function __construct($roleId)
    {
        $this->_roleId = $roleId;
    }
    
    /**
     * @desc 获取角色标示
     * @return string
     */
    public function getRoleId()
    {
        return $this->_roleId;
    }
}
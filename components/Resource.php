<?php
/**
 * @desc 资源类
 */
namespace app\components;
use yii\base\Object;
class Resource extends Object
{
    protected $_resourceId = null;      //角色标示
    
    public function __construct($resourceId)
    {
        $this->_resourceId = $resourceId;
    }
    
    /**
     * @desc 获取资源标示
     * @return string
     */
    public function getResourceId()
    {
        return $this->_resourceId;
    }
}
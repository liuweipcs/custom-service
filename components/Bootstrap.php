<?php
/**
 * @desc 应用引导文件
 * @author Fun
 */
namespace app\components;
use yii\base\BootstrapInterface;
class Bootstrap implements BootstrapInterface
{
    /**
     * @desc 应用实例
     * @var unknown
     */
    public $app = null;
    /**
     * @desc inherit
     * @param unknown $app
     */
    public function bootstrap($app)
    {
        $this->app = $app;
        $this->_initAcl();
    }
    
    /**
     * @desc 初始化访问控制
     */
    public function _initAcl()
    {
        $session = $this->app->session;
        $identity = $this->app->user->getIdentity();
        $roleKey = AclManage::ROLE_KEY_PREFIX . '0';
        if (!empty($identity))
            $roleKey = AclManage::ROLE_KEY_PREFIX . $identity->id;
        $acl = $session->get('_acl');
        if (empty($acl) || !isset($acl[$roleKey]))
        {
            $aclManage = new \app\components\AclManage();
            $aclManage->setRoles()
                ->setResources()
                ->setPrivileges();
            $acl[$roleKey] = $aclManage;
            $session->set('_acl', $acl);
        }
    }
}
<?php
/**
 * @desc 扩展controller类型
 * @auth Fun
 */
namespace app\components;
use app\modules\users\models\RoleMenu;
class Controller extends \yii\web\Controller
{
    /**
     * @desc 是否是弹窗显示
     * @var unknown
     */
    public $isPopup = false;
    
    /**
     * @desc 请求对象
     * @var unknown
     */
    public $request = null;
    
    /**
     * @desc 角色ID
     * @var unknown
     */
    //public $roleId = null;
    
    /**
     * @desc 角色CODE
     * @var unknown
     */
    //public $roleCode = null;
    
    /**
     * @desc 角色信息
     * @var unknown
     */
    protected $_roles = null;
    
    /**
     * @desc 用户ID
     * @var unknown
     */
    protected $_userId = null;
    
    public $authenticated = false;
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\base\Object::init()
     */
    public function init()
    {
        parent::init();
        $identity = \Yii::$app->user->getIdentity();
        $roles = ['0' => 'guest'];
        $roleCode = 'guest';
        if (!empty($identity))
        {
            $roles = $identity->roles;
            //$roleCode = $identity->role->role_code;
        
            //定义常量 add by allen <2018-02-11>
            $is_admin = in_array(\app\modules\users\models\Role::ROLE_CODE_ADMIN, $roles) ? TRUE : FALSE;
            define("IS_ADMIN",$is_admin);
            //define('ROLE_ID',$roleId);
            //define('ROLE_CODE',$roleCode);
            define("USER_ID",$identity->id);
            define('USER_NAME',$identity->login_name);
            $this->_userId = $identity->id;
        }
        $this->view->params['identity'] = $identity;
        //$this->roleId = $roleId;
        //$this->roleCode = $roleCode;
        $this->_roles = $roles;
        //$this->_initAuth();
        $this->request = \Yii::$app->getRequest();
        //注册调before action事件
        $this->on(self::EVENT_BEFORE_ACTION, [$this, '_beforeActionEvent']);
        //$this->layout = '@app/views/layouts/layout';
    }
    
    protected function _beforeActionEvent($event)
    {
        $this->_initAuth();
        $this->_initUserMenu();
        $this->_checkAcl();
    }
    
    /**
     * @desc 初始化认证数据
     */
    protected function _initAuth()
    {
        $moduleName = $this->module->id;
        $controllerName = $this->id;
        $actionName = $this->action->id;
        $route = $this->route;
        //获取父moudle名称
        $routes = explode('/', $route);
        $parentModule = isset($routes[0]) ? $routes[0] : '';
        //过滤忽略认证的模块或者路由
        $authIgnores = isset(\Yii::$app->params['authIgnores']) ? \Yii::$app->params['authIgnores'] : [];
        $ignoreModules = isset($authIgnores['modules']) ? (array)$authIgnores['modules'] : [];
        $ignoreRoutes = isset($authIgnores['routes']) ? (array)$authIgnores['routes'] : [];
        if (\Yii::$app->user->getIsGuest() && (in_array($moduleName, $ignoreModules) || in_array($route, $ignoreRoutes) ||
            in_array($parentModule, $ignoreModules)))
        {
            $this->authenticated = true;
            return true;
        }
        if (\Yii::$app->user->getIsGuest() && $this->authenticated == false)
        {
            $this->redirect(['/users/user/login']);
            \Yii::$app->end();
        }
    }
    
    protected function _initUserMenu()
    {
        if ($this->authenticated)
            return true;
        $session = \Yii::$app->session;
        $menuList = $session->get('_menuList');
        //获取用户对应菜单
        if (empty($menuList) || !isset($menuList[$this->_userId]))
        {
            $menuList = [];
            if (in_array(\app\modules\users\models\Role::ROLE_CODE_ADMIN, $this->_roles))
            {
                $menuListResult = \app\modules\systems\models\Menu::getRoleMenuList(0, null);
                $menuList = array_merge($menuList, $menuListResult);
            }
            else
            {
                $menuIds = [];
                $roleIds = array_keys($this->_roles);
                $roleMenuIds = RoleMenu::getRoleMenuIds($roleIds, \app\modules\users\models\Role::ROLE_TYPE_ROLE);
                $userMenuIds = RoleMenu::getRoleMenuIds($this->_userId, \app\modules\users\models\Role::ROLE_TYPE_USER);
                $menuIds = array_unique(array_merge($roleMenuIds, $userMenuIds));
                $menuList = \app\modules\systems\models\Menu::getMenuList(0, $menuIds);
            }
            //var_dump($menuList);exit;
            if (!empty($menuList))
            {
                $sessionMenuList[$this->_userId] = $menuList;
                $session->set('_menuList', $sessionMenuList);
            }
        }
        $menuList = $session->get('_menuList');
        $this->view->params['menuList'] = isset($menuList[$this->_userId]) ? $menuList[$this->_userId] : "";
    }
    
    public function _checkAcl()
    {
        if ($this->authenticated)
            return true;
        $session = \Yii::$app->session;
        $aclSession = $session->get('_acl');
        $roleKey = AclManage::ROLE_KEY_PREFIX . $this->_userId;
        if (empty($aclSession) || !isset($aclSession[$roleKey]))
            $this->_showMessage(\Yii::t('system', 'No Privilege Access'), false, \yii\helpers\Url::home());
        $acl = $aclSession[$roleKey];
        $moduleName = $this->module->id;
        $controllerName = $this->id;
        $actionName = $this->action->id;
        $resourceName = ucfirst($moduleName) . '_' . ucfirst($controllerName) . 'Controller' . '_' . 'action' . ucfirst($actionName);
        if (!$acl->checkAccess($resourceName, null))
            $this->_showMessage(\Yii::t('system', 'No Privilege Access'), false, \yii\helpers\Url::home());
    }
    
    /**
     * @desc 渲染列表页
     * @param unknown $view
     * @param unknown $params
     * @return \yii\base\string
     */
    public function renderList($view, $params)
    {
        $dataProvider = isset($params['dataProvider']) ? $params['dataProvider'] : null;
        if (\Yii::$app->request->getIsAjax())
        {
            $jsonData = [
                'total' => 0,
                'rows' => [],
            ];
            if (isset($params['dataProvider']))
            {
                $dataProvider = $params['dataProvider'];
                $totalCount = $dataProvider->getTotalCount();
                $jsonData['total'] = $totalCount;
                $page = $dataProvider->getPagination()->getPage();
                if ($page <= 0) {
                    $page = 1;
                }
                $jsonData['pageNumber'] = $page;
                $jsonData['pageSize'] = $dataProvider->getPagination()->getPageSize();
                if (isset($params['tagList'])) {
                    $jsonData['tagList'] = $params['tagList'];
                }
                if (isset($params['siteList'])) {
                    $jsonData['siteList'] = $params['siteList'];
                }
                if  (isset($params['account_email'])) {
                    $jsonData['account_email'] = $params['account_email'];
                }
                $models = $dataProvider->getModels();
                foreach ($models as $model) {
                    $jsonData['rows'][] = $model->toArray();
                }
            }
            echo \yii\helpers\Json::encode($jsonData);
            \Yii::$app->end();
        }
        return $this->render($view, $params);
    }
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\base\Controller::render()
     */
    public function render($view, $params = [])
    {
        if ($this->isPopup)
        {
            $this->layout = '@app/views/layouts/layout_popup';
        }
        $content = $this->getView()->render($view, $params, $this);
        return $this->renderContent($content);
    }
    
    /**
     * @desc 显示前端信息
     * @param unknown $message
     * @param string $success
     * @param string $url
     * @param string $refresh
     * @param unknown $extraResult
     * @param string $extraJs
     */
    public function _showMessage($message, $success = true, $url = null, $refresh = false, 
        $extraResult = array(), $extraJs = null, $closePopup = true, $alertormsg = 'alert', $top = false)
    {
        $result = array();
        $result['code'] = '0';
        $result['refresh'] = $refresh;
        $result['closePopup'] = $closePopup;
        if ($success)
            $result['code'] = '200';
        if (!empty($url))
            $result['url'] = $url;
        $result['message'] = $message;
        if (!empty($extraResult))
            $result['data'] = $extraResult;
        if (!empty($extraJs))
            $result['js'] = $extraJs;
        if (!empty($top))
            $result['top'] = $top;
        $result['alertormsg'] = $alertormsg;
        //$result = array_merge($result, $extraResult);
        if ($this->request->getIsAjax()) {
            echo \yii\helpers\Json::encode($result);
            \Yii::$app->end();
        }
        $js = '<script type="text/javascript">' . "\n";
        if($alertormsg == 'msg')
        {
            $js .='window.layer.msg("' . $message . '", ' . "\n";
        }
        else
        {
            $js .='window.layer.alert("' . $message . '", ' . "\n";
        }
        $js .= '{icon: ' . ($success ? '1' : '5') . '}, ' . "\n";
        $js .= 'function(index){' . "\n";
        if (!is_null($url))
            $js .= 'window.location.replace("' . $url . '");';
        $js .= 'window.layer.close(index);' . "\n";
        $js .= $extraJs . "\n";
        $js .= '});' . "\n";
        $js .=	'</script>' . "\n";
        $html =
        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
		<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
		<title></title>
		<script type="text/javascript" language="JavaScript" src="' . \yii\helpers\Url::base() . '/js/jquery-1.9.1.min.js"></script>
		<script type="text/javascript" language="JavaScript" src="' . \yii\helpers\Url::base() . '/js/layer/layer.js"></script>
		</head>
		<body>
		' . $js . '
		</body>
		</html>';
        echo $html;
        exit;
    }
}
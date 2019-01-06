<?php
/**
 * @desc　用户控制器
 * @author Fun
 */
namespace app\modules\users\controllers;

use app\components\Controller;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\RoleResource;
use app\modules\users\models\Role;
use app\modules\systems\models\Menu;
use app\modules\users\models\User;
use yii\helpers\Url;

class RoleController extends Controller
{
    /**
     * @desc 角色列表
     */
    public function actionList()
    {
        $model = new Role();
        $roleTree = $model->getRoleTree();
        return $this->renderList('list', [
            'model' => $model,
            'roleTree' => $roleTree,
        ]);
    }

    /**
     * @desc 新增记录
     */
    public function actionAdd()
    {
        $this->isPopup = true;
        $model = new Role();
        if ($this->request->getIsAjax()) {
            $model->load($this->request->post());
            $model->setAttribute('platform_code', implode(',', $model->platform_code));
            if ($model->validate()) {
                if ($model->save()) {
                    $extraJs = 'top.window.location.replace("' . Url::toRoute('/users/role/list') . '");';
                    $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
                } else {
                    $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
                }
            } else {
                $errors = $model->getErrors();
                $error = array_shift($errors);
                $this->_showMessage(\Yii::t('system', $error[0]), false);
            }
        }
        $roleList = [0 => \Yii::t('system', 'None')];
        $model->getRoleTreeList(0, 1, true, $roleList);
        $platform = Platform::getPlatformAsArray();
        return $this->render('add', [
            'model' => $model,
            'roleList' => $roleList,
            'platform' => $platform,
        ]);
    }

    /**
     * @desc 删除记录
     */
    /*     public function actionDelete()
        {
            $id = (int)$this->request->getQueryParam('id');
            if (empty($id))
                $this->_showMessage(\Yii::t('system', 'Invalid Params'), false);
            $model = new User();
            $flag = $model->deleteById($id);
            if ($flag)
                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                    'top.refreshTable("' . \yii\helpers\Url::toRoute('/users/user/list') . '");');
            else
                $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
        } */

    /**
     * @desc 编辑记录
     */
    public function actionEdit()
    {
        $this->isPopup = true;
        $id = (int)$this->request->getQueryParam('id');

        if (empty($id)) {
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false, null, false, null, "top.layer.closeAll('iframe');");
        }

        $model = Role::findById($id);
        if (empty($model)) {
            $this->_showMessage(\Yii::t('system', 'Not Found Record'), false, null, false, null, "top.layer.closeAll('iframe');");
        }

        if ($this->request->getIsAjax()) {
            $model->load($this->request->post());
            $model->setAttribute('platform_code', implode(',', $model->platform_code));
            if ($model->validate()) {
                if ($model->parent_id == $id) {
                    $this->_showMessage(\Yii::t('role', 'Parent Role Is Self'), false, null, false, null, "top.layer.closeAll('iframe');");
                }
                if ($model->updateRole()) {
                    $extraJs = 'top.window.location.replace("' . Url::toRoute('/users/role/list') . '");';
                    $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
                } else {
                    $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
                }
            } else {
                $errors = $model->getErrors();
                $error = array_pop($errors);
                $this->_showMessage(\Yii::t('system', $error[0]), false);
            }
        }

        $roleList = [0 => \Yii::t('system', 'None')];
        $model->getRoleTreeList(0, 1, true, $roleList);
        $platform = Platform::getPlatformAsArray();
        return $this->render('edit', [
            'model' => $model,
            'roleList' => $roleList,
            'platform' => $platform,
        ]);
    }

    /**
     * @desc 设置权限
     */
    public function actionSetpower()
    {
        $id = (int)$this->request->getQueryParam('id');
        if (empty($id)) {
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false);
        }

        $modelRole = Role::findById($id);
        if (empty($modelRole)) {
            $this->_showMessage(\Yii::t('system', 'Not Found Record'), false, Url::toRoute('/users/role/list'));
        }

        if ($this->request->getIsAjax()) {
            $menuIds = $this->request->getBodyParam('menu_ids');
            $sourceIds = $this->request->getBodyParam('source_ids');
            if (empty($menuIds)) {
                $menuIds = [];
            }
            $menuIds = array_filter($menuIds);
            $flag = Role::refreshRoleMenu($id, $menuIds, $sourceIds);
            if (!$flag) {
                $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
            } else {
                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true);
            }
        }
        $modelMenu = new Menu();
        $childs = Role::getRoleMenuList(2);
        $menuList = $modelMenu->getMenuTree();
        $selectedMenuList = Role::getRoleMenuList($id);
        $selectedMenuIds = [];
        if (!empty($selectedMenuList)) {
            foreach ($selectedMenuList as $row) {
                $selectedMenuIds[] = $row['id'];
            }
        }
        $selectedSourceList = RoleResource::find()->where(['role_id' => $id])->asArray()->all();
        $selectedSourcesIds = [];
        if (!empty($selectedSourceList)) {
            foreach ($selectedSourceList as $row) {
                $selectedSourcesIds[] = $row['resource_id'];
            }
        }
        return $this->render('setpower', [
            'menuList' => $menuList,
            'model' => $modelRole,
            'selectedMenuIds' => $selectedMenuIds,
            'selectedSourcesIds' => $selectedSourcesIds
        ]);
    }

    /**
     * @desc 给用户设置权限
     */
    public function actionSetpowerforuser()
    {
        $this->isPopup = true;
        $id = (int)$this->request->getQueryParam('id');
        if (empty($id)) {
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false);
        }
        $modelRole = User::findById($id);
        if (empty($modelRole)) {
            $this->_showMessage(\Yii::t('system', 'Not Found Record'), false, Url::toRoute('/users/role/list'));
        }

        if ($this->request->getIsAjax()) {
            $menuIds = $this->request->getBodyParam('menu_ids');
            $sourceIds = $this->request->getBodyParam('source_ids');
            if (empty($menuIds)) {
                $menuIds = [];
            }
            $menuIds = array_filter($menuIds);
            $flag = Role::refreshRoleMenu($id, $menuIds, $sourceIds, Role::ROLE_TYPE_USER);
            if (!$flag) {
                $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
            } else {
                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true);
            }
        }
        $modelMenu = new Menu();
        $menuList = $modelMenu->getMenuTree();
        $selectedMenuList = Role::getRoleMenuList($id, null, Role::ROLE_TYPE_USER);
        $selectedMenuIds = [];
        if (!empty($selectedMenuList)) {
            foreach ($selectedMenuList as $row) {
                $selectedMenuIds[] = $row['id'];
            }
        }
        $selectedSourceList = RoleResource::find()->where(['role_id' => $id, 'type' => Role::ROLE_TYPE_USER])->asArray()->all();
        $selectedSourcesIds = [];
        if (!empty($selectedSourceList)) {
            foreach ($selectedSourceList as $row) {
                $selectedSourcesIds[] = $row['resource_id'];
            }
        }
        return $this->render('setpowerforuser', [
            'menuList' => $menuList,
            'model' => $modelRole,
            'selectedMenuIds' => $selectedMenuIds,
            'selectedSourcesIds' => $selectedSourcesIds
        ]);
    }
}
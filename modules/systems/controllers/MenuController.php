<?php
/**
 * @desc 平台控制器
 * @author Fun
 */
namespace app\modules\systems\controllers;
use app\components\Controller;
use app\modules\systems\models\Menu;
use app\modules\systems\models\Resource;
use app\modules\systems\models\MenuResource;
class MenuController extends Controller
{
    /**
     * @desc 平台列表
     * @return \yii\base\string
     */
    public function actionList()
    {
        $model = new Menu();
        $menuTree = $model->getMenuTree();
        return $this->renderList('list', [
            'model' => $model,
            'menuTree' => $menuTree,
        ]);
    }
    
    /**
     * @desc 新增平台
     * @return \yii\base\string
     */
    public function actionAdd()
    {
        $this->isPopup = true;
        $model = new Menu();
        if ($this->request->getIsAjax())
        {
//            var_dump($this->request->post());
//            exit;
            $model->load($this->request->post());
            if ($model->validate())
            {
                if ($model->save())
                    $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, \yii\helpers\Url::toRoute('/systems/menu/list'));   
                else 
                    $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
            } else {
                $errors = $model->getErrors();
                $error = array_pop($errors);
                $this->_showMessage(\Yii::t('system', $error[0]), false);
            }
        }
        $menuList = [0 => \Yii::t('menu', 'Top Menu')];
        $model->getMenuTreeList(0, 1, true, $menuList);
        return $this->render('add', [
            'model' => $model,
            'menuList' => $menuList,
        ]);
    }
    
    /**
     * @desc 批量删除记录
     */
    public function actionBatchdelete()
    {
        $model = new Menu();
        if ($this->request->getIsAjax())
        {
            $ids = $this->request->getBodyParam('ids');
            if (empty($ids))
                $this->_showMessage(\Yii::t('system', 'Not Selected Data'), false);
            $ids = array_filter($ids);
            $flag = $model->deleteByIds($ids);
            if ($flag)
                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, 
                    'top.refreshTable("' . \yii\helpers\Url::toRoute('/accounts/platform/list') . '");');
            else
                $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
        }
    }
    
    /**
     * @desc 删除记录
     */
    public function actionDelete()
    {
        $id = (int)$this->request->getQueryParam('id');
        if (empty($id))
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false);
        $model = new Menu();
        $flag = $model->deleteMenu($id);
        if ($flag)
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, \yii\helpers\Url::toRoute('/systems/menu/list'));
        else
            $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
    }
    
    /**
     * @desc 编辑记录
     * @return \yii\base\string
     */
    public function actionEdit()
    {
        $this->isPopup = true;
        $id = (int)$this->request->getQueryParam('id');
        if (empty($id))
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false, null, false, null, 
                "top.layer.closeAll('iframe');");
        $model = Menu::findById($id);
        if (empty($model))
            $this->_showMessage(\Yii::t('system', 'Not Found Record'), false, null, false, null, 
                "top.layer.closeAll('iframe');");
        if ($this->request->getIsAjax())
        {   
            
            $model->load($this->request->post());
            if ($model->validate())
            {
                if ($model->parent_id == $id)
                    $this->_showMessage(\Yii::t('system', 'Parent Menu Is Self'), false, null, false, null, 
                "top.layer.closeAll('iframe');");
                if ($model->updateMenu())
                    $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, \yii\helpers\Url::toRoute('/systems/menu/list'));
                else 
                    $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
            } else {
                $errors = $model->getErrors();
                $error = array_pop($errors);
                $this->_showMessage(\Yii::t('system', $error[0]), false);
            }
        }
        $menuList = [0 => \Yii::t('menu', 'Top Menu')];
        $model->getMenuTreeList(0, 1, true, $menuList);
        return $this->render('edit', [
            'model' => $model,
            'menuList' => $menuList,
        ]);
    }
    
    /**
     * @desc 设置权限
     * @throws \Exception
     * @return \yii\base\string
     */
    public function actionSetresource()
    {
        $this->isPopup = true;
        $id = $this->request->getQueryParam('id');
        if (empty($id))
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false, null, false, null, 
                "top.layer.closeAll('iframe');");
        $selectedResourceIds = MenuResource::getMenuResourceIds($id);
        if (empty($selectedResourceIds))
            $selectedResourceIds = [];
        if ($this->request->getIsAjax())
        {
            $modelMenuResource = new MenuResource();
            $dbTransaction = $modelMenuResource->getDb()->beginTransaction();
            try 
            {
                $resourceIds = $this->request->getBodyParam('resource_ids');
                $resourceIds = array_filter($resourceIds);
                $insertIds = [];
                $deleteIds = [];
                $insertIds = array_diff($resourceIds, $selectedResourceIds);
                $deleteIds = array_diff($selectedResourceIds, $resourceIds);
                if (!empty($insertIds))
                {
                    $flag = $modelMenuResource->insertMenuResource($id, $insertIds);
                    if (!$flag)
                        throw new \Exception(\Yii::t('menu', 'Operate Failed'));
                }
                if (!empty($deleteIds))
                {
                    $flag = $modelMenuResource->deleteMenuResource($id, $deleteIds);
                    if (!$flag)
                        throw new \Exception(\Yii::t('menu', 'Operate Failed'));
                }
                $dbTransaction->commit();
                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, \yii\helpers\Url::toRoute('/systems/menu/list'));
            }
            catch (\Exception $e)
            {
                $dbTransaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
            }
        }
        $modelResource = new Resource();
        $resourceList = $modelResource->getResourceRecurisive();
        //print_r($resourceList);exit;
        return $this->render('setresource',[
            'model' => $modelResource,
            'resourceList' => $resourceList,
            'selectedResourceIds' => $selectedResourceIds,
        ]);
    }
}
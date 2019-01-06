<?php
/**
 * @desc 平台控制器
 * @author Fun
 */
namespace app\modules\accounts\controllers;
use app\components\Controller;
use app\modules\accounts\models\Platform;
class PlatformController extends Controller
{
    /**
     * @desc 平台列表
     * @return \yii\base\string
     */
    public function actionList()
    {
        $model = new Platform();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);
        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }
    
    /**
     * @desc 新增平台
     * @return \yii\base\string
     */
    public function actionAdd()
    {
        $this->isPopup = true;
        $model = new Platform();
        if ($this->request->getIsAjax())
        {
            $model->load($this->request->post());
            if ($model->validate())
            {
                if ($model->save())
                    $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, 
                        'top.refreshTable("' . \yii\helpers\Url::toRoute('/accounts/platform/list') . '");');   
                else 
                    $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
            } else {
                $errors = $model->getErrors();
                $error = array_pop($errors);
                $this->_showMessage(\Yii::t('system', $error[0]), false);
            }
        }
        return $this->render('add', [
            'model' => $model,
        ]);
    }
    
    /**
     * @desc 批量删除记录
     */
    public function actionBatchdelete()
    {
        $model = new Platform();
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
        $model = new Platform();
        $flag = $model->deleteById($id);
        if ($flag)
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                'top.refreshTable("' . \yii\helpers\Url::toRoute('/accounts/platform/list') . '");');
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
        $model = Platform::findById($id);
        if (empty($model))
            $this->_showMessage(\Yii::t('system', 'Not Found Record'), false, null, false, null, 
                "top.layer.closeAll('iframe');");
        if ($this->request->getIsAjax())
        {
            $model->load($this->request->post());
            if ($model->validate())
            {
                if ($model->save())
                    $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, 
                        'top.refreshTable("' . \yii\helpers\Url::toRoute('/accounts/platform/list') . '");');   
                else 
                    $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
            } else {
                $errors = $model->getErrors();
                $error = array_pop($errors);
                $this->_showMessage(\Yii::t('system', $error[0]), false);
            }
        }
        
        return $this->render('edit', [
            'model' => $model,
        ]);
    }
}
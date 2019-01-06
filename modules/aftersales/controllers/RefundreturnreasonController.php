<?php

namespace app\modules\aftersales\controllers;

use Yii;
use app\modules\aftersales\models\RefundReturnReason;
//use app\modules\shouhou\models\RefundReturnReasonSearch;
use yii\web\NotFoundHttpException;
use app\components\Controller;
//use yii\filters\VerbFilter;

/**
 * RefundreturnreasonController implements the CRUD actions for RefundReturnReason model.
 */
class RefundreturnreasonController extends Controller
{
    
    /**
     * Lists all RefundReturnReason models.
     * @return mixed
     */
    public function actionIndex()
    {   
        
        $model = new RefundReturnReason();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);

        return $this->renderList('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @desc 新增标签
     * @return \yii\base\string
     */
    public function actionAdd()
    {
        $this->isPopup = true;
        $model = new RefundReturnReason();

        if ($this->request->getIsAjax()) {

            //数据验证以及保存数据
            $this->validateAndSaveData($this->request->post(),$model);

            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/aftersales/refundreturnreason/index') . '");';
            $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
        }
        return $this->render('add', [
            'model' => $model,
        ]);
    }
    /**
     * 验证数据以及保存数据
     * @param array  $postData 前端提交的post数据
     * @param object $model 模型对象
     */
    protected function validateAndSaveData($postData,$model)
    {   
        //模型加载数据
        $model->load($postData);

        //数据验证失败
        if (!$model->validate()) {
            $this->_showMessage(current(current($model->getErrors())), false);
        }

        //保存失败
        if (!$model->save()) {
            $this->_showMessage(\Yii::t('tag', 'Operate Failed'), false);
        }
    }
    /**
     * @desc 编辑记录
     * @return \yii\base\string
     */
    public function actionEdit()
    {
        $this->isPopup = true;
        $id = (int)$this->request->getQueryParam('id');
        
        //没有勾选标签
        if (empty($id)) {
            $this->_showMessage(\Yii::t('tag', 'Invalid Params'), false, null, false, null,"top.layer.closeAll('iframe');");
        }

        $model = RefundReturnReason::findById($id);
        
        //模型不合法
        if (empty($model)) {
            $this->_showMessage(\Yii::t('tag', 'Not Found Record'), false, null, false, null,"top.layer.closeAll('iframe');");
        }

        if ($this->request->getIsAjax()) {

            //$model->modfiy_time = date('Y-m-d H:i:s',time());
            //数据验证以及保存数据
            $this->validateAndSaveData($this->request->post(),$model);
            
            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/aftersales/refundreturnreason/index') . '");';
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,$refreshUrl);
        }

        return $this->render('edit', [
            'model' => $model,
        ]);
    }

    /**
     * @desc 删除记录
     */
    public function actionDelete()
    {
        $id = (int)$this->request->getQueryParam('id');

        //没有勾选标签
        if (empty($id)) {
            $this->_showMessage(\Yii::t('tag', 'Invalid Params'), false);
        }

        $model = new RefundReturnReason();
        $flag = $model->deleteById($id);

        //删除失败
        if (!$flag) {
            $this->_showMessage(\Yii::t('tag', 'Operate Failed'), false);
        }

        $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/aftersales/refundreturnreason/index') . '");';
        $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
    }

    /**
     * @desc 批量删除记录
     */
    public function actionBatchdelete()
    {
        if ($this->request->getIsAjax()) 
        {
            $model = new RefundReturnReason();
            $ids = $this->request->getBodyParam('ids');

            //没有选取标签
            if (empty($ids)) {
                $this->_showMessage(\Yii::t('tag', 'Not Selected Data'), false);
            }

            $ids = array_filter($ids);
            $result = $model->deleteByIds($ids);

            //删除失败
            if (!$result) {
                $this->_showMessage(\Yii::t('tag', 'Operate Failed'), false);
            }

            //删除成功
            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/aftersales/refundreturnreason/index') . '");';
            $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
        }
    }
}

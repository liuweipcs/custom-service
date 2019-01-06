<?php

namespace app\modules\accounts\controllers;
use app\components\Controller;
use app\modules\accounts\models\EbayCaseRefund;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
class EbaycaserefundController extends Controller
{
    public function actionList()
    {
        $model = new EbayCaseRefund();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);

        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
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

        $model = EbayCaseRefund::findById($id);

        //模型不合法
        if (empty($model)) {
            $this->_showMessage(\Yii::t('tag', 'Not Found Record'), false, null, false, null,"top.layer.closeAll('iframe');");
        }
        $accountInfo = Account::findOne($model->account_id);

        if ($this->request->getIsAjax()) {

            $model->modify_time = date('Y-m-d H:i:s',time());
            //数据验证以及保存数据
            $this->validateAndSaveData($this->request->post(),$model);
            
            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/accounts/ebaycaserefund/list') . '");';
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,$refreshUrl);
        }

        return $this->render('edit', [
            'model' => $model,
            'accountInfo' => $accountInfo,
        ]);
    }
    
    /**
     * @desc 修改为有效
     */
    public function actionChangetorefund()
    {
        $id = (int)$this->request->getQueryParam('id');

        //没有勾选账号
        if (empty($id)) {
            $this->_showMessage(\Yii::t('tag', 'Invalid Params'), false);
        }
        $accountInfo = Account::findOne($id);

        if($accountInfo->status == Account::STATUS_INVALID)
            $this->_showMessage(\Yii::t('system','帐号已经被禁用'));

        $model = EbayCaseRefund::findOne($id);
        $model->is_refund = 1;
        $flag = $model->save();

        //修改失败
        if (!$flag) {
            $this->_showMessage(\Yii::t('tag', 'Operate Failed'), false);
        }

        $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/accounts/ebaycaserefund/list') . '");';
        $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
    }

    /**
     * @desc 批量修改为自动退款
     */
    public function actionBatchchangetorefund()
    {
        $model = new EbayCaseRefund();
        if ($this->request->getIsAjax()) {
            $ids = $this->request->getBodyParam('ids');
//var_dump($ids);exit;
            //没有选取标签
            if (empty($ids)) {
                $this->_showMessage(\Yii::t('tag', 'Not Selected Data'), false);
            }
            $models = EbayCaseRefund::find()->where(['in','id',$ids])->all();

            $result = EbayCaseRefund::updateAll(['is_refund'=> EbayCaseRefund::STATUS_REFUND_YES],['in','id',$ids]);

            if (!$result) {
                $this->_showMessage(\Yii::t('tag', 'Operate Failed'), false);
            }

            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/accounts/ebaycaserefund/list') . '");';
            $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
        }
    }

    /**
     * @desc 批量修改为不自动退款
     */
    public function actionBatchchangetonotrefund()
    {
        $model = new EbayCaseRefund();
        if ($this->request->getIsAjax()) {
            $ids = $this->request->getBodyParam('ids');
//var_dump($ids);exit;
            //没有选取标签
            if (empty($ids)) {
                $this->_showMessage(\Yii::t('tag', 'Not Selected Data'), false);
            }
            $models = EbayCaseRefund::find()->where(['in','id',$ids])->all();

            $result = EbayCaseRefund::updateAll(['is_refund'=> EbayCaseRefund::STATUS_REFUND_NO],['in','id',$ids]);

            if (!$result) {
                $this->_showMessage(\Yii::t('tag', 'Operate Failed'), false);
            }

            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/accounts/ebaycaserefund/list') . '");';
            $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
        }
    }

}

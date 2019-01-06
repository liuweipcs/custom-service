<?php

namespace app\modules\systems\controllers;
use app\components\Controller;
use app\modules\systems\models\RefundAccount;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\AccountRefundaccountRelation;
class RefundaccountController extends Controller
{
    public function actionList()
    {
        $model = new RefundAccount();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);

        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }
    /**
     * @desc 新增账号
     * @return \yii\base\string
     */
    public function actionAdd()
    {
        $this->isPopup = true;
        $model = new RefundAccount();

        if ($this->request->getIsAjax()) {
            //数据验证以及保存数据
            $accountData = $this->request->post();
            foreach ($accountData['RefundAccount'] as $key => $value) {
                $accountData['RefundAccount'][$key] = trim($value);
            }
            $this->validateAndSaveData($this->request->post(),$model);

            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/refundaccount/list') . '");';
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
        
        //没有勾选账号
        if (empty($id)) {
            $this->_showMessage(\Yii::t('tag', 'Invalid Params'), false, null, false, null,"top.layer.closeAll('iframe');");
        }

        $model = RefundAccount::findById($id);
        
        //模型不合法
        if (empty($model)) {
            $this->_showMessage(\Yii::t('tag', 'Not Found Record'), false, null, false, null,"top.layer.closeAll('iframe');");
        }

        if ($this->request->getIsAjax()) {

            $model->modify_time = date('Y-m-d H:i:s',time());
            //数据验证以及保存数据
            $accountData = $this->request->post();
            foreach ($accountData['RefundAccount'] as $key => $value) {
                $accountData['RefundAccount'][$key] = trim($value);
            }
            $this->validateAndSaveData($accountData,$model);
            
            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/refundaccount/list') . '");';
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

        //没有勾选账号
        if (empty($id)) {
            $this->_showMessage(\Yii::t('tag', 'Invalid Params'), false);
        }
        
        //勾选的账号被绑定了ebay账户不允许删除
        if (!RefundAccount::isAlowDeleteById($id)) {
           $this->_showMessage(\Yii::t('tag', 'refundAccount have ebay account,forbidden delete'), false);
        }

        $model = new RefundAccount();
        $flag = $model->deleteById($id);

        //删除失败
        if (!$flag) {
            $this->_showMessage(\Yii::t('tag', 'Operate Failed'), false);
        }

        $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/refundaccount/list') . '");';
        $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
    }

    /**
     * @desc 批量删除记录
     */
    public function actionBatchdelete()
    {
        $model = new RefundAccount();
        if ($this->request->getIsAjax()) {
            $ids = $this->request->getBodyParam('ids');

            //没有选取标签
            if (empty($ids)) {
                $this->_showMessage(\Yii::t('tag', 'Not Selected Data'), false);
            }

            $ids = RefundAccount::getAllowDeleteId($ids);

            //没有允许删除的标签
            if (empty($ids)) {
                $this->_showMessage(\Yii::t('tag', 'no have ids allow delete'), false);
            }

            $ids = array_filter($ids);
            $result = $model->deleteByIds($ids);

            //删除失败
            if (!$result) {
                $this->_showMessage(\Yii::t('tag', 'Operate Failed'), false);
            }

            //删除成功
            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/refundaccount/list') . '");';
            $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
        }
    }
    /** eaby账号列表 提供绑定退票账号管理 **/
    public function actionEbaylist()
    {
        $model = new Account();
        $params = \Yii::$app->request->getBodyParams();
        $params['platform_code'] = Platform::PLATFORM_CODE_EB;
        $dataProvider = $model->searchList($params);
        return $this->renderList('listebay', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /** ebay 账户绑定退票账号 */
    public function actionPunitive()
    {
        $this->isPopup = true;
        $id = (int)$this->request->getQueryParam('id');
        $account_model = Account::findById($id);

        if (empty($account_model)) {
            $this->_showMessage(\Yii::t('tag', 'Not Found Record'), false, null, false, null,"top.layer.closeAll('iframe');");
        }

        if ($this->request->getIsAjax()) {

            $post_data = $this->request->post();
            
            $relation_model = AccountRefundaccountRelation::findOne([
                'account_id' => $account_model->id,
            ]);
            
            if (empty($relation_model)) {
                $relation_model = new AccountRefundaccountRelation();
                $relation_model->account_id = $account_model->id;
                $relation_model->old_account_id = $account_model->old_account_id;
                $relation_model->refund_account_id = $post_data['refund_account_id'];
            } else {
                $relation_model->refund_account_id = $post_data['refund_account_id'];
            }
            
            $result = $relation_model->save();

            if (!$result) {
                $this->_showMessage(\Yii::t('tag', 'relation create fail'), false, null, false, null,"top.layer.closeAll('iframe');");
            }

            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/refundaccount/ebaylist') . '");';
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,$refreshUrl);
        }

        return $this->render('punitive', [
            'refund_account_data' => RefundAccount::getList(),
            'relation_refund_account_id' => AccountRefundaccountRelation::getRefundAccountId($account_model->id),
        ]);
    }
  
}

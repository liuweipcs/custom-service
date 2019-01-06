<?php
/**
 * @desc 账号控制器
 * @author Fun
 */

namespace app\modules\accounts\controllers;

use Yii;
use app\components\Controller;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\UserAccount;
use app\modules\accounts\models\Platform;
use app\modules\orders\models\Logistic;
use app\modules\accounts\models\EbayCaseRefund;
use app\modules\systems\models\Tag;
use app\modules\mails\models\MailTemplate;
use app\modules\systems\models\Rule;
use app\modules\accounts\models\app\modules\accounts\models;

class AccountController extends Controller
{
    /**
     * @desc 列表
     * @return \yii\base\string
     */
    public function actionList()
    {
        $model = new Account();
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
        $model = new Account();
        if ($this->request->getIsAjax()) {

            $data = $this->request->post();
            $account = trim($data['Account']['account_name']);
            $platform_code = $data['Account']['platform_code'];
            $redata = Account::findAccountOne($account, $platform_code);
            if (!empty($redata))
                $this->_showMessage("帐号已存在，请重新命名", false);

            if (isset($data['Account']['site_code']))
                $model->setScenario('Amazon');
            else
                $model->setScenario('default');

            $model->load($data);
            if ($data['Account']['platform_code'] == Platform::PLATFORM_CODE_EB)
                $transactions = Account::getDb()->beginTransaction();
            if ($model->validate()) {
                //var_dump($model);exit;
                $flag = $model->save($data);
                if ($flag) {
                    $erpAccountModel = Account::getAccountFromErp($data['Account']['platform_code'], $data['Account']['account_name']);
                    if ($erpAccountModel) {
                        $model->old_account_id = $erpAccountModel->id;
                        $model->user_token = isset($erpAccountModel->user_token) ? $erpAccountModel->user_token : '';
                        $flag = $model->save();
                    }

                } else
                    $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);

                if ($flag && $data['Account']['platform_code'] == Platform::PLATFORM_CODE_EB) {
                    $caseRefundModel = new EbayCaseRefund();
                    $caseRefundModel->account_id = $model->id;
                    if ($caseRefundModel->save()) {
                        $transactions->commit();
                        $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                            'top.refreshTable("' . \yii\helpers\Url::toRoute('/accounts/account/list') . '");');
                    } else {
                        $transactions->rollBack();
                        $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
                    }
                } else {
                    $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                        'top.refreshTable("' . \yii\helpers\Url::toRoute('/accounts/account/list') . '");');
                }

            } else {
                $errors = $model->getErrors();
                $error = array_pop($errors);
                $this->_showMessage(\Yii::t('system', $error[0]), false);
            }
        }
        $platformList = Platform::getPlatformAsArray();
        $siteList = Account::getPlatformSite();
        return $this->render('add', [
            'model' => $model,
            'platformList' => $platformList,
            'siteList' => $siteList
        ]);
    }

    /**
     * @desc 批量删除记录
     */
    public function actionBatchdelete()
    {
        $model = new Account();
        if ($this->request->getIsAjax()) {
            $ids = $this->request->getBodyParam('ids');
            if (empty($ids))
                $this->_showMessage(\Yii::t('system', 'Not Selected Data'), false);
            $ids = array_filter($ids);
            $flag = $model->deleteByIds($ids);
            if ($flag)
                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                    'top.refreshTable("' . \yii\helpers\Url::toRoute('/accounts/account/list') . '");');
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
        $model = new Account();
        $flag = $model->deleteById($id);

        if ($flag) {
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                'top.refreshTable("' . \yii\helpers\Url::toRoute('/accounts/account/list') . '");');
        } else
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
        if (empty($id)) {
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false, null, false, null, "top.layer.closeAll('iframe');");
        }
        $model = Account::findById($id);
        if (empty($model)) {
            $this->_showMessage(\Yii::t('system', 'Not Found Record'), false, null, false, null, "top.layer.closeAll('iframe');");
        }
        if ($this->request->getIsAjax()) {
            $model->load($this->request->post());
            $account = $model->account_name;
            $platform_code = $model->platform_code;
            $redata = Account::find()->where(['account_name' => $account, 'platform_code' => $platform_code])->andWhere(['<>', 'id', $id])->one();
            if (!empty($redata)) {
                $this->_showMessage("帐号已存在，请重新命名", false);
            }
            if (isset($this->request->post('Account')['site'])) {
                $model->site = $this->request->post('Account')['site'];
            }
            if (isset($this->request->post('Account')['seller_id'])) {
                $model->seller_id = $this->request->post('Account')['seller_id'];
            }
            if ($model->validate()) {
                if ($model->save()) {
                    $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                        'top.refreshTable("' . \yii\helpers\Url::toRoute('/accounts/account/list') . '");');
                } else {
                    $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
                }
            } else {
                $errors = $model->getErrors();
                $error = array_pop($errors);
                $this->_showMessage(\Yii::t('system', $error[0]), false);
            }
        }
        $platformList = Platform::getPlatformAsArray();
        $siteList = Account::getPlatformSite();
        return $this->render('edit', [
            'model' => $model,
            'platformList' => $platformList,
            'siteList' => $siteList
        ]);
    }

    /**
     * 通过平台code获取该平台下的所有账号数据
     * @param string $platform_code 平台code
     * @param string $type 取数据的标示1标签数据2模板数据
     */
    public function actionGetaccount($platform_code, $type)
    {
        $result['account_info'] = Account::getAccountByPlatformCode($platform_code);
        $result['relation_data'] = array();
        $result['buyer_option_logistics'] = Logistic::getBuyerOptionLogistics($platform_code);

        //取标签数据
        if ($type == Rule::RULE_TYPE_TAG) {
            $result['relation_data'] = Tag::getTagAsArray($platform_code);
        }

        if ($type == Rule::RULE_TYPE_AUTO_ANSWER) {
            $result['relation_data'] = MailTemplate::getOrderTemplateDataAsArray($platform_code);
        }

        echo json_encode($result);
    }

    /**
     * 根据平台返回对应的账号或者站点信息
     * @param type $platform_code
     * @author allen <2018-06-14>
     */
    public function actionGetaccoutorsite()
    {
        $params = $this->request->post();
        $platform_code = isset($params['platform_code']) ? $params['platform_code'] : '';
        $type = isset($params['type']) ? $params['type'] : '';
        $data = UserAccount::getAccoutOrSite($platform_code, $type);
        echo json_encode($data);
    }
}
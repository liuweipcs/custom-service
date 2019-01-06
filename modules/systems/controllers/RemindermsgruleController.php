<?php

namespace app\modules\systems\controllers;

use Yii;
use app\modules\accounts\models\Platform;
use app\components\Controller;
use app\modules\systems\models\ReminderMsgRule;
use yii\db\Query;
use yii\helpers\Url;

/**
 * 自动催付订单规则管理
 */
class RemindermsgruleController extends Controller
{

    /**
     * 自动催付订单规则列表
     */
    public function actionList()
    {
        $model = new ReminderMsgRule();
        //获取查询参数
        $params = Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);

        return $this->renderList('list', [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }

    /**
     * 添加自动催付订单规则
     */
    public function actionAdd()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            //装载数据
            $model = new ReminderMsgRule();
            $model->load($data, '');
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Yii::$app->request->getUrl());
            }
            if ($model->account_type == 'custom' && empty($model->account_ids)) {
                $this->_showMessage('指定账号必须选择账号', false);
            }
            //设置模型数据
            if ($model->account_type == 'custom') {
                $shortNameList = $this->getaccountshortnamelist($model->platform_code, $model->account_ids);
                $shortNameList = array_column($shortNameList, 'account_name', 'id');
                $model->account_short_names = implode(',', $shortNameList);
                $model->account_ids = implode(',', $model->account_ids);
            }
            $model->create_by = Yii::$app->user->identity->login_name;
            $model->create_time = date('Y-m-d H:i:s');
            $model->update_by = Yii::$app->user->identity->login_name;
            $model->update_time = date('Y-m-d H:i:s');

            //保存数据
            if (!$model->save()) {
                $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
            } else {
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/systems/remindermsgrule/list') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }
        } else {
            $this->isPopup = true;
            $platformList = ReminderMsgRule::getPlatformList();
            return $this->render('add', [
                'platformList' => $platformList,
            ]);
        }
    }

    /**
     * 修改催付订单规则
     */
    public function actionEdit()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();

            $model = ReminderMsgRule::findOne($data['id']);
            if (empty($model)) {
                $this->_showMessage('没有找到规则信息', false);
            }
            //装载数据
            $model->load($data, '');
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Url::toRoute(['/systems/remindermsgrule/edit', 'id' => $data['id']]));
            }
            if ($model->account_type == 'custom' && empty($model->account_ids)) {
                $this->_showMessage('指定账号必须选择账号', false);
            }
            //设置模型数据
            if ($model->account_type == 'custom') {
                $shortNameList = $this->getaccountshortnamelist($model->platform_code, $model->account_ids);
                $shortNameList = array_column($shortNameList, 'account_name', 'id');
                $model->account_short_names = implode(',', $shortNameList);
                $model->account_ids = implode(',', $model->account_ids);
            } else {
                $model->account_short_names = '';
                $model->account_ids = '';
            }
            $model->update_by = Yii::$app->user->identity->login_name;
            $model->update_time = date('Y-m-d H:i:s');

            //保存数据
            if (!$model->save()) {
                $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
            } else {
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/systems/remindermsgrule/list') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }
        } else {
            $id = Yii::$app->request->get('id');

            if (empty($id)) {
                $this->_showMessage('规则ID不能为空', false);
            }

            $info = ReminderMsgRule::find()->where(['id' => $id])->asArray()->one();
            if (empty($info)) {
                $this->_showMessage('没有找到规则信息', false);
            }

            $this->isPopup = true;
            $shortNameList = $this->getaccountshortnamelist($info['platform_code']);
            $accountIdArr = [];
            if (!empty($info['account_ids'])) {
                $accountIdArr = explode(',', $info['account_ids']);
            }
            $platformList = ReminderMsgRule::getPlatformList();
            return $this->render('edit', [
                'info' => $info,
                'shortNameList' => $shortNameList,
                'accountIdArr' => $accountIdArr,
                'platformList' => $platformList,
            ]);
        }
    }

    /**
     * 批量删除催付订单规则
     */
    public function actionBatchdelete()
    {
        $ids = Yii::$app->request->post('ids');

        if (empty($ids) || !is_array($ids)) {
            $this->_showMessage('请选择删除项', false);
        }

        if (ReminderMsgRule::deleteAll(['in', 'id', $ids])) {
            $extraJs = 'top.refreshTable("' . Url::toRoute('/systems/remindermsgrule/list') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     * 删除催付订单规则
     */
    public function actionDelete()
    {
        $id = Yii::$app->request->get('id');

        if (empty($id)) {
            $this->_showMessage('请选择删除项', false);
        }
        $info = ReminderMsgRule::findOne($id);
        if (empty($info)) {
            $this->_showMessage('没有找到该规则', false);
        }
        if ($info->delete()) {
            $extraJs = 'top.refreshTable("' . Url::toRoute('/systems/remindermsgrule/list') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     * 获取账号简称列表
     */
    public function getaccountshortnamelist($platformCode = '', $accountIds = [])
    {
        $data = [];
        switch ($platformCode) {
            case Platform::PLATFORM_CODE_EB:
                $data = (new Query())
                    ->select('id, user_name as account_name')
                    ->from('{{%ebay_account}}')
                    ->andWhere(['status' => 1])
                    ->andFilterWhere(['in', 'id', $accountIds])
                    ->orderBy('id')
                    ->createCommand(Yii::$app->db_system)
                    ->queryAll();
                break;
            case Platform::PLATFORM_CODE_ALI:
                $data = (new Query())
                    ->select('id, account as account_name')
                    ->from('{{%aliexpress_account_qimen}}')
                    ->andWhere(['and', ['<>', 'access_token', ''], ['<>', 'refresh_token', '']])
                    ->andFilterWhere(['in', 'id', $accountIds])
                    ->orderBy('id')
                    ->createCommand(Yii::$app->db_system)
                    ->queryAll();
                break;
            default:
                break;
        }
        return $data;
    }

    /**
     * 获取账号简称列表
     */
    public function actionGetaccountshortnamelist()
    {
        $platformCode = Yii::$app->request->get('platformCode');
        if (empty($platformCode)) {
            die(json_encode([
                'code' => 0,
                'message' => '平台code不能为空',
            ]));
        }

        $data = $this->getaccountshortnamelist($platformCode);
        if (!empty($data)) {
            die(json_encode([
                'code' => 1,
                'message' => '成功',
                'data' => $data,
            ]));
        } else {
            die(json_encode([
                'code' => 0,
                'message' => '失败',
            ]));
        }
    }

    /**
     * 批量设置不催付买家ID
     */
    public function actionBatchsetnotreminderbuyer()
    {
        if (Yii::$app->request->isPost) {
            $ids = Yii::$app->request->post('ids', '');
            $notReminderBuyer = Yii::$app->request->post('not_reminder_buyer', '');

            if (empty($ids)) {
                $this->_showMessage('ids为空', false);
            }
            $idsArr = explode(',', $ids);

            //买家ID去重
            $notReminderBuyerArr = explode(',', trim($notReminderBuyer, ', '));
            $notReminderBuyer = implode(',', array_unique($notReminderBuyerArr));

            $rules = ReminderMsgRule::find()
                ->andWhere(['in', 'id', $idsArr])
                ->all();

            if (!empty($rules)) {
                foreach ($rules as $rule) {
                    $rule->not_reminder_buyer = $notReminderBuyer;
                    $rule->update_by = Yii::$app->user->identity->login_name;
                    $rule->update_time = date('Y-m-d H:i:s');
                    $rule->save();
                }
            }

            $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/systems/remindermsgrule/list') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {

            $ids = Yii::$app->request->get('ids', '');

            if (empty($ids)) {
                $this->_showMessage('请选中设置项', false);
            }
            $idsArr = explode(',', $ids);

            $rules = ReminderMsgRule::find()
                ->andWhere(['in', 'id', $idsArr])
                ->asArray()
                ->all();

            $notReminderBuyerArr = [];
            if (!empty($rules)) {
                foreach ($rules as $rule) {
                    if (!empty($rule['not_reminder_buyer'])) {
                        $notReminderBuyerArr = array_merge($notReminderBuyerArr, explode(',', $rule['not_reminder_buyer']));
                    }
                }
                //买家ID去重
                $notReminderBuyerArr = array_unique($notReminderBuyerArr);
            }
            $notReminderBuyer = implode(',', $notReminderBuyerArr);

            $this->isPopup = true;
            return $this->render('batchsetnotreminderbuyer', [
                'notReminderBuyer' => $notReminderBuyer,
                'ids' => $ids,
            ]);
        }
    }

    /**
     * 不催付
     */
    public function actionNotreminder()
    {
        $platformCode = Yii::$app->request->post('platform_code', '');
        $buyerId = Yii::$app->request->post('buyer_id', '');

        if (empty($platformCode)) {
            die(json_encode([
                'code' => 0,
                'message' => '平台code不能为空',
            ]));
        }
        if (empty($buyerId)) {
            die(json_encode([
                'code' => 0,
                'message' => '买家ID不能为空',
            ]));
        }

        $rules = ReminderMsgRule::find()
            ->andWhere(['platform_code' => $platformCode])
            ->andWhere(['status' => 1])
            ->all();

        if (empty($rules)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有催付规则',
            ]));
        }

        foreach ($rules as $rule) {
            if (!empty($rule->not_reminder_buyer)) {
                $notReminderBuyerArr = explode(',', $rule->not_reminder_buyer);
                $notReminderBuyerArr[] = $buyerId;
            } else {
                $notReminderBuyerArr = [$buyerId];
            }

            $notReminderBuyerArr = array_unique($notReminderBuyerArr);
            $rule->not_reminder_buyer = implode(',', $notReminderBuyerArr);
            $rule->update_by = Yii::$app->user->identity->login_name;
            $rule->update_time = date('Y-m-d H:i:s');
            $rule->save();
        }

        die(json_encode([
            'code' => 1,
            'message' => '成功',
        ]));
    }

    /**
     * 取消不催付
     */
    public function actionCancelnotreminder()
    {
        $platformCode = Yii::$app->request->post('platform_code', '');
        $buyerId = Yii::$app->request->post('buyer_id', '');

        if (empty($platformCode)) {
            die(json_encode([
                'code' => 0,
                'message' => '平台code不能为空',
            ]));
        }
        if (empty($buyerId)) {
            die(json_encode([
                'code' => 0,
                'message' => '买家ID不能为空',
            ]));
        }

        $rules = ReminderMsgRule::find()
            ->andWhere(['platform_code' => $platformCode])
            ->andWhere(['status' => 1])
            ->all();

        if (empty($rules)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有催付规则',
            ]));
        }

        foreach ($rules as $rule) {
            if (empty($rule->not_reminder_buyer)) {
                continue;
            }

            $notReminderBuyerArr = explode(',', $rule->not_reminder_buyer);

            if (!empty($notReminderBuyerArr)) {
                foreach ($notReminderBuyerArr as $key => $value) {
                    if ($value == $buyerId) {
                        unset($notReminderBuyerArr[$key]);
                    }
                }
            }

            $rule->not_reminder_buyer = implode(',', $notReminderBuyerArr);
            $rule->update_by = Yii::$app->user->identity->login_name;
            $rule->update_time = date('Y-m-d H:i:s');
            $rule->save();
        }

        die(json_encode([
            'code' => 1,
            'message' => '成功',
        ]));
    }
}
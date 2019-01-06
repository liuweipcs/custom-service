<?php

namespace app\modules\systems\controllers;

use Yii;
use app\modules\accounts\models\Platform;
use app\components\Controller;
use app\modules\systems\models\ExtendTimeRule;
use yii\db\Query;
use yii\helpers\Url;

/**
 * 自动延长收货规则管理
 */
class ExtendtimeruleController extends Controller
{

    /**
     * 自动延长收货规则列表
     */
    public function actionList()
    {
        $model = new ExtendTimeRule();
        //获取查询参数
        $params = Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);

        return $this->renderList('list', [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }

    /**
     * 添加自动延长收货规则
     */
    public function actionAdd()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            //装载数据
            $model = new ExtendTimeRule();
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
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/systems/extendtimerule/list') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }
        } else {
            $this->isPopup = true;
            $platformList = ExtendTimeRule::getPlatformList();
            return $this->render('add', [
                'platformList' => $platformList,
            ]);
        }
    }

    /**
     * 修改收货规则
     */
    public function actionEdit()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();

            $model = ExtendTimeRule::findOne($data['id']);
            if (empty($model)) {
                $this->_showMessage('没有找到规则信息', false);
            }
            //装载数据
            $model->load($data, '');
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Url::toRoute(['/systems/extendtimerule/edit', 'id' => $data['id']]));
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
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/systems/extendtimerule/list') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }
        } else {
            $id = Yii::$app->request->get('id');

            if (empty($id)) {
                $this->_showMessage('规则ID不能为空', false);
            }

            $info = ExtendTimeRule::find()->where(['id' => $id])->asArray()->one();
            if (empty($info)) {
                $this->_showMessage('没有找到规则信息', false);
            }

            $this->isPopup = true;
            $shortNameList = $this->getaccountshortnamelist($info['platform_code']);
            $accountIdArr = [];
            if (!empty($info['account_ids'])) {
                $accountIdArr = explode(',', $info['account_ids']);
            }
            $platformList = ExtendTimeRule::getPlatformList();
            return $this->render('edit', [
                'info' => $info,
                'shortNameList' => $shortNameList,
                'accountIdArr' => $accountIdArr,
                'platformList' => $platformList,
            ]);
        }
    }

    /**
     * 批量删除收货规则
     */
    public function actionBatchdelete()
    {
        $ids = Yii::$app->request->post('ids');

        if (empty($ids) || !is_array($ids)) {
            $this->_showMessage('请选择删除项', false);
        }

        if (ExtendTimeRule::deleteAll(['in', 'id', $ids])) {
            $extraJs = 'top.refreshTable("' . Url::toRoute('/systems/extendtimerule/list') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     * 删除收货规则
     */
    public function actionDelete()
    {
        $id = Yii::$app->request->get('id');

        if (empty($id)) {
            $this->_showMessage('请选择删除项', false);
        }
        $info = ExtendTimeRule::findOne($id);
        if (empty($info)) {
            $this->_showMessage('没有找到该规则', false);
        }
        if ($info->delete()) {
            $extraJs = 'top.refreshTable("' . Url::toRoute('/systems/extendtimerule/list') . '");';
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
}
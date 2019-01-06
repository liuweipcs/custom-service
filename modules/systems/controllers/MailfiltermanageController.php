<?php

namespace app\modules\systems\controllers;

use Yii;
use app\modules\systems\models\MailFilterRule;
use app\modules\systems\models\SiteManage;
use app\components\Controller;
use app\modules\systems\models\MailFilterManage;
use yii\helpers\Url;

/**
 * 邮件过滤器管理
 */
class MailfiltermanageController extends Controller
{
    /**
     * 列表
     */
    public function actionList()
    {
        $model = new MailFilterManage();
        //获取查询参数
        $params = Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);

        return $this->renderList('list', [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }

    /**
     * 添加
     */
    public function actionAdd()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();


            //装载数据
            $model = new MailFilterManage();
            $model->load($data, '');
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Yii::$app->request->getUrl());
            }

            if (!empty($data['rule_value']) && is_array($data['rule_value'])) {
                foreach ($data['rule_value'] as $value) {
                    if (empty($value)) {
                        $this->_showMessage('条件的值不能为空', false, Yii::$app->request->getUrl());
                    }
                }
            }

            if (!empty($data['move_site_ids']) && is_array($data['move_site_ids'])) {
                $model->move_site_ids = implode(',', $data['move_site_ids']);
            }

            $model->create_by = Yii::$app->user->identity->login_name;
            $model->create_time = date('Y-m-d H:i:s');
            $model->modify_by = Yii::$app->user->identity->login_name;
            $model->modify_time = date('Y-m-d H:i:s');

            //保存数据
            if (!$model->save()) {
                $this->_showMessage(Yii::t('system', 'Operate Failed'), false, Yii::$app->request->getUrl());
            } else {
                if (!empty($data['rule_type']) && !empty($data['rule_value'])) {
                    foreach ($data['rule_type'] as $key => $type) {
                        $rule = new MailFilterRule();
                        $rule->manage_id = $model->id;
                        $rule->type = $type;
                        $rule->value = array_key_exists($key, $data['rule_value']) ? $data['rule_value'][$key] : '';
                        $rule->status = 1;
                        $rule->save();
                    }
                }

                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/systems/mailfiltermanage/list') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }

        } else {
            $this->isPopup = true;
            //获取平台列表
            $platformList = MailFilterManage::getPlatformList();
            //规则类型列表
            $ruleTypeList = MailFilterRule::getRuleTypeList();

            return $this->render('add', [
                'platformList' => $platformList,
                'ruleTypeList' => $ruleTypeList,
            ]);
        }
    }

    /**
     * 编辑
     */
    public function actionEdit()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();

            $model = MailFilterManage::findOne($data['id']);
            if (empty($model)) {
                $this->_showMessage('没有找到邮件过滤器信息', false);
            }
            //装载数据
            $model->load($data, '');
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Url::toRoute(['/systems/mailfiltermanage/edit', 'id' => $data['id']]));
            }

            if (!empty($data['rule_value']) && is_array($data['rule_value'])) {
                foreach ($data['rule_value'] as $value) {
                    if (empty($value)) {
                        $this->_showMessage('条件的值不能为空', false, Yii::$app->request->getUrl());
                    }
                }
            }

            if (!empty($data['move_site_ids']) && is_array($data['move_site_ids'])) {
                $model->move_site_ids = implode(',', $data['move_site_ids']);
            } else {
                $model->move_site_ids = '';
            }

            if (empty($data['type_mark'])) {
                $model->type_mark = '0';
            }

            if (empty($data['mark_read'])) {
                $model->mark_read = '0';
            }

            $model->modify_by = Yii::$app->user->identity->login_name;
            $model->modify_time = date('Y-m-d H:i:s');

            //保存数据
            if (!$model->save()) {
                $this->_showMessage(Yii::t('system', 'Operate Failed'), false, Url::toRoute(['/systems/mailfiltermanage/edit', 'id' => $data['id']]));
            } else {
                if (!empty($data['rule_type']) && !empty($data['rule_value'])) {
                    if (MailFilterRule::deleteAll(['manage_id' => $model->id])) {
                        foreach ($data['rule_type'] as $key => $type) {
                            $rule = new MailFilterRule();
                            $rule->manage_id = $model->id;
                            $rule->type = $type;
                            $rule->value = array_key_exists($key, $data['rule_value']) ? $data['rule_value'][$key] : '';
                            $rule->status = 1;
                            $rule->save();
                        }
                    }
                }

                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/systems/mailfiltermanage/list') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }
        } else {
            $id = Yii::$app->request->get('id');

            if (empty($id)) {
                $this->_showMessage('ID不能为空', false);
            }

            $info = MailFilterManage::find()->where(['id' => $id])->asArray()->one();
            if (empty($info)) {
                $this->_showMessage('没有找到邮件过滤器信息', false);
            }

            if (!empty($info['move_site_ids'])) {
                $info['move_site_ids'] = explode(',', $info['move_site_ids']);
            }

            $this->isPopup = true;
            //获取平台列表
            $platformList = MailFilterManage::getPlatformList();
            //规则类型列表
            $ruleTypeList = MailFilterRule::getRuleTypeList();
            //规则列表
            $manageRuleList = MailFilterRule::getManageRuleList($info['id']);
            //邮件站点列表
            $mailSiteList = SiteManage::getSiteList($info['platform_code']);
            //邮件类型列表
            $mailTypeList = MailFilterManage::getMailTypeList($info['platform_code']);

            return $this->render('edit', [
                'info' => $info,
                'platformList' => $platformList,
                'ruleTypeList' => $ruleTypeList,
                'manageRuleList' => $manageRuleList,
                'mailSiteList' => $mailSiteList,
                'mailTypeList' => $mailTypeList,
            ]);
        }
    }

    /**
     * 批量删除
     */
    public function actionBatchdelete()
    {
        $ids = Yii::$app->request->post('ids');

        if (empty($ids) || !is_array($ids)) {
            $this->_showMessage('请选择删除项', false);
        }

        if (MailFilterManage::deleteAll(['in', 'id', $ids])) {
            foreach ($ids as $id) {
                MailFilterRule::deleteAll(['manage_id' => $id]);
            }

            $extraJs = 'top.refreshTable("' . Url::toRoute('/systems/mailfiltermanage/list') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     * 删除
     */
    public function actionDelete()
    {
        $id = Yii::$app->request->get('id');

        if (empty($id)) {
            $this->_showMessage('请选择删除项', false);
        }
        $info = MailFilterManage::findOne($id);
        if (empty($info)) {
            $this->_showMessage('没有找到该删除项', false);
        }
        if ($info->delete()) {
            MailFilterRule::deleteAll(['manage_id' => $id]);

            $extraJs = 'top.refreshTable("' . Url::toRoute('/systems/mailfiltermanage/list') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     * 获取平台站点列表
     */
    public function actionGetsitelist()
    {
        $platformCode = Yii::$app->request->post('platform_code', '');

        if (empty($platformCode)) {
            die(json_encode([
                'code' => 0,
                'message' => '平台code不能为空',
            ]));
        }

        $data = SiteManage::getSiteList($platformCode);
        if (empty($data)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到该平台下的站点',
            ]));
        } else {
            die(json_encode([
                'code' => 1,
                'message' => '成功',
                'data' => $data,
            ]));
        }
    }

    /**
     * 邮件类型列表
     */
    public function actionGetmailtypelist()
    {
        $platformCode = Yii::$app->request->post('platform_code', '');

        if (empty($platformCode)) {
            die(json_encode([
                'code' => 0,
                'message' => '平台code不能为空',
            ]));
        }

        $data = MailFilterManage::getMailTypeList($platformCode);
        if (empty($data)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到该平台下邮件类型',
            ]));
        } else {
            die(json_encode([
                'code' => 1,
                'message' => '成功',
                'data' => $data,
            ]));
        }
    }
}
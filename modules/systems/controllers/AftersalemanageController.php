<?php

namespace app\modules\systems\controllers;

use Yii;
use app\modules\aftersales\models\RefundReason;
use app\modules\orders\models\OrderKefu;
use app\modules\products\models\Product;
use app\modules\systems\models\AftersaleRule;
use app\modules\systems\models\PlatformDisputeReason;
use app\components\Controller;
use yii\helpers\Url;
use app\modules\systems\models\AftersaleManage;
use app\modules\systems\models\BasicConfig;

class AftersalemanageController extends Controller
{
    /**
     * 列表
     */
    public function actionList()
    {
        $model = new AftersaleManage();
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
            $model = new AftersaleManage();
            $model->load($data, '');
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Yii::$app->request->getUrl());
            }

            if (!empty($data['platform_reason_code']) && is_array($data['platform_reason_code'])) {
                $url = Yii::$app->request->getUrl();

                foreach ($data['platform_reason_code'] as $key => $value) {
                    if (empty($value) &&
                        empty($data['erp_order_status'][$key]) &&
                        empty($data['sku_status'][$key]) &&
                        empty($data['order_profit_cond'][$key]) &&
                        empty($data['order_profit_value'][$key])
                    ) {
                        $this->_showMessage('必须填写一个条件', false, $url);
                    }

                    if (array_key_exists($key, $data['department_id']) && empty($data['department_id'][$key])) {
                        $this->_showMessage('请选择责任所属部门', false, $url);
                    }

                    if (array_key_exists($key, $data['reason_id']) && empty($data['reason_id'][$key])) {
                        $this->_showMessage('请选择原因类别', false, $url);
                    }

                    if (array_key_exists($key, $data['formula_id']) && empty($data['formula_id'][$key])) {
                        $this->_showMessage('亏损计算方式不能为空', false, $url);
                    }

                    $erpOrderStatus = !empty($data['erp_order_status'][$key]) ? implode(',', $data['erp_order_status'][$key]) : '';
                    $skuStatus = !empty($data['sku_status'][$key]) ? implode(',', $data['sku_status'][$key]) : '';
                    $orderProfitCond = !empty($data['order_profit_cond'][$key]) ? $data['order_profit_cond'][$key] : '0';
                    $orderProfitValue = !empty($data['order_profit_value'][$key]) ? $data['order_profit_value'][$key] : '0';

                    $rule = AftersaleRule::find()
                        ->andWhere(['platform_code' => $data['platform_code']])
                        ->andWhere(['platform_reason_code' => $value])
                        ->andWhere(['erp_order_status' => $erpOrderStatus])
                        ->andWhere(['sku_status' => $skuStatus])
                        ->andWhere(['order_profit_cond' => $orderProfitCond])
                        ->andWhere(['order_profit_value' => $orderProfitValue])
                        ->asArray()
                        ->one();

                    if (!empty($rule)) {
                        $manage = AftersaleManage::findOne($rule['aftersale_manage_id']);
                        $this->_showMessage('存在相同的规则: ' . $manage->rule_name, false, $url);
                    }
                }
            }

            $model->create_by = Yii::$app->user->identity->login_name;
            $model->create_time = date('Y-m-d H:i:s');
            $model->modify_by = Yii::$app->user->identity->login_name;
            $model->modify_time = date('Y-m-d H:i:s');

            if (!$model->save()) {
                $this->_showMessage('保存售后单规则失败', false);
            }

            if (!empty($data['platform_reason_code']) && is_array($data['platform_reason_code'])) {
                foreach ($data['platform_reason_code'] as $key => $value) {

                    $erpOrderStatus = !empty($data['erp_order_status'][$key]) ? implode(',', $data['erp_order_status'][$key]) : '';
                    $skuStatus = !empty($data['sku_status'][$key]) ? implode(',', $data['sku_status'][$key]) : '';
                    $orderProfitCond = !empty($data['order_profit_cond'][$key]) ? $data['order_profit_cond'][$key] : '0';
                    $orderProfitValue = !empty($data['order_profit_value'][$key]) ? $data['order_profit_value'][$key] : '0';

                    $rule = new AftersaleRule();
                    $rule->platform_code = $model->platform_code;
                    $rule->aftersale_manage_id = $model->id;
                    $rule->platform_reason_code = $value;
                    $rule->erp_order_status = $erpOrderStatus;
                    $rule->sku_status = $skuStatus;
                    $rule->order_profit_cond = $orderProfitCond;
                    $rule->order_profit_value = $orderProfitValue;
                    $rule->department_id = $data['department_id'][$key];
                    $rule->reason_id = $data['reason_id'][$key];
                    $rule->formula_id = $data['formula_id'][$key];
                    $rule->save();
                }
            }

            $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/systems/aftersalemanage/list') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $this->isPopup = true;
            $platformList = AftersaleManage::platformDropdown();

            //责任所属部门
            $departmentList = BasicConfig::find()
                ->select('id, name')
                ->where(['parent_id' => 52, 'status' => 2])
                ->asArray()
                ->all();
            if (!empty($departmentList)) {
                $tmp = ['' => '请选择'];
                foreach ($departmentList as $item) {
                    $tmp[$item['id']] = $item['name'];
                }
                $departmentList = $tmp;
            }

            //原因类别
            $reasonList = ['' => '请选择'];

            //ERP订单状态
            $erpOrderStatusList = OrderKefu::getOrderCompleteStatus('');

            //sku状态
            $skuStatusList = Product::getProductStatus('');

            return $this->render('add', [
                'platformList' => $platformList,
                'departmentList' => $departmentList,
                'reasonList' => $reasonList,
                'erpOrderStatusList' => $erpOrderStatusList,
                'skuStatusList' => $skuStatusList,
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

            $model = AftersaleManage::findOne($data['id']);
            if (empty($model)) {
                $this->_showMessage('没有找到售后单规则信息', false);
            }

            $model->load($data, '');
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Url::toRoute(['/systems/aftersalemanage/edit', 'id' => $data['id']]));
            }

            if (!empty($data['platform_reason_code']) && is_array($data['platform_reason_code'])) {
                $url = Url::toRoute(['/systems/aftersalemanage/edit', 'id' => $data['id']]);

                foreach ($data['platform_reason_code'] as $key => $value) {
                    if (empty($value) &&
                        empty($data['erp_order_status'][$key]) &&
                        empty($data['sku_status'][$key]) &&
                        empty($data['order_profit_cond'][$key]) &&
                        empty($data['order_profit_value'][$key])
                    ) {
                        $this->_showMessage('必须填写一个条件', false, $url);
                    }

                    if (array_key_exists($key, $data['department_id']) && empty($data['department_id'][$key])) {
                        $this->_showMessage('请选择责任所属部门', false, $url);
                    }

                    if (array_key_exists($key, $data['reason_id']) && empty($data['reason_id'][$key])) {
                        $this->_showMessage('请选择原因类别', false, $url);
                    }

                    if (array_key_exists($key, $data['formula_id']) && empty($data['formula_id'][$key])) {
                        $this->_showMessage('亏损计算方式不能为空', false, $url);
                    }

                    $erpOrderStatus = !empty($data['erp_order_status'][$key]) ? implode(',', $data['erp_order_status'][$key]) : '';
                    $skuStatus = !empty($data['sku_status'][$key]) ? implode(',', $data['sku_status'][$key]) : '';
                    $orderProfitCond = !empty($data['order_profit_cond'][$key]) ? $data['order_profit_cond'][$key] : '0';
                    $orderProfitValue = !empty($data['order_profit_value'][$key]) ? $data['order_profit_value'][$key] : '0';

                    $rule = AftersaleRule::find()
                        ->andWhere(['<>', 'aftersale_manage_id', $model->id])
                        ->andWhere(['platform_code' => $data['platform_code']])
                        ->andWhere(['platform_reason_code' => $value])
                        ->andWhere(['erp_order_status' => $erpOrderStatus])
                        ->andWhere(['sku_status' => $skuStatus])
                        ->andWhere(['order_profit_cond' => $orderProfitCond])
                        ->andWhere(['order_profit_value' => $orderProfitValue])
                        ->asArray()
                        ->one();

                    if (!empty($rule)) {
                        $manage = AftersaleManage::findOne($rule['aftersale_manage_id']);
                        $this->_showMessage('存在相同的规则: ' . $manage->rule_name, false, $url);
                    }
                }
            }

            $model->modify_by = Yii::$app->user->identity->login_name;
            $model->modify_time = date('Y-m-d H:i:s');

            if (!$model->save()) {
                $this->_showMessage('保存售后单规则失败', false);
            }

            if (!empty($data['platform_reason_code']) && is_array($data['platform_reason_code'])) {
                if (AftersaleRule::deleteAll(['aftersale_manage_id' => $model->id]) !== false) {
                    foreach ($data['platform_reason_code'] as $key => $value) {

                        $erpOrderStatus = !empty($data['erp_order_status'][$key]) ? implode(',', $data['erp_order_status'][$key]) : '';
                        $skuStatus = !empty($data['sku_status'][$key]) ? implode(',', $data['sku_status'][$key]) : '';
                        $orderProfitCond = !empty($data['order_profit_cond'][$key]) ? $data['order_profit_cond'][$key] : '0';
                        $orderProfitValue = !empty($data['order_profit_value'][$key]) ? $data['order_profit_value'][$key] : '0';

                        $rule = new AftersaleRule();
                        $rule->platform_code = $model->platform_code;
                        $rule->aftersale_manage_id = $model->id;
                        $rule->platform_reason_code = $value;
                        $rule->erp_order_status = $erpOrderStatus;
                        $rule->sku_status = $skuStatus;
                        $rule->order_profit_cond = $orderProfitCond;
                        $rule->order_profit_value = $orderProfitValue;
                        $rule->department_id = $data['department_id'][$key];
                        $rule->reason_id = $data['reason_id'][$key];
                        $rule->formula_id = $data['formula_id'][$key];
                        $rule->save();
                    }
                }
            }

            $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/systems/aftersalemanage/list') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $id = Yii::$app->request->get('id');

            if (empty($id)) {
                $this->_showMessage('ID不能为空', false);
            }

            $info = AftersaleManage::find()->where(['id' => $id])->asArray()->one();
            if (empty($info)) {
                $this->_showMessage('没有找到售后单规则信息', false);
            }

            //售后单规则
            $rules = AftersaleRule::find()->where(['aftersale_manage_id' => $id])->asArray()->all();

            $this->isPopup = true;
            $platformList = AftersaleManage::platformDropdown();

            //责任所属部门
            $departmentList = BasicConfig::find()
                ->select('id, name')
                ->where(['parent_id' => 52, 'status' => 2])
                ->asArray()
                ->all();
            if (!empty($departmentList)) {
                $tmp = ['' => '请选择'];
                foreach ($departmentList as $item) {
                    $tmp[$item['id']] = $item['name'];
                }
                $departmentList = $tmp;
            }

            //平台退款原因
            $platformReasonList = PlatformDisputeReason::find()
                ->select('reason_code id, reason_name name')
                ->where(['platform_code' => $info['platform_code'], 'status' => 1])
                ->asArray()
                ->all();
            if (!empty($platformReasonList)) {
                $tmp = ['' => '请选择'];
                foreach ($platformReasonList as $item) {
                    $tmp[$item['id']] = $item['name'];
                }
                $platformReasonList = $tmp;
            }

            //所有基础配置
            $allBasicConfig = BasicConfig::getAllConfigData();

            //ERP订单状态
            $erpOrderStatusList = OrderKefu::getOrderCompleteStatus('');

            //sku状态
            $skuStatusList = Product::getProductStatus('');

            return $this->render('edit', [
                'info' => $info,
                'rules' => $rules,
                'platformList' => $platformList,
                'departmentList' => $departmentList,
                'platformReasonList' => $platformReasonList,
                'allBasicConfig' => $allBasicConfig,
                'erpOrderStatusList' => $erpOrderStatusList,
                'skuStatusList' => $skuStatusList,
            ]);
        }
    }

    /**
     * 获取原因列表
     */
    public function actionGetreasonlist()
    {
        $departmentId = Yii::$app->request->post('department_id');
        if (!isset($departmentId) || $departmentId == '') {
            die(json_encode([
                'code' => 0,
                'message' => '部门ID不能为空',
            ]));
        }

        $reasonList = BasicConfig::find()
            ->select('id, name')
            ->where(['parent_id' => $departmentId, 'status' => 2])
            ->asArray()
            ->all();

        die(json_encode([
            'code' => 1,
            'message' => '成功',
            'data' => $reasonList,
        ]));
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

        if (AftersaleManage::deleteAll(['in', 'id', $ids])) {
            foreach ($ids as $id) {
                AftersaleRule::deleteAll(['aftersale_manage_id' => $id]);
            }

            $extraJs = 'top.refreshTable("' . Url::toRoute('/systems/aftersalemanage/list') . '");';
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
        $info = AftersaleManage::findOne($id);
        if (empty($info)) {
            $this->_showMessage('没有找到该删除项', false);
        }
        if ($info->delete()) {
            AftersaleRule::deleteAll(['aftersale_manage_id' => $id]);

            $extraJs = 'top.refreshTable("' . Url::toRoute('/systems/aftersalemanage/list') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     * 获取亏损计算方式
     */
    public function actionGetformula()
    {
        $departmentId = Yii::$app->request->post('department_id');
        $reasonId = Yii::$app->request->post('reason_id');

        if (!isset($departmentId) || $departmentId == '') {
            die(json_encode([
                'code' => 0,
                'message' => '部门ID不能为空',
            ]));
        }

        if (!isset($reasonId) || $reasonId == '') {
            die(json_encode([
                'code' => 0,
                'message' => '原因类别ID不能为空',
            ]));
        }

        $formulaId = RefundReason::find()
            ->select('formula_id')
            ->where(['department_id' => $departmentId, 'reason_type_id' => $reasonId])
            ->limit(1)
            ->scalar();

        if (empty($formulaId)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到亏损计算方式',
            ]));

        }

        $parentList = BasicConfig::getParentList(108);
        $formulaName = array_key_exists($formulaId, $parentList) ? $parentList[$formulaId] : '';

        die(json_encode([
            'code' => 1,
            'message' => '成功',
            'data' => [
                'id' => $formulaId,
                'name' => $formulaName,
            ],
        ]));
    }

    /**
     * 获取平台纠纷原因
     */
    public function actionGetdisputereasonlist()
    {
        $platformCode = Yii::$app->request->post('platform_code', '');

        if (empty($platformCode)) {
            die(json_encode([
                'code' => 0,
                'message' => '平台code不能为空',
            ]));
        }

        $data = PlatformDisputeReason::find()
            ->select('reason_code id, reason_name name')
            ->where(['platform_code' => $platformCode, 'status' => 1])
            ->asArray()
            ->all();

        die(json_encode([
            'code' => 1,
            'message' => '成功',
            'data' => $data,
        ]));
    }
}
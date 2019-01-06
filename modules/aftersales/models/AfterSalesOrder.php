<?php

namespace app\modules\aftersales\models;

use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\UserAccount;
use app\modules\mails\components\GridView;
use app\modules\orders\models\OrderKefu;
use app\modules\orders\models\PlatformRefundOrder;
use app\modules\orders\models\Warehouse;
use yii\helpers\Url;
use Yii;
use app\modules\accounts\models\Platform;
use app\modules\orders\models\Transactionrecord;
use app\modules\systems\models\BasicConfig;
use app\modules\aftersales\models\OrderRedirectDetail;
use app\modules\aftersales\models\AfterSalesRedirect;
use app\modules\warehouses\models\WarehouseKefu;

class AfterSalesOrder extends AfterSalesModel {

    const ORDER_TYPE_REFUND = 1;    //退款单
    const ORDER_TYPE_RETURN = 2;    //退货单
    const ORDER_TYPE_REDIRECT = 3;  //重寄
    const ORDER_STATUS_WATTING_AUDIT = 1;       //未审核
    const ORDER_STATUS_AUDIT_PASSED = 2;        //审核通过
    const ORDER_STATUS_AUDIT_NO_PASSED = 3;     //退回修改
    const ORDER_STATUS_COMPLETED = 4;           //完成
    const ORDER_SEARCH_CONDITION_FROM_ALL = 'all'; //构造公共查询售后问题的搜索条件的标识

    public $error_message = '';
    public $time_type = "";
    public $refundStatusMap = array(1 => '待退款', 2 => '退款中', 3 => '退款完成', 4 => '退款失败', 5 => '待接受退款');
    public $returnStatusMap = array(1 => '待收货', 2 => '已收货', 3 => '取消退货');
    public $timeMap = [
        1 => '创建时间',
        2 => '审核时间',
        3 => '退款时间',
//        4 => '退货时间'
    ];

    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName() {
        return '{{%after_sales_order}}';
    }

    /**
     * 添加表属性
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\ActiveRecord::attributes()
     */
    public function attributes() {
        $attributes = parent::attributes();
        $downAttributes = ['order_type', 'account_name', 'account_short_name', 'order_id', 'audit_info', 'refund_status', 'return_status', 'return_time', 'rma', 'tracking_no', 'return_info', 'fail_reason', 'fail_count', 'department_text', 'reason_text',  'reason', 'type_text', 'status_text', 'after_sale_id_text', 'id', 'edit_after_sales_order',
            'platform_order_id', 'buyer_id', 'sku', 'warehouse_name', 'complete_status', 'order_status', 'ship_country_name', 'paytime', 'shipped_date',
            'currency', 'total_price', 'rtransaction_id', 'receive_type', 'sum_quantity', 'quantity', 'product_title', 'pro_name', 'line_cn_name', 'linelist_cn_name', 'ship_name', 'refund_time',
            'receiver_email', 'payer_email', 'refund_transaction_id', 'orientation_order_id', 'refund_currency', 'refund_amt', 'refund_amt_rmb', 'sku_total_price', 'sku_quantity', 'sku_refund_amt', 'sku_refund_amt_rmb',
            'reSku', 'rePname', 'reSumqty', 'amazon_fulfill_channel', 'refund_amount', 'order_number', 'refund_amount_rmb', 'create_info', 'modify_info', 'refund_status_time', 'refund_amount_info', 'is_fbc'];
        return array_merge($attributes, $downAttributes);
    }

    /**
     * @desc 设置主键
     * @return string
     */
    public static function primaryKey() {
        return ['after_sale_id'];
    }

    /**
     * @desc 获取订单的所有售后单
     * @param $platformCode
     * @param $orderId
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getByOrderId($platformCode, $orderId) {
        $model = self::find()->where(['platform_code' => $platformCode, 'order_id' => $orderId])->asArray()->all();
        if (is_array($model) && !empty($model)) {
            foreach ($model as &$val) {
                if ($val['type'] == self::ORDER_TYPE_REFUND) {
                    $refund_info = AfterSalesRefund::find()->select('refund_amount,currency')->where(['after_sale_id' => $val['after_sale_id']])->asArray()->one();
                    $val['refund_amount'] = $refund_info['refund_amount'];
                    $val['currency'] = $refund_info['currency'];
                } elseif ($val['type'] == self::ORDER_TYPE_REDIRECT) {
                    $redirect_info = AfterSalesRedirect::find()->select('order_amount,currency')->where(['after_sale_id' => $val['after_sale_id']])->asArray()->one();
                    $val['refund_amount'] = $redirect_info['order_amount'];
                    $val['currency'] = $redirect_info['currency'];
                }
            }
        }
        return $model;
    }

    /**
     * @desc 获取订单的所有售后单的责任部门Id
     * @param $platformCode
     * @param $orderId
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getDepartmentId($platformCode, $orderId) {
        $res = self::find()->where(['platform_code' => $platformCode, 'order_id' => $orderId])->asArray()->one();

        $department_id = ($res['department_id']) ? ($res['department_id']) : '';

        return $department_id;
    }

    /**
     * @desc 获取订单的所有售后单及获取{{%refund_return_reason}}的content
     * @param $platformCode
     * @param $orderId
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getByOrderIdCon($platformCode, $orderId) {

        $department_id = self::getDepartmentId($platformCode, $orderId);

        if ($department_id) {
            $model = self::find()
                    ->select(['A.*', '(B.name ) as reason_content'])
                    ->from(self::tableName() . ' A')
                    ->leftJoin(BasicConfig::tableName() . ' B', 'A.reason_id = B.id')
                    ->where(['A.platform_code' => $platformCode, 'A.order_id' => $orderId])
                    ->asArray()
                    ->all();
        } else {
            $model = self::find()
                    ->select(['A.*', '(B.content ) as reason_content'])
                    ->from(self::tableName() . ' A')
                    ->leftJoin(RefundReturnReason::tableName() . ' B', 'A.reason_id = B.id')
                    ->where(['A.platform_code' => $platformCode, 'A.order_id' => $orderId])
                    ->asArray()
                    ->all();
        }

        if (is_array($model) && !empty($model)) {
            foreach ($model as &$val) {
                if ($val['type'] == self::ORDER_TYPE_REFUND) {
                    $refund_info = AfterSalesRefund::find()->select('refund_amount,currency')->where(['after_sale_id' => $val['after_sale_id']])->asArray()->one();
                    $val['refund_amount'] = $refund_info['refund_amount'];
                    $val['currency'] = $refund_info['currency'];
                } elseif ($val['type'] == self::ORDER_TYPE_REDIRECT) {
                    $redirect_info = AfterSalesRedirect::find()->select('order_amount,currency')->where(['after_sale_id' => $val['after_sale_id']])->asArray()->one();
                    $val['refund_amount'] = $redirect_info['order_amount'];
                    $val['currency'] = $redirect_info['currency'];
                }
            }
        }
        return $model;
    }

    /**
     * @desc 查询
     */
    public function searchList($params = [], $url = null) {
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'after_sale_id' => SORT_DESC
        );
        $query = self::find();
        $query->from(self::tableName() . ' as t');

        if (isset($params['type']) && !empty($params['type']) && $params['type'] == 1) {
            $query->innerJoin('{{%after_sales_refund}} t2', 't2.after_sale_id = t.after_sale_id');
            //
            if (isset($params['refund_status']) && !empty($params['refund_status'])) {
                $query->andWhere('t2.refund_status = ' . $params['refund_status']);
            }
            if (isset($params['time_type']) && $params['time_type'] == 3) {
                //退款时间
                if (!empty($params['start_time']) && !empty($params['end_time'])) {
                    $query->andWhere(['between', 't2.refund_time', $params['start_time'], $params['end_time']]);
                } elseif (!empty($params['start_time'])) {
                    $query->andWhere(['>=', 't2.refund_time', $params['start_time']]);
                } elseif (!empty($params['end_time'])) {
                    $query->andWhere(['<=', 't2.refund_time', $params['end_time']]);
                }
                unset($params['time_type']);
            }
            if (isset($params['time_type']) && $params['time_type'] == 2) {
                //审核时间
                if (!empty($params['start_time']) && !empty($params['end_time'])) {
                    $query->andWhere(['between', 't.approve_time', $params['start_time'], $params['end_time']])
                            ->andWhere(['t.status' => '2']);
                } elseif (!empty($params['start_time'])) {
                    $query->andWhere(['>=', 't.approve_time', $params['start_time']])
                            ->andWhere(['t.status' => '2']);
                } elseif (!empty($params['end_time'])) {
                    $query->andWhere(['<=', 't.approve_time', $params['end_time']])
                            ->andWhere(['t.status' => '2']);
                }
                unset($params['time_type']);
            }
            if (isset($params['time_type']) && $params['time_type'] == 1) {
                if (!empty($params['start_time']) && !empty($params['end_time'])) {
                    $query->andWhere(['between', 't.create_time', $params['start_time'], $params['end_time']]);
                } else if (!empty($params['start_time'])) {
                    $query->andWhere(['>=', 't.create_time', $params['start_time']]);
                } else if (!empty($params['end_time'])) {
                    $query->andWhere(['<=', 't.create_time', $params['end_time']]);
                }
                unset($params['time_type']);
            }
        }
        //过滤退件类型
        $query->andWhere(['!=', 't.type', '2']);
        if (isset($params['type']) && !empty($params['type']) && $params['type'] == 3) {
            $query->innerJoin('{{%after_sales_redirect}} t2', 't2.after_sale_id = t.after_sale_id');
            //只有审核时间 创建时间
            if (isset($params['time_type']) && $params['time_type'] == 2) {
                //审核时间
                if (!empty($params['start_time']) && !empty($params['end_time'])) {
                    $query->andWhere(['between', 't.approve_time', $params['start_time'], $params['end_time']])
                            ->andWhere(['t.status' => '2']);
                } elseif (!empty($params['start_time'])) {
                    $query->andWhere(['>=', 't.approve_time', $params['start_time']])
                            ->andWhere(['t.status' => '2']);
                } elseif (!empty($params['end_time'])) {
                    $query->andWhere(['<=', 't.approve_time', $params['end_time']])
                            ->andWhere(['t.status' => '2']);
                }
                unset($params['time_type']);
            }
            if (isset($params['time_type']) && $params['time_type'] == 1) {
                if (!empty($params['start_time']) && !empty($params['end_time'])) {
                    $query->andWhere(['between', 't.create_time', $params['start_time'], $params['end_time']]);
                } else if (!empty($params['start_time'])) {
                    $query->andWhere(['>=', 't.create_time', $params['start_time']]);
                } else if (!empty($params['end_time'])) {
                    $query->andWhere(['<=', 't.create_time', $params['end_time']]);
                }
                unset($params['time_type']);
            }
        }

        //默认
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 't.create_time', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 't.create_time', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 't.create_time', $params['end_time']]);
        }
        unset($params['time_type']);

        if (isset($params['status_text']) && !empty($params['status_text'])) {
            $query->andWhere('t.status = ' . $params['status_text']);
            unset($params['status_text']);
        }


        if (isset($params['sku']) && !empty($params['sku'])) {
            $platform_code = $params['platform_code'];
            $orderIds = OrderKefu::getOrderIdsBySku($platform_code, $params['sku']);
            $query->andWhere(['in', 't.order_id', $orderIds]);
            unset($params['sku']);
        }

        $platformArray = isset(\Yii::$app->user->identity->role->platform_code) ? explode(',', \Yii::$app->user->identity->role->platform_code) : Platform::getPlatformAsArray();
        if (empty($params['platform_code'])) {
            $query->andWhere(['in', 't.platform_code', $platformArray]);
        }

        $user_id = Yii::$app->user->identity->id;
        $account_ids = UserAccount::find()
                ->select('account_id')
                ->where(['user_id' => $user_id])
                ->column();
        if (!empty($account_ids)) {
            $query->andWhere(['in', 't.account_id', $account_ids]);
        }
        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();

        $departmentList = BasicConfig::getParentList(52);
        $allConfigData = BasicConfig::getAllConfigData();
        foreach ($models as $key => $model) {
            $models[$key]->setAttribute('audit_info', self::getOrderStatusList($model->status) . '<br>' . $model->approver . '<br>' . substr($model->approve_time, 2));
            $models[$key]->setAttribute('create_info', $model->create_by . '<br>' . substr($model->create_time, 2));

            // 退款添加退款状态和退款失败原因
            if ($model->type == '1') {
                $refund_info = AfterSalesRefund::findOne(['after_sale_id' => $model->after_sale_id]);
                if (!empty($refund_info)) {
                    $refund_status = $this->refundStatusMap[$refund_info->refund_status];
                    $refund_amount = $refund_info->refund_amount . $refund_info->currency;
                    //退款金额人民币
                    if (!empty($refund_info->refund_amount)) {
                        $rmb = VHelper::getTargetCurrencyAmtKefu($refund_info->currency, 'CNY', $refund_info->refund_amount);
                        if ($rmb) {
                            $refund_amount_rmb = $rmb;
                        } else {
                            $refund_amount_rmb = '-';
                        }
                    } else {
                        $refund_amount_rmb = '-';
                    }

                    //退款时间处理
                    if (empty($refund_info->refund_time) && ($refund_info->refund_status == 3)) {
                        //如果退款时间为空，则找查手动登记退款单的时间
                        $after_info = AfterSalesOrder::findOne(['after_sale_id' => $model->after_sale_id]);
                        if (!empty($after_info) && $after_info['status'] == 2) {
                            //登记退款单审核通过的时间，可以认为是退款时间
                            $refund_time = substr($after_info['approve_time'], 2);
                        }
                    } else {
                        $refund_time = substr($refund_info->refund_time, 2);
                    }

                    $models[$key]->setAttribute('fail_reason', $refund_info->fail_reason);
                    $models[$key]->setAttribute('fail_count', $refund_info->fail_count);
                } else {
                    $refund_status = '-';
                    $refund_time = '';
                    $refund_amount = '-';
                    $refund_amount_rmb = '-';
                    $models[$key]->setAttribute('fail_reason', '-');
                    $models[$key]->setAttribute('fail_count', '88');
                }
            } else {
                $refund_status = '-';
                $refund_time = '';
                $refund_amount = '-';
                $refund_amount_rmb = '-';
                $models[$key]->setAttribute('fail_reason', '-');
                $models[$key]->setAttribute('fail_count', '88');
            }
            $models[$key]->setAttribute('refund_status', $refund_status);
            $models[$key]->setAttribute('refund_status_time', $refund_status . '<br>' . $refund_time);
            $models[$key]->setAttribute('refund_amount_info', $refund_amount . '<br>' . $refund_amount_rmb);
			
			$platformArray = ['WISH', 'LAZADA', 'JOOM', 'MALL', 'JUM', 'CDISCOUNT', 'SHOPEE'];
            $reason = '-';
            if (in_array($model->platform_code, $platformArray)) {
                $reason = PlatformRefundOrder::getReasonPlatform($model->platform_code, $model->order_id) ? : '-';
            }
            $models[$key]->setAttribute('reason', $reason);
            $account_short_name = null;
            if ($model->account_id) {
                $account_info = Account::findOne(['id' => $model->account_id]);
                if ($account_info)
                    $account_short_name = $account_info->account_short_name;
            }
            $models[$key]->setAttribute('order_id', self::getOrderLink('', $model->platform_code, $model->order_id, $account_short_name));
            if ($model->department_id) {
                $models[$key]->setAttribute("department_text", $departmentList[$model->department_id]);
                $models[$key]->setAttribute('reason_text', $model->reason_id ? self::getDepartAndReasonUrl($model->after_sale_id, $allConfigData[$model->reason_id]) : "");
            } else {
                $models[$key]->setAttribute("department_text", '');
                $models[$key]->setAttribute('reason_text', self::getDepartAndReasonUrl($model->after_sale_id, RefundReturnReason::getReasonContent($model->reason_id)));
            }
            $models[$key]->setAttribute('type_text', self::getOrderTypeList($model->type));
            $models[$key]->setAttribute('after_sale_id_text', self::getAfterSaleidLink($model->after_sale_id, $model->platform_code, $model->type, $model->status));
            $models[$key]->setAttribute('id', $model->after_sale_id);
            $models[$key]->setAttribute('edit_after_sales_order', $this->getEditLink($model, $url));
        }
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * 修改售后原因
     * @param $after_sale_id
     * @param $resaon_text
     * @return string
     */
    public static function getDepartAndReasonUrl($after_sale_id, $resaon_text) {

        $href = Url::toRoute(['/aftersales/sales/editdepart',
                    'after_sales_id' => $after_sale_id,
        ]);
        return "<a _width='50%' _height='69%' class='edit-button' href='" . $href . "' title='售后原因'>" . $resaon_text . "</a>";
    }

    /**
     * @desc 组装售后单号链接
     */
    public static function getAfterSaleidLink($after_sale_id, $platform_code, $type, $status) {
        switch ($type) {
            case "1":
                $url = '/aftersales/sales/detailrefund';
                break;
            case "2":
                $url = '/aftersales/sales/detailreturn';
                break;
            case "3":
                $url = '/aftersales/sales/detailredirect';
                break;
        }

        $href = Url::toRoute([$url, 'after_sale_id' => $after_sale_id, 'platform_code' => $platform_code, 'status' => $status]);
        return "<a _width='100%' _height='100%' class='edit-button' href='" . $href . "' title='售后单详情'>" . $after_sale_id . "</a>";
    }

    /**
     * @desc 组装订单号链接
     */
    public static function getOrderLink($order_id, $platform_code, $system_order_id, $account_short_name = null) {
        $href = Url::toRoute(['/orders/order/orderdetails',
                    'order_id' => $order_id,
                    'platform' => $platform_code,
                    'system_order_id' => $system_order_id,
        ]);
        if ($account_short_name)
            $system_order_id = $account_short_name . '--' . $system_order_id;
        return "<a _width='100%' _height='100%' class='edit-button' href='" . $href . "' title='订单详情'>" . $system_order_id . "</a>";
    }

    /**
     * @desc 组装修改售后单链接
     */
    protected function getEditLink($model, $tourl = null) {
        $url = '/aftersales/sales/edit';

        $after_sale_id = $model->after_sale_id;
        $platform_code = $model->platform_code;
        $type = $model->type;

        $identity = \Yii::$app->user->getIdentity();
        $roleCodes = $identity->roles;
        $timerefund = '';
        $timerefund = AfterSalesRefund::find()->select('refund_time')->where(['after_sale_id' => $after_sale_id])->andWhere(['refund_status' => 3])->asArray()->scalar();
        if (empty($timerefund)) {
            $timerefund = AfterSalesOrder::find()->select('approve_time')->where(['after_sale_id' => $after_sale_id])->andWhere(['status' => 2])->asArray()->scalar();
        }
		
		//是否有退款时间
        if (!empty($timerefund)) {
            //获取退款完成时间的月份
            $refunfm = date('m', strtotime($timerefund)); //退款月份
            $timem = date('m'); //当月
            //退款时间在当月内
            if ($refunfm != $timem) {
                $state = FALSE; //无法删除
            } else {
                $state = true; //可删除
            }
        } else {
            $state = true; //没退款 可删除
        }

        $href = '';
        if ($model->status != AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED) {
            $href = '<div class="btn-group btn-list">
            <button type="button" class="btn btn-default btn-sm">' . Yii::t('system', 'Operation') . '</button>
            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
                <span class="sr-only">' . Yii::t('system', 'Toggle Dropdown List') . '</span>
            </button>
            <ul class="dropdown-menu" rol="menu">
                <li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/order/audit',
                        'url' => $tourl,
                        'platform_code' => $platform_code,
                        'after_sales_id' => $after_sale_id,
                        'status' => AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED]) . '">审核通过</a></li>
                <li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/order/audit',
                        'url' => $tourl,
                        'after_sales_id' => $after_sale_id,
                        'status' => AfterSalesOrder::ORDER_STATUS_AUDIT_NO_PASSED]) . '">退回修改</a></li>';
            if ($model->status == AfterSalesOrder::ORDER_STATUS_AUDIT_NO_PASSED && GridView::_aclcheck(Yii::$app->user->identity->id, $url)) {
                $href .= '<li><a class="edit-button"  _width ="100%" _height="100%" href="' . Url::toRoute([$url,
                            'after_sales_id' => $after_sale_id,
                            'type' => $type]) . '">修改售后单</a></li>';
            }
            if ($model->status == AfterSalesOrder::ORDER_STATUS_AUDIT_NO_PASSED && GridView::_aclcheck(Yii::$app->user->identity->id, '/aftersales/sales/delete') && $state) {
                $href .= '<li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/sales/delete',
                            'after_sales_id' => $after_sale_id,
                            'type' => $type]) . '">删除售后单</a></li>';
            }

            $href .= '</ul>
                        </div>';
        } else {
            if ($type == self::ORDER_TYPE_REFUND && in_array(array_search($model->refund_status, $this->refundStatusMap), array(AfterSalesRefund::REFUND_STATUS_FAIL, AfterSalesRefund::REFUND_STATUS_ING))) {
                $href = '<div class="btn-group btn-list">
                <button type="button" class="btn btn-default btn-sm">' . Yii::t('system', 'Operation') . '</button>
                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                    <span class="sr-only">' . Yii::t('system', 'Toggle Dropdown List') . '</span>
                </button>
                <ul class="dropdown-menu" rol="menu">
                    <li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/sales/changestatus',
                            'after_sale_id' => $after_sale_id,]) . '">退款修改为成功</a></li>';
                if (array_search($model->refund_status, $this->refundStatusMap) == AfterSalesRefund::REFUND_STATUS_FAIL)
                    $href .= '<li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/order/audit',
                                'url' => $tourl,
                                'after_sales_id' => $after_sale_id,
                                'status' => AfterSalesOrder::ORDER_STATUS_AUDIT_NO_PASSED]) . '">退回修改</a></li>';
                if ($platform_code == Platform::PLATFORM_CODE_EB && $model->fail_count < 88)
                    $href .= '<li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/sales/refundeb', 'after_sale_id' => $after_sale_id,]) . '">执行退款</a></li>';
                $href .= '</ul>
                </div>';
            } else if ($type == self::ORDER_TYPE_REFUND && array_search($model->refund_status, $this->refundStatusMap) == AfterSalesRefund::REFUND_STATUS_WAIT) {
                $href = '<div class="btn-group btn-list">
                <button type="button" class="btn btn-default btn-sm">' . Yii::t('system', 'Operation') . '</button>
                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                    <span class="sr-only">' . Yii::t('system', 'Toggle Dropdown List') . '</span>
                </button>
                <ul class="dropdown-menu" rol="menu">
                    <li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/sales/changestatus',
                            'after_sale_id' => $after_sale_id,]) . '">退款修改为成功</a></li>
                    <li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/order/audit',
                            'url' => $tourl,
                            'after_sales_id' => $after_sale_id,
                            'status' => AfterSalesOrder::ORDER_STATUS_AUDIT_NO_PASSED]) . '">退回修改</a></li>
                </ul>
                </div>';
                //&& $model->platform_code == Platform::PLATFORM_CODE_EB
            } else if ($type == self::ORDER_TYPE_REFUND && array_search($model->refund_status, $this->refundStatusMap) == AfterSalesRefund::REFUND_STATUS_FINISH) {
                $href = '<div class="btn-group btn-list">
                <button type="button" class="btn btn-default btn-sm">' . Yii::t('system', 'Operation') . '</button>
                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                    <span class="sr-only">' . Yii::t('system', 'Toggle Dropdown List') . '</span>
                </button>
                <ul class="dropdown-menu" rol="menu">';
                if (!empty(array_intersect($roleCodes, ['admin', 'cs_leader', 'cs_amazonmanager', 'cs_aliexpress-manager', 'cs_ebay-manager', 'cs_cdwish-manager', 'LAZADA-SALE-MANAGER']))) {
                    //if (in_array(ROLE_CODE, ['admin', 'cs_leader', 'cs_amazonmanager', 'cs_aliexpress-manager', 'cs_ebay-manager', 'cs_cdwish-manager', 'LAZADA-SALE-MANAGER'])) {
                    /* if (GridView::_aclcheck(Yii::$app->user->identity->id, $url)) {
                      $href .= '<li><a class="edit-button" _width ="100%" _height="100%" href="' . Url::toRoute([$url,
                      'after_sales_id' => $after_sale_id,
                      'type'           => $type]) . '">修改售后单</a></li>';
                      } */
                    if (GridView::_aclcheck(Yii::$app->user->identity->id, '/aftersales/sales/delete') && $state) {
                        $href .= '<li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/sales/delete',
                                    'after_sales_id' => $after_sale_id,
                                    'type' => $type]) . '">删除售后单</a></li>';
                    }
                }
                $href .= '</ul>
                </div>';
            } else if ($type == self::ORDER_TYPE_REFUND && array_search($model->refund_status, $this->refundStatusMap) == AfterSalesRefund::REFUND_STATUS_WAIT_RECEIVE) {
                $href = '<div class="btn-group btn-list">
                <button type="button" class="btn btn-default btn-sm">' . Yii::t('system', 'Operation') . '</button>
                <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                    <span class="sr-only">' . Yii::t('system', 'Toggle Dropdown List') . '</span>
                </button>
                <ul class="dropdown-menu" rol="menu">
                    <li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/sales/changestatus',
                            'after_sale_id' => $after_sale_id,]) . '">退款修改为成功</a></li>';
                if (!empty(array_intersect($roleCodes, ['admin', 'cs_leader', 'cs_amazonmanager', 'cs_aliexpress-manager', 'cs_ebay-manager', 'cs_cdwish-manager', 'LAZADA-SALE-MANAGER']))) {
                    //if (in_array(ROLE_CODE, ['admin', 'cs_leader', 'cs_amazonmanager', 'cs_aliexpress-manager', 'cs_ebay-manager', 'cs_cdwish-manager', 'LAZADA-SALE-MANAGER'])) {

                    
                    if (GridView::_aclcheck(Yii::$app->user->identity->id, '/aftersales/sales/delete') && $state) {
                        $href .= '<li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/sales/delete',
                                    'after_sales_id' => $after_sale_id,
                                    'type' => $type]) . '">删除售后单</a></li>';
                    }
                }
                $href .= '</ul>
                </div>';
            }
            //退货操作
            if ($type == self::ORDER_TYPE_RETURN) {
                $href = '<div class="btn-group btn-list">
            <button type="button" class="btn btn-default btn-sm">' . Yii::t('system', 'Operation') . '</button>
            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
                <span class="sr-only">' . Yii::t('system', 'Toggle Dropdown List') . '</span>
            </button>';
                //1 默认待审核 2 审核通过 3 退回修改 $model->status
                //1 默认待收货 2 已收获 3 取消退货 $model->return_status

                if ($model->status == 1) {
                    if ($model->return_status == '待收货') {
                        //确认审核通过 退回修改 取消退货
                        $href .= '<ul class="dropdown-menu" rol="menu">
                                    <li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/order/audit',
                                    'url' => $tourl,
                                    'after_sales_id' => $after_sale_id,
                                    'status' => AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED]) . '">审核通过</a></li>
                                    <li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/order/audit',
                                    'url' => $tourl,
                                    'after_sales_id' => $after_sale_id,
                                    'status' => AfterSalesOrder::ORDER_STATUS_AUDIT_NO_PASSED]) . '">退回修改</a></li>
                                     <li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/sales/return',
                                    'url' => $tourl,
                                    'platform_code' => $platform_code,
                                    'after_sales_id' => $after_sale_id,
                                    'return_status' => 3]) . '">取消退货</a></li>';

                        $href .= '</ul></div>';
                    }
                }
                if ($model->status == 2) {
                    if ($model->return_status == '待收货') {
                        //确认审核通过 退回修改 取消退货
                        $href .= '<ul class="dropdown-menu" rol="menu">
                                     <li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/sales/return',
                                    'url' => $tourl,
                                    'after_sales_id' => $after_sale_id,
                                    'return_status' => 3]) . '">取消退货</a></li>
                                    <li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/sales/return',
                                    'url' => $tourl,
                                    'platform_code' => $platform_code,
                                    'after_sales_id' => $after_sale_id,
                                    'return_status' => 2]) . '">确认收货</a></li>';
                        $href .= '</ul></div>';
                    }
                }
            }
        }
        return $href;
    }

    /**
     * @desc 搜索过滤项
     * @return multitype:multitype:string multitype:  multitype:string multitype:string
     */
    public function filterOptions() {
        $platformArray = isset(\Yii::$app->user->identity->role->platform_code) ? explode(',', \Yii::$app->user->identity->role->platform_code) : array();
        $platform = array();
        $allplatform = UserAccount::getLoginUserPlatformAccounts();
        //$allplatform = Platform::getPlatformAsArray();
        if ($platformArray) {
            foreach ($platformArray as $value) {
                $platform[$value] = isset($allplatform[$value]) ? $allplatform[$value] : $value;
            }
        }
        $getCustomer = Yii::$app->request->get();
        $platform = !empty($platform) ? $platform : $allplatform;
        return [
            [
                'name' => 'platform_code',
                'alias' => 't',
                'type' => $_REQUEST['platform_code'] == static::ORDER_SEARCH_CONDITION_FROM_ALL ? 'dropDownList' : 'hidden',
                'search' => '=',
                'data' => $_REQUEST['platform_code'] == static::ORDER_SEARCH_CONDITION_FROM_ALL ? $allplatform : null,
                'value' => $getCustomer ? $getCustomer['platform_code'] : null,
            ],
            [
                'name' => 'after_sale_id',
                'alias' => 't',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'order_id',
                'alias' => 't',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'buyer_id',
                'type' => 'text',
                'search' => '=',
                'value' => $getCustomer ? $getCustomer['buyer_id'] : null,
            ],
            [
                'name' => 'department_id',
                'type' => 'dropDownList',
                'data' => BasicConfig::getParentList(52),
                'htmlOptions' => [],
                'search' => '=',
            ],
            [
                'name' => 'reason_id',
                'type' => 'dropDownList',
                'data' => self::getOrderReasonList(),
                'htmlOptions' => [],
                'search' => '=',
            ],
            [
                'name' => 'type',
                'type' => 'dropDownList',
                'data' => self::getOrderTypeList(),
                'search' => '=',
            ],
            [
                'name' => 'status_text',
                'type' => 'dropDownList',
                'data' => self::getOrderStatusList(),
                'search' => '=',
            ],
            [
                'name' => 'refund_status',
                'type' => 'dropDownList',
                'is_filtered' => false,
                'data' => $this->refundStatusMap,
                'search' => '=',
            ],
            [
                'name' => 'create_by',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'account_id',
                'type' => 'search',
                'data' => Account::getIdNameKVList($_REQUEST['platform_code']),
                'search' => '='
            ],
            [
                'name' => 'time_type',
                'type' => 'dropDownList',
//                'is_filtered' => true,
                'data' => $this->timeMap, //[1=>'售后单创建时间',2=>'退款单退款成功时间'],
//                'search' => '=',
            ],
            [
                'name' => 'start_time',
                'type' => 'date_picker',
                'search' => '<',
            ],
            [
                'name' => 'end_time',
                'type' => 'date_picker',
                'search' => '>',
            ],
            [
                'name' => 'sku',
                'type' => 'text',
                'search' => '=',
            ],
        ];
    }

    /**
     * 获取属性标签
     * @return array
     */
    public function attributeLabels() {
        return [
            'after_sale_id' => '售后单号',
            'after_sale_id_text' => '售后单号',
            'order_id' => '对应订单号',
            'type' => '售后类型',
            'type_text' => '售后类型',
            'platform_code' => '所属平台',
            'department_text' => '责任归属部门',
            'department_id' => '责任归属部门id',
            'reason_text' => '售后单原因',
            'reason' => '平台退款原因',
            'reason_id' => '售后单原因',
            'buyer_id' => '买家ID',
            'approver' => '审核人',
            'approve_time' => '审核时间',
            'remark' => '备注',
            'status_text' => '状态',
            'refund_status' => '退款状态',
            'refund_amount' => '退款金额',
            'refund_time' => '退款时间',
            'refund_amount_rmb' => '退款金额(RMB)',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
            'edit_after_sales_order' => '处理方式',
            'fail_reason' => '退款失败原因',
            'time_type' => '时间类型',
            'audit_info' => '审核状态/人/时间',
            'return_status' => '退货状态',
            'return_time' => '退货时间',
            'create_info' => '创建人/创建时间',
            'refund_status_time' => '退款状态/退款时间',
            'refund_amount_info' => '退款金额/退款金额(RMB)'
        ];
    }

    /**
     * 得到原因类型
     */
    public static function getOrderReasonList() {
        $list = RefundReturnReason::getList();
        $result = array();

        foreach ($list as $key => $value) {
            $result[$value->id] = $value->content;
        }

        return $result;
    }

    /**
     * @desc 获取售后类型列表
     * @param string $key
     * @return Ambigous <string>|string|multitype:string
     */
    public static function getOrderTypeList($key = null) {
        $list = [
            self::ORDER_TYPE_REFUND => '退款',
//            self::ORDER_TYPE_RETURN   => '退货',
            self::ORDER_TYPE_REDIRECT => '重寄',
        ];
        if (!is_null($key)) {
            if (array_key_exists($key, $list))
                return $list[$key];
            else
                return '';
        }
        return $list;
    }

    /**
     * @desc 获取状态列表
     * @param string $key
     * @return Ambigous <string>|string|multitype:string
     */
    public static function getOrderStatusList($key = null) {
        $list = [
            self::ORDER_STATUS_WATTING_AUDIT => "未审核",
            self::ORDER_STATUS_AUDIT_PASSED => '审核通过',
            self::ORDER_STATUS_AUDIT_NO_PASSED => "退回修改",
            self::ORDER_STATUS_COMPLETED => "完成",
        ];
        if (!is_null($key)) {
            if (array_key_exists($key, $list))
                return $list[$key];
            else
                return '';
        }
        return $list;
    }

    /**
     * @author alpha
     * @desc
     * @param null $key
     * @return array|mixed|string
     */
    public static function getOrderStatusListText($key = null) {
        $list = [
            self::ORDER_STATUS_WATTING_AUDIT => "<span >未审核</span>",
            self::ORDER_STATUS_AUDIT_PASSED => "<span style='color:#008000;'>审核通过</span>",
            self::ORDER_STATUS_AUDIT_NO_PASSED => "<span style='color:#FF0000;'>退回修改</span>",
            self::ORDER_STATUS_COMPLETED => "<span style='color:#FF7F00;'>完成</span>",
        ];
        if (!is_null($key)) {
            if (array_key_exists($key, $list))
                return $list[$key];
            else
                return '';
        }
        return $list;
    }

    /**
     * 获取售后类型model
     * @param $type
     * @return AfterSalesRedirect|AfterSalesRefund|AfterSalesReturn|null
     */
    public static function getAfterSalesModel($type) {
        switch ($type) {
            case self::ORDER_TYPE_REFUND:
                return new AfterSalesRefund();
            case self::ORDER_TYPE_RETURN:
                return new AfterSalesReturn();
            case self::ORDER_TYPE_REDIRECT:
                return new AfterSalesRedirect();
        }
        return null;
    }

    /**
     * 售后审核
     * @param $status
     * @return bool
     * @throws \yii\db\Exception
     */
    public function audit($status) {
        $dbTransaction = $this->getDb()->beginTransaction();
        $this->approve_time = date('Y-m-d H:i:s');
        try {
            if ($status == self::ORDER_STATUS_AUDIT_PASSED) {
                $model = self::getAfterSalesModel($this->type);
                if (!$model)
                    return false;
                $flag = $model->audit($this);

                if (!$flag) {
                    $this->error_message = $model->error_message;
                    return false;
                }
            }
            $user = \Yii::$app->user->getIdentity();
            $userName = $user->login_name;
            $this->status = $status;
            $this->approver = $userName;
            $flag = $this->save(false, ['status', 'approver', 'approve_time']);
            if (!$flag)
                return false;
            $dbTransaction->commit();
            return true;
        } catch (\Exception $e) {
            $dbTransaction->rollBack();
            return false;
        }
    }

    /**
     * @desc 检查订单是否有售后单
     * @param unknown $platformCode
     * @param unknown $orderId
     * @return \yii\db\static
     */
    public static function hasAfterSalesOrder($platformCode, $orderId) {
        return self::findOne(['platform_code' => $platformCode, 'order_id' => $orderId]);
    }

    /**
     * 通过订单id查找改订单id已经审核通过的所有的售后退款单
     * @param $params 参数数组 ['order_id' => $order_id, 'platform_code' => $platform_code]
     * @return array
     */
    public static function getAfterSaleIdByOrderId($params) {
        extract($params);
        $result = []; //要返回的售后退款单号

        $where_condition_string = "status=:status and type=:type";
        $where_condition_array[':type'] = static::ORDER_TYPE_REFUND;
        $where_condition_array[':status'] = static::ORDER_STATUS_AUDIT_PASSED;

        //查找指定订单号的审核通过的售后退款单
        if (isset($order_id) && !empty($order_id)) {
            $where_condition_array[':order_id'] = $order_id;
            $where_condition_string = $where_condition_string . " and order_id=:order_id";
        }

        //查找指定平台code的审核通过的售后退款单
        if (isset($platform_code) && !empty($platform_code)) {
            $where_condition_array[':platform_code'] = $platform_code;
            $where_condition_string = $where_condition_string . " and platform_code=:platform_code";
        }

        //查找指定订单号而且类型为退款单而且状态为已经审核通过的售后单
        $query = self::find()->select('after_sale_id')->where($where_condition_string, $where_condition_array);

        if (isset($limit) && !empty($limit)) {
            $query->limit($limit);
        }

        $list = $query->all();

        //组装返回结果
        foreach ($list as $key => $value) {
            $result[] = $value->after_sale_id;
        }

        return $result;
    }

    /**
     * @author alpha
     * @desc 根据订单id 查询所有售后单
     * @param $order_id
     * @param $platform_code
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getAfterSalesOrderByOrderId($order_id, $platform_code) {
        $refund_res = [];
        $return_res = [];
        $redirect_res = [];
        $where_condition_array = [];
        if (isset($order_id) && !empty($order_id)) {
            $where_condition_array[':order_id'] = $order_id;
        }
        if (isset($platform_code) && !empty($platform_code)) {
            $where_condition_array[':platform_code'] = $platform_code;
        }
        $query = self::find()->where(['order_id' => $order_id, 'platform_code' => $platform_code]);
        $list = $query->all();
        foreach ($list as $key => $value) {
            if ($value['type'] == 1) {
                //退款
                $refund_res[] = AfterSalesRefund::findByAfterSaleId($value['after_sale_id']);
            } elseif ($value['type'] == 2) {
                //退货
                $return_res[] = AfterSalesReturn::findByAfterSaleId($value['after_sale_id']);
            } elseif ($value['type'] == 3) {
                $redirect_res[] = AfterSalesRedirect::findByAfterSaleId($value['after_sale_id']);
            }
        }
        //国内退货数据
        $domestic_return = Domesticreturngoods::findByOrderId($order_id);
        $list['refund_res'] = $refund_res;
        $list['return_res'] = $return_res;
        $list['redirect_res'] = $redirect_res;
        $list['domestic_return'] = $domestic_return;
        return $list;
    }

    public static function findByAfterSalesOrderId($saleids) {

        $query = self::find()->select("*")->andWhere(['in', 'after_sale_id', $saleids])->all();

        return $query;
    }

    /**
     * @author allen <2018-1->
     * 1.根据条件获取需要导出的售后单信息(如果有选中参数的直接导出选中参数中的数据)
     * 2.通过符合条件的售后单获取到对应的erp订单ID 保存数组
     * 3.根据保存的erp订单ID 查询导出需要的相关产品信息(返回的数据总带有交易记录的交易ID)
     * 4.根据交易记录ID查询对应的交易记录并处理成导出需要的格式
     * @param $condition
     * @param $where
     * @param $andWhere
     * @return array|\yii\db\ActiveRecord[]
     * @throws \yii\db\Exception
     */
    public static function getReFundData($condition, $where, $andWhere) {
        $query = self::find(); //获取对象
        //查询字段
        $query->select(['t.account_id', 't.type', 't.department_id', 't.platform_code', 't.after_sale_id', 't.order_id', 't.status', 't.reason_id', 't.create_by', 't.remark', 't1.currency', 't1.refund_amount', 't1.refund_time',
            't1.refund_status', 't2.transaction_id', 't3.account_name', 't3.account_short_name', 't.create_time', 't.approve_time']);
        //关联相关表
        $query->from('{{%after_sales_order}} t')
                ->join('LEFT JOIN', '{{%after_sales_refund}} t1', 't.after_sale_id = t1.after_sale_id')
                ->join('LEFT JOIN', '{{%eb_paypal_transaction_record}} t2', 't.transaction_id = t2.transaction_id')
                ->join('LEFT JOIN', '{{%account}} t3', 't.account_id = t3.id');
        //根据不同条件处理查询条件
        if ($condition) {
            $query->where(['in', 't.after_sale_id', $condition]);
        } else {
            $query->where($where);
            if (!empty($andWhere)) {
                foreach ($andWhere as $value) {
                    $query->andWhere($value);
                }
            }
        }

        //限定条件
        $platformArray = isset(\Yii::$app->user->identity->role->platform_code) ? explode(',', \Yii::$app->user->identity->role->platform_code) : Platform::getPlatformAsArray();
        if (empty($params['platform_code'])) {
            $query->andWhere(['in', 't.platform_code', $platformArray]);
        }

        $user_id = Yii::$app->user->identity->id;
        $account_ids = UserAccount::find()
                ->select('account_id')
                ->where(['user_id' => $user_id])
                ->column();
        if (!empty($account_ids)) {
            $query->andWhere(['in', 'account_id', $account_ids]);
        }

        //获取结果集
        $model = $query->orderBy('t.platform_code,t.account_id,t.after_sale_id')->all();

        //获取旧退款原因
        $refundReasonList = RefundReturnReason::getList('Array');
        //获取新退款原因
        $getAllConfigList = BasicConfig::getAllConfigData();
        //售后单审核状态
        $auditStatus = VHelper::afterSalesStatusList();
        //ERP订单完成状态
        $orderStats = VHelper::orderStatusList();
        if (!empty($model)) {
            $order = [];
            $afterOrderId = "";
            $rateMonth = date('Ym'); //默认查当前月汇率
            foreach ($model as $key => $value) {
                if (in_array($value->platform_code, ['ALI', 'AMAZON', 'EB', 'WISH'])) {
                    $order[$value->platform_code][] = $value->order_id;
                } else {
                    $order['Other'][] = $value->order_id;
                }

                $afterOrderId .= "'" . $value->after_sale_id . "',";

                //如果退款时间为空，则登记退款单审核通过的时间，可以认为是退款时间
                if (empty($value->refund_time) && ($value->refund_status == 3)) {
                    $value->refund_time = $value->approve_time;
                }

                //获取退款时间用做查询汇率用 add by allen 2018-05-31 str
                if ($key == 0) {
                    $rateMonth = date('Ym', strtotime($value->refund_time . ' -1 month'));
                }
                //获取退款时间用做查询汇率用 add by allen 2018-05-31 end
            }

            //获取币种转换人民币
            $rateMonth = date('Ym'); //先写死数据[搜索时间目前查出数据不准确先写死数据]
            $rmbReturn = VHelper::getTargetCurrencyAmtAll($rateMonth);
            $afterOrderIds = rtrim($afterOrderId, ',');
            //查询客服售后单的相关产品信息
            if (!empty($afterOrderIds)) {
                $sql = "SELECT after_sale_id,group_concat(product_title separator ',') as pro_name,
                            group_concat(concat(sku,'*',quantity) separator ',') as sku,
                            sum(quantity) as sum_quantity,group_concat(linelist_cn_name separator ',') as line_cn_name 
                            FROM {{%after_sales_product}} WHERE after_sale_id in(" . $afterOrderIds . ")
                            GROUP BY after_sale_id";
                $command = Yii::$app->db->createCommand($sql);
                $datas = $command->queryAll();
                if (!empty($datas)) {
                    foreach ($model as $value) {
                        foreach ($datas as $val) {
                            if ($value->after_sale_id == $val['after_sale_id']) {
                                $value->sku = $val['sku'];
                                $value->quantity = $val['quantity'];
                                $value->sum_quantity = $val['sum_quantity'];
                                $value->pro_name = $val['pro_name'];
                                $value->line_cn_name = $val['line_cn_name'];
                            }
                        }
                    }
                }
            }

            //erp订单相关数据
            //从库订单相关数据
            $response = AfterSalesRefundDown::getCertainInfo($order);

            if (empty($response)) {
                foreach ($model as $k => $v) {
                    if ($v->department_id) {
                        $v->department_id = isset($getAllConfigList[$v->department_id]) ? $getAllConfigList[$v->department_id] : '';
                        $v->reason_id = isset($getAllConfigList[$v->reason_id]) ? $getAllConfigList[$v->reason_id] : '';
                    } else {
                        $v->reason_id = isset($refundReasonList[$v->reason_id]) ? $refundReasonList[$v->reason_id] : '';
                    }
                    $v->status = $auditStatus[$v->status];
                    $v->order_type = '';
                }
            }
            if (!empty($response)) {
                $orderTransactionInfo = [];
                foreach ($model as $k => $val) {
                    foreach ($response as $resp_k => $resp_val) {
                        if ($val->order_id == $resp_val['order_id']) {
                            $val->platform_order_id = $resp_val['platform_order_id'];
                            $val->orientation_order_id = $val->account_short_name . '--' . $val['platform_order_id'];
                            $val->buyer_id = $resp_val['buyer_id'];
                            $val->order_type = isset($resp_val['order_type']) ? $resp_val['order_type'] : ""; //订单类型
                            $val->warehouse_name = $resp_val['warehouse_name'];
                            $val->complete_status = isset($resp_val['complete_status']) ? $orderStats[$resp_val['complete_status']] : ""; //完成状态
                            $val->order_status = $resp_val['order_status']; //订单状态
                            $val->ship_country_name = $resp_val['ship_country_name'];
                            $val->paytime = $resp_val['paytime'];
                            $val->shipped_date = $resp_val['shipped_date']; //订单发货时间
                            $val->currency = $resp_val['currency']; //收款币种
                            $val->total_price = $resp_val['total_price']; //收款总金额
                            $val->rtransaction_id = $resp_val['rtransaction_id']; //交易ID (包含退款收款交易ID需要处理)
                            $val->receive_type = $resp_val['receive_type']; //收款类型 1：收款 0：付款
                            $val->sku = $resp_val['sku']; //!empty($val->sku) ? $val->sku : $resp_val['sku'];
                            $val->quantity = !empty($val->quantity) ? $val->quantity : $resp_val['quantity'];
                            $val->sum_quantity = !empty($val->sum_quantity) ? $val->sum_quantity : $resp_val['sum_quantity'];
                            $val->pro_name = !empty($val->pro_name) ? $val->pro_name : $resp_val['pro_name'];
                            $val->line_cn_name = !empty($val->line_cn_name) ? $val->line_cn_name : $resp_val['line_cn_name'];
                            $val->ship_name = $resp_val['ship_name'];
							$platformArray = ['WISH', 'LAZADA', 'JOOM', 'MALL', 'JUM', 'CDISCOUNT', 'SHOPEE'];
                            $reason = '-';
                            if (in_array($val->platform_code, $platformArray)) {
                                $reason = PlatformRefundOrder::getReasonPlatform($val->platform_code, '', $resp_val['platform_order_id']) ? : '-';
                            }
                            $val->reason = $reason;
                            if (isset($resp_val['order_number'])) {
                                $val->order_number = $resp_val['order_number'];
                            }

                            if ($val->type == 3) {
                                if ($resp_val['parent_amazon_fulfill_channel'] == 'AFN')
                                    $val->amazon_fulfill_channel = 'FBA';
                                if ($resp_val['parent_amazon_fulfill_channel'] == 'MFN')
                                    $val->amazon_fulfill_channel = 'FBM';
                            } else {
                                if ($resp_val['amazon_fulfill_channel'] == 'AFN')
                                    $val->amazon_fulfill_channel = 'FBA';
                                if ($resp_val['amazon_fulfill_channel'] == 'MFN')
                                    $val->amazon_fulfill_channel = 'FBM';
                            }


                            if ($val->platform_code == Platform::PLATFORM_CODE_EB && $resp_val['rtransaction_id']) {
                                $orderTransactionInfo[$val->order_id] = $resp_val['rtransaction_id'];
                            }
                        }
                    }
                    if ($val->department_id) {
                        $val->department_id = isset($getAllConfigList[$val->department_id]) ? $getAllConfigList[$val->department_id] : '';
                        $val->reason_id = isset($getAllConfigList[$val->reason_id]) ? $getAllConfigList[$val->reason_id] : '';
                    } else {
                        $val->reason_id = isset($refundReasonList[$val->reason_id]) ? $refundReasonList[$val->reason_id] : '';
                    }
                    $val->status = $auditStatus[$val->status];
                }
            }

            //获取交易信息
            if (!empty($orderTransactionInfo)) {
                $transactionInfo = Transactionrecord::getAfterSalesTransactionRecord($orderTransactionInfo);
                //循环将交易记录保存到对象中
                if ($transactionInfo) {
                    foreach ($model as $key => $value) {
                        foreach ($transactionInfo as $k => $val) {
                            if ($value->order_id == $val['order_id'] && $value->refund_transaction_id == "" && $val['tag'] == 2) {
                                $value->receiver_email = isset($val['receiverEmail']) ? $val['receiverEmail'] : "";
                                $value->transaction_id = isset($val['transactionId']) ? $val['transactionId'] : "";
                                $value->payer_email = isset($val['payerEmail']) ? $val['payerEmail'] : "";
                                $value->refund_transaction_id = isset($val['refundTransactionID']) ? $val['refundTransactionID'] : "";
                                $value->refund_currency = isset($val['currency']) ? $val['currency'] : "";
                                $value->refund_amt = isset($val['amt']) && !empty($val['amt']) ? $val['amt'] : $value['refund_amount'];
                                $target_currency_amt = $value->refund_amt * $rmbReturn[$value->currency];
                                $rmb = sprintf("%.2f", $target_currency_amt);
                                $value->refund_amt_rmb = $rmb ? $rmb : '';
                                $transactionInfo[$k]['tag'] = 1;
                            } else {
                                $value->refund_amt = $value['refund_amount'];
                                $target_currency_amt = $value['refund_amount'] * $rmbReturn[$value->currency];
                                $rmb = sprintf("%.2f", $target_currency_amt);
                                $value->refund_amt_rmb = $rmb ? $rmb : '';
                            }
                        }
                    }
                } else {
                    foreach ($model as $key => $value) {
                        $value->refund_amt = $value['refund_amount'];
                        $target_currency_amt = $value['refund_amount'] * $rmbReturn[$value->currency];
                        $rmb = sprintf("%.2f", $target_currency_amt);
                        $value->refund_amt_rmb = $rmb ? $rmb : '';
                    }
                }
            } else {
                foreach ($model as $key => $value) {
                    $value->refund_amt = $value['refund_amount'];
                    $target_currency_amt = $value['refund_amount'] * $rmbReturn[$value->currency];
                    $rmb = sprintf("%.2f", $target_currency_amt);
                    $value->refund_amt_rmb = $rmb ? $rmb : '';
                }
            }
        }
        return $model;
    }

    /**
     * @author alpha
     * @desc 下载售后单 获取退货数据
     * @param $condition
     * @param $where
     * @param $andWhere
     * @return array|\yii\db\ActiveRecord[]
     * @throws \yii\db\Exception
     */
    public static function getRetuenData($condition, $where, $andWhere) {
        $query = self::find(); //获取对象
        //查询字段
        $query->select(['t.account_id', 't.type', 't.department_id', 't.platform_code', 't.after_sale_id', 't.order_id', 't.status', 't.reason_id', 't.create_by', 't.remark', 't1.return_time',
            't1.return_status', 't1.rma', 't1.tracking_no', 't3.account_name', 't3.account_short_name', 't.create_time', 't.approve_time']);
        //关联相关表
        $query->from('{{%after_sales_order}} t')
                ->join('LEFT JOIN', '{{%after_sales_return}} t1', 't.after_sale_id = t1.after_sale_id')
                ->join('LEFT JOIN', '{{%eb_paypal_transaction_record}} t2', 't.transaction_id = t2.transaction_id')
                ->join('LEFT JOIN', '{{%account}} t3', 't.account_id = t3.id');
        //根据不同条件处理查询条件
        if ($condition) {
            $query->where(['in', 't.after_sale_id', $condition]);
        } else {
            $query->where($where);
            if (!empty($andWhere)) {
                foreach ($andWhere as $value) {
                    $query->andWhere($value);
                }
            }
        }
        //限定条件
        $platformArray = isset(\Yii::$app->user->identity->role->platform_code) ? explode(',', \Yii::$app->user->identity->role->platform_code) : Platform::getPlatformAsArray();
        if (empty($params['platform_code'])) {
            $query->andWhere(['in', 't.platform_code', $platformArray]);
        }
        $user_id = Yii::$app->user->identity->id;
        $account_ids = UserAccount::find()
                ->select('account_id')
                ->where(['user_id' => $user_id])
                ->column();
        if (!empty($account_ids)) {
            $query->andWhere(['in', 'account_id', $account_ids]);
        }
        //获取结果集
        $model = $query->orderBy('t.platform_code,t.account_id,t.after_sale_id')->all();
        //获取旧退款原因
        $refundReasonList = RefundReturnReason::getList('Array');
        //获取新退款原因
        $getAllConfigList = BasicConfig::getAllConfigData();
        //售后单审核状态
        $auditStatus = VHelper::afterSalesStatusList();
        //ERP订单完成状态
        $orderStats = VHelper::orderStatusList();
        if (!empty($model)) {
            $order = [];
            $afterOrderId = "";
            foreach ($model as $key => $value) {
                if (in_array($value->platform_code, ['ALI', 'AMAZON', 'EB', 'WISH'])) {
                    $order[$value->platform_code][] = $value->order_id;
                } else {
                    $order['Other'][] = $value->order_id;
                }
                $afterOrderId .= "'" . $value->after_sale_id . "',";

                //如果退款时间为空，则登记退款单审核通过的时间，可以认为是退款时间
                if (empty($value->return_time) && ($value->return_status == 3)) {
                    $value->return_time = $value->approve_time;
                }
            }
            $afterOrderIds = rtrim($afterOrderId, ',');
            //查询客服售后单的相关产品信息
            if (!empty($afterOrderIds)) {
                $sql = "SELECT after_sale_id,group_concat(product_title separator ',') 
                      as pro_name,group_concat(concat(sku,'*',quantity) separator ',') as sku,
                      sum(quantity) as sum_quantity,group_concat(linelist_cn_name separator ',') as line_cn_name 
                      FROM {{%after_sales_product}} WHERE after_sale_id in(" . $afterOrderIds . ") 
                      GROUP BY after_sale_id";
                $command = Yii::$app->db->createCommand($sql);
                $datas = $command->queryAll();
                if (!empty($datas)) {
                    foreach ($model as $value) {
                        foreach ($datas as $val) {
                            if ($value->after_sale_id == $val['after_sale_id']) {
                                $value->sku = $val['sku'];
                                $value->quantity = $val['quantity'];
                                $value->sum_quantity = $val['sum_quantity'];
                                $value->pro_name = $val['pro_name'];
                                $value->line_cn_name = $val['line_cn_name'];
                            }
                        }
                    }
                }
            }
            //erp订单相关数据
            if (!empty($response)) {
                $orderTransactionInfo = [];
                foreach ($model as $k => $val) {
                    foreach ($response as $resp_k => $resp_val) {
                        if ($val->order_id == $resp_val['order_id']) {
                            $val->platform_order_id = $resp_val['platform_order_id'];
                            $val->orientation_order_id = $val->account_short_name . '--' . $val['platform_order_id'];
                            $val->buyer_id = $resp_val['buyer_id'];
                            $val->order_type = isset($resp_val['order_type']) ? $resp_val['order_type'] : ""; //订单类型
                            $val->warehouse_name = $resp_val['warehouse_name'];
                            $val->complete_status = isset($resp_val['complete_status']) ? $orderStats[$resp_val['complete_status']] : ""; //完成状态
                            $val->order_status = $resp_val['order_status']; //订单状态
                            $val->ship_country_name = $resp_val['ship_country_name'];
                            $val->paytime = $resp_val['paytime'];
                            $val->shipped_date = $resp_val['shipped_date']; //订单发货时间
                            $val->currency = $resp_val['currency']; //收款币种
                            $val->total_price = $resp_val['total_price']; //收款总金额
                            $val->rtransaction_id = $resp_val['rtransaction_id']; //交易ID (包含退款收款交易ID需要处理)
                            $val->receive_type = $resp_val['receive_type']; //收款类型 1：收款 0：付款
                            $val->sku = !empty($val->sku) ? $val->sku : $resp_val['sku'];
                            $val->quantity = !empty($val->quantity) ? $val->quantity : $resp_val['quantity'];
                            $val->sum_quantity = !empty($val->sum_quantity) ? $val->sum_quantity : $resp_val['sum_quantity'];
                            $val->pro_name = !empty($val->pro_name) ? $val->pro_name : $resp_val['pro_name'];
                            $val->line_cn_name = !empty($val->line_cn_name) ? $val->line_cn_name : $resp_val['line_cn_name'];
                            $val->ship_name = $resp_val['ship_name'];
                            if (isset($resp_val['order_number'])) {
                                $val->order_number = $resp_val['order_number'];
                            }

                            if ($val->type == 3) {
                                if ($resp_val['parent_amazon_fulfill_channel'] == 'AFN')
                                    $val->amazon_fulfill_channel = 'FBA';
                                if ($resp_val['parent_amazon_fulfill_channel'] == 'MFN')
                                    $val->amazon_fulfill_channel = 'FBM';
                            } else {
                                if ($resp_val['amazon_fulfill_channel'] == 'AFN')
                                    $val->amazon_fulfill_channel = 'FBA';
                                if ($resp_val['amazon_fulfill_channel'] == 'MFN')
                                    $val->amazon_fulfill_channel = 'FBM';
                            }
                            if ($val->platform_code == Platform::PLATFORM_CODE_EB && $resp_val['rtransaction_id']) {
                                $orderTransactionInfo[$val->order_id] = $resp_val['rtransaction_id'];
                            }
                        }
                    }
                    if ($val->department_id) {
                        $val->department_id = isset($getAllConfigList[$val->department_id]) ? $getAllConfigList[$val->department_id] : '';
                        $val->reason_id = isset($getAllConfigList[$val->reason_id]) ? $getAllConfigList[$val->reason_id] : '';
                    } else {
                        $val->reason_id = isset($refundReasonList[$val->reason_id]) ? $refundReasonList[$val->reason_id] : '';
                    }
                    $val->status = $auditStatus[$val->status];
                }
            }
        }
        return $model;
    }

    /**
     * 判断当前订单是否存在售后单
     * @param type $orderId
     * @param type $type
     * @return type
     * @author allen <2018-1-27>
     */
    public static function isSetAfterSalesOrder($orderId, $type) {
        return self::find()->where(['order_id' => $orderId, 'type' => $type])->all();
    }

    /**
     *
     * @param type $afterSalesId
     * @param type $reutrnFileds
     * @return type获取售后单相关信息
     * @author allen <2018-2-1>
     */
    public static function getAfterSalsesData($afterSalesId, $reutrnFileds) {
        $model = self::find()->select($reutrnFileds)->where(['after_sale_id' => $afterSalesId])->asArray()->one();
        if (!empty($model)) {
            if (isset($model['reason_id'])) {
                $reason_text = RefundReturnReason::getReasonContent($model['reason_id']);
            } else {
                $reason_text = isset($model['remark']) ? $model['remark'] : "-";
            }
        }
        return $reason_text ? $reason_text : '-';
    }

    /**
     * @author alpha
     * @desc 退货状态
     * @param $key
     * @return array|mixed|string
     */
    public static function getReturnStatusText($key) {
        $list = [
            1 => "<span style='color:#f0ad4e;'>待收货</span>",
            2 => "<span style='color:#008000;'>已收货</span>",
            3 => "<span style='color:#ff0000;'>取消退货</span>"
        ];
        if (!is_null($key)) {
            if (array_key_exists($key, $list))
                return $list[$key];
            else
                return '';
        }
        return $list;
    }

    public function getRootline($id) {
        static $linest;
        empty($linest) && $linest = $this->getCnnameLists();
        $one = isset($linest[$id]) ? $linest[$id] : 0;

        if (!empty($one)) {
            $parent = $one;
            if ($parent['linelist_parent_id'] != 0) {
                $parent = $linest[$one['linelist_parent_id']];
                while ($parent['linelist_parent_id'] > 0) {
                    $parent = $linest[$parent['linelist_parent_id']];
                }
            }

            return !empty($parent) ? $parent['linelist_cn_name'] : '';
        }
        return '';
    }

    /**
     * 修改原因检查当前售后单是否是当前月份
     * @param type $afterOrderId 售后单号
     * @param type $model 售后单对象
     * @return boolean ture:是当前月份  false:非当前月份
     * @author allen <2018-09-24>
     */
    public static function changeReasonCheckDate($afterOrderId, $model) {
        $bool = TRUE;
        $type = $model->type;

        switch ($type) {
            //退款单
            case 1:
                $refundModel = AfterSalesRefund::find()->where(['after_sale_id' => $afterOrderId])->asArray()->one();
                if ($refundModel) {
                    //当退款状态为已退款 审核状态为已审核 退款时间为空 的时候可以判断当前退款单是登记售后单  退款时间为审核时间
                    if ($refundModel['refund_status'] == 3 && $model->status == 2 && empty($refundModel['refund_time'])) {
                        $refundModel['refund_time'] = $model->approve_time;
                        if (date('Y-m') != date('Y-m', strtotime($refundModel['refund_time']))) {
                            $bool = FALSE;
                        }
                    }
                }
                break;
            //退货单
            case 2;
                $salesreturn = AfterSalesReturn::getAfterSalesretruen($afterOrderId);
                //当退货状态为已推送收货             
                if ($salesreturn['return_status'] == 2) {
                    //$salesreturn['return_time'] = $model->approve_time;
                    if (date('Y-m') != date('Y-m', strtotime($model->approve_time))) {
                        $bool = FALSE;
                    }
                }
                break;


            //重寄单
            case 3:
                if (date('Y-m') != date('Y-m', strtotime($model->approve_time))) {
                    $bool = FALSE;
                }
                break;
        }
        return $bool;
    }

    /**
     * @param $platform_code
     * @param $order_id
     * @param $sku
     * @param $refund_amount
     * @param $currency
     * @param $type
     * @return bool
     * 计算退款重寄sku金额/成本
     */
    public static function getRefundRedirectData($platform_code, $order_id, $sku, $refund_amount, $currency, $type, $redirect_order_id = '') {
        if (empty($type)) {
            return false;
        }
        $platform = OrderKefu::getOrderModel($platform_code);

        if (empty($platform)) {
            return false;
        }
        if (empty($order_id) || empty($sku)) {
            return false;
        }

        if ($type == 1) {

            $detail = OrderKefu::model($platform->orderdetail)->select('transaction_id, quantity, total_price as sku_total_price')->where(['order_id' => $order_id, 'sku' => $sku])->asArray()->one();
            if (empty($detail)) {
                $detail = OrderKefu::model($platform->orderdetailcopy)->select('transaction_id, quantity, total_price as sku_total_price')->where(['order_id' => $order_id, 'sku' => $sku])->asArray()->one();
            }

            $model = OrderKefu:: model($platform->ordermain)->select('total_price')->where(['order_id' => $order_id])->asArray()->one();
            if (empty($model)) {
                $model = OrderKefu:: model($platform->ordermaincopy)->select('total_price')->where(['order_id' => $order_id])->asArray()->one();
            }

            if (!empty($detail['transaction_id'])) {
                $transactionrecord = Transactionrecord::find()->select('amt')->where(['transaction_id' => $detail['transaction_id']])->asArray()->one();
                $return_amt = $transactionrecord['amt'];
            }

            if (empty($transactionrecord['amt'])) {
                $return_amt = $refund_amount;
            }
            $data = [];
            //计算SKU的退款金额
            //SKU总计金额/订单总费用/SKU购买数量*退款金额*SKU退款数量
            if (isset($model['total_price']) && $detail['quantity']) {
                $target_amt = ($detail['sku_total_price'] / $model['total_price'] / $detail['quantity']) * $return_amt;
            } else {
                $target_amt = 0;
            }

            $data['sku_refund_amt'] = sprintf("%.2f", $target_amt);
            $rateMonth = date('Ym');

            $rmbReturn = VHelper::getTargetCurrencyAmtAll($rateMonth);
            $target_currency_amt = $target_amt * $rmbReturn[$currency];
            $rmb = sprintf("%.2f", $target_currency_amt);
            //rmb
            $data['sku_refund_amt_rmb'] = $rmb ? $rmb : 0;

            return $data;
        }
        if ($type == 2) {
            //调用接口获取单个sku的重寄成本 todo platform_code order_id
            /*             * *****
             * purchase_cost_new1，最新采购价，
             * package_cost包装成本，
             * packing_cost包材成本，
             * exchange_price汇况损失成本，
             * shipping_cost运费成本，
             * stock_price库存折扣，
             * first_carrier_cost头程费用，
             * duty_cost_new1关税，
             * extra_price偏远附加费，
             * exceedprice超尺寸附加费，
             * processing海外仓处理费，
             * pack复核打包费，
             * residence_price住宅费
             * ********* */

            $detail = OrderKefu::model($platform->orderdetail)->select('transaction_id, quantity, total_price as sku_total_price')->where(['order_id' => $order_id, 'sku' => $sku])->asArray()->one();

            if (empty($detail)) {
                $detail = OrderKefu::model($platform->orderdetailcopy)->select('transaction_id, quantity, total_price as sku_total_price')->where(['order_id' => $order_id, 'sku' => $sku])->asArray()->one();
            }
            $model = OrderKefu:: model($platform->ordermain)->select('warehouse_id, total_price')->where(['order_id' => $order_id])->asArray()->one();
            if (empty($model)) {
                $model = OrderKefu:: model($platform->ordermaincopy)->select('warehouse_id, total_price')->where(['order_id' => $order_id])->asArray()->one();
            }

            $warehouse = Warehouse::find()->select('warehouse_name')->where(['id' => $model['warehouse_id']])->asArray()->one();

            $redirect_detail = OrderRedirectDetail::find()->select('quantity')->where(['sku' => $sku])->asArray()->one();

           // $url = 'http:/1m7597h064.iok.la:10006/services/orders/order/getprofitdetail';
             $url = "http://120.78.243.154/services/orders/order/getprofitdetail";
            $url .= '?order_id=' . $redirect_order_id . '&platform_code=EB';
            //$url .= '?order_id=' . $redirect_order_id . '&platform_code=' . $platform_code;
            $amt_redirect = VHelper::curl_post_async($url);

            $amt_redirect = mb_convert_encoding($amt_redirect, "gb2312", "utf-8");
            if (substr($amt_redirect, 0, 1) != '{') {
                $amt_redirect = substr($amt_redirect, strpos($amt_redirect, '{"status"'));
            }

            $sku_data = json_decode($amt_redirect, true);
            $data = [];

            if ($sku_data['status'] == true) {
                $order_sku_arr = $sku_data['data'];
                foreach ($order_sku_arr as $k => $item) {

                    if ($k == $sku) {
                        //SKU加钱重寄金额
                        $rmbReturn = VHelper::getTargetCurrencyAmtAll(date('Ym'));
                        if (isset($model['total_price']) && isset($detail['quantity'])) {
                            $sku_redirect_price = $detail['sku_total_price'] / $model['total_price'] / $detail['quantity'] * $refund_amount * $redirect_detail['quantity'] * $rmbReturn[$currency];
                        } else {
                            $sku_redirect_price = 0;
                        }

                        $rmb = sprintf("%.2f", $sku_redirect_price);

                        //获取当前sku的重寄成本
                        if (in_array($warehouse['warehouse_name'], ['虚拟海外仓', '易佰东莞仓'])) {
                            //国内仓

                            $sku_redirect_amt_rmb = ($item['purchase_cost_new1'] + $item['package_cost'] +
                                    $item['packing_cost'] + $item['exchange_price'] + $item['shipping_cost']) - $rmb;

                            $sku_redirect_amt = $sku_redirect_amt_rmb / $rmbReturn[$currency];
                            $data['sku_redirect_amt'] = sprintf("%.2f", $sku_redirect_amt);
                            $data['sku_redirect_amt_rmb'] = sprintf("%.2f", $sku_redirect_amt_rmb);
                        } else {
                            //海外仓
                            $sku_redirect_amt_rmb = ($item['purchase_cost_new1'] + $item['package_cost'] +
                                    $item['packing_cost'] + $item['exchange_price'] + $item['shipping_cost'] + $item['stock_price'] + $item['first_carrier_cost'] + $item['duty_cost_new1'] + $item['extra_price'] + $item['exceedprice'] + $item['processing'] + $item['pack'] + $item['residence_price']) - $rmb;

                            $sku_redirect_amt = $sku_redirect_amt_rmb / $rmbReturn[$currency];
                            $data['sku_redirect_amt'] = sprintf("%.2f", $sku_redirect_amt);
                            $data['sku_redirect_amt_rmb'] = sprintf("%.2f", $sku_redirect_amt_rmb);
                        }
                    }
                }
            }else{
                $data['sku_redirect_amt'] = 0;
                $data['sku_redirect_amt_rmb'] = 0;
            }
            return $data;
        }
    }

}

<?php

namespace app\modules\aftersales\models;

use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\UserAccount;
use app\modules\mails\components\GridView;
use app\modules\orders\models\OrderKefu;
use app\modules\orders\models\Warehouse;
use app\modules\systems\models\ErpOrderApi;
use yii\helpers\Url;
use Yii;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\BasicConfig;

class AfterSalesReturn extends AfterSalesModel {

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
    public static $return_table;
    public $returnStatusMap = array(1 => '待收货', 2 => '已推送收货', 3 => '取消退货');
    public static $is_receiveMap = [1 => '未收到货', 2 => '已收到货'];
    public $timeMap = [
        1 => '创建时间',
        2 => '审核时间',
        4 => '退货时间'
    ];

    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName() {
        if (self::$return_table == 'RETURN') {
            return '{{%after_sales_return}}';
        }
        return '{{%after_sales_order}}';
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\ActiveRecord::attributes()
     */
    public function attributes() {
        $attributes = parent::attributes();
        $downAttributes = ['order_type', 'order_id', 'audit_info', 'return_status', 'return_time', 'rma', 'tracking_no', 'return_info', 'department_text', 'reason_text', 'type_text', 'status_text', 'after_sale_id_text', 'id', 'edit_after_sales_order','refund_code',
            'platform_order_id', 'buyer_id', 'sku', 'warehouse_name', 'complete_status', 'order_status', 'ship_country_name', 'warehouse_id',
            'receive_type', 'sum_quantity', 'quantity', 'product_title', 'pro_name', 'line_cn_name', 'linelist_cn_name', 'ship_name', 'return_by', 'return_status_info',
            'carrier', 'order_number', 'refund_amount_rmb', 'create_info', 'modify_info', 'is_receive'];
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
     * @desc
     */
    public function searchList($params = [], $url = null) {
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'after_sale_id' => SORT_DESC
        );
        $query = self::find();
        $query->select(['t.after_sale_id', 't.order_id', 't.refund_code','t.type', 't.platform_code', 't.department_id', 't.reason_id', 't.buyer_id', 't.approver', 't.approve_time', 't.status', 't.create_by', 't2.create_time', 't2.create_by', 't2.modify_by', 't2.modify_time',
            't2.warehouse_id', 't2.carrier', 't2.tracking_no', 't2.rma', 't2.remark', 't2.return_status', 't2.return_time', 't2.return_by', 't2.is_receive']);
        $query->from(self::tableName() . ' as t');

        $query->innerJoin('{{%after_sales_return}} t2', 't2.after_sale_id = t.after_sale_id');
        //
        if (isset($params['return_status']) && !empty($params['return_status'])) {
            $query->andWhere('t2.return_status = ' . $params['return_status']);
        }
        if (isset($params['time_type']) && $params['time_type'] == 4) {
            //退退货时间
            if (!empty($params['start_time']) && !empty($params['end_time'])) {
                $query->andWhere(['between', 't2.return_time', $params['start_time'], $params['end_time']]);
            } elseif (!empty($params['start_time'])) {
                $query->andWhere(['>=', 't2.return_time', $params['start_time']]);
            } elseif (!empty($params['end_time'])) {
                $query->andWhere(['<=', 't2.return_time', $params['end_time']]);
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
         //退货编码
        if(!empty($params['refund_code'])){
            $query->andWhere(['refund_code'=>$params['refund_code']]);
            unset($params['refund_code']);
        }
     
        if (isset($params['warehouse_id']) && !empty($params['warehouse_id'])) {
            $query->andWhere('t2.warehouse_id = ' . $params['warehouse_id']);
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
        $warehouseList = Warehouse::getAllWarehouseList(true);
        foreach ($models as $key => $model) {

            $models[$key]->setAttribute('is_receive', self::getIsReceiveStatus($model->is_receive));
            $models[$key]->setAttribute('audit_info', self::getOrderStatusList($model->status) . '<br>' . $model->approver . '<br>' . $model->approve_time);
            $models[$key]->setAttribute('create_info', $model->create_by . '<br>' . $model->create_time);
            $models[$key]->setAttribute('modify_info', $model->modify_by . '<br>' . $model->modify_time);
            $models[$key]->setAttribute('warehouse_name', isset($model->warehouse_id) && $model->warehouse_id > 0 ? $warehouseList[$model->warehouse_id] : '');
            //退货添加 退货状态/时间 RMA/退货跟踪号
            $models[$key]->setAttribute('return_status_info', isset($this->returnStatusMap[$model->return_status]) ? $this->returnStatusMap[$model->return_status] . '<br>' . $model->modify_by . '<br>' . $model->modify_time : '-' . '<br>' . $model->modify_by . '<br>' . $model->modify_time);
            $models[$key]->setAttribute('return_status', isset($this->returnStatusMap[$model->return_status]) ? $this->returnStatusMap[$model->return_status] : '-');
            if (!empty($model->tracking_no)) {
                $tracking_url = '<a href="https://t.17track.net/en#nums=' . $model->tracking_no . '" target="_blank" title="查看物流跟踪信息">' . $model->tracking_no . '</a>';
            } else {
                $tracking_url = '-';
            }
            $models[$key]->setAttribute('return_info', $model->rma . '<br>' . $tracking_url);
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
            $models[$key]->setAttribute('status_text', self::getOrderStatusList($model->status));
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
                        'after_sales_id' => $after_sale_id,
                        'status' => AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED]) . '">审核通过</a></li>
                <li><a class="ajax-button" href="' . Url::toRoute(['/aftersales/order/audit',
                        'url' => $tourl,
                        'after_sales_id' => $after_sale_id,
                        'status' => AfterSalesOrder::ORDER_STATUS_AUDIT_NO_PASSED]) . '">退回修改</a></li>
                 <li><a style="cursor: pointer"  onclick="cancelReturn(' . "'$after_sale_id'" . ',3)" >取消退货</a></li>
                 <li><a style="cursor: pointer"  onclick="deleteReturn(' . "'$after_sale_id'" . ')" >刪除</a></li>';

            if ($model->status == AfterSalesOrder::ORDER_STATUS_AUDIT_NO_PASSED && GridView::_aclcheck(Yii::$app->user->identity->id, $url)) {
                $href .= '<li><a class="edit-button"  _width ="100%" _height="100%" href="' . Url::toRoute([$url,
                            'after_sales_id' => $after_sale_id,
                            'type' => $type]) . '">修改售后单</a></li>';
            }
            $href .= '</ul>
                        </div>';
        } else {
            //退货操作
            if ($type == self::ORDER_TYPE_RETURN) {
                $href = '<div class="btn-group btn-list">
            <button type="button" class="btn btn-default btn-sm">' . Yii::t('system', 'Operation') . '</button>
            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
                <span class="sr-only">' . Yii::t('system', 'Toggle Dropdown List') . '</span>
            </button>';
                //1 默认待审核 2 审核通过 3 退回修改 $model->status
                //1 默认待推送收货 2 已推送收货 3 取消退货 $model->return_status
                //1 待收货 2 已收货
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
                                    'platform_code' => $platform_code,
                                    'after_sales_id' => $after_sale_id,
                                    'status' => AfterSalesOrder::ORDER_STATUS_AUDIT_NO_PASSED]) . '">退回修改</a></li>
                                     <li><a style="cursor: pointer" onclick="cancelReturn(' . "'$after_sale_id'" . ',3)" >取消退货</a></li>';
                        $href .= '</ul></div>';
                    }
                }

                if ($model->is_receive == '未收到货') {
                    //确认审核通过 退回修改 取消退货
                    $href .= '<ul class="dropdown-menu" rol="menu">
                                     <li><a style="cursor: pointer" onclick="cancelReturn(' . "'$after_sale_id'" . ',3)" >取消退货</a></li>';
                    $href .= '</ul></div>';
                }
            }
        }
        return $href;
    }

    /**
     * @author alpha
     * @desc 搜索过滤项
     * @return \app\components\multitype|array
     */
    public function filterOptions() {
        $platformArray = isset(Yii::$app->user->identity->role->platform_code) ? explode(',', Yii::$app->user->identity->role->platform_code) : [];
        $platform = [];
        //$allplatform   = Platform::getPlatformAsArray();
        $allplatform = UserAccount::getLoginUserPlatformAccounts();
        if (is_array($platformArray) && !empty($platformArray)) {
            foreach ($platformArray as $value) {
                $platform[$value] = isset($allplatform[$value]) ? $allplatform[$value] : $value;
            }
        }
        $platform = !empty($platform) ? $platform : $allplatform;
        return [
            [
                'name' => 'platform_code',
                'alias' => 't',
                'type' => $_REQUEST['platform_code'] == static::ORDER_SEARCH_CONDITION_FROM_ALL ? 'dropDownList' : 'hidden',
                'search' => '=',
                'data' => $_REQUEST['platform_code'] == static::ORDER_SEARCH_CONDITION_FROM_ALL ? $allplatform : null,
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
                'name' => 'refund_code',
                'alias' => 't',
                'type' => 'text',
                'search' => '=',
            ],  
            [
                'name' => 'buyer_id',
                'type' => 'text',
                'search' => '=',
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
                'name' => 'status_text',
                'type' => 'dropDownList',
                'data' => self::getOrderStatusList(),
                'search' => '=',
            ],
            [
                'name' => 'return_status',
                'type' => 'dropDownList',
                'is_filtered' => false,
                'data' => $this->returnStatusMap,
                'search' => '=',
            ],
            [
                'name' => 'warehouse_id',
                'type' => 'search',
                'data' => Warehouse::getAllWarehouseList(true),
                'search' => '=',
            ],
            [
                'name' => 'time_type',
                'type' => 'dropDownList',
                'data' => $this->timeMap, //[1=>'售后单创建时间',2=>'退款单退款成功时间'],
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
                'name' => 'rma',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'tracking_no',
                'type' => 'text',
                'search' => '=',
            ],
        ];
    }

    public function attributeLabels() {
        return [
            'after_sale_id' => '售后单号',
            'after_sale_id_text' => '售后单号',
            'order_id' => '对应订单号',
             'refund_code' => '退货编码',
            'platform_code' => '所属平台',
            'department_text' => '责任归属部门',
            'department_id' => '责任归属部门id',
            'reason_text' => '售后单原因',
            'reason_id' => '售后单原因',
            'buyer_id' => '买家ID',
            'approver' => '审核人',
            'approve_time' => '审核时间',
            'remark' => '备注',
            'status_text' => '状态',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
            'edit_after_sales_order' => '操作',
            'time_type' => '时间类型',
            'audit_info' => '审核状态/人/时间',
            'return_status' => '退货状态',
            'return_status_info' => '退货状态',
            'return_time' => '退货时间',
            'return_info' => 'RMA/退货跟踪号',
            'create_info' => '创建人/创建时间',
            'modify_info' => '修改人/修改时间',
            'tracking_no' => '退货跟踪号',
            'warehouse_name' => '接受仓库',
            'carrier' => 'Carrier',
            'warehouse_id' => '接受仓库',
            'is_receive' => '是否收货'
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
            self::ORDER_TYPE_RETURN => '退货',
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

    public static function getIsReceiveStatus($key) {
        $list = [
            1 => "未收到货",
            2 => '已收到货',
        ];
        if (array_key_exists($key, $list))
            return $list[$key];
        else
            return '';
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
     * @author alpha
     * @desc 查询退件列表
     * @param $saleids
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function findByAfterSalesOrderId($saleids) {
        self::$return_table = 'RETURN';

        $query = self::find()->select("*")->andWhere(['in', 'after_sale_id', $saleids])->all();
        return $query;
    }

    public static function getAfterSalesretruen($afterOrderId) {
        self::$return_table = 'RETURN';
        $query = self::find()->select('return_time')->where(['after_sale_id' => $afterOrderId])->asArray()->one();
        return $query;
    }

    /**
     * @author alpha
     * @desc 查询单个退件信息
     * @param $after_sale_id
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function findByAfterSaleId($after_sale_id) {
        self::$return_table = 'RETURN';
        $query = self::find()->select("*")->andWhere(['after_sale_id' => $after_sale_id])->asArray()->one();
        return $query;
    }

    /**
     * @author alpha
     * @desc 重写findOne方法
     * @param $after_sale_id
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function findOne_overwrite($after_sale_id) {
        self::$return_table = 'RETURN';
        $query = self::find()->select("*")->andWhere(['after_sale_id' => $after_sale_id])->one();
        return $query;
    }

    /**
     * @author alpha
     * @desc 添加order id 重写findone方法
     * @param $order_id
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function findOne_ByOrderId($order_id) {
        self::$return_table = 'RETURN';
        $query = self::find()->select("*")->andWhere(['order_id' => $order_id])->one();
        return $query;
    }

    /**
     * @author alpha
     * @desc 重写插入表操作
     * @return bool
     * @throws \Exception
     */
    public function insert_overwrite() {
        self::$return_table = 'RETURN';
        return self::insert();
    }

    /**
     *
     * @return int
     */
    public static function delete_overwrite($afterSalesId) {
        self::$return_table = 'RETURN';
        return self::deleteAll(['after_sale_id' => $afterSalesId]);
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
            2 => "<span style='color:#008000;'>已推送收货</span>",
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

    /**
     * @desc 根据售后单号获取退货信息
     */
    public static function getList($after_sale_id, $platform_code) {
        self::$return_table = 'RETURN';
        return self::find()->where(['after_sale_id' => $after_sale_id, 'platform_code' => $platform_code])->one();
    }

    /**
     * @author alpha
     * @desc 退货审核
     * @param AfterSalesOrder $model
     * @return bool
     */
    public function audit(AfterSalesOrder $model) {
       $refund_code= self::getrefundcode($model->after_sale_id);
        $afterSalesReturnInfo = $this->findOne_overwrite(['after_sale_id' => $model->after_sale_id]);
        if (!$afterSalesReturnInfo) {
            $this->error_message = '售后单: ' . $model->after_sale_id . ' 未找到!';
            return false;
        }
        //
        $warehouse_name = Warehouse::getAllWarehouseList(true);
        $warehouse_code = Warehouse::getAllWarehouseListCode();
        $datas = [
            'order_id' => $afterSalesReturnInfo->order_id,
            'platform_code' => $afterSalesReturnInfo->platform_code,
            'create_by' => $afterSalesReturnInfo->create_by,
            'create_time' => $afterSalesReturnInfo->create_time,
            'after_sale_id' => $afterSalesReturnInfo->after_sale_id,
            'warehouse_code' => isset($afterSalesReturnInfo->warehouse_id) ? $warehouse_code[$afterSalesReturnInfo->warehouse_id] : null, //
            'warehouse_name' => isset($afterSalesReturnInfo->warehouse_id) ? $warehouse_name[$afterSalesReturnInfo->warehouse_id] : null,
            'carrier' => $afterSalesReturnInfo->carrier,
            'tracking_no' => $afterSalesReturnInfo->tracking_no,
            'rma' => $afterSalesReturnInfo->rma,
            'remark' => $afterSalesReturnInfo->remark,
            'refund_code'=>$refund_code ,
        ]; 
        $erpOrderApi = new ErpOrderApi();
        if (!$erpOrderApi->Reviewaftersaleorder($datas)) {
            $this->error_message = $erpOrderApi->getExcptionMessage();
            return false;
        }
        //erp确认成功 同时更改退件单状态为审核通过
        $afterSalesReturnInfo->return_status = 2; //审核通过
        $afterSalesReturnInfo->save();
        //修改状态
        return true;
    }
   /**
    * 获取退货编码
    * **/
  protected static function  getrefundcode($after_sale_id){
     $res= self::find()->where(['after_sale_id'=>$after_sale_id])->asArray()->one();
      return $res['refund_code'];
  }
}

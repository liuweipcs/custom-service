<?php

namespace app\modules\systems\models;

use app\modules\orders\models\OrderAliexpress;
use app\modules\orders\models\OrderAliexpressDetail;
use app\modules\orders\models\OrderAliexpressKefu;
use app\modules\orders\models\OrderAmazonDetail;
use app\modules\orders\models\OrderAmazonKefu;
use app\modules\orders\models\OrderEbayKefu;
use app\modules\orders\models\OrderOtherDetail;
use app\modules\orders\models\OrderOtherKefu;
use app\modules\orders\models\OrderWishDetail;
use app\modules\orders\models\OrderWishKefu;
use Yii;
use app\components\Model;
use app\modules\accounts\models\Platform;
use yii\data\Sort;
use app\modules\accounts\models\Account;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\orders\models\OrderEbay;
use app\modules\orders\models\OrderEbayDetail;
use app\modules\orders\models\OrderKefu;
use app\modules\orders\models\OrderProfit;
use app\modules\products\models\Product;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\orders\models\OderEbayDetail;
use app\modules\aftersales\models\AfterSalesProduct;

class AftersaleManage extends Model {

    public static function tableName() {
        return '{{%aftersale_manage}}';
    }

    public function attributes() {
        $attributes = parent::attributes();
        $extraAttributes = [
            'rule_desc'
        ];
        return array_merge($attributes, $extraAttributes);
    }

    /**
     * 设置规则
     */
    public function rules() {
        return [
            [['rule_name', 'platform_code', 'aftersale_type', 'status'], 'required'],
            [['rule_name', 'platform_code'], 'string'],
            [['aftersale_type', 'auto_audit', 'status'], 'integer'],
            [['create_by', 'create_time', 'modify_by', 'modify_time'], 'safe']
        ];
    }

    /**
     * 返回属性标签
     */
    public function attributeLabels() {
        return [
            'rule_name' => '规则名称',
            'platform_code' => '所属平台',
            'aftersale_type' => '售后类型',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
            'auto_audit' => '是否自动审核',
            'status' => '状态',
            'rule_desc' => '规则',
        ];
    }

    /**
     * 返回表单筛选项
     */
    public function filterOptions() {
        return [
            [
                'name' => 'platform_code',
                'type' => 'dropDownList',
                'data' => self::platformDropdown(),
                'search' => '='
            ],
            [
                'name' => 'rule_name',
                'type' => 'text',
                'search' => '=',
            ]
        ];
    }

    /**
     * 返回平台下拉框数据
     */
    public static function platformDropdown() {
        return Platform::getPlatformAsArray();
    }

    /**
     * 查询列表
     */
    public function searchList($params = []) {
        //默认排序方式
        $sort = new Sort([
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
        ]);

        $query = self::find();
        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        $this->chgModelData($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * 修改模型中的数据
     */
    public function chgModelData(&$models) {
        //ERP订单状态
        $erpOrderStatus = OrderKefu::getOrderCompleteStatus('');

        //SKU状态
        $skuStatus = Product::getProductStatus('');

        //所有基础配置
        $allBasicConfig = BasicConfig::getAllConfigData();


        foreach ($models as $model) {
            //平台退款原因
            $disputeReasonList = PlatformDisputeReason::getDisputeReasonList($model->platform_code);

            $model->status = empty($model->status) ? '无效' : '有效';

            $rules = AftersaleRule::find()
                    ->where(['aftersale_manage_id' => $model->id])
                    ->asArray()
                    ->all();

            $ruleDesc = '';
            if (!empty($rules)) {
                foreach ($rules as $rule) {
                    $ruleDesc .= '<p class="ruleWhen">当: 平台纠纷原因为 ';
                    $ruleDesc .= '"' . (array_key_exists($rule['platform_reason_code'], $disputeReasonList) ? $disputeReasonList[$rule['platform_reason_code']] : '') . '" ';
                    $ruleDesc .= '且 ERP订单状态为 ';
                    $erpOrderStatusIds = explode(',', $rule['erp_order_status']);
                    if (!empty($erpOrderStatusIds)) {
                        foreach ($erpOrderStatusIds as $id) {
                            $ruleDesc .= '"' . (array_key_exists($id, $erpOrderStatus) ? $erpOrderStatus[$id] : '') . '",';
                        }
                    }
                    $ruleDesc = rtrim($ruleDesc, ',');
                    $ruleDesc .= '; ';

                    $ruleDesc .= '且 SKU状态为 ';
                    $skuStatusIds = explode(',', $rule['sku_status']);
                    if (!empty($skuStatusIds)) {
                        foreach ($skuStatusIds as $id) {
                            $ruleDesc .= '"' . (array_key_exists($id, $skuStatus) ? $skuStatus[$id] : '') . '",';
                        }
                    }
                    $ruleDesc = rtrim($ruleDesc, ',');
                    $ruleDesc .= '; ';

                    $ruleDesc .= '且 订单利润 ';
                    switch ($rule['order_profit_cond']) {
                        case 1:
                            $ruleDesc .= '大于';
                            break;
                        case 2:
                            $ruleDesc .= '大于等于';
                            break;
                        case 3:
                            $ruleDesc .= '小于';
                            break;
                        case 4:
                            $ruleDesc .= '小于等于';
                            break;
                    }
                    $ruleDesc .= $rule['order_profit_value'] . '; </p>';

                    $ruleDesc .= '<p class="ruleBe"> 则: 责任所属部门 ';
                    $ruleDesc .= '"' . (array_key_exists($rule['department_id'], $allBasicConfig) ? $allBasicConfig[$rule['department_id']] : '') . '", ';
                    $ruleDesc .= '匹配售后问题 ';
                    $ruleDesc .= '"' . (array_key_exists($rule['reason_id'], $allBasicConfig) ? $allBasicConfig[$rule['reason_id']] : '') . '", ';
                    $ruleDesc .= '亏损计算方式 ';
                    $ruleDesc .= '"' . (array_key_exists($rule['formula_id'], $allBasicConfig) ? $allBasicConfig[$rule['formula_id']] : '') . '"; </p>';
                }
            }

            $model->setAttribute('rule_desc', $ruleDesc);

            $model->auto_audit = empty($model->auto_audit) ? '否' : '是';
        }
    }

    /**
     * 获取平台售后单规则
     */
    public static function getAfterSaleOrderRule($platformCode) {
        $manages = self::find()
                ->select('id, rule_name')
                ->andWhere(['platform_code' => $platformCode])
                ->andWhere(['status' => 1])
                ->asArray()
                ->all();

        if (!empty($manages)) {
            foreach ($manages as &$manage) {
                $rules = AftersaleRule::find()
                        ->andWhere(['aftersale_manage_id' => $manage['id']])
                        ->andWhere(['platform_code' => $platformCode])
                        ->asArray()
                        ->all();

                if (!empty($rules)) {
                    $manage['rules'] = $rules;
                } else {
                    $manage['rules'] = [];
                }
            }
        }

        return $manages;
    }

    /**
     * 获取与该订单相匹配的售后单规则
     */
    public static function getMatchAfterSaleOrderRule($platformCode, $platformOrderId, $platformDisputeReason = '') {
        $result = [];

        //平台订单模型
        $orderQuery = null;
        //平台订单详情模型
        $orderDetailQuery = null;

        switch ($platformCode) {
            case Platform::PLATFORM_CODE_EB:
                $orderQuery = OrderEbayKefu::find();
                $orderDetailQuery = OrderEbayDetail::find();
                break;
            case Platform::PLATFORM_CODE_ALI:
                $orderQuery = OrderAliexpressKefu::find();
                $orderDetailQuery = OrderAliexpressDetail::find();
                break;
            case Platform::PLATFORM_CODE_AMAZON:
                $orderQuery = OrderAmazonKefu::find();
                $orderDetailQuery = OrderAmazonDetail::find();
                break;
            case Platform::PLATFORM_CODE_WISH:
                $orderQuery = OrderWishKefu::find();
                $orderDetailQuery = OrderWishDetail::find();
                break;
            case Platform::PLATFORM_CODE_CDISCOUNT:
            case Platform::PLATFORM_CODE_SHOPEE:
            case Platform::PLATFORM_CODE_LAZADA:
            case Platform::PLATFORM_CODE_WALMART:
            case Platform::PLATFORM_CODE_OFFLINE:
            case Platform::PLATFORM_CODE_MALL:
            case Platform::PLATFORM_CODE_JOOM:
            case Platform::PLATFORM_CODE_PF:
            case Platform::PLATFORM_CODE_BB:
            case Platform::PLATFORM_CODE_DDP:
            case Platform::PLATFORM_CODE_STR:
            case Platform::PLATFORM_CODE_JUM:
            case Platform::PLATFORM_CODE_JET:
            case Platform::PLATFORM_CODE_GRO:
            case Platform::PLATFORM_CODE_DIS:
            case Platform::PLATFORM_CODE_SPH:
            case Platform::PLATFORM_CODE_INW:
            case Platform::PLATFORM_CODE_JOL:
            case Platform::PLATFORM_CODE_SOU:
            case Platform::PLATFORM_CODE_PM:
            case Platform::PLATFORM_CODE_WADI:
            case Platform::PLATFORM_CODE_OBERLO:
            case Platform::PLATFORM_CODE_WJFX:
            case Platform::PLATFORM_CODE_ALIXX:
                $orderQuery = OrderOtherKefu::find();
                $orderDetailQuery = OrderOtherDetail::find();
                break;
        }

        //获取平台所有有效规则
        $manages = self::getAfterSaleOrderRule($platformCode);
        if (!empty($manages)) {
            //获取订单信息
            $orderInfo = $orderQuery
                    ->andWhere(['platform_code' => $platformCode])
                    ->andWhere(['platform_order_id' => $platformOrderId])
                    ->asArray()
                    ->one();
            if (empty($orderInfo)) {
                return false;
            }
            //被合并的订单 3 查找主订单状态 @author harvin
            if ($orderInfo['order_type'] == 3) {
                $parentcomplete = $orderQuery->select('complete_status')->andWhere(['order_id' => $orderInfo['parent_order_id']])
                                ->andWhere(['platform_code' => $platformCode])
                                ->asArray()->one();
            }
            //拆分的主订单 4 查找订单状态  @author harvin
            if ($orderInfo['order_type'] == 4) {
                $parentcomplete = $orderQuery->select('complete_status')->andWhere(['parent_order_id' => $orderInfo['order_id']])
                                ->andWhere(['platform_code' => $platformCode])
                                ->asArray()->all();
                $stuatscomplete = [];
                foreach ($stuatscomplete as $key => $value) {
                    $stuatscomplete[] = $value['complete_status'];
                }
            }




            //获取订单产品的SKU
            $skuArr = $orderDetailQuery
                    ->select('sku')
                    ->andWhere(['order_id' => $orderInfo['order_id']])
                    ->column();

            //订单所有产品的状态
            $productStatus = [];
            if (!empty($skuArr)) {
                $productStatus = Product::find()
                        ->select('product_status')
                        ->andWhere(['in', 'sku', $skuArr])
                        ->column();
            }

            //获取订单的利润
            $orderProfit = OrderProfit::find()
                    ->select('profit')
                    ->andWhere(['platform_code' => $platformCode])
                    ->andWhere(['order_id' => $orderInfo['order_id']])
                    ->scalar();
            $orderProfit = !empty($orderProfit) ? $orderProfit : 0;

            //循环规则，只要符合条件，就自动建售后单
            foreach ($manages as $manage) {
                if (empty($manage['rules'])) {
                    continue;
                }

                $rules = $manage['rules'];
                foreach ($rules as $rule) {
                    //是否创建售后单标识
                    $flag = true;

                    //判断平台纠纷原因
                    //EBay平台不需要判断
                    if ($platformCode != Platform::PLATFORM_CODE_EB) {
                        if (!empty($rule['platform_reason_code'])) {
                            if ($platformDisputeReason != $rule['platform_reason_code']) {
                                $flag = false;
                            }
                        }
                    }
                    //判断erp订单状态
                    if (!empty($rule['erp_order_status'])) {
                        $orderStatusIds = explode(',', $rule['erp_order_status']);
                        if (!empty($orderStatusIds)) {
                            if (!in_array($orderInfo['complete_status'], $orderStatusIds)) {
                                $flag = false;
                            }
                            //被合并的订单 3 查找主订单状态  @author harvin
                            if ($orderInfo['order_type'] == 3) {
                                if (in_array($parentcomplete['complete_status'], $orderStatusIds)) {
                                    $flag = true;
                                }
                            }
                            //拆分的主订单 4 查找子订单状态   @author harvin   
                            if ($orderInfo['order_type'] == 4) {
                                //判断两个数组的交集
                                $inters = array_intersect($stuatscomplete, $orderStatusIds);
                                if (!empty($inters)) {
                                    $flag = true;
                                }
                            }
                        }
                    
                }

                //判断sku状态
                if (!empty($rule['sku_status'])) {
                    $skuStatusIds = explode(',', $rule['sku_status']);
                    if (!empty($skuStatusIds)) {
                        $inter = array_intersect($productStatus, $skuStatusIds);
                        //判断两个数组的交集
                        if (empty($inter)) {
                            $flag = false;
                        }
                    }
                }

                //判断订单利润
                if (!empty($rule['order_profit_cond']) && !empty($rule['order_profit_value'])) {
                    $value = $rule['order_profit_value'];
                    switch ($rule['order_profit_cond']) {
                        case 1 :
                            if ($orderProfit <= $value) {
                                $flag = false;
                            }
                            break;
                        case 2:
                            if ($orderProfit < $value) {
                                $flag = false;
                            }
                            break;
                        case 3:
                            if ($orderProfit >= $value) {
                                $flag = false;
                            }
                            break;
                        case 4:
                            if ($orderProfit > $value) {
                                $flag = false;
                            }
                            break;
                    }
                }

                //标识为真，表示通过该规则，可以创建售后单
                if ($flag) {
                    $result[$rule['id']] = $rule;
                }
            }
        }
        }
        return $result;
    }

    /**
     * 根据规则自动创建售后订单
     * @param $platformCode 平台code
     * @param $platformOrderId 平台订单ID
     * @param $platformDisputeReason 平台纠纷原因
     * @param $transactionId 交易ID
     * @param $refundAmount 退款金额(如果不填,默认取订单金额)
     * @param $refundTime 退款时间
     */
    public static function autoCreateAfterSaleOrder($platformCode, $platformOrderId, $platformDisputeReason = '', $transactionId = '', $refundAmount = 0, $refundTime = '') {
        $rules = self::getMatchAfterSaleOrderRule($platformCode, $platformOrderId, $platformDisputeReason);

        if (empty($rules)) {
            return false;
        }

        //平台订单模型
        $orderQuery = null;

        switch ($platformCode) {
            case Platform::PLATFORM_CODE_EB:
                $orderQuery = OrderEbayKefu::find();
                $orderDetail = OderEbayDetail::find();
                break;
            case Platform::PLATFORM_CODE_ALI:
                $orderQuery = OrderAliexpressKefu::find();
                $orderDetail = OrderAliexpressDetail::find();
                break;
            case Platform::PLATFORM_CODE_AMAZON:
                $orderQuery = OrderAmazonKefu::find();
                $orderDetail = OrderAmazonDetail::find();
                break;
            case Platform::PLATFORM_CODE_WISH:
                $orderQuery = OrderWishKefu::find();
                $orderDetail = OrderWishDetail::find();
                break;
            case Platform::PLATFORM_CODE_CDISCOUNT:
            case Platform::PLATFORM_CODE_SHOPEE:
            case Platform::PLATFORM_CODE_LAZADA:
            case Platform::PLATFORM_CODE_WALMART:
            case Platform::PLATFORM_CODE_OFFLINE:
            case Platform::PLATFORM_CODE_MALL:
            case Platform::PLATFORM_CODE_JOOM:
            case Platform::PLATFORM_CODE_PF:
            case Platform::PLATFORM_CODE_BB:
            case Platform::PLATFORM_CODE_DDP:
            case Platform::PLATFORM_CODE_STR:
            case Platform::PLATFORM_CODE_JUM:
            case Platform::PLATFORM_CODE_JET:
            case Platform::PLATFORM_CODE_GRO:
            case Platform::PLATFORM_CODE_DIS:
            case Platform::PLATFORM_CODE_SPH:
            case Platform::PLATFORM_CODE_INW:
            case Platform::PLATFORM_CODE_JOL:
            case Platform::PLATFORM_CODE_SOU:
            case Platform::PLATFORM_CODE_PM:
            case Platform::PLATFORM_CODE_WADI:
            case Platform::PLATFORM_CODE_OBERLO:
            case Platform::PLATFORM_CODE_WJFX:
            case Platform::PLATFORM_CODE_ALIXX:
                $orderQuery = OrderOtherKefu::find();
                $orderDetail = OrderOtherDetail::find();
                break;
        }

        //获取订单信息
        $orderInfo = $orderQuery
                ->andWhere(['platform_code' => $platformCode])
                ->andWhere(['platform_order_id' => $platformOrderId])
                ->asArray()
                ->one();

        if (empty($orderInfo)) {
            return false;
        }

        //判断付款状态
        if (empty($orderInfo['payment_status'])) {
            //未付款的订单不能建售后单
            return false;
        }

        //遍历匹配上的规则，创建售后单
        foreach ($rules as $rule) {
            $after = AfterSalesOrder::findOne([
                        'platform_code' => $platformCode,
                        'order_id' => $orderInfo['order_id'],
                        'type' => AfterSalesOrder::ORDER_TYPE_REFUND,
            ]);

            //如果系统已经建了一个退款单，则不需要再次创建
            if (!empty($after)) {
                continue;
            }

            $accountInfo = Account::find()
                    ->where(['platform_code' => $platformCode, 'old_account_id' => $orderInfo['account_id']])
                    ->asArray()
                    ->one();
            $after = new AfterSalesOrder();
            $after->after_sale_id = AutoCode::getCode('after_sales_order');
            $after->order_id = $orderInfo['order_id'];
            $after->transaction_id = $transactionId;
            $after->type = AfterSalesOrder::ORDER_TYPE_REFUND;
            $after->platform_code = $platformCode;
            $after->department_id = !empty($rule['department_id']) ? $rule['department_id'] : 0;
            $after->reason_id = !empty($rule['reason_id']) ? $rule['reason_id'] : 0;
            $after->buyer_id = !empty($orderInfo['buyer_id']) ? $orderInfo['buyer_id'] : '';
            $after->account_name = !empty($accountInfo) ? $accountInfo['account_name'] : '';
            $after->create_by = '系统';
            $after->create_time = date('Y-m-d H:i:s');
            $after->modify_by = '系统';
            $after->modify_time = date('Y-m-d H:i:s');
            $after->account_id = !empty($accountInfo) ? $accountInfo['id'] : 0;
            $after->order_amount = !empty($orderInfo['total_price']) ? $orderInfo['total_price'] : 0;
            $after->currency = !empty($orderInfo['currency']) ? $orderInfo['currency'] : '';

            //判断规则是否自动审核
            $afterManage = AftersaleManage::findOne($rule['aftersale_manage_id']);
            if (!empty($afterManage) && $afterManage->auto_audit == 1) {
                $after->status = AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED;
                $after->approver = '系统';
                $after->approve_time = date('Y-m-d H:i:s');
            } else {
                $after->status = AfterSalesOrder::ORDER_STATUS_WATTING_AUDIT;
            }

            if ($after->save()) {
                $refund = new AfterSalesRefund();
                $refund->after_sale_id = $after->after_sale_id;
                //退款金额如果不填,默认取订单金额
                $refund->refund_amount = !empty($refundAmount) ? $refundAmount : $after->order_amount;

                //如果退款金额等于订单金额
                if ($refund->refund_amount == $after->order_amount) {
                    $refund->refund_type = AfterSalesRefund::REFUND_TYPE_FULL;
                } else {
                    $refund->refund_type = AfterSalesRefund::REFUND_TYPE_PARTIAL;
                }

                $refund->currency = $after->currency;
                $refund->transaction_id = $after->transaction_id;
                $refund->order_id = $after->order_id;
                $refund->platform_code = $platformCode;
                $refund->order_amount = $after->order_amount;
                $refund->reason_code = $after->reason_id;
                $refund->refund_time = !empty($refundTime) ? $refundTime : date('Y-m-d H:i:s');
                $refund->refund_status = AfterSalesRefund::REFUND_STATUS_FINISH;
                $refund->platform_order_id = $orderInfo['platform_order_id'];
                $refund->save();
                //获取erp订单产品表详情
                $orderlist = $orderDetail->where(['platform_code' => $orderInfo['platform_code']])->andWhere(['order_id' => $after->order_id])->asArray()->all();
                foreach ($orderlist as $key => $val) {
                    //插入客户系统订单产品表详情里
                    $orderproduct = new AfterSalesProduct();
                    $orderproduct->platform_code = $val['platform_code']; //平台CODE
                    $orderproduct->order_id = $val['order_id']; //订单号
                    $orderproduct->sku = $val['sku_old']; //sku编号
                    $orderproduct->product_title = $val['title']; //产品标题
                    $orderproduct->quantity = $val['quantity_old']; //订单产品数量
                    $orderproduct->linelist_cn_name = Product::getLineListNameBySku($val['sku_old']); //产品线
                    $orderproduct->issue_quantity = $val['quantity_old']; //有问题的产品数量
                    $orderproduct->reason_id = $after->reason_id; //问题原因
                    $orderproduct->after_sale_id = $after->after_sale_id; //售后单号
                    $orderproduct->refund_redirect_price = ""; //退款/重寄金额
                    $orderproduct->refund_redirect_price_rmb = ""; //退款/重寄人民币
                    $orderproduct->save();
                }
                return true;
            }
        }

        return false;
    }

}

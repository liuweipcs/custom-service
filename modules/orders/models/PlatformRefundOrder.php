<?php

namespace app\modules\orders\models;

use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\systems\models\AftersaleManage;
use Yii;
use app\components\Model;
use yii\data\Sort;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\UserAccount;
use yii\helpers\Url;

class PlatformRefundOrder extends Model
{

    public static function tableName()
    {
        return '{{%platform_refund_order}}';
    }

    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = [
            'sel_time_type',
            'is_aftersale_type',
            'system_order_id',
            'account_name',
        ];
        return array_merge($attributes, $extraAttributes);
    }

    /**
     * 返回属性标签
     */
    public function attributeLabels()
    {
        return [
            'platform_code' => '平台',
            'platform_order_id' => '平台订单ID',
            'account_id' => '账号',
            'buyer_id' => '买家ID',
            'email' => '买家邮箱',
            'amount' => '退款金额/币种',
            'ship_amount' => '运费',
            'currency' => '币种',
            'order_status' => '平台订单状态',
            'reason' => '平台退款原因',
            'refund_time' => '平台退款时间',
            'is_aftersale' => '自动建立售后单',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
            'sel_time_type' => '选择时间类型',
            'start_time' => '开始时间',
            'end_time' => '结束时间',
            'is_aftersale_type' => '规则和售后单',
            'system_order_id' => '系统订单ID',
            'account_name' => '账号'
        ];
    }

    /**
     * 返回表单筛选项
     */
    public function filterOptions()
    {
        $platform = self::platformDropdown();
        if(count($platform)>1){
            $plt = false;
        }else{
            $plt = true;
        }
        return [
            [
                'name' => 'platform_code',
                'type' => 'dropDownList',
                'data' => $platform,
                'search' => '='
            ],
            [
                'name' => 'account_id',
                'type' => 'search',
                'data' => $plt ? self::accountDropdown($platform) : self::accountDropdown(''),
                'search' => '='
            ],
            [
                'name' => 'platform_order_id',
                'type' => 'text',
                'search' => '='
            ],
            [
                'name' => 'system_order_id',
                'type' => 'text',
                'search' => '='
            ],
            [
                'name' => 'order_status',
                'type' => 'text',
                'search' => '='
            ],
            [
                'name' => 'buyer_id',
                'type' => 'text',
                'search' => '='
            ],
            [
                'name' => 'reason',
                'type' => 'search',
                'data' => self::reasonDropdown(),
                'search' => '='
            ],
            [
                'name' => 'is_aftersale',
                'type' => 'dropDownList',
                'data' => ['' => '全部', 'is_after' => '已登记售后单(有规则)', 'no_match_rule' => '未登记售后单(无规则)','have_aftersale' => '手工售后单'],
                'search' => '='
            ],

            [
                'name' => 'sel_time_type',
                'type' => 'dropDownList',
                'data' => ['refund' => '退款时间', 'create' => '创建时间'],
                'search' => '='
            ],
            [
                'name'   => 'start_time',
                'type'   => 'date_picker',
                'search' => '<',
            ],
            [
                'name'   => 'end_time',
                'type'   => 'date_picker',
                'search' => '>',
            ],
        ];
    }

    /**
     * 返回平台下拉框数据
     */
    public static function platformDropdown()
    {
        return Platform::getPlatformAsArray();
    }

    /**
     * 账号列表
     */
    public static function accountDropdown($platform)
    {

        $accountmodel = new Account();
        if($platform){
            $all_account = $accountmodel->findAll(['status' => 1,'platform_code'=>$platform]);
        }else{
            $all_account = $accountmodel->findAll(['status' => 1]);
        }
        $arr = [' ' => '全部'];
        foreach ($all_account as $key => $value) {
            $arr[$value['id']] = $value['account_name'];
        }
        return $arr;
    }

    /**
     * 平台退款原因
     */
    public static function reasonDropdown()
    {
        $data = [' ' => '全部'];
        $query = self::find()
            ->select('reason as reason_key,reason')
            ->andWhere(['<>', 'reason', ''])
            ->groupBy('reason');

        //请求参数
        $params = Yii::$app->request->getBodyParams();
        if (!empty($params) && !empty($params['platform_code'])) {
            $query->andWhere(['platform_code' => $params['platform_code']]);
        }

        $reason = $query->asArray()->all();
        if (!empty($reason)) {
            $reason = array_column($reason, 'reason', 'reason_key');
            $data = array_merge($data, $reason);
        }
        return $data;
    }

    /**
     * 查询列表
     */
    public function searchList($params = [])
    {
        //默认排序方式
        $sort = new Sort([
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
        ]);



        $query = self::find(); 
        
        if(!empty($params['is_aftersale'])){
            if ($params['is_aftersale'] == 'is_after') {
                $query->andWhere(['is_aftersale' => 1,'is_match_rule' => 1]);
            } else if ($params['is_aftersale'] == 'no_match_rule') {
                $query->andWhere(['is_aftersale' => 0,'is_match_rule' => 0]);
            }else if($params['is_aftersale'] == 'have_aftersale'){
                $query->andWhere(['is_aftersale' => 1,'is_match_rule' => 0]);
            }
          //  unset($params['is_aftersale']);
        }

        //查询订单表得平台订单号
        if (!empty($params['system_order_id'])) {
            if(!empty($params['platform_code'])){
                $platform = OrderKefu::getOrderModel($params['platform_code']);
                $platform_order_id = OrderKefu::model($platform->ordermain)->select('platform_order_id')
                    ->where(['order_id' => $params['system_order_id']])
                    ->asArray()
                    ->one();
            }else{
                $platform_order_id = OrderOtherKefu::find()->select('platform_order_id')
                    ->where(['order_id' => $params['system_order_id']])
                    ->asArray()
                    ->one();
            }

            $query->andWhere(['platform_order_id' => $platform_order_id['platform_order_id']]);

            unset($params['system_order_id']);
        }
    
        if (!empty($params['sel_time_type'])) {
            if ($params['sel_time_type'] == 'refund') {
                $field = 'refund_time';
            } else if ($params['sel_time_type'] == 'create') {
                $field = 'create_time';
            }

            if (!empty($params['start_time']) && !empty($params['end_time'])) {
                $query->andWhere(['between', $field, $params['start_time'], $params['end_time']]);
            } else if (!empty($params['start_time'])) {
                $query->andWhere(['>=', $field, $params['start_time']]);
            } else if (!empty($params['end_time'])) {
                $query->andWhere(['<=', $field, $params['end_time']]);
            }

            unset($params['sel_time_type']);
//            unset($params['start_time']);
//            unset($params['end_time']);
        }
 
        $dataProvider = parent::search($query, $sort, $params);
         
        $models = $dataProvider->getModels();
        $this->chgModelData($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * 修改模型中的数据
     */
    public function chgModelData(&$models)
    {       
        foreach ($models as $model) {
            //如果没有匹配到规则，则重新试着匹配并创建售后单
            if (empty($model->is_aftersale) || empty($model->is_match_rule)) {  
                //匹配售后单规则
                $afterSaleRules = AftersaleManage::getMatchAfterSaleOrderRule($model->platform_code, $model->platform_order_id, $model->reason);
                if (!empty($afterSaleRules)) {
                    //如果匹配到规则，创建售后单
                    $result = AftersaleManage::autoCreateAfterSaleOrder($model->platform_code, $model->platform_order_id, $model->reason, $model->transaction_id, $model->amount, $model->refund_time);                 
                
                    if ($result) {
                        $refundOrder = self::findOne($model->id);
                        $refundOrder->is_aftersale = 1;
                        $refundOrder->is_match_rule = 1;
                        if ($refundOrder->save()) {
                            $model->setAttribute('is_aftersale', 1);
                            $model->setAttribute('is_match_rule', 1);
                        }
                    }
                }                
            }

            //是否自动建立售后单
            $is_aftersale = '';
           
            if (!empty($model->is_aftersale)) {
                $is_aftersale = '已登记<br>';
            } else {
                $is_aftersale = '未登记<br>';
            }

            if (!empty($model->is_match_rule)) {
                $is_aftersale .= '已有售后单<br>';
            } else {
                $is_aftersale .= '无规则<br>';
            }

            if ((!empty($model->is_aftersale)) && (empty($model->is_match_rule))) {
                $is_aftersale = '手动登记售后单<br>';
            }
            //获取erp account_id
            $account_id = Account::find()
                        ->select('old_account_id')
                        ->where(['id' => $model->account_id])
                        ->asArray()
                        ->scalar();

            //获取订单信息
            $orderInfo = OrderKefu::getOrderStack($model->platform_code, $model->platform_order_id,'',1,$account_id);

            if (!empty($orderInfo)) {
                //查询是否有售后单
                $afterSales = AfterSalesOrder::find()
                    ->where(['platform_code' => $model->platform_code, 'order_id' => $orderInfo['info']['order_id']])
                    ->asArray()
                    ->all();

                if (!empty($afterSales)) {
                    foreach ($afterSales as $afterSale) {
                        if ($afterSale['type'] == 1) {
                            //退款
                            $is_aftersale .= "<a _width='100%' _height='100%' class='edit-button' href='" . Url::toRoute(['/aftersales/sales/detailrefund', 'after_sale_id' => $afterSale['after_sale_id'], 'platform_code' => $afterSale['platform_code'], 'status' => $afterSale['status']]) . "'>{$afterSale['after_sale_id']}</a><br>";
                        } else if ($afterSale['type'] == 2) {
                            //退货
                            $is_aftersale .= "<a _width='100%' _height='100%' class='edit-button' href='" . Url::toRoute(['/aftersales/sales/detailreturn', 'after_sale_id' => $afterSale['after_sale_id'], 'platform_code' => $afterSale['platform_code'], 'status' => $afterSale['status']]) . "'>{$afterSale['after_sale_id']}</a><br>";
                        } else if ($afterSale['type'] == 3) {
                            //重寄
                            $is_aftersale .= "<a _width='100%' _height='100%' class='edit-button' href='" . Url::toRoute(['/aftersales/sales/detailredirect', 'after_sale_id' => $afterSale['after_sale_id'], 'platform_code' => $afterSale['platform_code'], 'status' => $afterSale['status']]) . "'>{$afterSale['after_sale_id']}</a><br>";
                        }
                    }
                } else {
                    $is_aftersale .= '无售后单<br>';
                }
            }
            $account = Account::findOne($model->account_id);
            $systemOrderId = '';
            if (!empty($orderInfo)) {
                if (!empty($account)) {
                    $systemOrderId = $account->account_short_name . '-';
                }
                $systemOrderId .= $orderInfo['info']['order_id'];
            }
            $model->setAttribute('account_name',$account->account_name);
            $model->setAttribute('system_order_id', $systemOrderId);
            $model->setAttribute('platform_order_id', '<a _width="100%" _height="100%" class="edit-button" href="' . Url::toRoute(['/orders/order/orderdetails', 'order_id' => $model->platform_order_id, 'platform' => $model->platform_code, 'account_id' => $model->account_id]) . '">' . $model->platform_order_id . '</a>');

            $model->setAttribute('is_aftersale', $is_aftersale);
            $model->setAttribute('amount', $model->amount . '/' . $model->currency);
        }
    }

    public static function getReasonPlatform($platform_code,$order_id=null,$platform_order_id=null){
        if (empty($platform_order_id)) {
            //获取订单id
            $orderInfo = OrderKefu::getOrders($platform_code,$order_id);
            $platformOrderId = $orderInfo['platform_order_id'];
        }

        $reason = '';
        if (!empty($platformOrderId) || $platform_order_id ) {
            $platformOrderId = $platform_order_id ? : $platformOrderId;
            $reason = self::find()
                      ->select('reason')
                      ->where(['platform_code'=>$platform_code,'platform_order_id'=>$platformOrderId])
                      ->scalar();
        }
        return $reason;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/6 0006
 * Time: 下午 12:15
 */

namespace app\modules\services\modules\ebay\controllers;

use app\modules\orders\models\OrderEbayDetail;
use yii\web\Controller;
use app\modules\accounts\models\Platform;
use app\modules\orders\models\OrderEbay;
use app\modules\customer\models\CustomerList;
use app\modules\orders\models\Tansaction;
use app\modules\orders\models\Transactionrecord;
use app\common\VHelper;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\accounts\models\Account;
use Yii;
use app\modules\customer\models\CustomerTags;
use app\modules\customer\models\CustomerTagsRule;
use app\modules\customer\models\CustomerTagsDetail;
use app\modules\orders\models\OrderKefu;

class CustormerorderController extends Controller
{

    //从订单表拉数据到客户列表
    public function actionIndex()
    {
        set_time_limit(0);
        $platform_code = \Yii::$app->request->get('platform_code');
        if(empty($platform_code)){
            die('platfrom_code is null');
        }

        $pageCur   = \Yii::$app->request->get('pageCur');
        $pageSize  = 5000;
        $offset    = ($pageCur - 1) * $pageSize;
        //获取币种转换人民币
        $rateMonth = date('Ym');//先写死数据[搜索时间目前查出数据不准确先写死数据]
        $rmbReturn = VHelper::getTargetCurrencyAmtAll($rateMonth);
        $platform = OrderKefu::getOrderModel($platform_code);
        if (empty($platform)) {
            return false;
        }

        $query = OrderKefu::model($platform->ordermain)
            ->select(['order_id','account_id', 'buyer_id', 'email', 'platform_code', 'account_id', 'ship_phone', 'last_update_time','created_time'])
            ->where(['platform_code' => $platform_code])
            ->offset($offset)
            ->limit($pageSize)
            ->orderBy(['order_id' => SORT_ASC]);

        if($platform_code == Platform::PLATFORM_CODE_AMAZON){
            $query->groupBy(['email']);
        }else{
            $query->groupBy(['buyer_id']);
        }
        $info = $query->all();

        if(empty($info)){
            die('已无数据');
        }
        if (!empty($info)) {
            foreach ($info as $k => $v) {
                try{
                if($platform_code == Platform::PLATFORM_CODE_AMAZON){
                    if(empty($v->email) || empty($v->buyer_id)){
                        continue;
                    }
                }else{
                    if(empty($v->buyer_id)){
                        continue;
                    }
                }

                if($platform_code == Platform::PLATFORM_CODE_AMAZON){
                    $customerData = CustomerList::find()->where(['platform_code' => $platform_code, 'buyer_email' => $v->email])->one();
                }else{
                    $customerData = CustomerList::find()->where(['platform_code' => $platform_code, 'buyer_id' => $v->buyer_id])->one();
                }
                if (empty($customerData)) {
                    $customerData = new CustomerList();
                }
                $customerData->platform_code = $platform_code;
                $customerData->buyer_id = !empty($v->buyer_id) ? $v->buyer_id : '';
                $customerData->buyer_email = !empty($v->email) ? $v->email : '';
                $transations = Tansaction::getOrderTransactionIdEbayByOrderId($v->order_id, $v->platform_code);
                $transaction_id = Transactionrecord::find()->where(['transaction_id' => $transations['transaction_id']])->one();
                $customerData->pay_email = !empty($transaction_id->payer_email) ? $transaction_id->payer_email : '';
                if($platform_code == Platform::PLATFORM_CODE_AMAZON){
                    $purchase_times = OrderKefu::model($platform->ordermain)->where(['email' => $v->email, 'payment_status' => 1])->count();
                }else{
                    $purchase_times = OrderKefu::model($platform->ordermain)->where(['buyer_id' => $v->buyer_id, 'payment_status' => 1])->count();
                }
                $customerData->purchase_times = $purchase_times;
                if($platform_code == Platform::PLATFORM_CODE_AMAZON){
                    $turnover = OrderKefu::model($platform->ordermain)->select(['sum(total_price) as total_price', 'currency'])->where(['email' => $v->email, 'payment_status' => 1])->groupBy('currency')->all();
                }else{
                    $turnover = OrderKefu::model($platform->ordermain)->select(['sum(total_price) as total_price', 'currency'])->where(['buyer_id' => $v->buyer_id, 'payment_status' => 1])->groupBy('currency')->all();
                }
                $rmb = 0;
                if (!empty($turnover)) {
                    foreach ($turnover as $item) {
                        $total_price = $item->total_price * $rmbReturn[$item->currency];
                        $rmb += sprintf("%.2f", $total_price);
                    }
                }
                $customerData->turnover = $rmb ? $rmb : 0;
                $type = [1, 3];
                $disputes_number = AfterSalesOrder::find()->where(['buyer_id' => $v->buyer_id, 'platform_code' => $platform_code])->andWhere(['in', 'type', $type])->count();
                $customerData->disputes_number = $disputes_number;
                $customerData->type = 0;
                $account_name = Account::find()->where(['old_account_id' => $v->account_id, 'platform_code' => $platform_code])->one();
                $customerData->account_name = !empty($account_name) ? $account_name->account_name : '';
                $customerData->account_id = $v->account_id;
                $customerData->phone = !empty($v->ship_phone) ? $v->ship_phone : '';
                $product_number = OrderKefu::model($platform->orderdetail)->where(['order_id' => $v->order_id])->count();
                $customerData->product_number = $product_number;
                $day = time() - strtotime($v->last_update_time);
                $customerData->last_time = intval($day / 86400);
                $customerData->create_by = '系统';
                $customerData->create_time = $v->created_time;
                $customerData->modify_by = '系统';
                $customerData->modify_time = $v->created_time;
                $customerData->save(false);

                $tags_rule_list = CustomerTags::getTagRule($platform_code);
                //客户是否满足规则条件
                if (!empty($tags_rule_list)) {
                    foreach ($tags_rule_list as $rule_list) {
                        //如果规则为空，则跳过
                        if (empty($rule_list['tag_rule'])) {
                            continue;
                        }
                        //条件表达式
                        $cond = [];
                        switch ($rule_list['cond_type']) {
                            case CustomerTags::COND_TYPE_ALL:
                                //满足所有规则
                                $cond[] = 'and';
                                break;
                            case CustomerTags::COND_TYPE_ANY:
                                //满足任一规则
                                $cond[] = 'or';
                                break;
                        }
                        foreach ($rule_list['tag_rule'] as $rule) {
                            switch ($rule['type']) {
                                //累计订单数
                                case CustomerTagsRule::RULE_TYPE_ORDER:
                                    if (!empty($rule['value']) && !empty($rule['value1'])) {
                                        $cond[] = [
                                            'and',
                                            ['>=', 'purchase_times', $rule['value']],
                                            ['<=', 'purchase_times', $rule['value1']],
                                        ];
                                    } else if (!empty($rule['value'])) {
                                        $cond[] = ['>=', 'purchase_times', $rule['value']];
                                    } else if (!empty($rule['value1'])) {
                                        $cond[] = ['<=', 'purchase_times', $rule['value1']];
                                    }
                                    break;
                                //累计成交金额
                                case CustomerTagsRule::RULE_TYPE_MONERY:
                                    if (!empty($rule['value']) && !empty($rule['value1'])) {
                                        $cond[] = [
                                            'and',
                                            ['>=', 'turnover', $rule['value']],
                                            ['<=', 'turnover', $rule['value1']],
                                        ];
                                    } else if (!empty($rule['value'])) {
                                        $cond[] = ['>=', 'turnover', $rule['value']];
                                    } else if (!empty($rule['value1'])) {
                                        $cond[] = ['<=', 'turnover', $rule['value1']];
                                    }
                                    break;
                                //最后下单时间
                                case CustomerTagsRule::RULE_TYPE_TIME:
                                    if (!empty($rule['value']) && !empty($rule['value1'])) {
                                        $cond[] = [
                                            'and',
                                            ['>=', 'last_time', $rule['value']],
                                            ['<=', 'last_time', $rule['value1']],
                                        ];
                                    } else if (!empty($rule['value'])) {
                                        $cond[] = ['>=', 'last_time', $rule['value']];
                                    } else if (!empty($rule['value1'])) {
                                        $cond[] = ['<=', 'last_time', $rule['value1']];
                                    }
                                    break;
                                //累计纠纷次数
                                case CustomerTagsRule::RULE_TYPE_DISPUTE:
                                    if (!empty($rule['value']) && !empty($rule['value1'])) {
                                        $cond[] = [
                                            'and',
                                            ['>=', 'disputes_number', $rule['value']],
                                            ['<=', 'disputes_number', $rule['value1']],
                                        ];
                                    } else if (!empty($rule['value'])) {
                                        $cond[] = ['>=', 'disputes_number', $rule['value']];
                                    } else if (!empty($rule['value1'])) {
                                        $cond[] = ['<=', 'disputes_number', $rule['value1']];
                                    }
                                    break;
                                //累计产品数
                                case CustomerTagsRule::RULE_TYPE_PRODUCT:
                                    if (!empty($rule['value']) && !empty($rule['value1'])) {
                                        $cond[] = [
                                            'and',
                                            ['>=', 'product_number', $rule['value']],
                                            ['<=', 'product_number', $rule['value1']],
                                        ];
                                    } else if (!empty($rule['value'])) {
                                        $cond[] = ['>=', 'product_number', $rule['value']];
                                    } else if (!empty($rule['value1'])) {
                                        $cond[] = ['<=', 'product_number', $rule['value1']];
                                    }
                                    break;
                            }
                        }
                        $result = CustomerList::find()->where(['buyer_id' => $customerData->buyer_id, 'platform_code' => $platform_code])->andWhere($cond)->one();
                        if ($result) {
                            $tagsDetail = new CustomerTagsDetail();
                            $tagsDetail->tags_id = $rule_list['id'];
                            $tagsDetail->buyer_id = $customerData->id;
                            $tagsDetail->platform_code = $platform_code;
                            $tagsDetail->save(false);
                        }

                    }

                }
                 } catch (\Exception $e) {
                    //防止出现的异常中断整个程序执行
                }
            }

            exit('RUN ORDER END');
        }


    }
}
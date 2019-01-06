<?php

namespace app\modules\services\modules\aftersales\models;

use app\modules\accounts\models\Account;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\aftersales\models\AfterSalesProduct;
use app\modules\aftersales\models\AfterSalesRedirect;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\aftersales\models\RefundReason;
use app\modules\orders\models\OrderKefu;
use app\modules\products\models\Product;
use app\modules\systems\models\BasicConfig;
use app\modules\systems\models\CurrencyRateKefu;

class FinancialStatistics
{
    /**
     * 刷新售后单成本数据统计
     */
    public function flushData($accountId)
    {
        if (empty($accountId)) {
            return false;
        }
        $accountInfo = Account::findOne($accountId);
        if (empty($accountInfo)) {
            return false;
        }

        //获取上个月的开始时间与结束时间
        $startTime = date('Y-m-01 00:00:00', strtotime('-1 month'));
        $endTime = date('Y-m-t 23:59:59', strtotime('-1 month'));

        //统计上个月所有审核通过的重寄单
        $this->redirect($accountInfo, $startTime, $endTime);

        //统计上个月所有审核通过的退款单
        $this->refund($accountInfo, $startTime, $endTime);


    }

    /**
     * 处理重寄单(已审核时间为准)
     */
    public function redirect($accountInfo, $startTime, $endTime)
    {
        //已审核时间为准
        $afterSales = AfterSalesOrder::find()
            ->andWhere(['account_id' => $accountInfo->id])
            ->andWhere(['type' => AfterSalesOrder::ORDER_TYPE_REDIRECT])
            ->andWhere(['status' => AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED])
            ->andWhere(['between', 'approve_time', $startTime, $endTime])
            ->asArray()
            ->all();
        if (empty($afterSales)) {
            return false;
        }

        foreach ($afterSales as $afterSale) {
            try {
                //获取售后订单信息
                $orderInfo = OrderKefu::getOrderStack($afterSale['platform_code'], '', $afterSale['order_id']);

                if (empty($orderInfo)) {
                    continue;
                } else {
                    $orderInfo = json_decode(json_encode($orderInfo), true, 512, JSON_BIGINT_AS_STRING);
                }

                //获取重寄售后单
                $redirect = AfterSalesRedirect::findOne(['after_sale_id' => $afterSale['after_sale_id']]);
                if (empty($redirect)) {
                    continue;
                }

                //获取重寄订单信息
                $redirectOrderInfo = OrderKefu::getOrderStack($redirect->platform_code, '', $redirect->redirect_order_id);
                if (empty($redirectOrderInfo)) {
                    continue;
                } else {
                    $redirectOrderInfo = json_decode(json_encode($redirectOrderInfo), true, 512, JSON_BIGINT_AS_STRING);
                }

                $statistics = AftersalesFinancialStatistics::findOne([
                    'platform_code' => $afterSale['platform_code'],
                    'after_sales_id' => $afterSale['after_sale_id'],
                    'account_id' => $afterSale['account_id'],
                    'order_id' => $afterSale['order_id'],
                ]);
                if (empty($statistics)) {
                    $statistics = new AftersalesFinancialStatistics();
                    $statistics->create_time = date('Y-m-d H:i:s');
                }
                $statistics->platform_code = $afterSale['platform_code'];
                $statistics->type = AftersalesFinancialStatistics::AFTER_TYPE_REDIRECT;
                $statistics->after_sales_id = $afterSale['after_sale_id'];
                $statistics->account_id = $afterSale['account_id'];
                $statistics->erp_account_id = $accountInfo->old_account_id;
                $statistics->platform_order_id = !empty($orderInfo['info']['platform_order_id']) ? $orderInfo['info']['platform_order_id'] : '';
                $statistics->order_id = $afterSale['order_id'];
                $statistics->re_order_id = $redirect->redirect_order_id;
                $statistics->total = !empty($orderInfo['info']['total_price']) ? $orderInfo['info']['total_price'] : 0;
                $statistics->currency = !empty($orderInfo['info']['currency']) ? $orderInfo['info']['currency'] : '';
                $statistics->resend_time = $afterSale['approve_time'];

                //发货仓库(原订单)
                if (!empty($orderInfo['wareh_logistics']['warehouse'])) {
                    $statistics->warehouse_code = $orderInfo['wareh_logistics']['warehouse']['warehouse_code'];
                } else {
                    $statistics->warehouse_code = '';
                }

                //发货仓库(重寄订单)
                if (!empty($redirectOrderInfo['wareh_logistics']['warehouse'])) {
                    $statistics->re_warehouse_code = $redirectOrderInfo['wareh_logistics']['warehouse']['warehouse_code'];
                } else {
                    $statistics->re_warehouse_code = '';
                }

                if (!empty($orderInfo['profit'])) {

                    //未发货的订单
                    if ($orderInfo['info']['complete_status'] < OrderKefu::COMPLETE_STATUS_PARTIAL_SHIP) {
                        //原单利润
                        $statistics->profit = !empty($orderInfo['profit']['profit_new1']) ? $orderInfo['profit']['profit_new1'] : 0;

                        //原订单运费
                        $statistics->shipping_cost = !empty($orderInfo['profit']['shipping_cost']) ? $orderInfo['profit']['shipping_cost'] : 0;
                    } else {
                        //已发货的订单

                        //原单利润
                        $statistics->profit = !empty($orderInfo['profit']['true_profit_new1']) ? $orderInfo['profit']['true_profit_new1'] : 0;

                        //原订单运费
                        $statistics->shipping_cost = !empty($orderInfo['profit']['true_shipping_fee']) ? $orderInfo['profit']['true_shipping_fee'] : 0;
                    }
                } else {
                    $statistics->profit = 0;
                    $statistics->shipping_cost = 0;
                }

                //重寄单利润
                if (!empty($redirectOrderInfo['profit'])) {
                    //未发货的订单
                    if ($redirectOrderInfo['info']['complete_status'] < OrderKefu::COMPLETE_STATUS_PARTIAL_SHIP) {
                        $statistics->re_profit = !empty($redirectOrderInfo['profit']['profit_new1']) ? $redirectOrderInfo['profit']['profit_new1'] : 0;

                        $statistics->re_shipping_cost = !empty($redirectOrderInfo['profit']['shipping_cost']) ? $redirectOrderInfo['profit']['shipping_cost'] : 0;
                    } else {
                        //已发货的订单
                        $statistics->re_profit = !empty($redirectOrderInfo['profit']['true_profit_new1']) ? $redirectOrderInfo['profit']['true_profit_new1'] : 0;

                        $statistics->re_shipping_cost = !empty($redirectOrderInfo['profit']['true_shipping_fee']) ? $redirectOrderInfo['profit']['true_shipping_fee'] : 0;
                    }
                } else {
                    $statistics->re_profit = 0;
                    $statistics->re_shipping_cost = 0;
                }

                //获取当前月的汇率
                if (!empty($orderInfo['info']['currency'])) {
                    $rate = CurrencyRateKefu::findOne([
                        'from_currency_code' => $orderInfo['info']['currency'],
                        'to_currency_code' => 'CNY',
                        'rate_month' => date('Ym', strtotime('-1 month')),
                    ]);
                    if (!empty($rate)) {
                        $statistics->rate = $rate->rate;
                    }
                }

                //获取重寄成本计算方式
                $refundReason = RefundReason::findOne([
                    'department_id' => $afterSale['department_id'],
                    'reason_type_id' => $afterSale['reason_id'],
                ]);

                if (!empty($refundReason->resend_cost_id)) {
                    $resendCost = BasicConfig::findOne($refundReason->resend_cost_id);
                    if (!empty($resendCost)) {
                        //重寄单利润的相反数
                        if ($resendCost->id == 129) {
                            $statistics->cost = -($statistics->re_profit / $statistics->rate);
                            $statistics->cost_rmb = -($statistics->re_profit);
                        } else if ($resendCost->id == 130) {
                            //(重寄单利润+成本)的相反数
                            //成本是平均采购价*数量
                            $cost = 0;
                            $pros = AfterSalesProduct::find()
                                ->andWhere(['after_sale_id' => $afterSale['after_sale_id']])
                                ->asArray()
                                ->all();
                            if (!empty($pros)) {
                                foreach ($pros as $pro) {
                                    //获取产品平均采购价
                                    $avg_purchase_cost = 0;
                                    $sku = Product::findOne(['sku' => $pro['sku']]);
                                    if (!empty($sku)) {
                                        $avg_purchase_cost = $sku->avg_price;
                                    }
                                    $cost += $pro['issue_quantity'] * $avg_purchase_cost;
                                }
                            }

                            $statistics->cost = -(($statistics->re_profit + $cost) / $statistics->rate);
                            $statistics->cost_rmb = -($statistics->re_profit + $cost);
                        }
                    }
                }

                //保存重寄单信息
                if (!$statistics->save(false)) {
                    continue;
                }

                //保存重寄单详情
                $products = AfterSalesProduct::find()
                    ->andWhere(['after_sale_id' => $afterSale['after_sale_id']])
                    ->asArray()
                    ->all();

                if (!empty($products)) {
                    foreach ($products as $product) {
                        $statisticsDel = AftersalesFinancialStatisticsDel::findOne([
                            'financial_id' => $statistics->id,
                            'after_sales_id' => $product['after_sale_id'],
                            'sku' => $product['sku'],
                        ]);
                        if (empty($statisticsDel)) {
                            $statisticsDel = new AftersalesFinancialStatisticsDel();
                            $statisticsDel->create_time = date('Y-m-d H:i:s');
                        }
                        $statisticsDel->financial_id = $statistics->id;
                        $statisticsDel->after_sales_id = $product['after_sale_id'];
                        $statisticsDel->sku = !empty($product['sku']) ? $product['sku'] : '';
                        $statisticsDel->qty = !empty($product['issue_quantity']) ? $product['issue_quantity'] : 0;
                        $statisticsDel->resend_cost = !empty($product['refund_redirect_price']) ? $product['refund_redirect_price'] : 0;
                        $statisticsDel->resend_cost_rmb = !empty($product['refund_redirect_price_rmb']) ? $product['refund_redirect_price_rmb'] : 0;

                        //获取产品平均采购价
                        $sku = Product::findOne(['sku' => $product['sku']]);
                        if (!empty($sku)) {
                            $statisticsDel->avg_purchase_cost = $sku->avg_price;
                        } else {
                            $statisticsDel->avg_purchase_cost = 0;
                        }

                        $statisticsDel->save(false);
                    }
                }

            } catch (\Exception $e) {
                //防止出现的异常中断整个程序
            }
        }
    }

    /**
     * 处理退款单(已退款时间为准)
     */
    public function refund($accountInfo, $startTime, $endTime)
    {
        //已退款时间为准
        $afterSales = AfterSalesOrder::find()
            ->alias('a')
            ->select('a.*, r.refund_amount, r.refund_time')
            ->leftJoin(['r' => AfterSalesRefund::tableName()], 'r.after_sale_id = a.after_sale_id')
            ->andWhere(['a.account_id' => $accountInfo->id])
            ->andWhere(['a.type' => AfterSalesOrder::ORDER_TYPE_REFUND])
            ->andWhere(['a.status' => AfterSalesOrder::ORDER_STATUS_AUDIT_PASSED])
            ->andWhere(['between', 'r.refund_time', $startTime, $endTime])
            ->asArray()
            ->all();
        if (empty($afterSales)) {
            return false;
        }

        foreach ($afterSales as $afterSale) {
            try {
                //获取售后订单信息
                $orderInfo = OrderKefu::getOrderStack($afterSale['platform_code'], '', $afterSale['order_id']);

                if (empty($orderInfo)) {
                    continue;
                } else {
                    $orderInfo = json_decode(json_encode($orderInfo), true, 512, JSON_BIGINT_AS_STRING);
                }

                $statistics = AftersalesFinancialStatistics::findOne([
                    'platform_code' => $afterSale['platform_code'],
                    'after_sales_id' => $afterSale['after_sale_id'],
                    'account_id' => $afterSale['account_id'],
                    'order_id' => $afterSale['order_id'],
                ]);
                if (empty($statistics)) {
                    $statistics = new AftersalesFinancialStatistics();
                    $statistics->create_time = date('Y-m-d H:i:s');
                }
                $statistics->platform_code = $afterSale['platform_code'];
                $statistics->type = AftersalesFinancialStatistics::AFTER_TYPE_REFUND;
                $statistics->after_sales_id = $afterSale['after_sale_id'];
                $statistics->account_id = $afterSale['account_id'];
                $statistics->erp_account_id = $accountInfo->old_account_id;
                $statistics->platform_order_id = !empty($orderInfo['info']['platform_order_id']) ? $orderInfo['info']['platform_order_id'] : '';
                $statistics->order_id = $afterSale['order_id'];
                $statistics->total = !empty($orderInfo['info']['total_price']) ? $orderInfo['info']['total_price'] : 0;
                $statistics->currency = !empty($orderInfo['info']['currency']) ? $orderInfo['info']['currency'] : '';
                $statistics->refund_time = $afterSale['refund_time'];

                //发货仓库(原订单)
                if (!empty($orderInfo['wareh_logistics']['warehouse'])) {
                    $statistics->warehouse_code = $orderInfo['wareh_logistics']['warehouse']['warehouse_code'];
                } else {
                    $statistics->warehouse_code = '';
                }

                if (!empty($orderInfo['profit'])) {
                    //未发货的订单
                    if ($orderInfo['info']['complete_status'] < OrderKefu::COMPLETE_STATUS_PARTIAL_SHIP) {
                        //原单利润
                        $statistics->profit = !empty($orderInfo['profit']['profit_new1']) ? $orderInfo['profit']['profit_new1'] : 0;

                        //原订单运费
                        $statistics->shipping_cost = !empty($orderInfo['profit']['shipping_cost']) ? $orderInfo['profit']['shipping_cost'] : 0;
                    } else {
                        //已发货的订单

                        //原单利润
                        $statistics->profit = !empty($orderInfo['profit']['true_profit_new1']) ? $orderInfo['profit']['true_profit_new1'] : 0;

                        //原订单运费
                        $statistics->shipping_cost = !empty($orderInfo['profit']['true_shipping_fee']) ? $orderInfo['profit']['true_shipping_fee'] : 0;
                    }
                } else {
                    $statistics->profit = 0;
                    $statistics->shipping_cost = 0;
                }

                //获取当前月的汇率
                if (!empty($orderInfo['info']['currency'])) {
                    $rate = CurrencyRateKefu::findOne([
                        'from_currency_code' => $orderInfo['info']['currency'],
                        'to_currency_code' => 'CNY',
                        'rate_month' => date('Ym', strtotime('-1 month')),
                    ]);
                    if (!empty($rate)) {
                        $statistics->rate = $rate->rate;
                    }
                }

                //获取退款成本计算方式
                $refundReason = RefundReason::findOne([
                    'department_id' => $afterSale['department_id'],
                    'reason_type_id' => $afterSale['reason_id'],
                ]);

                if (!empty($refundReason->refund_cost_id)) {
                    $refundCost = BasicConfig::findOne($refundReason->refund_cost_id);
                    if (!empty($refundCost)) {
                        //退款金额
                        if ($refundCost->id == 124) {
                            $statistics->cost = $afterSale['refund_amount'];
                            $statistics->cost_rmb = ($afterSale['refund_amount'] * $statistics->rate);
                        } else if ($refundCost->id == 125) {
                            //退款金额-问题产品成本
                            //登记的售后单退款金额，再减去退款SKU的平均采购成本×退款件数
                            $cost = 0;
                            $pros = AfterSalesProduct::find()
                                ->andWhere(['after_sale_id' => $afterSale['after_sale_id']])
                                ->asArray()
                                ->all();
                            if (!empty($pros)) {
                                foreach ($pros as $pro) {
                                    //获取产品平均采购价
                                    $avg_purchase_cost = 0;
                                    $sku = Product::findOne(['sku' => $pro['sku']]);
                                    if (!empty($sku)) {
                                        $avg_purchase_cost = $sku->avg_price;
                                    }
                                    $cost += $pro['issue_quantity'] * $avg_purchase_cost;
                                }
                            }

                            $statistics->cost = (($afterSale['refund_amount'] * $statistics->rate) - $cost) / $statistics->rate;
                            $statistics->cost_rmb = ($afterSale['refund_amount'] * $statistics->rate) - $cost;

                        } else if ($refundCost->id == 126) {
                            //原单运费
                            $statistics->cost = ($statistics->shipping_cost/ $statistics->rate);
                            $statistics->cost_rmb = $statistics->shipping_cost;
                        } else if ($refundCost->id == 127) {
                            //零
                            $statistics->cost = 0;
                            $statistics->cost_rmb = 0;
                        } else if ($refundCost->id == 128) {
                            //原单利润
                            $statistics->cost = ($statistics->profit / $statistics->rate);
                            $statistics->cost_rmb = $statistics->profit;
                        }
                    }
                }

                //保存退款单信息
                if (!$statistics->save(false)) {
                    continue;
                }

                //保存退款单详情
                $products = AfterSalesProduct::find()
                    ->andWhere(['after_sale_id' => $afterSale['after_sale_id']])
                    ->asArray()
                    ->all();

                if (!empty($products)) {
                    foreach ($products as $product) {
                        $statisticsDel = AftersalesFinancialStatisticsDel::findOne([
                            'financial_id' => $statistics->id,
                            'after_sales_id' => $product['after_sale_id'],
                            'sku' => $product['sku'],
                        ]);
                        if (empty($statisticsDel)) {
                            $statisticsDel = new AftersalesFinancialStatisticsDel();
                            $statisticsDel->create_time = date('Y-m-d H:i:s');
                        }
                        $statisticsDel->financial_id = $statistics->id;
                        $statisticsDel->after_sales_id = $product['after_sale_id'];
                        $statisticsDel->sku = !empty($product['sku']) ? $product['sku'] : '';
                        $statisticsDel->qty = !empty($product['issue_quantity']) ? $product['issue_quantity'] : 0;

                        //SKU退款成本计算公式: SKU金额/订单总金额*订单退款成本
                        if (!empty($orderInfo['product'])) {
                            foreach ($orderInfo['product'] as $item) {
                                if ($item['sku'] == $product['sku']) {
                                    //SKU退款成本计算公式: SKU金额/订单总金额*订单退款成本
                                    $statisticsDel->refund_cost = $item['total_price'] / $orderInfo['info']['total_price'] * $statistics->cost;
                                    $statisticsDel->refund_cost_rmb = ($item['total_price'] / $orderInfo['info']['total_price'] * $statistics->cost) * $statistics->rate;
                                    break;
                                }
                            }
                        } else {
                            $statisticsDel->refund_cost = 0;
                            $statisticsDel->refund_cost_rmb = 0;
                        }

                        //获取产品平均采购价
                        $sku = Product::findOne(['sku' => $product['sku']]);
                        if (!empty($sku)) {
                            $statisticsDel->avg_purchase_cost = $sku->avg_price;
                        } else {
                            $statisticsDel->avg_purchase_cost = 0;
                        }

                        $statisticsDel->save(false);
                    }
                }

            } catch (\Exception $e) {
                //防止出现的异常中断整个程序
            }
        }
    }
}
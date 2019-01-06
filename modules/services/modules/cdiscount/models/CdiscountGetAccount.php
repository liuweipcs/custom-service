<?php

namespace app\modules\services\modules\cdiscount\models;

use app\modules\services\modules\cdiscount\components\cdiscountApi;
use app\modules\accounts\models\CdiscountAccountOverview;

class CdiscountGetAccount
{
    /**
     * 获取cd账号表现
     */
    public function getSellerIndicators($account) {
        if (empty($account)) {
            return false;
        }

        $cdApi = new cdiscountApi($account->refresh_token);


        $result = $cdApi->getSellerIndicators();

        if (empty($result['GetSellerIndicatorsResponse']) || empty($result['GetSellerIndicatorsResponse']['GetSellerIndicatorsResult']['SellerIndicators'])) {
            return false;
        }

        $indicators = $result['GetSellerIndicatorsResponse']['GetSellerIndicatorsResult']['SellerIndicators']['SellerIndicator'];
        //纠纷率
        $claim_rate = $indicators[2];


        if(empty($claim_rate)){
            return false;
        }
        //退款率
        $refunds_rate = $indicators[3];


        if(empty($refunds_rate)){
            return false;
        }

        //截止时间
        $indicators_time = str_replace('T',' ',$claim_rate['ComputationDate']);
        $day = date('Y-m-d 00:00:00',strtotime('-2 day'));


        try {
            $seller_indicators = CdiscountAccountOverview::findOne(['account_id' => $account->id, 'indicators_time' => $day]);
            if (empty($seller_indicators)) {
                $seller_indicators = new CdiscountAccountOverview();
                $seller_indicators->create_by = 'system';
                $seller_indicators->create_time = date('Y-m-d H:i:s');
            }
            $seller_indicators->account_id = $account->id;
            $seller_indicators->indicators_time = !empty($indicators_time) ? $indicators_time : '';
            $seller_indicators->claim_rate = !empty($claim_rate['ValueD30']) ? $claim_rate['ValueD30'] : 0;
            $seller_indicators->claims_rate = !empty($claim_rate['ValueD60']) ? $claim_rate['ValueD60'] : 0;
            $seller_indicators->refund_rate = !empty($refunds_rate['ValueD30']) ? $refunds_rate['ValueD30'] : 0;
            $seller_indicators->refunds_rate = !empty($refunds_rate['ValueD60']) ? $refunds_rate['ValueD60'] : 0;
            $seller_indicators->modify_by = 'system';
            $seller_indicators->modify_time = date('Y-m-d H:i:s');
            $seller_indicators->save();

        }catch (\Exception $exc) {
            echo $exc->getTraceAsString();
        }

    }

}
<?php

namespace app\modules\services\modules\aliexpress\models;

use Yii;
use app\components\ConfigFactory;
use app\modules\services\modules\aliexpress\components\TaobaoQimenApi;
use app\modules\accounts\models\AliexpressAccount;

/**
 * 延长速卖通买家收货确认时间
 */
class ExtendsBuyerAcceptGoodsTime
{

    /**
     * 延长速卖通买家收货确认时间
     */
    public function extendAcceptGoodsTime($platformOrderId, $day)
    {

        $info = AliexpressOrder::find()->where(['platform_order_id' => $platformOrderId])->asArray()->one();
        if (empty($info)) {
            return '订单信息为空';
        }

        //获取速卖通账号
        $account = AliexpressAccount::find()->where(['id' => $info['account_id']])->asArray()->one();
        if (empty($account)) {
            return '账号信息为空';
        }

        //判断订单是否完成
        $orderInfo = AliexpressOrder::getOrderInfo($platformOrderId, $account['id']);
        if (!empty($orderInfo) && !empty($orderInfo['target']['order_status']) && $orderInfo['target']['order_status'] == 'FINISH') {
            return '该订单已完成，禁止延长收货时间。';
        }

        if (!empty($orderInfo) && !empty($orderInfo['target']['issue_status']) && $orderInfo['target']['issue_status'] == 'IN_ISSUE') {
            return '纠纷中的订单，禁止延长收货时间。';
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['erp_gatewayUrl']) ? $qimenApiInfo['erp_gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return '接口网关为空';
        }

        //创建奇门请求api
        $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
        $taobaoQimenApi->setGatewayUrl($gatewayUrl);

        //配置请求参数
        $request = new \MinxinAliexpressExtendbuyerreceipttimeRequest();
        //设置账号ID
        $request->setAccountId($account['id']);
        //设置订单ID
        $request->setOrderId($platformOrderId);
        //设置延长时间
        $request->setTimeExpand($day);

        $taobaoQimenApi->doRequest($request);

        if ($taobaoQimenApi->isSuccess()) {
            return true;
        } else {
            return '延长买家收货失败, ' . $taobaoQimenApi->getErrorMessage();
        }
    }

}
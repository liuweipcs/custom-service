<?php

namespace app\modules\services\modules\ebay\controllers;

use yii\web\Controller;
use app\modules\mails\models\CancelOrderHandle;

class CancelorderhandleController extends Controller
{
    /**
     * 获取取消订单纠纷未处理的订单号
     */
    public function actionGetcancelorders()
    {
        $platformCode = !empty($_REQUEST['platform_code']) ? trim($_REQUEST['platform_code']) : '';
        $accountId = !empty($_REQUEST['account_id']) ? trim($_REQUEST['account_id']) : 0;

        $query = CancelOrderHandle::find();
        if (!empty($platformCode)) {
            $query->andWhere(['platform_code' => $platformCode]);
        }
        if (!empty($accountId)) {
            $query->andWhere(['account_id' => $accountId]);
        }

        $result = $query->select('platform_order_id')->andWhere(['status' => 0])->column();

        if (empty($result)) {
            die(json_encode([]));
        } else {
            die(json_encode($result));
        }
    }

    /**
     * 设置已经处理过的订单号
     */
    public function actionSetcancelorders()
    {
        $orderId = !empty($_REQUEST['order_id']) ? trim($_REQUEST['order_id']) : '';

        if (empty($orderId)) {
            die('平台订单ID不能为空');
        }

        //可以一次性传多个平台订单ID
        $orderIds = explode(',', $orderId);
        if (!empty($orderIds)) {
            foreach ($orderIds as $id) {
                $cancel = CancelOrderHandle::findOne(['platform_order_id' => $id, 'status' => 0]);
                if (!empty($cancel)) {
                    $cancel->status = 1;
                    $cancel->handle_time = date('Y-m-d H:i:s');
                    $cancel->save();
                }
            }
        }

        die('DONE');
    }
}
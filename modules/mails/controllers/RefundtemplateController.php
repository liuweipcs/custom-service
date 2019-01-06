<?php

namespace app\modules\mails\controllers;

use app\common\VHelper;
use app\components\Controller;
use app\modules\aftersales\models\AfterSalesReturn;
use app\modules\mails\models\RefundTemplate;
use yii\helpers\Json;

class RefundtemplateController extends Controller
{
    /**
     * 获取退货信息
     */
    public function actionGetrefundinfo()
    {
        $id           = \yii::$app->request->post('rule_warehouse_id');
        $erp_order_id = \Yii::$app->request->post('order_id');

        //先查询海外仓退件表 是否有rma
        $yb = AfterSalesReturn::findOne_ByOrderId(['order_id' => $erp_order_id]);
        if (empty($id)) {
            $response['status']  = 'error';
            $response['message'] = '无仓库id!';
            die(Json::encode($response));
        }
        $template = RefundTemplate::find()->select('is_get_rma,refund_name,refund_address')->where(['rule_warehouse_id' => $id])->asArray()->one();

        if (empty($template)) {
            $response['status']  = 'error';
            $response['message'] = '暂无退货信息!';
            die(Json::encode($response));
        }
        if (!empty($yb->rma)) {
            if ($template['is_get_rma'] == 1) {
                //调接口
                $template['is_get_rma'] = $yb->rma;

            } else {
                $template['is_get_rma'] = '不需要';
            }
        } else {
            //调用接口获取rma
            $url         = "http://dc.yibainetwork.com/index.php/orders/createReturnOrder";
            $tracking_no = \Yii::$app->request->post('tracking_no');
            if (isset($tracking_no) && !empty($tracking_no)) {
                $json_str = [
                    'order' => json_encode(
                        [
                            'order_id'    => trim($erp_order_id),
                            'tracking_no' => trim($tracking_no)
                        ]
                    )
                ];
            } else {
                $json_str = [
                    'order' => json_encode(
                        [
                            'order_id' => trim($erp_order_id)
                        ]
                    )
                ];
            }
            //退货信息
            $rma_code_info = VHelper::http_post_json($url, $json_str);
            if (isset($rma_code_info)) {
                if ($rma_code_info->error == 0) {
                    $rma_code = $rma_code_info->rma_code;
                    if ($template['is_get_rma'] == 1) {
                        //调接口
                        $template['is_get_rma'] = $rma_code;

                    } else {
                        $template['is_get_rma'] = '不需要';
                    }
                    //存进海外仓退件表
                    $ybreturn = AfterSalesReturn::findOne_ByOrderId(['order_id' => $erp_order_id]);
                    if($ybreturn){
                        $ybreturn->rma         = $rma_code;
                        $ybreturn->modify_time = date('Y-m-d H:i:s');
                        $ybreturn->modify_by   = \Yii::$app->user->identity->username;
                        $ybreturn->save();
                    }
                } else {
                    $response['status']  = 'error';
                    $response['message'] = $rma_code_info->message;
                    die(Json::encode($response));
                }
            } else {
                $response['status']  = 'error';
                $response['message'] = '未获取到rma信息';
                die(Json::encode($response));
            }
        }
        $response['status']  = 'success';
        $response['content'] = $template;
        die(Json::encode($response));
    }
}

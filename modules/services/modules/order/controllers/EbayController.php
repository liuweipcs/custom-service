<?php

namespace app\modules\services\modules\order\controllers;

use app\common\VHelper;
use Yii;
use yii\web\Controller;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\orders\models\Order;
use app\modules\mails\models\EbayFeedback;
use app\modules\systems\models\FeedbackAccountRule;
use app\modules\mails\models\FeedbackTemplate;
use app\modules\services\modules\ebay\models\LeaveFeedback;
use app\modules\services\modules\order\models\Feedbacklog;
use app\modules\aftersales\models\WarehouseprocessingModel;
use app\modules\aftersales\models\ComplaintModel;

/**
 *
 * Class AccountController
 * @package app\commands
 */
class EbayController extends Controller {
    /*     * *
     * 自动回复留评Ebay订单
     * 
     * * */

    public function actionTemplateorder() {
        set_time_limit(0);
        //获取ebay平台code
        $platform = Platform::PLATFORM_CODE_EB;
        //开始时间
        $start_time = date("Y-m-d 00:00:00", strtotime("-6 month"));
        //结束时间
        $end_time = date("Y-m-d 23:59:59", strtotime("-1 days"));
        $page = Yii::$app->request->get('page', '');
        if (!empty($page)) {
            $limit = 10;
            $pages = ($page - 1) * $limit;
            //获取ebay订单前一天的订单数量
            $sql = "SELECT platform_order_id FROM {{%order_ebay}}  WHERE payment_status=1 and paytime  BETWEEN " . "'$start_time'" . " AND " . "'$end_time'" . " LIMIT " . $pages . ',' . $limit;    
            $orderlist = Yii::$app->db_order->createCommand($sql)->queryAll();
            if (empty($orderlist)) {
                die('没有数据');
            }
            $orderId = [];
            foreach ($orderlist as $key => $value) {
                $model = new EbayFeedback();
                $order = new Order();
                $info = $order->getOrderByPlatformOrderID($platform, $value['platform_order_id']);
             
                $itemIds = array();
                $itemInfos = array();
                foreach ($info->order->items as $item) {
                    $itemIds[$item->item_id] = $item->sku;
                    $itemInfos['sku'] = $item->sku;
                    $itemInfos['item_id'] = $item->item_id;
                    $itemInfos['sale_price'] = $item->sale_price;
                    $itemInfos['currency'] = $item->currency;
                    $itemInfos['transaction_id'] = $item->transaction_id;
                    $item_id = $item->item_id;
                }

                // 获取erp 帐号数据
                $account_info = Account::getHistoryAccountInfo($info->order->account_id, $platform);
              
                if ($account_info == false) {

                    continue; //跳出本次循环  
                }
                $account_name = $account_info->account_name;

                $account_id = $account_info->id;
                //获取eBay自动回复规则 先判断所有的账号规则
                $accountruleall = FeedbackAccountRule::find()->where(['platform_code' => $platform])->andwhere(['account_type' => 'all'])->andWhere(['status' => 1])->asArray()->one();
                $accountrulecustom = FeedbackAccountRule::find()->where(['platform_code' => $platform])->andwhere(['account_type' => 'custom'])->andWhere(['status' => 1])->asArray()->all();
               
                if (empty($accountruleall) && empty($accountrulecustom)) {
                    die('没有相关的规则');
                }
                if (!empty($accountruleall)) {
                    //取随机回复模板
                    $templatecontent = FeedbackTemplate::find()->select('template_content')->where(['platform_code' => $platform])->all();
                    $temp = [];
                    foreach ($templatecontent as $key => $vv) {
                        $temp[] = $vv->template_content;
                    }
                    $key = array_rand($temp);
                    $comment_text = $temp[$key];
                } else {
                    if (!empty($accountrulecustom)) {
                        //获取erp 帐号配置相应的规则
                        foreach ($accountrulecustom as $val) {
                            $accountshortnames = explode(',', $val['account_short_names']);
                            if (in_array($account_name, $accountshortnames)) {
                                //获取对应的回复模板
                                $feedback_id = $val['feedback_id'];
                                break;
                            }
                        }
              
                    }
                    if (empty($feedback_id)) {
                        continue; //跳出本次循环  
                    }
             
                    $templatecontent = FeedbackTemplate::find()->select('template_content')->where(['id' => $feedback_id])->asArray()->one();
                    $comment_text = $templatecontent['template_content'];
                }
                
                // 填充feedback数据
                $model->item_id = $item_id;
                $model->comment_text = $comment_text;
                $model->commenting_user = $account_name;
                $model->comment_time = date('Y-m-d H:i:s');
                $model->comment_type = 4;
                $model->item_price = $itemInfos['sale_price'];
                $model->currency = $itemInfos['currency'];
                $model->role = 2;
                $model->transaction_id = $itemInfos['transaction_id'];
                $model->order_line_item_id = $info->order->platform_order_id;
                $model->account_id = $account_id;
                $leaveFeedbackModel = new LeaveFeedback($model, $info->order->buyer_id);
                $result = $leaveFeedbackModel->handleResponse();
                $result = simplexml_load_string($result);
                if ($result->Ack == 'Success') {
                    $model->comment_time = date('Y-m-d H:i:s', strtotime($result->Timestamp));
                    $model->feedback_id = $result->FeedbackID;
                    $model->save();
                    echo "回复评论成功";
                } else {
                    $feedbacklog = new Feedbacklog();
                    $feedbacklog->platform_order_id = $value['platform_order_id'];
                    $feedbacklog->item_id = $item_id;
                    $feedbacklog->account_id = $account_id;
                    $feedbacklog->created_time = date('Y-m-d H:i:s');
                    $feedbacklog->save();
                    echo "失败日志记录成功";
                }
            }
            $page++;
            VHelper::throwTheader('/services/order/ebay/templateorder', ['page' => $page], 'GET', 1200);
            die('没有相关数据');
        } else {
            VHelper::throwTheader('/services/order/ebay/templateorder', ['page' => 1], 'GET', 1200);
            die('没有相关数据？');
        }





        //www.erp.com/services/order/ebay/templateorder
    }

  /*     * *
     * 接受仓库返回的信息
     * ** */

    public function actionGetwms() {
//        $data = [
//            [
//                'complaint_order' => 'KS201812114026', //客诉单号
//                'type' => "产品颜色错误", //客诉类型
//                'description' => "数据的骄傲新疆城建", //描述    
//                'SKU_IMG' => ["ES-GSGJ7000" => [['/complaint/sku/0020033143720852_b.jpg'], ['/complaint/sku/timg.jpg'], ['/complaint/sku/58cdb24be3624488ad3e8d3d00b4585f.jpeg']]], //sku图片地址
//                'consuming_time' => '3625363', //本次处理消耗时间
//                'processing_user' => '张三', //处理人
//                'processing_time' => "2018-12-08 18:30:23", //处理时间
//                'id' => 81,
//            ],
//        ];
        //测试环境地址
        // $url="http://192.168.71.210:30081/services/order/ebay/getwms";
        // 仓库返回的信息
        $vms_infos = file_get_contents("php://input");
        $data = json_decode($vms_infos, true);
        if (empty($data)) {
            return json_encode(['code' => 0, 'msg' => '参数错误']);
        }
        foreach ($data as $key => $val) {
            //改变客诉单为仓库处理完成待确认
            $complaint_orderId = ComplaintModel::find()->select('id,processing_times')->where(['complaint_order' => $val['complaint_order']])->one();
            ComplaintModel::updateAll(['status' => 3, 'processing_times' => $complaint_orderId->processing_times + 1], ['complaint_order' => $val['complaint_order']]);
            WarehouseprocessingModel::updateAll(['status' => 1,
                'processing_user' => $val['processing_user'],
                'processing_time' => $val['processing_time'],
                'consuming_time' => $val['consuming_time'],
                'processing_type' => $val['type'],
                'description' => $val['description'],
                'img_info' => json_encode($val['SKU_IMG']),
                    ], ['id' => $val['id']]);
        };
        return json_encode(['code' => 1, 'msg' => '操作成功']);
    }

}

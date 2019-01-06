<?php

namespace app\modules\services\modules\amazon\controllers;

use app\modules\orders\models\OrderAmazonSearch;
use yii\web\Controller;
use app\common\VHelper;
use app\modules\mails\models\AmazonReviewData;
use app\modules\mails\models\AmazonReviewMessageData;
use Yii;
use app\modules\mails\models\AmazonReviewLog;

class AmazonreviewController extends Controller {

    /**
     *
     */
    public function actionGetamazonreviewsdata($startTime = '', $endTime = '') {
        ini_set('max_execution_time', '0');
        $nowPage = 1;
        do {
//            $url = "http://www.erp.com/services/amazon/amazonreview/getreviews"; //测试
            $url = "http://120.24.249.36/services/amazon/amazonreview/getreviews";
            if (empty($startTime)) {
                $startTime = date("Y-m-d");
            }
            if (empty($endTime)) {
                $endTime = date("Y-m-d");
            }
            $postData = [
                'limit' => 200,
//                'start_time' => '2018-07-01 00:00:00',
//                'end_time' => '2018-07-31 23:59:59',
                'start_time' => $startTime . ' 00:00:00',
                'end_time' => $endTime . ' 23:59:59',
                'page' => $nowPage
            ];

            $data = VHelper::curl_post($url, $postData);
            //显示获得的数据
            $jsonDecode = json_decode($data);
            $totalPages = $jsonDecode->totalPage;
            if (is_array($jsonDecode->data) && !empty($jsonDecode->data)) {
                foreach ($jsonDecode->data as $value) {
                    $transaction = Yii::$app->db->beginTransaction();
                    $bool = FALSE;

                    $reviewModel = AmazonReviewData::find()->where(['id' => $value->id])->one();
                    if (empty($reviewModel)) {
                        //新增操作
                        $reviewModel = new AmazonReviewData();
                    }
                    $res = $reviewModel->saveData($reviewModel, $value);
                    if ($res) {
                        $bool = TRUE;
                    }
                    
                    //message表
                    $messageData = $value->message_data[0];
                    if (!$bool && !empty($messageData)) {
                        foreach ($messageData as &$val) {
                            $isMessDataModel = AmazonReviewMessageData::find()->where(['id' => $val->id])->one();
                            if (empty($isMessDataModel)) {
                                $messageDataModel = new AmazonReviewMessageData();
                                //订单id  orderId  获取amazon_fulfill_channel
                                $amazon_fulfill_channel = OrderAmazonSearch::getAmazonfulfillChannel(trim($val->orderId));
                                if (!empty($amazon_fulfill_channel)) {
                                    if (trim($amazon_fulfill_channel) == 'MFN') {
                                        $amazon_fulfill_channel = 'FBM';
                                    } elseif (trim($amazon_fulfill_channel) == 'AFN') {
                                        $amazon_fulfill_channel = 'FBA';
                                    }
                                } else {
                                    $amazon_fulfill_channel = '';
                                }
                                $val->amazon_fulfill_channel = $amazon_fulfill_channel; //添加
                                $messRes = $messageDataModel->saveData($val);
                                if ($messRes) {
                                    $bool = TRUE;
                                }
                            }
                        }
                    }

                    //处理事务
                    if (!$bool) {
                        $transaction->commit();
                    } else {
                        $transaction->rollBack();
                    }
                }


                echo '第' . $nowPage . '页数据执行成功!<br/>';
            } else {
                echo '数据同步完成...';
                break;
            }
            $nowPage++;
        } while ($nowPage <= $totalPages);
    }

    /**
     * 获取review操作日志
     * @author allen <2018-04-06>
     */
    public function actionGetamazonreviewsoptionslog() {
        $id = Yii::$app->request->get('id');
        $data = [];
        if ($id) {
            $data = AmazonReviewLog::getLogData($id);
        }
        return json_encode($data);
    }

    /**
     * 批量更新amazon 订单类型
     */
    public function actionUpdateamazon() {
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '256M');
        $data = AmazonReviewMessageData::find()->asArray()->all();
        foreach ($data as &$val) {
            if (!empty($val['amazon_fulfill_channel'])) {
                continue;
            }
            $isMessDataModel = AmazonReviewMessageData::find()->where(['id' => $val['id']])->one();
            $amazon_fulfill_channel = OrderAmazonSearch::getAmazonfulfillChannel(trim($val['orderId']));
            if (empty($amazon_fulfill_channel)) {
                continue;
            }
            if (trim($amazon_fulfill_channel) == 'MFN') {
                $amazon_fulfill_channel = 'FBM';
            } elseif (trim($amazon_fulfill_channel) == 'AFN') {
                $amazon_fulfill_channel = 'FBA';
            }
            $isMessDataModel->amazon_fulfill_channel = $amazon_fulfill_channel;
            $isMessDataModel->save();
        }
        die('update completed');
    }

}

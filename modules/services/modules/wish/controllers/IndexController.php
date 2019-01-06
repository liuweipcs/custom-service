<?php

namespace wish\controllers;

use app\modules\services\modules\wish\models\WishGetRefundOrder;

use wish\components\MerchantWishApi;
use yii\web\Controller;
use wish\components\WishApi;
use wish\models\WishInboxInfo;
use wish\models\WishInbox;
use wish\models\WishReply;
use wish\models\WishAccount;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use app\common\VHelper;
use Yii;

class IndexController extends Controller
{

    public function actionIndex()
    {
        $id = \Yii::$app->request->get("id");
        $account = (new \yii\db\Query())->select('wish_id,access_token')->from(WishAccount::tableName())
            ->where('wish_id=:i', [':i' => $id])->one(\Yii::$app->db_system);
        if (empty($account)) exit("没有账号");
// 		$api = new WishApi('006332dbd7d140aa81a7c22f95bd0095');
        $api = new WishApi($account['access_token']);
        $result = $api->getTicketList();
        $wishId = $account['wish_id'];
        if (!empty($result->data)) {
            $ticketIds = array();
            foreach ($result->data as $value) {
                !empty($value->Ticket->id) && array_push($ticketIds, $value->Ticket->id);
            }
            $rows = (new \yii\db\Query())->select('id,account_id,platform_id,info_id')->from(WishInbox::tableName())
                ->where(
                    ['in', 'platform_id', $ticketIds]
                )
                ->andWhere('account_id=:a', [':a' => $wishId])
                ->indexBy('platform_id')->all(\Yii::$app->db);
            $tran = \Yii::$app->db->beginTransaction();
            try {
                foreach ($result->data as $value) {
                    $infoId = 0;
                    if (!isset($rows[$value->Ticket->id])) {
                        foreach ($value->Ticket->items as $val) {
                            $orderId = 0;
                            $data = [
                                'order_id' => $val->Order->order_id,
                                'product_id' => $val->Order->product_id,
                                'variant_id' => $val->Order->variant_id,
                                'transaction_id' => $val->Order->transaction_id,
                                'sku' => $val->Order->sku,
                                'goods_name' => $val->Order->product_name,
                                'image_url' => $val->Order->product_image_url,
                                'quantity' => $val->Order->order_id,
                                'product_image' => $val->Order->order_id,
                                'price' => $val->Order->price,
                                'state' => $val->Order->state,
                                'is_wish_express' => $val->Order->is_wish_express,
                                'shipping_cost' => $val->Order->shipping_cost,
                                'tracking_confirmed' => $val->Order->tracking_confirmed,
                                'phone_number' => $val->Order->ShippingDetail->phone_number,
                                'city' => $val->Order->ShippingDetail->city,
                                'states' => $val->Order->ShippingDetail->state,
                                'receiver_name' => $val->Order->ShippingDetail->name,
                                'zipcode' => $val->Order->ShippingDetail->zipcode,
                                'street_address1' => $val->Order->ShippingDetail->street_address1,
                                'street_address2' => $val->Order->ShippingDetail->street_address2,
                                'order_time' => $val->Order->order_time,
                                'last_updated' => $val->Order->last_updated,
                            ];
                            isset($val->Order->shipping_provider) && $data['shipping_provider'] = $val->Order->shipping_provider;
                            isset($val->Order->tracking_number) && $data['track_number'] = $val->Order->tracking_number;
                            isset($val->Order->tracking_number) && $data['track_number'] = $val->Order->tracking_number;
                            isset($val->Order->shipped_date) && $data['shipped_date'] = $val->Order->shipped_date;
                            isset($val->Order->size) && $data['size'] = $val->Order->size;
                            isset($val->Order->color) && $data['color'] = $val->Order->color;
                            if (!\Yii::$app->db->createCommand()->insert(WishInboxInfo::tableName(), $data)->execute()) {
                                throw new \Exception("添加订单信息失败");
                            }
                            $infoId = \Yii::$app->db->getLastInsertID();
                            unset($data);
                        }
                        $inboxData = [
                            'info_id' => $infoId,
                            'transaction_id' => $value->Ticket->transaction_id,
                            'platform_id' => $value->Ticket->id,
                            'account_id' => $wishId,
                            'merchant_id' => $value->Ticket->merchant_id,
                            'label' => $value->Ticket->label,
                            'sublabel' => $value->Ticket->sublabel,
                            'open_date' => $value->Ticket->open_date,
                            'state' => $value->Ticket->state,
                            'subject' => $value->Ticket->subject,
                            'photo_proof' => $value->Ticket->photo_proof,
                            'user_locale' => $value->Ticket->UserInfo->locale,
                            'user_id' => $value->Ticket->UserInfo->id,
                            'user_name' => $value->Ticket->UserInfo->name,
                        ];
                        if (!\Yii::$app->db->createCommand()->insert(WishInbox::tableName(), $inboxData)->execute()) {
                            throw new \Exception("添加信息失败");
                        }
                        $infoId = \Yii::$app->db->getLastInsertID();
                        unset($inboxData);
                    } else {
                        $inboxData = [
                            'state' => $value->Ticket->state,
                        ];
                        if (!\Yii::$app->db->createCommand()->update(WishInbox::tableName(), $inboxData, 'id=:i', array(
                            ':i' => $rows[$value->Ticket->id]['id']
                        ))->execute()
                        ) {
                            throw new \Exception("更新状态失败");
                        }
                        $infoId = $rows[$value->Ticket->id]['id'];
                        unset($inboxData);
                    }
                    if (!empty($value->Ticket->replies)) {
                        $existBox = (new \yii\db\Query())
                            ->select('*')->from(WishReply::tableName())
                            ->where(['inbox_id' => $infoId])
                            ->orderBy('message_time asc')
                            ->indexBy('message_time')->all(\Yii::$app->db);
                        foreach ($value->Ticket->replies as $v) {
                            $key = str_replace('T', ' ', $v->Reply->date);
                            if (isset($existBox[$key])) continue;
                            $inbox = [
                                'inbox_id' => $infoId,
                                'reply_content' => $v->Reply->message,
                                'message_translated' => $v->Reply->translated_message,
                                'message_zh' => $v->Reply->translated_message_zh,
                                'image_urls' => implode('|', json_decode($v->Reply->image_urls)),
                                'message_time' => str_replace('T', ' ', $v->Reply->date),
                                'type' => $v->Reply->sender,
                            ];
                            if (!\Yii::$app->db->createCommand()->insert(WishReply::tableName(), $inbox)->execute()) {
                                throw new \Exception("添加新的回复失败");
                            }
                        }
                    }
                }
                $tran->commit();
            } catch (Exception $e) {
                $tran->rollBack();
            }
        }
    }

    /**
     * 获取退款订单数据
     */
    public function actionGetrefundorder()
    {
        //避免服务器拉取信息超时
        set_time_limit(0);
        ignore_user_abort(true);

        $accountId = Yii::$app->request->get('id', 0);

        if (!empty($accountId)) {
            //默认拉取前一周的退款
            $since = date('Y-m-d', strtotime('-1 week'));

            $account = Account::findOne($accountId);
            if (!empty($account)) {
                $refundOrder = new WishGetRefundOrder();
                $refundOrder->refundOrderList($account, $since);
            }

            //从队列中获取下一个任务
            $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_WISH, AccountTaskQueue::WISH_REFUND);
            if (!empty($nextTask)) {
                $nextAccountId = $nextTask->account_id;
                //从队列中删除该任务
                $nextTask->delete();
                //非阻塞的请求接口
                VHelper::throwTheader('/services/wish/index/getrefundorder', ['id' => $nextAccountId], 'GET', 1200);
            }

            die('GET WISH REFUND');
        } else {
            //获取当前任务队列数
            $count = AccountTaskQueue::find()->where([
                'platform_code' => Platform::PLATFORM_CODE_WISH,
                'type' => AccountTaskQueue::WISH_REFUND
            ])->count();

            if (empty($count)) {
                //获取账号信息(客服系统的)
                $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_WISH, Account::STATUS_VALID);
                if (!empty($accountList)) {
                    foreach ($accountList as $account) {
                        $accountTaskQenue = new AccountTaskQueue();
                        $accountTaskQenue->account_id = $account->id;
                        $accountTaskQenue->type = AccountTaskQueue::WISH_REFUND;
                        $accountTaskQenue->platform_code = $account->platform_code;
                        $accountTaskQenue->create_time = time();
                        $accountTaskQenue->save(false);
                    }
                }
            }

            //默认先从队列中取5条
            $taskList = AccountTaskQueue::getTaskList([
                'type' => AccountTaskQueue::WISH_REFUND,
                'platform_code' => Platform::PLATFORM_CODE_WISH
            ]);

            //循环的请求接口
            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
                    VHelper::throwTheader('/services/wish/index/getrefundorder', ['id' => $accountId], 'GET', 1200);
                    sleep(2);
                }
            }
            die('RUN GET WISH');
        }
    }

    public function actionTicket()
    {
        try {
            set_time_limit(0);

            $account = \Yii::$app->request->get('account');
            if (!empty($account)) {
                $url = \Yii::$app->request->get('url');
                $accountInfo = Account::findOne($account);
                if (empty($accountInfo)) {
                    return false;
                }
                $erpAccount = WishAccount::findOne(['wish_id' => $accountInfo->old_account_id]);
                if (empty($erpAccount)) {
                    return false;
                }
                $token = $erpAccount->access_token;

                if (empty($url)) {
                    /* $MerchantWishApi = new MerchantWishApi($account);
                     $token = $MerchantWishApi->getAccessToken();*/
                    $api = new WishApi($token);
                    $list = $api->getTicketList();
                } else {
                    $api = new WishApi('');
                    $list = $api->getUrlResult($url);
                }

                if (!empty($list->data)) {
                    foreach ($list->data as $value) {
                        if (!empty($value->Ticket->items)) {
                            $infoId = 0;
                            foreach ($value->Ticket->items as $val) {
                                $inboxInfo = WishInboxInfo::findOne(['order_id' => $val->Order->order_id]);
                                if (empty($inboxInfo)) {
                                    $inboxInfo = new WishInboxInfo();
                                }

                                $inboxInfo->order_id = !empty($val->Order->order_id) ? $val->Order->order_id : '';
                                $inboxInfo->product_id = !empty($val->Order->product_id) ? $val->Order->product_id : '';
                                $inboxInfo->variant_id = !empty($val->Order->variant_id) ? $val->Order->variant_id : '';
                                $inboxInfo->transaction_id = !empty($val->Order->transaction_id) ? $val->Order->transaction_id : '';
                                $inboxInfo->sku = !empty($val->Order->sku) ? $val->Order->sku : '';
                                $inboxInfo->goods_name = !empty($val->Order->product_name) ? $val->Order->product_name : '';
                                $inboxInfo->image_url = !empty($val->Order->product_image_url) ? $val->Order->product_image_url : '';
                                $inboxInfo->quantity = !empty($val->Order->quantity) ? $val->Order->quantity : '';
                                $inboxInfo->product_image = !empty($val->Order->product_image_url) ? $val->Order->product_image_url : '';
                                $inboxInfo->price = !empty($val->Order->price) ? $val->Order->price : '';
                                $inboxInfo->state = !empty($val->Order->state) ? $val->Order->state : '';
                                $inboxInfo->is_wish_express = !empty($val->Order->is_wish_express) ? $val->Order->is_wish_express : '';
                                $inboxInfo->shipping_cost = !empty($val->Order->shipping_cost) ? $val->Order->shipping_cost : '';
                                $inboxInfo->tracking_confirmed = !empty($val->Order->tracking_confirmed) ? $val->Order->tracking_confirmed : '';
                                $inboxInfo->phone_number = !empty($val->Order->ShippingDetail->phone_number) ? $val->Order->ShippingDetail->phone_number : '';
                                $inboxInfo->city = !empty($val->Order->ShippingDetail->city) ? $val->Order->ShippingDetail->city : '';
                                $inboxInfo->receiver_name = !empty($val->Order->ShippingDetail->name) ? $val->Order->ShippingDetail->name : '';
                                $inboxInfo->zipcode = !empty($val->Order->ShippingDetail->zipcode) ? $val->Order->ShippingDetail->zipcode : '';
                                $inboxInfo->street_address1 = !empty($val->Order->ShippingDetail->street_address1) ? $val->Order->ShippingDetail->street_address1 : '';
                                $inboxInfo->order_time = !empty($val->Order->order_time) ? $val->Order->order_time : '';
                                $inboxInfo->last_updated = !empty($val->Order->last_updated) ? $val->Order->last_updated : '';
                                $inboxInfo->street_address2 = !empty($val->Order->ShippingDetail->street_address2) ? $val->Order->ShippingDetail->street_address2 : '';
                                $inboxInfo->states = !empty($val->Order->ShippingDetail->state) ? $val->Order->ShippingDetail->state : '';
                                $inboxInfo->shipping_provider = !empty($val->Order->shipping_provider) ? $val->Order->shipping_provider : '';
                                $inboxInfo->track_number = !empty($val->Order->tracking_number) ? $val->Order->tracking_number : '';
                                $inboxInfo->track_confrimed_date = !empty($val->Order->tracking_confirmed_date) ? $val->Order->tracking_confirmed_date : '';
                                $inboxInfo->shipped_date = !empty($val->Order->shipped_date) ? $val->Order->shipped_date : '';
                                $inboxInfo->size = !empty($val->Order->size) ? $val->Order->size : '';
                                $inboxInfo->color = !empty($val->Order->color) ? $val->Order->color : '';
                                if ($inboxInfo->save(false)) {
                                    $infoId = $inboxInfo->info_id;
                                }
                            }

                            $inboxData = WishInbox::findOne(['platform_id' => $value->Ticket->id]);
                            if (empty($inboxData)) {
                                $inboxData = new WishInbox();
                            }

                            $inboxData->info_id = $infoId;
                            $inboxData->transaction_id = !empty($value->Ticket->transaction_id) ? $value->Ticket->transaction_id : '';
                            $inboxData->platform_id = !empty($value->Ticket->id) ? $value->Ticket->id : '';
                            $inboxData->account_id = $account;
                            $inboxData->merchant_id = !empty($value->Ticket->merchant_id) ? $value->Ticket->merchant_id : '';
                            $inboxData->label = !empty($value->Ticket->label) ? $value->Ticket->label : '';
                            $inboxData->sublabel = !empty($value->Ticket->sublabel) ? $value->Ticket->sublabel : '';
                            $inboxData->open_date = !empty($value->Ticket->open_date) ? $value->Ticket->open_date : '';
                            $inboxData->status = !empty($value->Ticket->state) ? $value->Ticket->state : '';
                            $inboxData->subject = !empty($value->Ticket->subject) ? $value->Ticket->subject : '';
                            $inboxData->photo_proof = !empty($value->Ticket->photo_proof) ? $value->Ticket->photo_proof : '';
                            $inboxData->user_locale = !empty($value->Ticket->UserInfo->locale) ? $value->Ticket->UserInfo->locale : '';
                            $inboxData->user_id = !empty($value->Ticket->UserInfo->id) ? $value->Ticket->UserInfo->id : '';
                            $inboxData->user_name = !empty($value->Ticket->UserInfo->name) ? $value->Ticket->UserInfo->name : '';
                            $inboxData->create_time = date("Y-m-d H:i:s");
                            $inboxData->read_stat = 0;
                            $inboxData->is_replied = 0;
                            $inboxData->save(false);

                            if (!empty($value->Ticket->replies)) {
                                foreach ($value->Ticket->replies as $v) {
                                    $reply = WishReply::findOne(['platform_id' => $value->Ticket->id, 'type' => $v->Reply->sender, 'reply_content' => $v->Reply->message]);
                                    if (empty($reply)) {
                                        $reply = new WishReply();
                                    }
                                    $image_urls = $v->Reply->image_urls;
                                    $image_urls = str_replace('u&#39;', '"', $image_urls);
                                    $image_urls = str_replace('&#39;', '"', $image_urls);
                                    $reply->platform_id = $value->Ticket->id;
                                    $reply->reply_content = $v->Reply->message;
                                    $reply->image_urls = implode(',', json_decode($image_urls));
                                    $reply->message_time = str_replace('T', ' ', $v->Reply->date);
                                    $reply->type = $v->Reply->sender;
                                    $reply->message_translated = !empty($v->Reply->translated_message) ? $v->Reply->translated_message : '';
                                    $reply->message_zh = !empty($v->Reply->translated_message_zh) ? $v->Reply->translated_message_zh : '';
                                    $reply->save(false);
                                }
                            }
                        }
                    }
                }

                $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_WISH, AccountTaskQueue::TASK_TYPE_MESSAGE);
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    VHelper::throwTheader('/services/wish/index/ticket', ['account' => $nextAccountId]);
                }

                die('GET WISH TICKET END');
            } else {

                $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_WISH, Account::STATUS_VALID);

                if (!empty($accountList)) {
                    $list = array();
                    foreach ($accountList as $account) {
                        $Queue = new AccountTaskQueue();
                        $Queue->account_id = $account->id;
                        $Queue->type = AccountTaskQueue::TASK_TYPE_MESSAGE;
                        $Queue->platform_code = Platform::PLATFORM_CODE_WISH;
                        $Queue->create_time = time();
                        $Queue->save(false);
                    }
                }

                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::TASK_TYPE_MESSAGE,
                    'platform_code' => Platform::PLATFORM_CODE_WISH
                ]);

                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        VHelper::throwTheader('/services/wish/index/ticket', ['account' => $accountId]);
                        sleep(2);
                    }
                }
                exit('RUN WISH TICKET END');
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            echo $e->getFile();
            echo $e->getLine();
        }
    }

    public function actionToken()
    {
        $api = new WishApi('7095477efad741f99dcb93127d6e8f6d');
        $result = $api->getTicketList();
        file_put_contents('reply.txt', json_encode($result));
        echo '<pre>';
        var_dump($result);
    }

    public function actionDb()
    {
// 		echo '<pre>';
// 		$rows = (new \yii\db\Query())->select('id,account_id,platform_id')->from(WishInbox::tableName())
// 		->where('account_id=:i',[':i'=>1])
// 		->andWhere(['in','platform_id',array('58086e0b000000000000','57fb5f78000000000000','57f619780000000000000000')])
// 		->indexBy('platform_id')->all(\Yii::$app->db);
// 		var_dump($rows);
        /* 		$rows = \Yii::$app->db->createCommand()->insert(WishReply::tableName(), array(
                        'inbox_id'=>123,
                        'message'=>'This is taking too long, I demand a refund',
                        'message_translated'=>'This is taking too long, I demand a refund',
                        'message_zh'=>'这是时间太长，我要求退款',
        // 				'image_urls'=>implode('|',json_decode($v->Reply->image_urls)),
                        'message_time'=>str_replace('T', ' ', '2016-10-13T07:11:07'),
                        'type'=>'user',
                )->execute());
                var_dump($rows); */
    }

    public function actionTest()
    {
// 		$api = new WishApi('006332dbd7d140aa81a7c22f95bd0095');
// 		$result = $api->getTicketById("5800a5780000000000000000");
// 		$result = $api->reOpenTicket("5800a5780000000000000000","goods hello world");\
// 		$result = $api->getTicketById("57f617da0000000000000000");
// 		var_dump($result);
// 		echo \Yii::$app->urlManager->createUrl('/services/wish/test');
        $content = file_get_contents('reply.txt');
        echo '<pre>';
        var_dump(json_decode($content));
    }
}
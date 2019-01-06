<?php

namespace app\modules\services\modules\cdiscount\models;

use app\modules\mails\models\CdiscountInbox;
use app\modules\mails\models\CdiscountInboxReply;
use app\modules\mails\models\CdiscountInboxSubject;
use app\modules\services\modules\cdiscount\components\cdiscountApi;

class CdiscountGetOfferQuestionList
{

    /**
     * @param $account
     * @param $startTime
     * @param $endTime
     * @return bool
     */
    public function getOfferQuestionList($account, $startTime, $endTime)
    {
        if (empty($account)) {
            return false;
        }

        $cdApi = new cdiscountApi($account->refresh_token);
        $result = $cdApi->GetOfferQuestionList(strtotime($startTime), strtotime($endTime), 'All');

        if (empty($result['GetOfferQuestionListResponse']) || empty($result['GetOfferQuestionListResponse']['GetOfferQuestionListResult']['OfferQuestionList'])) {
            return false;
        }
        $result = $result['GetOfferQuestionListResponse']['GetOfferQuestionListResult']['OfferQuestionList'];
        $offerQuestionList = $result['OfferQuestion'];
        //注意这里，有可能是一维的，全部转换成二给数组
        if (!empty($offerQuestionList['Id'])) {
            $offerQuestionList = [$offerQuestionList];
        }

        foreach ($offerQuestionList as $offer) {
            //合并主题，通过账号，产品ID，产品SKU，主题名称来判断一个主题是否已经存在
            $subject = CdiscountInboxSubject::findOne([
                'account_id' => $account->id,
                'product_ean' => $offer['ProductEAN'],
                'product_seller_reference' => $offer['ProductSellerReference'],
                'subject' => $offer['Subject'],
            ]);
            if (empty($subject)) {
                $subject = new CdiscountInboxSubject();
                $subject->inbox_id = $offer['Id'];
                $subject->create_by = 'system';
                $subject->create_time = date('Y-m-d H:i:s');
            } else {
                $inbox_id = explode(',', $subject->inbox_id);
                $inbox_id[] = $offer['Id'];
                $inbox_id = array_unique($inbox_id);
                $subject->inbox_id = implode(',', $inbox_id);
                $subject->is_read=0;
                $subject->is_reply=0;
            }

            $subject->account_id = $account->id;
            $subject->inbox_type = 'offerquestion';
            $subject->claim_type = '';
            $subject->platform_order_id = '';
            $subject->product_ean = !empty($offer['ProductEAN']) ? $offer['ProductEAN'] : '';
            $subject->product_seller_reference = !empty($offer['ProductSellerReference']) ? $offer['ProductSellerReference'] : '';
            $subject->subject = !empty($offer['Subject']) ? $offer['Subject'] : '';
            $subject->close_date = !empty($offer['CloseDate']) ? $this->handleTime($offer['CloseDate']) : '';
            $subject->creation_date = !empty($offer['CreationDate']) ? $this->handleTime($offer['CreationDate']) : '';
            $subject->last_updated_date = !empty($offer['LastUpdatedDate']) ? $this->handleTime($offer['LastUpdatedDate']) : '';
            $subject->status = !empty($offer['Status']) ? $offer['Status'] : '';
            $subject->modify_by = 'system';
            $subject->modify_time = date('Y-m-d H:i:s');

            if ($subject->save()) {
                if (!empty($offer['Messages']) && !empty($offer['Messages']['Message'])) {
                    $messageList = $offer['Messages']['Message'];

                    //注意这里，消息列表有可能是一维的，全部转换成二给数组
                    if (isset($messageList['Content'])) {
                        $messageList = [$messageList];
                    }

                    foreach ($messageList as $message) {
                        try {
                            //判断发送人是否为空，如果为空，则默认为空字符
                            if (empty($message['Sender'])) {
                                $message['Sender'] = '';
                            }
                            $timestamp = !empty($message['Timestamp']) ? $this->handleTime($message['Timestamp']) : '';
                            $inbox = CdiscountInbox::findOne(['inbox_subject_id' => $offer['Id'], 'sender' => $message['Sender'], 'content' => $message['Content']]);
                            if (empty($inbox)) {
                                $inbox = new CdiscountInbox();
                                $inbox->create_by = 'system';
                                $inbox->create_time = date('Y-m-d H:i:s');
                            }

                            $inbox->account_id = $account->id;
                            $inbox->inbox_subject_id = $offer['Id'];
                            $inbox->subject_id = $subject->id;
                            $inbox->sender = $message['Sender'];
                            $inbox->content = $message['Content'];
                            $inbox->timestamp = !empty($message['Timestamp']) ? $this->handleTime($message['Timestamp']) : '';
                            $inbox->modify_by = 'system';
                            $inbox->modify_time = date('Y-m-d H:i:s');
                            $inbox->save();
                        } catch (\Exception $e) {
                            //这里捕获异常，防止中断，以免后面的数据无法正常插入
                        }
                    }

                    //更新邮件的回复状态
                    $inboxs = CdiscountInbox::find()
                        ->andWhere(['account_id' => $account->id])
                        ->andWhere(['inbox_subject_id' => $offer['Id']])
                        ->orderBy('timestamp ASC')
                        ->all();
                    if (!empty($inboxs)) {
                        foreach ($inboxs as $key => $inbox) {
                            if (strtolower(trim($inbox->sender)) != strtolower(trim($account->account_discussion_name))) {
                                $ix = $key + 1;
                                //如果当前邮件的下一封邮件是我们回复的，说明该邮件已经回复过了
                                if (array_key_exists($ix, $inboxs) && !empty($inboxs[$ix])) {
                                    if (strtolower(trim($inboxs[$ix]->sender)) == strtolower(trim($account->account_discussion_name))) {
                                        $inbox->is_reply = 1;
                                        $inbox->save();
                                    }
                                }
                            }
                        }
                    }
                }

                //获取最新的邮件
                $lastSender = CdiscountInbox::find()
                    ->andWhere(['account_id' => $account->id])
                    ->andWhere(['inbox_subject_id' => $offer['Id']])
                    ->orderBy('timestamp DESC')
                    ->limit(1)
                    ->one();

                //如果最后一封邮件发送人是账号讨论名，则说明该邮件已回复
                if (!empty($account->account_discussion_name)) {
                    if (strtolower(str_replace(' ','',$account->account_discussion_name)) == strtolower(str_replace(' ','',$lastSender->sender))) {
                        $subject->is_read = 1;
                        $subject->is_reply = 1;
                        $subject->save(false);
                    } else {
                        $subject->is_read = 0;
                        $subject->is_reply = 0;
                        $subject->save(false);
                    }
                }

                //如果邮件回复状态还是未回复，则判断系统的最新回复时间与最新邮件时间
                if (empty($subject->is_reply)) {
                    //最新的回复
                    $lastReply = CdiscountInboxReply::find()
                        ->andWhere(['account_id' => $account->id])
                        ->andWhere(['inbox_subject_id' => $offer['Id']])
                        ->orderBy('reply_time DESC')
                        ->limit(1)
                        ->one();

                    if (!empty($lastReply) && !empty($lastSender)) {
                        //减去5分钟，表示cd邮件发送的频率是每5分钟一次
                        if (strtotime($lastReply->reply_time) > (strtotime($lastSender->timestamp) + (6 * 3600) - (5 * 60))) {
                            $subject->is_read = 1;
                            $subject->is_reply = 1;
                            $subject->save(false);
                        }
                    }
                }
            }
        }
    }

    /**
     * 处理时间，把时间中带有T和.XX后缀去掉(用法国时间显示)
     */
    public function handleTime($time)
    {
        //替换T为空格
        $time = str_replace('T', ' ', $time);
        //去除.XX后缀
        $ix = strpos($time, '.');
        if ($ix !== false) {
            $time = substr($time, 0, $ix);
        }
        return $time;
    }
}
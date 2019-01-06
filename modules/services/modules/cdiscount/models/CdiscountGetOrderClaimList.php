<?php

namespace app\modules\services\modules\cdiscount\models;

use app\modules\mails\models\CdiscountInbox;
use app\modules\mails\models\CdiscountInboxReply;
use app\modules\mails\models\CdiscountInboxSubject;
use app\modules\services\modules\cdiscount\components\cdiscountApi;

class CdiscountGetOrderClaimList
{

    //纠纷发送人名称
    const CLAIM_SENDER_NAME = 'Service client Cdiscount';
    //纠纷升级内容
    const CLAIM_UPGRADE_CONTENT = 'Le client vient de passer cette discussion en réclamation. Vous avez désormais 2 jours pour lui apporter une solution. This customer just turned this discussion into a claim. You have two days to get back to him.';

    /**
     * 拉取订单索赔列表
     */
    public function getOrderClaimList($account, $startTime, $endTime)
    {
        if (empty($account)) {
            return false;
        }
        $cdApi = new cdiscountApi($account->refresh_token);
        $result = $cdApi->GetOrderClaimList(strtotime($startTime), strtotime($endTime), 'All');

        if (empty($result['GetOrderClaimListResponse']) || empty($result['GetOrderClaimListResponse']['GetOrderClaimListResult']['OrderClaimList'])) {
            return false;
        }

        $result = $result['GetOrderClaimListResponse']['GetOrderClaimListResult']['OrderClaimList'];
        $orderClaimList = $result['OrderClaim'];
        //注意这里，有可能是一维的，全部转换成二给数组
        if (!empty($orderClaimList['Id'])) {
            $orderClaimList = [$orderClaimList];
        }

        foreach ($orderClaimList as $claim) {
            //合并主题，通过账号，平台订单号，主题名称来判断一个主题是否已经存在
            $subject = CdiscountInboxSubject::findOne([
                'account_id' => $account->id,
                'platform_order_id' => $claim['OrderNumber'],
                'subject' => $claim['Subject'],
            ]);
            if (empty($subject)) {
                $subject = new CdiscountInboxSubject();
                $subject->inbox_id = $claim['Id'];
                $subject->create_by = 'system';
                $subject->create_time = date('Y-m-d H:i:s');
            } else {
                $inbox_id = explode(',', $subject->inbox_id);
                $inbox_id[] = $claim['Id'];
                $inbox_id = array_unique($inbox_id);
                $subject->inbox_id = implode(',', $inbox_id);
                $subject->is_read=0;
                $subject->is_reply=0;
            }

            $subject->account_id = $account->id;
            //这里默认站内信类型为订单咨询
            $subject->inbox_type = 'orderquestion';
            $subject->claim_type = !empty($claim['ClaimType']) ? $claim['ClaimType'] : '';
            $subject->platform_order_id = !empty($claim['OrderNumber']) ? $claim['OrderNumber'] : '';
            $subject->product_ean = '';
            $subject->product_seller_reference = '';
            $subject->subject = !empty($claim['Subject']) ? $claim['Subject'] : '';
            $subject->close_date = !empty($claim['CloseDate']) ? $this->handleTime($claim['CloseDate']) : '';
            $subject->creation_date = !empty($claim['CreationDate']) ? $this->handleTime($claim['CreationDate']) : '';
            $subject->last_updated_date = !empty($claim['LastUpdatedDate']) ? $this->handleTime($claim['LastUpdatedDate']) : '';
            $subject->status = !empty($claim['Status']) ? $claim['Status'] : '';
            $subject->modify_by = 'system';
            $subject->modify_time = date('Y-m-d H:i:s');

            if ($subject->save()) {
                if (!empty($claim['Messages']) && !empty($claim['Messages']['Message'])) {
                    $messageList = $claim['Messages']['Message'];

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
                            $inbox = CdiscountInbox::findOne(['inbox_subject_id' => $claim['id'], 'sender' => $message['Sender'], 'content' => $message['Content']]);
                            if (empty($inbox)) {
                                $inbox = new CdiscountInbox();
                                $inbox->create_by = 'system';
                                $inbox->create_time = date('Y-m-d H:i:s');
                            }

                            $inbox->account_id = $account->id;
                            $inbox->inbox_subject_id = $claim['Id'];
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
                        ->andWhere(['inbox_subject_id' => $claim['Id']])
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

                //如果站内信发送人中有CLAIM_SENDER_NAME，则说明该邮件是纠纷邮件
                $inboxTypes = CdiscountInbox::find()
                    ->andWhere(['account_id' => $account->id])
                    ->andWhere(['inbox_subject_id' => $claim['Id']])
                    ->andWhere(['like', 'sender', self::CLAIM_SENDER_NAME])
                    ->asArray()
                    ->all();
                if (!empty($inboxTypes)) {
                    //设置站内信类型为纠纷问题
                    // $subject->inbox_type = 'claim';
                    // $subject->save(false);

                    //如果邮件内容，有升级信息，则设置站内信类型为纠纷升级问题
                    $claimUpgradeContent = preg_replace('/\s+/', ' ', self::CLAIM_UPGRADE_CONTENT);
                    foreach ($inboxTypes as $inboxType) {
                        $content = preg_replace('/\s+/', ' ', $inboxType['content']);
                        if (strtolower($content) == strtolower($claimUpgradeContent)) {
                            $subject->inbox_type = 'claim';
                            $subject->save(false);
                            break;
                        }
                    }
                }

                //最新的第一封邮件
                $lastSender = CdiscountInbox::find()
                    ->andWhere(['account_id' => $account->id])
                    ->andWhere(['inbox_subject_id' => $claim['Id']])
                    ->orderBy('timestamp DESC')
                    ->offset(0)
                    ->limit(1)
                    ->one();

                //如果最新的邮件发送人名称为空
                if (empty($lastSender->sender)) {
                    //最新的第二封邮件
                    $lastSecondSender = CdiscountInbox::find()
                        ->andWhere(['account_id' => $account->id])
                        ->andWhere(['inbox_subject_id' => $claim['Id']])
                        ->orderBy('timestamp DESC')
                        ->offset(1)
                        ->limit(1)
                        ->one();

                    //如果最新的第二封邮件发送人是账号讨论名，则该邮件已回复
                    if (!empty($account->account_discussion_name)) {
                        if (strtolower(str_replace(' ','',$account->account_discussion_name)) == strtolower(str_replace(' ','',$lastSecondSender->sender))) {
                            $subject->is_read = 1;
                            $subject->is_reply = 1;
                            $subject->save(false);
                        } else {
                            $subject->is_read = 0;
                            $subject->is_reply = 0;
                            $subject->save(false);
                        }
                    }
                } else {
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
                }

                //如果邮件回复状态还是未回复，则判断系统的最新回复时间与最新邮件时间
                if (empty($subject->is_reply)) {
                    //最新的回复
                    $lastReply = CdiscountInboxReply::find()
                        ->andWhere(['account_id' => $account->id])
                        ->andWhere(['inbox_subject_id' => $claim['Id']])
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

<?php
/**
 * @desc 速卖通消息临时表
 * @author Fun
 */

namespace app\modules\mails\models;

use app\components\MongodbModel;
use app\modules\mails\models\AliexpressReply;
use app\modules\reports\models\MailStatistics;
use app\modules\accounts\models\Platform;

class AliexpressInboxTmp extends MongodbModel
{
    public $exceptionMessage = null;

    /**
     * @desc 设置集合
     * @return string
     */
    public static function collectionName()
    {
        return DB_TABLE_PREFIX . 'aliexpress_inbox_tmp';
    }

    /**
     * @desc 属性字段
     */
    public function attributes()
    {
        return [
            '_id', 'account_id', 'channel_id', 'type', 'relation', 'relation_detail', 'create_time'
        ];
    }

    /**
     * @desc　获取待处理的列表
     * @param number $limit
     */
    public static function getWaitingProcessList($limit = 100)
    {
        return self::find()->limit($limit)->all();
    }

    /**
     * @desc 将临时保存的消息转移到消息表
     * @param unknown $tmpInbox
     * @throws \Exception
     * @return boolean
     */
    public function processTmpInbox($tmpInbox)
    {
        $startTime1 = microtime(true);
        $dbTransaction = AliexpressInbox::getDb()->beginTransaction();
        try {
            $startTime2 = microtime(true);

            $channelId = $tmpInbox->channel_id;
            $accountId = $tmpInbox->account_id;
            $type = 'message_center';
            $relation = $tmpInbox->relation;
            $relationDetails = $tmpInbox->relation_detail;

            $startTime6 = microtime(true);

            $relationInfo = AliexpressInbox::findOne(['channel_id' => $channelId, 'account_id' => $accountId]);
            //是否是新的关系
            $newRelation = false;
            if (empty($relationInfo)) {
                $newRelation = true;
                $relationInfo = new AliexpressInbox();
            }

            $endTime6 = microtime(true);
            echo 'Point 7:';
            var_dump($endTime6 - $startTime6);

            $relationInfo->msg_sources = $type;
            $relationInfo->unread_count = $relation['unread_count'];
            $relationInfo->account_id = $accountId;
            $relationInfo->channel_id = $relation['channel_id'];
            $relationInfo->last_message_id = $relation['last_message_id'];
            $relationInfo->last_message_content = $relation['last_message_content'];
            $relationInfo->last_message_is_own = isset($relation['last_message_is_own']) ? $relation['last_message_is_own'] : 0;
            $relationInfo->child_name = isset($relation['child_name']) ? $relation['child_name'] : '';
            $relationInfo->receive_date = date('Y-m-d H:i:s', substr((string)$relation['message_time'], 0, -3));
            $relationInfo->child_id = $relation['child_id'];
            $relationInfo->other_name = $relation['other_name'];
            $relationInfo->other_login_id = $relation['other_login_id'];

            $startTime7 = microtime(true);

            //如果最新一条消息在回复表存在，则不更新是否已读状态和处理状态
            if (empty(AliexpressReply::findOne(['message_id' => (string)$relationInfo->last_message_id]))) {
                $relationInfo->deal_stat = $relation['deal_stat'];
                $relationInfo->read_stat = $relation['read_stat'];
            }

            $endTime7 = microtime(true);
            echo 'Point 8:';
            var_dump($endTime7 - $startTime7);

            $relationInfo->rank = $relation['rank'];
            $flag = $relationInfo->save();
            if (!$flag) {
                throw new \Exception('Save Inbox Failed');
            }

            $endTime2 = microtime(true);
            echo 'Point 1:';
            var_dump($endTime2 - $startTime2);

            $startTime3 = microtime(true);

            $inboxId = $relationInfo->id;
            //获取inbox所有没有message id的回复记录
            $replyList = AliexpressReply::find()
                ->where(['channel_id' => $channelId])
                ->orderBy(['gmt_create' => SORT_DESC])
                ->all();

            //已经保存过的message id
            $replyMessageIds = [];
            //没有message id的并且已经同步到平台的回复，即通过客户系统回复的
            $noMessageIdReply = [];
            //有message id的并且已经同步到平台的回复，即通过客户系统回复的
            $messageIdReply = [];
            if (!empty($replyList)) {
                foreach ($replyList as $reply) {
                    if ($reply->message_id != '0') {
                        $messageIdReply[$reply->message_id] = $reply;
                    } else if ($reply->is_send == 1) {
                        $noMessageIdReply[] = $reply;
                    }
                }
            }
            unset($replyList);

            $endTime3 = microtime(true);
            echo 'Point 2:';
            var_dump($endTime3 - $startTime3);

            $startTime4 = microtime(true);

            foreach ($relationDetails as $relationDetail) {
                $messageId = strval($relationDetail['id']);
                $userLoginId = isset($relationDetail['summary']['sender_login_id']) ? $relationDetail['summary']['sender_login_id'] : '';
                //已经保存过的消息不再保存
                //if (in_array($messageId, $replyMessageIds)) continue;
                //$replyInfo = AliexpressReply::findOne(['message_id'=>$messageId]);
                //临时更新已保存过的回复消息的平台回复时间
                if (array_key_exists($messageId, $messageIdReply)) {
                    $reply = $messageIdReply[$messageId];
                    if (isset($relationDetail['gmt_create']) && !empty($relationDetail['gmt_create'])) {
                        $reply->gmt_create = date('Y-m-d H:i:s', substr($relationDetail['gmt_create'], 0, -3));
                        $reply->save(false, ['gmt_create']);
                    }
                    continue;
                }

                //如果是我们自己回复,
                //则查询回复的信息有没有同步
                //有则编辑

                //判断是客户回复还是我们回复 1我们 2客户
                $replyFrom = 1;
                if ($relationInfo->other_login_id == $userLoginId) {
                    $replyFrom = 2;
                }

                //如果是卖家回复，检查该记录是不是通过客服系统回复的，如果是则更新对应记录，如果不是，新插入一条数据
                $continue = true;
                if ($replyFrom == 1 && !empty($noMessageIdReply)) {
                    foreach ($noMessageIdReply as $key => $reply) {
                        if (strcmp(trim($reply->reply_content), trim($relationDetail['content'])) === 0) {
                            $reply->message_id = $messageId;
                            if (isset($relationDetail['gmt_create']) && !empty($relationDetail['gmt_create'])) {
                                $reply->gmt_create = date('Y-m-d H:i:s', substr($relationDetail['gmt_create'], 0, -3));
                            }
                            $reply->message_type = $relationDetail['message_type'];
                            $reply->type_id = isset($relationDetail['extern_id']) ? $relationDetail['extern_id'] : '';
                            $flag = $reply->save(false, ['message_id', 'gmt_create', 'message_type', 'type_id']);
                            if (!$flag) {
                                throw new \Exception('Save Inbox Reply Failed');
                            }
                            $continue = false;
                            unset($noMessageIdReply[$key]);
                            break;
                        }
                    }
                }
                if (!$continue) {
                    continue;
                }

                //新增回复信息
                $relationListModel = new AliexpressReply();
                $relationListModel->inbox_id = $inboxId;
                $relationListModel->message_id = $relationDetail['id'];
                $relationListModel->channel_id = $channelId;
                $relationListModel->gmt_create = date('Y-m-d H:i:s', substr($relationDetail['gmt_create'], 0, -3));
                $relationListModel->message_type = $relationDetail['message_type'];
                $relationListModel->reply_content = $relationDetail['content'];
                $relationListModel->reply_by = $relationDetail['sender_name'];
                $relationListModel->sender_ali_id = $relationDetail['sender_ali_id'];
                $relationListModel->account_id = $accountId;
                $relationListModel->is_send = 1;
                $relationListModel->type_id = isset($relationDetail['extern_id']) ? $relationDetail['extern_id'] : '';
                $relationListModel->reply_from = $replyFrom;
                $flag = $relationListModel->save();
                if (!$flag) {
                    throw new \Exception('Save Inbox Reply Failed');
                }

                //新增图片
                if (isset($relationDetail['file_path_list']) && !empty($relationDetail['file_path_list'])) {
                    $filepathModel = new AliexpressFilepath();
                    $filepathModel->s_path = $relationDetail['file_path_list']['file_path'][0]['s_path'];
                    $filepathModel->m_path = $relationDetail['file_path_list']['file_path'][0]['m_path'];
                    $filepathModel->l_path = $relationDetail['file_path_list']['file_path'][0]['l_path'];
                    $filepathModel->message_id = $relationDetail['id'];
                    $filepathModel->reply_id = $relationListModel->attributes['id'];
                    $flag = $filepathModel->save();
                    if (!$flag) {
                        throw new \Exception('Save FilePath Failed');
                    }
                }

                //新增附属信息
                AliexpressSummary::deleteAll(['message_id' => "{$messageId}"]);
                if (isset($relationDetail['summary']) && !empty($relationDetail['summary'])) {
                    $summaryModel = new AliexpressSummary();
                    $summaryModel->message_id = $messageId;
                    $summaryModel->reply_id = $relationListModel->attributes['id'];
                    $summaryModel->product_name = isset($relationDetail['summary']['product_name']) ?
                        $relationDetail['summary']['product_name'] : '';
                    $summaryModel->product_image_url = isset($relationDetail['summary']['product_image_url']) ?
                        $relationDetail['summary']['product_image_url'] : '';
                    $summaryModel->product_detail_url = isset($relationDetail['summary']['product_detail_url']) ?
                        $relationDetail['summary']['product_detail_url'] : '';
                    $summaryModel->order_url = isset($relationDetail['summary']['order_url']) ?
                        $relationDetail['summary']['order_url'] : '';
                    $summaryModel->sender_name = isset($relationDetail['summary']['sender_name']) ?
                        $relationDetail['summary']['sender_name'] : '';
                    $summaryModel->receiver_name = isset($relationDetail['summary']['receiver_name']) ?
                        $relationDetail['summary']['receiver_name'] : '';
                    $summaryModel->sender_login_Id = isset($relationDetail['summary']['sender_login_id']) ?
                        $relationDetail['summary']['sender_login_id'] : '';
                    $flag = $summaryModel->save();
                    if (!$flag) {
                        throw new \Exception('Save Summary Failed');
                    }
                }
            }

            $endTime4 = microtime(true);
            echo 'Point 4:';
            var_dump($endTime4 - $startTime4);

            $startTime5 = microtime(true);

            //将邮件插入到邮件统计表
            $isExistMailStatistics = MailStatistics::findOne(['message_id' => (string)$relation['last_message_id'], 'platform_code' => Platform::PLATFORM_CODE_ALI]);
            if (empty($isExistMailStatistics)) {
                $mailStatistics = new MailStatistics();
                $mailStatistics->platform_code = Platform::PLATFORM_CODE_ALI;
                $mailStatistics->message_id = $relation['last_message_id'];
                $mailStatistics->account_id = $accountId;
                $mailStatistics->status = 0;
                $mailStatistics->create_time = date('Y-m-d H:i:s');
                $mailStatistics->save(false);
            }

            $endTime5 = microtime(true);
            echo 'Point 5:';
            var_dump($endTime5 - $startTime5);

            //如果是新的消息
            if ($newRelation) {
                //匹配inbox的标签
                $flag = $relationInfo->matchTags($relationInfo);
                if (!$flag) {
                    throw new \Exception('Match Tag Failed');
                }
                //检查是否需要自动回复
                $flag = $relationInfo->matchTemplates($relationInfo);
                if (!$flag) {
                    throw new \Exception('Match Auto Reply Failed');
                }
            }

            //处理完成删除临时记录
            $dbTransaction->commit();
            $tmpInbox->delete();

            $endTime1 = microtime(true);
            echo 'Point 6:';
            var_dump($endTime1 - $startTime1);

            return true;
        } catch (\Exception $e) {
            echo $e->getFile() . "\n";
            echo $e->getLine() . "\n";
            $dbTransaction->rollBack();
            $this->exceptionMessage = $e->getMessage();
            return false;
        }
    }

    public function getExceptionMessage()
    {
        return $this->exceptionMessage;
    }
}
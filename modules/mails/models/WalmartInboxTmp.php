<?php

namespace app\modules\mails\models;

use app\components\MongodbModel;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\WalmartInbox;

/**
 * 沃尔玛邮件临时表
 */
class WalmartInboxTmp extends MongodbModel
{
    public $exceptionMessage = null;

    public static function collectionName()
    {
        return DB_TABLE_PREFIX . 'walmart_inbox_tmp';
    }

    public function attributes()
    {
        return [
            '_id', 'account_id', 'mid', 'mail', 'folder','attachments', 'is_read', 'is_replied', 'is_garbage', 'create_time'
        ];
    }

    /**
     * 获取待处理的列表
     */
    public static function getWaitingProcessList($limit = 100)
    {
        return self::find()->limit($limit)->all();
    }

    /**
     * 将mongodb中保存的邮件信息转存到mysql
     */
    public function processTmpInbox($tmpInbox)
    {
        $dbTransaction = WalmartInbox::getDb()->beginTransaction();
        try {
            $accountId = $tmpInbox->account_id;
            $mail = json_decode($tmpInbox->mail, true, 512, JSON_BIGINT_AS_STRING);
            $attachments = json_decode($tmpInbox->attachments, true, 512, JSON_BIGINT_AS_STRING);
            $isRead = !empty($tmpInbox->is_read) ? $tmpInbox->is_read : 0;
            $isReplied = !empty($tmpInbox->is_replied) ? $tmpInbox->is_replied : 0;
            $isGarbage = !empty($tmpInbox->is_garbage) ? $tmpInbox->is_garbage : 0;
            $folder = isset($tmpInbox->folder) ? $tmpInbox->folder : '';

            if (empty($accountId)) {
                $tmpInbox->delete();
                $this->exceptionMessage = 'Account Id Empty';
                return false;
            }

            //过滤walmart平台邮件
            if ($this->isFilterEmail($mail['fromAddress'])) {
                $tmpInbox->delete();
                $this->exceptionMessage = 'Filter Email';
                return false;
            }

            //是否是新的邮件
            $newRecord = false;
            $walmartInbox = WalmartInbox::findOne(['message_id' => $mail['messageId'], 'account_id' => $accountId]);
            if (empty($walmartInbox)) {
                $newRecord = true;
                $walmartInbox = new WalmartInbox();
                $walmartInbox->create_by = 'system';
                $walmartInbox->create_time = date('Y-m-d H:i:s', time());
            }

            //保存邮件
            $walmartInbox->mid = $mail['id'];
            $walmartInbox->message_id = $mail['messageId'];
            $walmartInbox->order_id = $this->getOrderId($mail['subject']);
            $walmartInbox->account_id = $accountId;
            $walmartInbox->subject = $mail['subject'];
            //邮件存储内容全部默认为html，因为textPlain中有可能内容会误删
            $walmartInbox->body = !empty($mail['textHtml']) ? $mail['textHtml'] : $mail['textPlain'];
            $walmartInbox->content_type = !empty($mail['textHtml']) ? 2 : 1;
            $walmartInbox->mail_type = strpos($mail['fromAddress'], 'walmart.com') === false ? 2 : 1;
            $walmartInbox->sender = $mail['fromName'];
            $walmartInbox->sender_email = $mail['fromAddress'];

            //判断是否数组还是字符串
            if (is_string($mail['to'])) {
                $toEmail = $mail['to'];
            } else if (is_array($mail['to'])) {
                //再次判断是一维数组，还是二维数组
                if (!empty($mail['to'][0]) && is_array($mail['to'][0])) {
                    $toEmail = array_shift($mail['to']);
                } else {
                    $toEmail = $mail['to'];
                }
                //获取键名
                $toEmail = key($toEmail);
            }

            $walmartInbox->receiver = !empty($toEmail) ? $toEmail : $mail['toString'];
            $walmartInbox->receive_email = !empty($toEmail) ? $toEmail : $mail['toString'];
            $walmartInbox->receive_date = $mail['date'];
            $walmartInbox->message_time = $mail['date'];
            $walmartInbox->modify_by = 'system';
            $walmartInbox->modify_time = date('Y-m-d H:i:s', time());

            if ($newRecord) {
                $walmartInbox->is_read = $isRead == 1 ? 2 : 0;
                $walmartInbox->is_replied = $isReplied == 1 ? 2 : 0;
            }

            $flag = $walmartInbox->save(false);
            if (!$flag) {
                throw new \Exception('Save Inbox Failed');
            }

            //如果是新的消息
            if ($newRecord) {
                //保存主题,获取主题模型
                $subject_model = WalmartInboxSubject::getSubjectInfo($walmartInbox, count($attachments));
                if ($subject_model === false) {
                    throw new \Exception('Save Subject Failed');
                }

                $walmartInbox->inbox_subject_id = $subject_model->id;
                if (!$walmartInbox->save()) {
                    throw new \Exception('Update inbox_subject_id Failed');
                }

                //如果该邮件是系统退信的，则需要找到是哪封邮件被退信，并设置状态为失败
                $this->returnToMailOutbox($walmartInbox);

                //判断是否有客服主动联系发送的邮件，以此来关联新拉进来的邮件
                $active = ActiveSendEmail::findOne([
                    'platform_code' => Platform::PLATFORM_CODE_WALMART,
                    'account_id' => $walmartInbox->account_id,
                    'receive_email' => $walmartInbox->sender_email,
                    'platform_order_id' => $walmartInbox->order_id,
                ]);
                if (!empty($active) && empty($active->inbox_id)) {
                    $reply = new WalmartReply();
                    $reply->inbox_id = $walmartInbox->id;
                    $reply->reply_content = $active->content;
                    $reply->reply_title = $active->title;
                    $reply->reply_by = $active->create_by;
                    $reply->is_draft = 0;
                    $reply->is_delete = 0;
                    $reply->is_send = 1;
                    $reply->create_by = $active->create_by;
                    $reply->create_time = $active->create_time;
                    $reply->modify_by = $active->modify_by;
                    $reply->modify_time = $active->modify_time;
                    if ($reply->save()) {
                        $active->inbox_id = $walmartInbox->id;
                        $active->save();
                    }
                    //给主题打上标签
                    if (!empty($active->tag)) {
                        $tagIds = explode(',', $active->tag);
                        if (!empty($tagIds)) {
                            foreach ($tagIds as $tagId) {
                                $subjectTag = MailSubjectTag::findOne([
                                    'platform_code' => Platform::PLATFORM_CODE_WALMART,
                                    'tag_id' => $tagId,
                                    'subject_id' => $subject_model->id
                                ]);
                                if (empty($subjectTag)) {
                                    $subjectTag = new MailSubjectTag();
                                    $subjectTag->platform_code = Platform::PLATFORM_CODE_WALMART;
                                    $subjectTag->tag_id = $tagId;
                                    $subjectTag->subject_id = $subject_model->id;
                                    $subjectTag->save();
                                }
                            }
                        }
                    }
                }

                //对主题自动筛选归类
                //所有邮件全部走自动筛选规则，不管是否是垃圾邮件
                WalmartInboxSubject::mailFilterClassify($subject_model, $isGarbage);

                //匹配inbox的标签(主题)
                $flag = $subject_model->matchTags($walmartInbox);
                if (!$flag) {
                    throw new \Exception('Match Tag Failed');
                }
                //匹配inbox的标签(邮件)
                $flag = $walmartInbox->matchTags();
                if (!$flag) {
                    throw new \Exception('Match Tag Failed');
                }

                //保存邮件附件
                foreach ($attachments as $key => $value) {
                    $attachment = new WalmartInboxAttachment();
                    $attachment->walmart_inbox_id = $walmartInbox->id;
                    $attachment->attachment_id = $value['id'];
                    $attachment->name = $value['name'];
                    $attachment->file_path = $value['filePath'];
                    $attachment->save();
                }
            }
            //处理完成删除临时记录
            $dbTransaction->commit();
            //将邮件ID插入到已拉取的列表
            $email = strtolower($toEmail);
            if (empty(AmazonMailList::findOne(['email' => $email, 'folder' => $folder, 'mid' => $mail['id']])))
            {
                $amazonMailList = new AmazonMailList;
                $amazonMailList->email = $email;
                $amazonMailList->folder = $folder;
                $amazonMailList->mid = $mail['id'];
                $amazonMailList->create_time = date('Y-m-d H:i:s');
                $amazonMailList->save();
            }
            $tmpInbox->delete();
            return true;
        } catch (\Exception $e) {
            $dbTransaction->rollBack();
            $this->exceptionMessage = $e->getMessage();
            return false;
        }
    }

    /**
     * @desc 根据邮箱地址确认该邮箱是否进入mysql
     * @param string $email
     * @return boolean  true表示过滤该邮件，false表示不能过滤
     */
    protected function isFilterEmail($email)
    {
        //$email_suffix = substr($email, strripos($email, '@'));
        //if (strpos($email_suffix, 'walmart') === false) {
        //    return true;
        //}
        return false;
    }

    public function getExceptionMessage()
    {
        return $this->exceptionMessage;
    }

    /**
     * 如果该邮件是系统退信的，则需要找到是哪封邮件被退信，并设置状态为失败
     */
    public function returnToMailOutbox($walmartInbox)
    {
        if (empty($walmartInbox)) {
            return false;
        }
        if (stripos($walmartInbox->sender_email, 'postmaster') === false) {
            return false;
        }
        $body = $walmartInbox->body;

        //从邮件内容中提取所需内容
        $matchs = [];
        preg_match_all('|<tr>[\s\S]*?<th[\s\S]*?>(.*)</th>[\s\S]*?<td[\s\S]*?style=[\'\"]line-height:1[\'\"]>(.*)</td>[\s\S]*?</tr>|u', $body, $matchs);

        if (empty($matchs) || empty($matchs[1]) || empty($matchs[2])) {
            return false;
        }

        //时间
        $time = '';
        //主题
        $subject = '';
        //收件人
        $receiveEmail = '';
        foreach ($matchs[1] as $key => $match) {
            //注意这里，去掉utf-8的空格
            $match = preg_replace('/[\s\v'.chr(227).chr(128).']*/', '', $match);
            if (stripos($match, '时间') !== false) {
                $time = !empty($matchs[2][$key]) ? trim($matchs[2][$key]) : '';
            } else if (stripos($match, '主题') !== false) {
                $subject = !empty($matchs[2][$key]) ? trim($matchs[2][$key]) : '';
            } else if (stripos($match, '收件人') !== false) {
                $receiveEmail = !empty($matchs[2][$key]) ? trim($matchs[2][$key]) : '';
            }
        }

        if (empty($subject) || empty($receiveEmail)) {
            return false;
        }

        //通过主题，账号ID，收件人邮箱，看能否找到唯一的退信邮件
        $mails = MailOutbox::find()
            ->andWhere(['platform_code' => Platform::PLATFORM_CODE_WALMART])
            ->andWhere(['account_id' => $walmartInbox->account_id])
            ->andWhere(['receive_email' => $receiveEmail])
            ->andWhere(['subject' => $subject])
            ->orderBy('create_time DESC')
            ->all();

        if (empty($mails)) {
            return false;
        }

        //如果匹配到的邮件数量超过1个，则说明无法确定唯一的退信邮件
        //则默认选取第一个作为退信邮件
        if (count($mails) > 1) {
            $mails = [$mails[0]];
        }

        foreach ($mails as $mail) {
            //设置该邮件为发送失败
            $mail->send_status = MailOutbox::SEND_STATUS_FAILED;
            $mail->send_failure_reason = "系统退信, 退信时间: {$time}";
            $mail->modify_by = 'system';
            $mail->modify_time = date('Y-m-d H:i:s');
            $mail->save();
        }
    }

    /**
     * 获取订单ID
     */
    public function getOrderId($subject)
    {
        $id = '';
        preg_replace_callback('/\d{10,}/', function ($match) use (&$id) {
            $id = $match[0];
        }, $subject, 1);

        return $id;
    }
}
<?php
/**
 * @desc 亚马逊消息临时表
 * @author Fun
 */

namespace app\modules\mails\models;

use app\components\MongodbModel;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\AmazonInbox;

class AmazonInboxTmp extends MongodbModel
{
    public $exceptionMessage = null;

    /**
     * @desc 设置集合
     * @return string
     */
    public static function collectionName()
    {
        return DB_TABLE_PREFIX . 'amazon_inbox_tmp';
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\mongodb\ActiveRecord::attributes()
     */
    public function attributes()
    {
        return [
            '_id', 'account_id', 'mid', 'folder', 'mail', 'attachments', 'is_read', 'is_replied', 'is_garbage', 'create_time'
        ];
    }

    /**
     * @desc　获取待处理的列表
     * @param number $limit
     * @param integer $modNumber 按account_id取模的数
     * @param integer $modRemain 取模的余数
     */
    public static function getWaitingProcessList($limit = 100, $modNumber = null, $modRemain = null)
    {
        $query = self::find();
        if ($modNumber > 0 && $modRemain >= 0)
            $query->where(['account_id' => ['$mod' => [(int)$modNumber, (int)$modRemain]]]);
        return $query->limit($limit)->all();
    }

    /**
     * @desc 将临时保存的消息转移到消息表
     * @param unknown $tmpInbox
     * @throws \Exception
     * @return boolean
     */
    public function processTmpInbox($tmpInbox)
    {
        $dbTransaction = AmazonInbox::getDb()->beginTransaction();
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
            //过滤amazon平台邮件
            if ($this->isFilterEmail($mail['fromAddress'])) {
                $tmpInbox->delete();
                $this->exceptionMessage = 'Filter Email';
                return false;
            }
            //是否是新的邮件
            $newRecord = false;
            $amazonInbox = AmazonInbox::findOne(['message_id' => $mail['messageId'], 'account_id' => $accountId]);
            if (empty($amazonInbox)) {
                $newRecord = true;
                $amazonInbox = new AmazonInbox();
                $amazonInbox->create_by = 'system';
                $amazonInbox->create_time = date('Y-m-d H:i:s', time());
            }
            //保存邮件
            $amazonInbox->mid = $mail['id'];
            $amazonInbox->message_id = $mail['messageId'];
            $amazonInbox->order_id = $this->getOrderId($mail['subject']);
            $amazonInbox->account_id = $accountId;
            $amazonInbox->subject = $mail['subject'];
            //邮件存储内容全部默认为html，因为textPlain中有可能内容会误删
            $amazonInbox->body = !empty($mail['textHtml']) ? $mail['textHtml'] : $mail['textPlain'];
            $amazonInbox->content_type = !empty($mail['textHtml']) ? 2 : 1;
            $amazonInbox->mail_type = strpos($mail['fromAddress'], 'amazon.com') === false ? 2 : 1;
            $amazonInbox->sender = $mail['fromName'];
            $amazonInbox->sender_email = $mail['fromAddress'];

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

            $amazonInbox->receiver = !empty($toEmail) ? $toEmail : $mail['toString'];
            $amazonInbox->receive_email = !empty($toEmail) ? $toEmail : $mail['toString'];
            $amazonInbox->receive_date = $mail['date'];
            $amazonInbox->message_time = $mail['date'];
            $amazonInbox->modify_by = 'system';
            $amazonInbox->modify_time = date('Y-m-d H:i:s', time());

            if ($newRecord) {
                $amazonInbox->is_read = $isRead == 1 ? 2 : 0;
                $amazonInbox->is_replied = $isReplied == 1 ? 2 : 0;
            }

            $flag = $amazonInbox->save(false);
            if (!$flag) {
                throw new \Exception('Save Inbox Failed');
            }

            //如果是新的消息
            if ($newRecord) {
                //保存主题,获取主题模型
                $subject_model = AmazonInboxSubject::getSubjectInfo($amazonInbox, count($attachments));
                if ($subject_model === false) {
                    throw new \Exception('Save Subject Failed');
                }

                $amazonInbox->inbox_subject_id = $subject_model->id;
                if (!$amazonInbox->save()) {
                    throw new \Exception('Update inbox_subject_id Failed');
                }

                //如果该邮件是系统退信的，则需要找到是哪封邮件被退信，并设置状态为失败
                $this->returnToMailOutbox($amazonInbox);

                //判断是否有客服主动联系发送的邮件，以此来关联新拉进来的邮件
                $active = ActiveSendEmail::findOne([
                    'platform_code' => Platform::PLATFORM_CODE_AMAZON,
                    'account_id' => $amazonInbox->account_id,
                    'receive_email' => $amazonInbox->sender_email,
                    'platform_order_id' => $amazonInbox->order_id,
                ]);
                if (!empty($active) && empty($active->inbox_id)) {
                    $reply = new AmazonReply();
                    $reply->inbox_id = $amazonInbox->id;
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
                        $active->inbox_id = $amazonInbox->id;
                        $active->save();
                    }
                    //给主题打上标签
                    if (!empty($active->tag)) {
                        $tagIds = explode(',', $active->tag);
                        if (!empty($tagIds)) {
                            foreach ($tagIds as $tagId) {
                                $subjectTag = MailSubjectTag::findOne([
                                    'platform_code' => Platform::PLATFORM_CODE_AMAZON,
                                    'tag_id' => $tagId,
                                    'subject_id' => $subject_model->id
                                ]);
                                if (empty($subjectTag)) {
                                    $subjectTag = new MailSubjectTag();
                                    $subjectTag->platform_code = Platform::PLATFORM_CODE_AMAZON;
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
                AmazonInboxSubject::mailFilterClassify($subject_model, $isGarbage);

                //匹配inbox的标签(主题)
                $flag = $subject_model->matchTags($amazonInbox);
                if (!$flag) {
                    throw new \Exception('Match Tag Failed');
                }

                //匹配inbox的标签(邮件)
                $flag = $amazonInbox->matchTags();
                if (!$flag) {
                    throw new \Exception('Match Tag Failed');
                }

                //保存邮件附件
                foreach ($attachments as $key => $value) {
                    $attachment = new AmazonInboxAttachment();
                    $attachment->amazon_inbox_id = $amazonInbox->id;
                    $attachment->attachment_id = $value['id'];
                    $attachment->name = $value['name'];
                    if (strpos($value['filePath'], 'D:\wamp\www\CRM_NEW\trunk\web\attachments') !== false)
                    {
                        $value['filePath'] = str_replace('D:\wamp\www\CRM_NEW\trunk\web\attachments\\', 
                            '/mnt/data/www/crm/web/attachments/', $value['filePath']);
                    }
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
        //如果邮箱是onlineselling.compliance@hmrc.gsi.gov.uk 不过滤
        if($email == 'onlineselling.compliance@hmrc.gsi.gov.uk'){
            return FALSE;
        }
        //邮箱包含 HMRC Joint Liability Letter、Pre Joint Liability Letter、JSL、Pre JSL都不过滤
        if((stripos($email, 'HMRC Joint Liability Letter') !== false) || (stripos($email, 'Pre Joint Liability Letter') !== false) || (stripos($email, 'JSL') !== false) || (stripos($email, 'Pre JSL') !== false)){
            return FALSE;
        }
        
        if (stripos($email, 'amazon') === false &&
            stripos($email, 'postmaster') === false) {
            return true;
        }
        if (stripos($email, 'non-rispondere') !== false) {
            return true;
        }
        if (stripos($email, 'do-not-reply') !== false) {
            return true;
        }
        if (stripos($email, 'merch.service') !== false) {
            return true;
        }
        return false;
    }

    public function getExceptionMessage()
    {
        return $this->exceptionMessage;
    }

    /**
     * 如果该邮件是系统退信的，则需要找到是哪封邮件被退信，并设置状态为失败
     */
    public function returnToMailOutbox($amazonInbox)
    {
        if (empty($amazonInbox)) {
            return false;
        }
        if (stripos($amazonInbox->sender_email, 'postmaster') === false) {
            return false;
        }
        $body = $amazonInbox->body;

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
            ->andWhere(['platform_code' => Platform::PLATFORM_CODE_AMAZON])
            ->andWhere(['account_id' => $amazonInbox->account_id])
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
        preg_replace_callback('/\d{3}-\d{7}-\d{7}/', function ($match) use (&$id) {
            $id = $match[0];
        }, $subject, 1);

        return $id;
    }
}
<?php

namespace app\modules\mails\components;

use app\modules\services\modules\amazon\components\Mail;
use yii\helpers\Json;
use app\modules\systems\models\Email;

/**
 * walmart邮件发送
 */
class WalmartMessageSender extends MessageSenderAbstract
{
    public function runSendMessage()
    {
        try {
            //邮件标题
            $subject = $this->messageEntity->subject;
            //邮件内容
            $content = $this->messageEntity->content;
            //发送参数
            $sendParams = $this->messageEntity->send_params;
            $sendParams = Json::decode($sendParams);
            //发件人
            $fromAddress = !empty($sendParams['sender_email']) ? $sendParams['sender_email'] : '';
            //收件人
            $to = !empty($sendParams['receive_email']) ? $sendParams['receive_email'] : '';
            //附件
            $attachments = !empty($sendParams['attachments']) ? $sendParams['attachments'] : [];

            //指定邮箱用亚马逊邮件服务器发送 update by allen str <2018-10-11>
            $email = Email::findOne(['emailaddress' => $fromAddress]);

            //指定邮箱用亚马逊邮件服务器发送 update by allen str <2018-10-11>
            if($email->is_amazon_send == 1){
                $from = 'email-smtp.us-east-1.amazonaws.com';
            } else {
                $from = $fromAddress;
            }
            //指定邮箱用亚马逊邮件服务器发送 update by allen end <2018-10-11>
            
            //获取一个邮件发送实例
            $mail = Mail::instance($from);
            if (empty($mail)) {
                throw new \Exception('邮件实例获取失败');
            }

            $mail->setTo($to)->setSubject($subject)->setTextBody($content)->setFrom($fromAddress);
            //添加附件
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    $mail->addAttach($attachment);
                }
            }
            //发送
            $result = $mail->sendmail();
            $this->sendFlag = ($result === true ? true : false);

            if ($result === false) {
                $this->exception = current(Mail::$errorMsg);
            }

        } catch (\Exception $e) {
            $this->sendFlag = false;
            $this->exception = $e->getMessage();
        }
    }
}
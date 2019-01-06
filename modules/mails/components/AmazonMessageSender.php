<?php
namespace app\modules\mails\components;
use app\modules\services\modules\amazon\components\Mail;
use app\modules\mails\models\MailTemplateStrReplacement;
use app\modules\accounts\models\Platform;
class AmazonMessageSender extends MessageSenderAbstract
{
    //public $name = 'ebay message sender';
    
    //public $type = 1;
    
    public function runSendMessage()
    {
       try 
       {   
           $messageContent = $this->messageEntity->content;
           $sendParams = $this->messageEntity->send_params;
           $sendParams = \yii\helpers\Json::decode($sendParams);
           $fromAddress = isset($sendParams['sender_email']) ? $sendParams['sender_email'] : null;
           $to   = isset($sendParams['receive_email']) ? $sendParams['receive_email'] : null;
           $orderId = isset($sendParams['order_id']) ? $sendParams['order_id'] : '';
           $subject = $this->messageEntity->subject;
           $body = MailTemplateStrReplacement::replaceContent($messageContent,
                Platform::PLATFORM_CODE_AMAZON, $orderId);
           // $path = isset($sendParams['attachments']) && !empty($sendParams['attachments']) ? $sendParams['attachments'][0] : null;
           $path = isset($sendParams['attachments']) && !empty($sendParams['attachments']) ? $sendParams['attachments'] : null;
           //发送邮件
           //如果是亚马逊平台都用亚马逊邮件发服务器发送  update by allen str <2018-10-08>
           if(Platform::PLATFORM_CODE_AMAZON == 'AMAZON'){
               $from = 'email-smtp.us-east-1.amazonaws.com';
           }else{
                //系统自动发送的邮件有amazon ses服务器发送,163和126的邮箱的邮件也用amazon ses服务器发送
                if ($this->messageEntity->create_by == 'system' || $this->messageEntity->create_by == '系统' || stripos($fromAddress, '@163.com') !== false || stripos($fromAddress, '@126.com') !== false){
                    $from = 'email-smtp.us-east-1.amazonaws.com';
                }else{
                    $from = $fromAddress;
                }

                //126  163邮箱所有邮件都从亚马逊邮件服务器发送
                if(stripos($fromAddress, '@163.com') !== false || stripos($fromAddress, '@126.com') !== false){
                    $from = 'email-smtp.us-east-1.amazonaws.com';
                }
           }
           
           if(Mail::instance($from)){
                $mailer = Mail::instance($from)->setTo($to)->setSubject($subject)->setTextBody($body)->setFrom($fromAddress);
                 if (!empty($path)) {
                    foreach ($path as $v) {
                      $mailer->addAttach($v);
                    }
                 }
                $result = $mailer->sendmail();
                $this->sendFlag = $result === true ? true : false;

                //发送邮件失败
                if ($result === false) {
                    $this->sendFlag = false;
     //               var_dump(Mail::$errorMsg);
                    $this->exception = current(Mail::$errorMsg);
                }
           }

       }
       catch (\Exception $e)
       {
           $this->sendFlag = false;
           $this->exception = $e->getMessage();
       }
    }
}
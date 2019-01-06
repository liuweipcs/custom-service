<?php
namespace app\modules\mails\components;
use app\modules\services\modules\aliexpress\models\AliexpressMessage;
class AliexpressMessageSender extends MessageSenderAbstract
{
    public $name = 'ebay message sender';
    
    public $type = 1;
    
    public function runSendMessage()
    {
       $messageContent = $this->messageEntity->content;
       $sendParams = $this->messageEntity->send_params;
       $params = [];
       $aliexpressMessage = new AliexpressMessage();
       try 
       {
           $sendParams = \yii\helpers\Json::decode($sendParams);
           $params['account_id'] = isset($sendParams['account_id']) ? $sendParams['account_id'] : null;
           $params['message_type'] = isset($sendParams['message_type']) ? $sendParams['message_type'] : null;
           $params['buyer_id'] = isset($sendParams['buyer_id']) ? $sendParams['buyer_id'] : null;
           $params['channel_id'] = isset($sendParams['channel_id']) ? $sendParams['channel_id'] : null;
           $params['imgPath'] = isset($sendParams['imgPath']) ? $sendParams['imgPath'] : null;
           $params['content'] = $messageContent;
           //$params['seller_id'] = isset($sendParams['seller_id']) ? $sendParams['seller_id'] : null;
           $params['extern_id'] = isset($sendParams['extern_id']) ? $sendParams['extern_id'] : null;
           $result = $aliexpressMessage->addMessage($params);
           //$result = true;
           if ($result == true)
           {
               $this->sendFlag = true;
           }
           else
           {
               $this->sendFlag = false;
               $this->exception = $aliexpressMessage->getErrorMessage();
           }
       }
       catch (\Exception $e)
       {echo $e->getFile();
       echo $e->getLine();
           $this->sendFlag = false;
           $this->exception = $e->getMessage();
       }
    }
}
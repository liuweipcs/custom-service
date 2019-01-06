<?php
namespace app\modules\mails\components;
use app\modules\services\modules\ebay\models\AddMemberMessageAAQToPartner;
use app\modules\services\modules\ebay\models\AddMemberMessageRTQ;

class EbayMessageSender extends MessageSenderAbstract
{
    public function runSendMessage()
    {
        $sendParams = $this->messageEntity->send_params;
        try{
            $sendParams = \yii\helpers\Json::decode($sendParams);
            $replyModel = new \stdClass();
            $replyModel->parent_message_id = isset($sendParams['ExternalMessageID']) ? $sendParams['ExternalMessageID'] : '';
            $replyModel->item_id = $sendParams['ItemID'];
            $replyModel->reply_content = $this->messageEntity->content;
            $replyModel->reply_content = str_replace('&nbsp;', ' ', $replyModel->reply_content);
            $replyModel->question_type = isset($sendParams['QuestionType']) ? $sendParams['QuestionType'] : null;
            $replyModel->account_id = $sendParams['account_id'];
            $replyModel->recipient_id = $sendParams['RecipientID'];
            $replyModel->reply_title = $this->messageEntity->subject;
            $replyModel->id = $this->messageEntity->reply_id;
            if(($this->messageEntity->subject == null || $this->messageEntity->subject == '') && !empty($this->messageEntity->inbox_id) && isset($sendParams['ExternalMessageID']))
            {
                //回复
                $model = new AddMemberMessageRTQ();
                $model->replyModel = $replyModel;
                if($model->paramInit()->addMessage())
                {
                    $this->sendFlag = $model->handleResponse();
                }
                else
                    $this->sendFlag = false;
                if(!$this->sendFlag)
                    $this->exception = $model->responseError;

            }
            elseif(($this->messageEntity->subject != null && $this->messageEntity->subject != '') && !isset($this->messageEntity->inbox_id) && !isset($sendParams['ExternalMessageID']))
            {
                //主动
                $model = new AddMemberMessageAAQToPartner();
                $model->replyModel = $replyModel;
                if($model->paramInit()->addMessage())
                {
                    $this->sendFlag = $model->handleResponse();
                }
                else
                    $this->sendFlag = false;
                if(!$this->sendFlag)
                    $this->exception = $model->responseError;
            }
        }catch(\Exception $e){
            $this->sendFlag = false;
            $this->exception = $e->getMessage().$e->getLine().$e->getFile();
//            throw new \Exception($this->exception);
            $this->sendFlag = false;

        }
    }
}
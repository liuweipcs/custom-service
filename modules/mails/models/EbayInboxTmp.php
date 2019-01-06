<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/29 0029
 * Time: 下午 4:45
 */

namespace app\modules\mails\models;


use app\common\VHelper;
use app\components\MongodbModel;
use app\modules\services\modules\ebay\models\GetMyMessages;
use app\modules\systems\models\SubjectPreg;
use PhpImap\Exception;
use app\modules\mails\models\EbayInboxContentMongodb;
use phpQuery;
use app\modules\reports\models\MailStatistics;
use app\modules\accounts\models\Platform;

class EbayInboxTmp extends MongodbModel
{
    public $exceptionMessage;

    public static function collectionName()
    {
        return DB_TABLE_PREFIX . 'ebay_inbox_tmp';
    }

    public function attributes()
    {
        return [
            '_id', 'message_id','account_id', 'relation', 'relation_detail', 'create_time'
        ];
    }

    public static function getWaitingProcessList($limit = 100, $modNumber = null, $modRemain = null)
    {
        $query = self::find();
        if ($modNumber > 0 && $modRemain >= 0)
            $query->where(['account_id' => ['$mod' => [(int)$modNumber, (int)$modRemain]]]);
        return $query->limit($limit)->all();
        //return self::find()->limit($limit)->offset($offset)->all();
    }

    public function processTmpInbox($tmpInbox)
    {
        require_once \Yii::getAlias('@vendor').'/phpquery/phpQuery.php';
        $transaction = EbayInbox::getDb()->beginTransaction();
        // ebay来信部分主题需要改为已读
        $startTime = microtime(true);
        $subject_pregs = SubjectPreg::find()->select('preg_str')->where(['status'=>SubjectPreg::TAG_STATUS_VALID])->asArray()->column();
        $endTime = microtime(true);
        echo 'Point 3:';
        var_dump($endTime - $startTime);
        try {
            $startTime = microtime(true);
            $model = EbayInbox::findOne(['message_id' => $tmpInbox->message_id]);
            $endTime = microtime(true);
            echo 'Point 4:';
            var_dump($endTime - $startTime);
            $startTime = microtime(true);
            $isNew = false;
            if (empty($model))
            {
                $model = new EbayInbox();
                $isNew = true;
            }
            $headersMessage = simplexml_load_string($tmpInbox->relation);
            $model->flagged = isset($headersMessage->Flagged) ? GetMyMessages::$flaggedTransform[strtolower($headersMessage->Flagged->__toString())] : 0;
            $model->folder_id = $headersMessage->Folder->FolderID->__toString();
            $model->message_id = $tmpInbox->message_id;
            $model->external_message_id = isset($headersMessage->ExternalMessageID) ? $headersMessage->ExternalMessageID:'';
            $model->high_priority = isset($headersMessage->HighPriority) ? GetMyMessages::$flaggedTransform[strtolower($headersMessage->HighPriority->__toString())] : 0;
            $model->item_id = isset($headersMessage->ItemID) ? $headersMessage->ItemID->__toString() : '';
            // 北京时间
            $model->expiration_date = date('Y-m-d H:i:s',strtotime($headersMessage->ExpirationDate->__toString()));
            $model->ch_expiration_date = explode('.',str_replace('T',' ',$headersMessage->ExpirationDate->__toString()))[0];
            $model->message_type = isset($headersMessage->MessageType) ? array_search($headersMessage->MessageType->__toString(), EbayInbox::$messageTypeMap) : 0;
            $model->question_type = isset($headersMessage->ItemID) ? array_search($headersMessage->ItemID->__toString(), EbayInbox::$questionTypeMap) : 0;
            $model->is_read = GetMyMessages::$poolTransform[strtolower($headersMessage->Read->__toString())];
            // 北京时间
            $model->receive_date = date('Y-m-d H:i:s',strtotime($headersMessage->ReceiveDate->__toString()));
            $model->ch_receive_date = explode('.',str_replace('T',' ',$headersMessage->ReceiveDate->__toString()))[0];

            $model->recipient_user_id = $headersMessage->RecipientUserID->__toString();
            if($isNew)
                $model->is_replied = GetMyMessages::$poolTransform[strtolower($headersMessage->Replied->__toString())];
            $model->response_enabled = isset($headersMessage->ResponseDetails->ResponseEnabled) ? GetMyMessages::$poolTransform[strtolower($headersMessage->ResponseDetails->ResponseEnabled->__toString())] : 1;
            $model->response_url = isset($headersMessage->ResponseDetails->ResponseURL) ? $headersMessage->ResponseDetails->ResponseURL->__toString() : '';
            $model->sender = $headersMessage->Sender->__toString();

            // 特例：sender = csfeedback@ebay.com的不进入mysql
            if($model->sender == 'csfeedback@ebay.com')
            {
                $transaction->rollback();
                $tmpInbox->delete();
                return true;
            }

            $model->send_to_name = isset($headersMessage->SendToName) ? $headersMessage->SendToName->__toString() : '';
            $model->subject = $headersMessage->Subject->__toString();
            $model->platform_id = 0;
            $model->account_id = $tmpInbox->account_id;

            $contentMessage = simplexml_load_string($tmpInbox->relation_detail);
            if (isset($contentMessage->Content))
                $content = $contentMessage->Content->__toString();
            else if (isset($contentMessage->Text))
                $content = $contentMessage->Text->__toString();
            if($model->sender == 'eBay')
            {
                if (isset($contentMessage->Text))
                    $content = $contentMessage->Text->__toString();
                else if (isset($contentMessage->Content))
                    $content = $contentMessage->Content->__toString();
            }

            if(isset($contentMessage->MessageMedia)){

                foreach ($contentMessage->MessageMedia as $key => $value) {

                    $OrgialImg[] = $value->MediaURL->__toString();
                }
                $OrgialImg = implode(',', $OrgialImg);
                
            }else
                $OrgialImg = '';
            if (preg_match('/<td[^>]*class="product-bids"[^>]*>.*<br\/?>[^\d]*(\d{7,}[-]?\d*)[^<]*<br\/?>.*<\/td>/is', $content, $matchOrderId))
                $model->transaction_id = $matchOrderId[1];
            $endTime = microtime(true);
            echo 'Point 5:';
            var_dump($endTime - $startTime);
            $startTime = microtime(true);
            preg_match("/<div id=\"UserInputtedText\">(.|\n)*?<\/div>/", $content,$mat);
            $endTime = microtime(true);
            echo 'Point 6:';
            var_dump($endTime - $startTime);
            $startTime = microtime(true);
           // $model->new_message = empty($mat[0])? "":$mat[0];
            $new_message = empty($mat[0])? "":$mat[0];
            
            //发件人如果是eBay 没截取到发件内容则保存所有内容 update by allen <2017-9-17> str
            if(empty($new_message) && $model->sender == 'eBay'){
                $new_message = $content;
            }
            //发件人如果是eBay 没截取到发件内容则保存所有内容 update by allen <2017-9-17> end
            
            $model->new_message = (string)$new_message;
            phpQuery::newDocumentHtml($content);
            $imgContent = pq("div#ImagePreview")->html();
            $imageUrl = htmlentities($imgContent);
            $model->img_exists = $imageUrl == null ? $model->img_exists = 0 : $img_exists = 1;
            $model->orgial_img = $OrgialImg;
            $endTime = microtime(true);
            echo 'Point 6:';
            var_dump($endTime - $startTime);
            $startTime = microtime(true);
            //获取主题id
            $subject_model = EbayInboxSubject::getSubjectInfo($model,$isNew,$subject_pregs);
            if($subject_model == false){
                $this->exceptionMessage = 'Save Subject false';
                $flag = false;
            }else{
                $model->inbox_subject_id = $subject_model->id;
            }
            $endTime = microtime(true);
            echo 'Point 7:';
            var_dump($endTime - $startTime);
            $flag = $model->save();
            if(!$flag){
                $this->exceptionMessage = VHelper::getModelErrors($model);
            }
            
//            if($flag)
//            {
//                $subject_model = EbayInboxSubject::getSubjectInfo($model,$isNew,$subject_pregs);
//                if($subject_model == false)
//                {
//                    $this->exceptionMessage = 'Save Subject false';
//                    $flag = false;
//                }
//                else
//                {
//                    $model->inbox_subject_id = $subject_model->id;
//                    $flag = $model->save();
//                    if(!$flag)
//                        $this->exceptionMessage = VHelper::getModelErrors($model);
//                }
//            }
            //将邮件插入到邮件统计表
            $startTime = microtime(true);
            $mailStatistics = MailStatistics::findOne(['message_id'=>(string)$tmpInbox->message_id,'platform_code' => Platform::PLATFORM_CODE_EB]);
            if(empty($mailStatistics)){
                $mailStatistics = new MailStatistics();
                $mailStatistics->status = 0;
            }
            $mailStatistics->platform_code = Platform::PLATFORM_CODE_EB;
            $mailStatistics->message_id = $tmpInbox->message_id;
            $mailStatistics->account_id = $tmpInbox->account_id;
            $mailStatistics->create_time = date('Y-m-d H:i:s');
            $mailStatistics->sender = $headersMessage->Sender->__toString();
            $mailStatistics->receive_date = date('Y-m-d H:i:s',strtotime($headersMessage->ReceiveDate->__toString()));
            $mailStatistics->save(false);
            $endTime = microtime(true);
            echo 'Point 8:';
            var_dump($endTime - $startTime);

        } catch(Exception $e){
            $flag = false;
            $this->exceptionMessage = $e->getMessage();
        }
        if($flag)
        {
            $startTime = microtime(true);
            $contentMongodb = EbayInboxContentMongodb::findOne(['ueb_ebay_inbox_id'=>(int)$model->id]);
            var_dump((int)$model->id);
            $endTime = microtime(true);
            echo 'Point 10:';
            var_dump($endTime - $startTime);
            $startTime = microtime(true);
            if(empty($contentMongodb))
                $contentMongodb = new EbayInboxContentMongodb();
            $contentMongodb->ueb_ebay_inbox_id = $model->id;
            $contentMongodb->message_id = $model->message_id;
            $contentMongodb->content = $content;

            // 有图片的消息mongodb保存
            $contentMongodb->image_url=$imageUrl;
            try{
                $flag = $contentMongodb->save();
            }catch(\Exception $e){
                $this->exceptionMessage = $e->getMessage();
            }
            $endTime = microtime(true);
            echo 'Point 12:';
            var_dump($endTime - $startTime);
        }

        if($flag)
        {
            $startTime = microtime(true);
            $transaction->commit();
            $tmpInbox->delete();
            try{
//                if(!$model->matchTags())
//                    $this->exceptionMessage = 'Match Tag Failed.';
                if($isNew)
                {
                    if(!$subject_model->matchTags($model))
                        $this->exceptionMessage = 'Match Subject Tag Failed';
//                    if(!$subject_model->matchTemplates($model))
//                        $this->exceptionMessage = 'Match Auto Reply Failed';
                }

            }catch(\Exception $e){
                if(empty($this->exceptionMessage))
                    $this->exceptionMessage = $e->getMessage();
                else
                    $this->exceptionMessage .= $e->getMessage();
            }
            $endTime = microtime(true);
            echo 'Point 11:';
            var_dump($endTime - $startTime);
        }
        else
            $transaction->rollBack();
        return (bool)$flag;
    }

    public function getExceptionMessage()
    {
        return $this->exceptionMessage;
    }
}
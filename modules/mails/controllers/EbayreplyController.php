<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/20 0020
 * Time: 下午 3:10
 */

namespace app\modules\mails\controllers;

use app\common\VHelper;
use app\components\Controller;
use app\models\UploadForm;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\EbayInbox;
use app\modules\mails\models\EbayInboxSubject;
use app\modules\mails\models\EbayReply;
use app\modules\mails\models\EbayReplyPicture;
use app\modules\mails\models\MailSubjectTag;
use app\modules\orders\models\Order;
use app\modules\systems\models\ErpOrderApi;
use app\modules\systems\models\Tag;
use PhpImap\Exception;
use yii\helpers\Url;
use yii\helpers\Json;
use app\modules\mails\models\MailOutbox;
use yii\web\UploadedFile;
use app\modules\reports\models\MailStatistics;
use app\modules\systems\models\Country;
use app\modules\systems\models\PaypalAccount;
use app\modules\orders\models\Transactionrecord;

class EbayreplyController extends Controller
{
    //主动发送邮件的列表
    public function actionList()
    {
        $model = new EbayReply();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);
        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    //主动发邮件
    public function actionInitiativeadd()
    {
        $orderId = $this->request->get('order_id');
        $platform = $this->request->get('platform');
        $this->isPopup = true;
        $model = new EbayReply();
        $data = $this->request->post('EbayReply');
        $googleLangCode = VHelper::googleLangCode();
        if(isset($data) && \Yii::$app->request->isAjax)
        {
            $flag = true;
            $image = [];
            if($_FILES['image']['name'][0] != '')
            {
                foreach($_FILES['image']['name'] as $key=>$value)
                {
                    if($value == '')
                        continue;
                    $uploadModel = new UploadForm();
                    $uploadModel->imageFile = UploadedFile::getInstanceByName('image['.$key.']');
                    $nameArr = explode('.',$uploadModel->imageFile->name);
                    if ($uploadModel->upload()) {
                        $image[$nameArr[0]] = $uploadModel->getFilePath();
                    }
                    else
                    {
                        $flag = false;
                        $errorInfo = VHelper::getModelErrors($uploadModel);
                        break;
                    }
                }
            }
            if($flag)
            {
                $transaction = EbayReply::getDb()->beginTransaction();

                $reply_title = $data['reply_title'];
                if(empty(trim($reply_title)))
                    $reply_title = $data['sender'].'针对物品编号'.$data['item_id'].'提出问题';

                $model->item_id = $data['item_id'];
                $model->reply_content = $data['reply_content'];
                $model->reply_content_en = $data['reply_content_en'];
                $model->question_type = $data['question_type'];
                $model->sender = $data['sender'];
                $model->account_id = $data['account_id'];
                $model->recipient_id = $data['recipient_id'];
                $model->reply_title = $reply_title;
                $model->platform_order_id = $data['platform_order_id'];
                $model->is_draft = 0;
                try{
                    $flag = $model->save();
                    if(!$flag)
                        $errorInfo = VHelper::getModelErrors($model);
                }catch(Exception $e){
                    $flag = false;
                    $errorInfo = $e->getMessage();
                }
            }
            if($flag && !empty($image))
            {
                $imageInfo = [];
                foreach($image as $imageK=>$imageV)
                {
                    $pictureModel = new EbayReplyPicture();
                    $pictureModel->reply_table_id = $model->id;
                    $pictureModel->picture_url = $this->request->hostInfo.'/'.$imageV;
                    $pictureModel->picture_name = $imageK;
                    try{
                        $flag = $pictureModel->save();
                        if(!$flag)
                            $errorInfo = VHelper::getModelErrors($pictureModel);
                    }catch(Exception $e){
                        $flag = false;
                        $errorInfo = $e->getMessage();
                    }
                    if($flag)
                        $imageInfo[] = $pictureModel->id;
                    else
                        break;
                }
            }
            if($flag && !$model->is_draft)
            {
                $mailOutBox = new MailOutbox();
                $mailOutBox->platform_code = Platform::PLATFORM_CODE_EB;
                $mailOutBox->reply_id = $model->id;
                $mailOutBox->account_id = $model->account_id;
                $mailOutBox->subject = $model->reply_title;
                $mailOutBox->content = $model->reply_content;
                $mailOutBox->send_status = MailOutbox::SEND_STATUS_WAITTING;
                $sendParams = ['account_id'=>$model->account_id,'ItemID'=>$model->item_id,'QuestionType'=>EbayReply::$questionTypeMap[$model->question_type],'RecipientID'=>$model->recipient_id];
                if(!empty($imageInfo))
                    $sendParams['MessageMedia'] = $imageInfo;
                $mailOutBox->send_params = json_encode($sendParams);
                try{
                    $flag = $mailOutBox->save();
                    if(!$flag)
                        $errorInfo = VHelper::getModelErrors($mailOutBox);
                }catch(Exception $e){
                    $flag = false;
                    $errorInfo = $e->getMessage();
                }

                if($flag)
                {
                    $subject_model = EbayInboxSubject::findOne(['item_id'=>$model->item_id,'buyer_id'=>$model->recipient_id,'account_id'=>$model->account_id]);
                    if(!$subject_model)
                    {
                        $subject_model = new EbayInboxSubject();
                        $subject_model->first_subject = $model->reply_title;
                    }
                    $subject_model->item_id = $model->item_id;
                    $subject_model->buyer_id = $model->recipient_id;
                    $subject_model->account_id = $model->account_id;
                    $subject_model->now_subject = $model->reply_title;
                    $subject_model->is_read = 1;
                    $subject_model->is_replied = 1;
                    $subject_model->receive_date = date('Y-m-d H:i:s');

                    try{
                        $flag = $subject_model->save();
                        if(!$flag)
                            $errorInfo = VHelper::getModelErrors($subject_model);
                    }catch(Exception $e){
                        $flag = false;
                        $errorInfo = $e->getMessage();
                    }
                }

                if($flag && isset($data['tag_id']))
                {
                    $tagIds = $data['tag_id'];
                    if(!empty($tagIds))
                    {
//                        $tagIds = explode(',', $tagIds);
                        //删除已经关联的标签
                        try{
                            MailSubjectTag::deleteMialTags(Platform::PLATFORM_CODE_EB, $subject_model->id);
                            $flag = MailSubjectTag::saveMailTags(Platform::PLATFORM_CODE_EB, $subject_model->id, $tagIds);
                        }catch(Exception $e){
                            $flag = false;
                            $errorInfo = $e->getMessage();
                        }
                    }

                }
            }
            /*if($flag)
            {
                $result = $model->sendInitiativeSendMessage();
                $flag = $result['status'];
                $errorInfo = $result['info'];
            }*/
            if($flag)
            {
                $transaction->commit();
                $response = ['status'=>'success','info'=>'成功！'];
            }
            else
            {
                if(isset($transaction))
                    $transaction->rollBack();
                $response = ['status'=>'error','info'=>$errorInfo];
            }
            echo json_encode($response);
            \Yii::$app->end();
        }

        $orderInfo = Order::getOrderStackByOrderId($platform,'',$orderId);

        if(empty($orderInfo) || empty($orderInfo->info))
        {
            echo '找不到订单信息。';
            \Yii::$app->end();
        }

//        findClass($orderInfo,1);
        $accountModel = Account::findOne(['old_account_id'=>$orderInfo->info->account_id,'status'=>1,'platform_code'=>Platform::PLATFORM_CODE_EB]);
        if(empty($accountModel))
        {
            echo '找不到账号。';
            \Yii::$app->end();
        }
        $model->account_id = $accountModel->id;
        $model->sender = $accountModel->account_name;
        $itemIds = [];
        if(!empty($orderInfo->product))
        {
            foreach ($orderInfo->product as $detail)
                $itemIds[$detail->item_id] = $detail->sku;
        }
        $model->recipient_id = $orderInfo->info->buyer_id;
        $model->platform_order_id = $orderInfo->info->platform_order_id;

        // 查询标签信息
        $tags = Tag::getTagAsArray(Platform::PLATFORM_CODE_EB);

        return $this->render('initiativeadd',['model'=>$model,'itemIds'=>$itemIds,'tags'=>$tags,'googleLangCode'=>$googleLangCode]);
    }

    public function actionInitiativebatchadd()
    {
        $order_ids = $this->request->get('orderids');
        $platform = $this->request->get('platform');
        $this->isPopup = true;
        $model = new EbayReply();
        $data = $this->request->post('EbayReply');
        $googleLangCode = VHelper::googleLangCode();
        if(isset($data) && \Yii::$app->request->isAjax)
        {
            $flag = true;
            $order_ids = $data['order_ids'];
            $order_ids = explode(',',$order_ids);
            $message = '';

            $reply_title = $data['reply_title'];
            foreach($order_ids as $order_id)
            {
                $order_info = Order::getOrderStackByOrderId(Platform::PLATFORM_CODE_EB,'',$order_id);
                if(empty($order_info))
                {
                    $message .= '未找到订单'.$order_id.'信息';
                    continue;
                }
                else
                {
                    $order_info = Json::decode(Json::encode($order_info), true);
                    $track_number   = '';
                    $track          = '';
                    $buyer_id       = '';
                    $ship_name      = '';
                    $payer_email    = '';
                    $receiver_email = '';
                    $transaction_id = '';
                    $email          = '';
                    $paytime        = '';
                    $item_id        = '';
                    if ($order_info['info']) {
                        $info = $order_info['info'];
                        if($order_info['product']){
                            $product = $order_info['product'][0];
                            $item_id = $product['item_id'] ? $product['item_id'] : '';
                        }
                        $track_number = $info['track_number'] ? $info['track_number'] : '';
                        $track = $info['track_number'] ? 'http://www.17track.net/zh-cn/track?nums=' . $info['track_number'] : '';
                        $buyer_id = $info['buyer_id'] ? $info['buyer_id'] : '';
                        $ship_name = $info['ship_name'] ? $info['ship_name'] : '';
                        $ship_name .= "(tel:" . $info['ship_phone'] . ")";
                        $ship_name .= $info['ship_street1'] . ',' . ($info['ship_street2'] == '' ? '' : $info['ship_street2'] . ',') . $info['ship_city_name'] . ',';
                        $ship_name .= $info['ship_stateorprovince'] . ',';
                        $ship_name .= $info['ship_zip'] . ',';
                        $ship_name .= $info['ship_country_name'];
                        $email = $info['email'] ? $info['email'] : '';
                        $paytime = $info['paytime'] ? $info['paytime'] : '';

                        //如果在erp没获取到交易信息  则在客服系统重新获取一遍
                        //获取所以paypal账号信息
                        $paypal = PaypalAccount::getPaypleEmail();
                        if (!empty($order_info['trade'])) {
                            foreach ($order_info['trade'] as $key => $value) {
                                $transactionRecord = Transactionrecord::find()->where(['transaction_id' => $value['transaction_id']])->andwhere(['in', 'payer_email', $paypal])->asArray()->one();
                                if (!empty($transactionRecord)) {
                                    $transaction_id = $transactionRecord['transaction_id'];
                                    $receiver_email = $transactionRecord['receiver_email'];
                                    $payer_email = $transactionRecord['payer_email'];
                                } else {
                                    $transactionRecord = Transactionrecord::find()->where(['transaction_id' => $value['transaction_id']])->asArray()->one();
                                    if (!empty($transactionRecord)) {
                                        $receiver_email = $transactionRecord['receiver_email'];
                                        $payer_email = $transactionRecord['payer_email'];
                                    }
                                }

                            }
                        }
                    }
                    //动态参数转换
                    $data['reply_content'] = str_replace('{1track1}',$track,$data['reply_content']);
                    $data['reply_content'] = str_replace('{1track_number1}',$track_number,$data['reply_content']);
                    $data['reply_content'] = str_replace('{1buyer_id1}',$buyer_id,$data['reply_content']);
                    $data['reply_content'] = str_replace('{1ship_name1}',$ship_name,$data['reply_content']);
                    $data['reply_content'] = str_replace('{1email1}',$email,$data['reply_content']);
                    $data['reply_content'] = str_replace('{1paytime1}',$paytime,$data['reply_content']);
                    $data['reply_content'] = str_replace('{1transaction_id1}',$transaction_id,$data['reply_content']);
                    $data['reply_content'] = str_replace('{1receiver_email1}',$receiver_email,$data['reply_content']);
                    $data['reply_content'] = str_replace('{1payer_email1}',$payer_email,$data['reply_content']);
                    $data['reply_content'] = str_replace('{1item_id1}',$item_id,$data['reply_content']);

                    $data['reply_content_en'] = str_replace('{1track1}',$track,$data['reply_content_en']);
                    $data['reply_content_en'] = str_replace('{1track_number1}',$track_number,$data['reply_content_en']);
                    $data['reply_content_en'] = str_replace('{1buyer_id1}',$buyer_id,$data['reply_content_en']);
                    $data['reply_content_en'] = str_replace('{1ship_name1}',$ship_name,$data['reply_content_en']);
                    $data['reply_content_en'] = str_replace('{1email1}',$email,$data['reply_content_en']);
                    $data['reply_content_en'] = str_replace('{1paytime1}',$paytime,$data['reply_content_en']);
                    $data['reply_content_en'] = str_replace('{1transaction_id1}',$transaction_id,$data['reply_content_en']);
                    $data['reply_content_en'] = str_replace('{1receiver_email1}',$receiver_email,$data['reply_content_en']);
                    $data['reply_content_en'] = str_replace('{1payer_email1}',$payer_email,$data['reply_content_en']);
                    $data['reply_content_en'] = str_replace('{1item_id1}',$item_id,$data['reply_content_en']);
                    if(empty($order_info['product']))
                    {
                        $message .= '未找到订单'.$order_id.'产品信息';
                        continue;
                    }
                    $transaction = EbayReply::getDb()->beginTransaction();
                    if(empty(trim($reply_title)))
                        $reply_title = $order_info['info']['buyer_id'].'针对物品编号'.$order_info['product'][0]['item_id'].'提出问题';

                    $account_info = Account::getHistoryAccountInfo($order_info['info']['account_id'],Platform::PLATFORM_CODE_EB);
                    if(empty($account_info))
                    {
                        $message .= '订单'.$order_id.'未找到帐号信息';
                        continue;
                    }

                    $model = new EbayReply();
                    $model->item_id = $order_info['product'][0]['item_id'];
                    $model->reply_content = $data['reply_content'];
                    $model->reply_content_en = $data['reply_content_en'];
                    $model->question_type = $data['question_type'];
                    $model->sender = $account_info->account_name;
                    $model->account_id = $account_info->id;
                    $model->recipient_id = $order_info['info']['buyer_id'];
                    $model->reply_title = $reply_title;
                    $model->platform_order_id = $order_info['info']['platform_order_id'];
                    $model->is_draft = 0;
                    try{
                        $flag = $model->save();
                        if(!$flag)
                            $message .= VHelper::getModelErrors($model);
                    }catch(Exception $e){
                        $flag = false;
                        $message .= $e->getMessage();
                    }

                    if($flag)
                    {
                        $mailOutBox = new MailOutbox();
                        $mailOutBox->platform_code = Platform::PLATFORM_CODE_EB;
                        $mailOutBox->reply_id = $model->id;
                        $mailOutBox->subject = $model->reply_title;
                        $mailOutBox->content = $model->reply_content;
                        $mailOutBox->send_status = MailOutbox::SEND_STATUS_WAITTING;
                        $sendParams = ['account_id'=>$model->account_id,'ItemID'=>$model->item_id,'QuestionType'=>EbayReply::$questionTypeMap[$model->question_type],'RecipientID'=>$model->recipient_id];
                        $mailOutBox->send_params = json_encode($sendParams);
                        try{
                            $flag = $mailOutBox->save();
                            if(!$flag)
                                $message .= VHelper::getModelErrors($mailOutBox);
                        }catch(Exception $e){
                            $flag = false;
                            $message .= $e->getMessage();
                        }

                        if($flag)
                        {
                            $subject_model = EbayInboxSubject::findOne(['item_id'=>$model->item_id,'buyer_id'=>$model->recipient_id,'account_id'=>$model->account_id]);
                            if(!$subject_model)
                            {
                                $subject_model = new EbayInboxSubject();
                                $subject_model->first_subject = $model->reply_title;
                            }
                            $subject_model->item_id = $model->item_id;
                            $subject_model->buyer_id = $model->recipient_id;
                            $subject_model->account_id = $model->account_id;
                            $subject_model->now_subject = $model->reply_title;
                            $subject_model->is_read = 1;
                            $subject_model->is_replied = 1;
                            $subject_model->receive_date = date('Y-m-d H:i:s');

                            try{
                                $flag = $subject_model->save();
                                if(!$flag)
                                    $message .= VHelper::getModelErrors($subject_model);
                            }catch(Exception $e){
                                $flag = false;
                                $message .= $e->getMessage();
                            }

                            if($flag)
                            {
                                $transaction->commit();
                            }
                            else
                            {
                                $transaction->rollBack();
                            }
                        }
                    }
                }
            }

            if($message)
            {
                $response = ['status'=>'error','info'=>$message];
            }
            else
            {
                $response = ['status'=>'success','info'=>'成功！'];
            }
            echo json_encode($response);
            \Yii::$app->end();
        }

        return $this->render('initiativebatchadd',['order_ids'=>$order_ids,'model'=>$model,'googleLangCode'=>$googleLangCode]);
    }

    public function actionAdd()
    {   

        $inboxId = \Yii::$app->request->post('inbox_id');
        $replyContent = \Yii::$app->request->post('reply_content');
        $isDraft = \Yii::$app->request->post('is_draft');
        $id = \Yii::$app->request->post('id');      
        $draft_id = \Yii::$app->request->post('draft_id');
        $images = \Yii::$app->request->post('image');
        $inboxModel = EbayInbox::findOne((int)$inboxId);        
        $transaction = EbayReply::getDb()->beginTransaction();
        $model = new EbayReply();
        if(is_numeric($id) && $id > 0 && $id%1 === 0) 
        {
            //有回复过
            if(!is_numeric($draft_id) && !is_numeric($isDraft))
            {
                $model = new EbayReply();
            }elseif(!is_numeric($draft_id) && is_numeric($isDraft)){
                $model = new EbayReply();   
            }else{
                if($isDraft == 0)
                {
                    $model = new EbayReply();
                }else{
                    $model=$model->find()->where(['inbox_id'=>$inboxId,'is_draft'=>1])->one();
                }
            }
        }else{
            //没有回复没有草稿，第一次点回复
            if(!is_numeric($draft_id) && $isDraft == 0)
            {
                $model = new EbayReply();
            }else{
                $model = new EbayReply();
            }
        }
        if($isDraft)
        {
            EbayInbox::setExcludeList($inboxModel->id);
            $inboxModel->is_replied = 0;
        }else{

            $inboxModel->is_replied = 1;
        }
        /*if(is_numeric($id) && $id > 0 && $id%1 === 0 && $isDraft == 1)
        {   
            //证明内容是草稿,所以要覆盖
            $model = EbayReply::findOne((int)$id);  //如果是在已回复内容后再存草稿时，则重新插入一条数据
            if(!empty($model) && !is_numeric($draft_id)){//不是草稿且已经回复
                $model = new EbayReply();   //插入一条数据
                $model->is_draft = $isDraft;
            }else{
                $model=$model->find()->where(['inbox_id'=>$inboxId,'is_draft'=>1])->one();
                $model->is_draft = $isDraft;
            }
            EbayInbox::setExcludeList($inboxModel->id);
            $inboxModel->is_replied = 0;   
        }elseif(is_numeric($draft_id) && !is_numeric($id)){
            $model=$model->find()->where(['id'=>$draft_id,'is_draft'=>1])->one();
            $model->is_draft = 1;

        }else{
            $model = new EbayReply();
            $inboxModel->is_replied = 1;
            $model->is_draft = $isDraft;
        }*/
        //exit;
        $model->inbox_id = $inboxId;
        $model->reply_content = $replyContent;
        $model->is_draft = $isDraft;
        $model->recipient_id = $inboxModel->sender;
        $model->account_id = $inboxModel->account_id;
        $model->sender = $inboxModel->recipient_user_id;
        $model->item_id = $inboxModel->item_id;
        $model->parent_message_id = $inboxModel->message_id;
        $model->external_message_id = $inboxModel->external_message_id;
        $model->create_by = \Yii::$app->user->id;
        $model->create_time = date('Y-m-d H:i:s');
        
        try{
            
            $flag = $model->save();
            if($flag)
                $flag = $inboxModel->save();
                if($flag)
                    $inboxModels = EbayInbox::NoReplySign($inboxModel->account_id,$inboxModel->transaction_id,$inboxModel->receive_date);
        }catch(Exception $e){
            $flag = false;
            $response['message'] = $e->getMessage();
        }

        if($flag && !empty($images))
        {
            $imageInfo = [];
            EbayReplyPicture::deleteAll(['reply_table_id'=>$model->id]);
            foreach ($images as $image)
            {
                $pictureModel = new EbayReplyPicture();
                $pictureModel->reply_table_id = $model->id;
                $pictureModel->picture_url = $image;
                try{
                    $flag = $pictureModel->save();
                    if(!$flag)
                        $response['message'] = VHelper::getModelErrors($pictureModel);
                }catch(Exception $e){
                    $flag = false;
                    $response['message'] = $e->getMessage();
                }
                if($flag)
                    $imageInfo[] = $pictureModel->id;
                else
                    break;
            }
        }
        if($flag && !$isDraft)
        {
            $mailOutBox = new MailOutbox();
            $mailOutBox->platform_code = Platform::PLATFORM_CODE_EB;
            $mailOutBox->inbox_id = $model->inbox_id;
            $mailOutBox->account_id = $model->account_id;
            $mailOutBox->reply_id = $model->id;
            $mailOutBox->content = $model->reply_content;
            $mailOutBox->send_status = 0;
            $sendParams = ['account_id'=>$model->account_id,'ItemID'=>$model->item_id,'ParentMessageID'=>$model->parent_message_id,'RecipientID'=>$model->recipient_id,'ExternalMessageID'=>$model->external_message_id];
            if(!empty($imageInfo))
                $sendParams['MessageMedia'] = $imageInfo;
            $mailOutBox->send_params = json_encode($sendParams);
            try{
                $flag = $mailOutBox->save();
                if(!$flag)
                    $response['message'] = VHelper::getModelErrors($mailOutBox);
            }catch(Exception $e){
                $flag = false;
                $response['message'] = $e->getMessage();
            }
        }
        if($flag)
        {
            $transaction->commit();
            $nextInbox = $inboxModel->nextInbox();
            $response['status'] = 'success';
            if(!empty($nextInbox))
            {
                $response['url'] = Url::toRoute(['/mails/ebayinbox/detail','id'=>$nextInbox->id]);
            }else{
                $response['url'] = Url::toRoute(['/mails/ebayinbox/detail','id'=>$inboxId]);
            }
        }
        else
        {
            $transaction->rollBack();
            $response['status'] = 'error';
        }
         echo Json::encode($response);
        \Yii::$app->end();

        /*if(is_numeric($id) && $id > 0 && $id%1 === 0)      
            $model = EbayReply::findOne((int)$id);      //如果是草稿
        else
            $model = new EbayReply();

        $model->inbox_id = $inboxId;
        $model->reply_content = $replyContent;
        $model->is_draft = $isDraft;
        $model->recipient_id = $inboxModel->sender;
        $model->account_id = $inboxModel->account_id;
        $model->sender = $inboxModel->recipient_user_id;
        $model->item_id = $inboxModel->item_id;
        $model->parent_message_id = $inboxModel->message_id;
        $model->external_message_id = $inboxModel->external_message_id;
        $model->create_by = \Yii::$app->user->id;
        $model->create_time = date('Y-m-d H:i:s');
        if($isDraft)
        {
            EbayInbox::setExcludeList($inboxModel->id);
            $inboxModel->is_replied = 0;
        }
        else{

            $inboxModel->is_replied = 1;
           
        }
        try{
            $flag = $model->save();
            if($flag)
                $flag = $inboxModel->save();
                if($flag)
                    $inboxModels = EbayInbox::NoReplySign($inboxModel->account_id,$inboxModel->transaction_id,$inboxModel->receive_date);
        }catch(Exception $e){
            $flag = false;
            $response['message'] = $e->getMessage();
        }
        if($flag && !empty($images))
        {
            $imageInfo = [];
            EbayReplyPicture::deleteAll(['reply_table_id'=>$model->id]);
            foreach ($images as $image)
            {
                $pictureModel = new EbayReplyPicture();
                $pictureModel->reply_table_id = $model->id;
                $pictureModel->picture_url = $image;
                try{
                    $flag = $pictureModel->save();
                    if(!$flag)
                        $response['message'] = VHelper::getModelErrors($pictureModel);
                }catch(Exception $e){
                    $flag = false;
                    $response['message'] = $e->getMessage();
                }
                if($flag)
                    $imageInfo[] = $pictureModel->id;
                else
                    break;
            }
        }
        if($flag && !$isDraft)
        {
            $mailOutBox = new MailOutbox();
            $mailOutBox->platform_code = Platform::PLATFORM_CODE_EB;
            $mailOutBox->inbox_id = $model->inbox_id;
            $mailOutBox->reply_id = $model->id;
            $mailOutBox->content = $model->reply_content;
            $mailOutBox->send_status = 0;
            $sendParams = ['account_id'=>$model->account_id,'ItemID'=>$model->item_id,'ParentMessageID'=>$model->parent_message_id,'RecipientID'=>$model->recipient_id,'ExternalMessageID'=>$model->external_message_id];
            if(!empty($imageInfo))
                $sendParams['MessageMedia'] = $imageInfo;
            $mailOutBox->send_params = json_encode($sendParams);
            try{
                $flag = $mailOutBox->save();
                if(!$flag)
                    $response['message'] = VHelper::getModelErrors($mailOutBox);
            }catch(Exception $e){
                $flag = false;
                $response['message'] = $e->getMessage();
            }
        }
        if($flag)
        {
            $transaction->commit();
            $nextInbox = $inboxModel->nextInbox();
            $response['status'] = 'success';
            if(!empty($nextInbox))
            {
                $response['url'] = Url::toRoute(['/mails/ebayinbox/detail','id'=>$nextInbox->id]);
            }else{
                $response['url'] = Url::toRoute(['/mails/ebayinbox/detail','id'=>$inboxId]);
            }
        }
        else
        {
            $transaction->rollBack();
            $response['status'] = 'error';
        }
         echo Json::encode($response);
        \Yii::$app->end();*/
    }

    public function actionAddsubject()
    {
//        $subjectId = \Yii::$app->request->post('subject_id');
        $inboxId = \Yii::$app->request->post('inbox_id');

        if(empty($inboxId))
            $this->_showMessage('客户message不存在，主动联系客户的主题信息无法使用回复操作！');
        $replyContent = \Yii::$app->request->post('reply_content');//需发送给客户的
        $replyContentEn = \Yii::$app->request->post('reply_content_en');//不需要发送给客户
        $isDraft = \Yii::$app->request->post('is_draft');   // 1-存草稿，0-回复消息
        $id = \Yii::$app->request->post('id');              // 回复表id
        $images = \Yii::$app->request->post('image');
        $transaction = EbayReply::getDb()->beginTransaction();
        $flag = true;
        $inboxModel = EbayInbox::findOne((int)$inboxId);
        if(empty($inboxId) || empty($inboxModel)){
            $flag = false;
            $response['message'] = '未找到客户回复信息,无法给客户发送消息';
        }
        $inboxSubjectModel = EbayInboxSubject::findOne($inboxModel->inbox_subject_id);
        if(empty($inboxSubjectModel))
        {
            $flag = false;
            $reponse['message'] = '未找到该邮件主题';
        }
        if($flag){
            if(is_numeric($id) && $id > 0 && $id%1 === 0)
            {
                // 找到该消息的回复草稿
                $model = EbayReply::find()->where(['inbox_id'=>$inboxId,'is_draft'=>1])->one();
                // 没有草稿，新建model
                if(empty($model))
                    $model = new EbayReply();
            }
            else
            {
                $model = new EbayReply();
            }

            if($isDraft)
            {
                EbayInbox::setExcludeList($inboxModel->id);
                $inboxModel->is_replied = 0;
            }else{

                $inboxModel->is_replied = 1;
            }

            $model->inbox_id = $inboxId;
            $model->reply_content = $replyContent;
            $model->reply_content_en = $replyContentEn;
            $model->is_draft = $isDraft;
            $model->recipient_id = $inboxModel->sender;
            $model->account_id = $inboxModel->account_id;
            $model->sender = $inboxModel->recipient_user_id;
            $model->item_id = $inboxModel->item_id;
            $model->parent_message_id = $inboxModel->message_id;
            $model->external_message_id = $inboxModel->external_message_id;
            $model->create_by = \Yii::$app->user->identity->login_name;
            $model->create_time = date('Y-m-d H:i:s');

            if(!$isDraft)
            {
                try{
                    $flag  = EbayInbox::updateAll(['is_replied' => 3],['inbox_subject_id' => $inboxModel->inbox_subject_id]);
                    if(!$flag)
                        $response['message'] = 'updateAll false';
                    else
                    {
                        $inboxSubjectModel->is_replied = 1;
                        $flag = $inboxSubjectModel->save();
                    }
                }catch(Exception $e){
                    $flag = false;
                    $response['message'] = $e->getMessage;
                }

            }

            try{
                $flag = $model->save();
                if($flag){
                    $flag = $inboxModel->save();
                }
                if($flag){
                    $inboxModels = EbayInbox::NoReplySign($inboxModel->account_id,$inboxModel->inbox_subject_id,$inboxModel->receive_date);
                }
            }catch(Exception $e){
                $flag = false;
                $response['message'] = $e->getMessage();
            }

            if($flag && !empty($images))
            {
                $imageInfo = [];
                EbayReplyPicture::deleteAll(['reply_table_id'=>$model->id]);
                foreach ($images as $image)
                {
                    $pictureModel = new EbayReplyPicture();
                    $pictureModel->reply_table_id = $model->id;
                    $pictureModel->picture_url = $image;
                    try{
                        $flag = $pictureModel->save();
                        if(!$flag)
                            $response['message'] = VHelper::getModelErrors($pictureModel);
                    }catch(Exception $e){
                        $flag = false;
                        $response['message'] = $e->getMessage();
                    }
                    if($flag){
                        $imageInfo[] = $pictureModel->id;
                    }else{
                        break;
                    }
                }
            }

            if($flag && !$isDraft)
            {
                $mailOutBox = new MailOutbox();
                $mailOutBox->platform_code = Platform::PLATFORM_CODE_EB;
                $mailOutBox->inbox_id = $model->inbox_id;
                $mailOutBox->reply_id = $model->id;
                $mailOutBox->account_id = $model->account_id;
                $mailOutBox->content = $model->reply_content;
                $mailOutBox->send_status = 0;
                $sendParams = ['account_id'=>$model->account_id,'ItemID'=>$model->item_id,'ParentMessageID'=>$model->parent_message_id,'RecipientID'=>$model->recipient_id,'ExternalMessageID'=>$model->external_message_id];
                if(!empty($imageInfo))
                    $sendParams['MessageMedia'] = $imageInfo;
                $mailOutBox->send_params = json_encode($sendParams);
                try{
                    $flag = $mailOutBox->save();
                    if(!$flag)
                        $response['message'] = VHelper::getModelErrors($mailOutBox);
                }catch(Exception $e){
                    $flag = false;
                    $response['message'] = $e->getMessage();
                }
            }
        }

        if($flag)
        {
            $transaction->commit();
//            $nextInbox = $inboxModel->nextInbox();
            $session = \Yii::$app->session;
            $sessionKey = EbayInboxSubject::PLATFORM_CODE . '_INBOX_SUBJECT_PROCESSED_LIST';
            $sessionKeyWhere = $sessionKey . '_WHERE';
            $queryParams = $session->get($sessionKey);
            $queryParamsWhere = $session->get($sessionKeyWhere);
            $next_id = '';
            if($queryParams)
            {
                $result = EbayInboxSubject::find()
                    ->from(EbayInboxSubject::tableName().' as t');

                if($queryParams['query']->join)
                {
                    $result->join = $queryParams['query']->join;
                    $result->addParams([':platform_code'=>EbayInboxSubject::PLATFORM_CODE]);
                }
                $result = $result->where($queryParamsWhere)
                    ->andWhere(['>','receive_date',$inboxSubjectModel->receive_date])
                    ->orderBy('is_replied DESC,receive_date ASC')
                    ->limit(1)
                    ->column();

                if($result)
                    $next_id = $result[0];
            }
//            $next_id = $session->get('next_ebay_subject_id');
            $response['status'] = 'success';
            //处理邮件
            $mailStatistics = MailStatistics::findOne(['message_id'=>(string)$inboxModel->message_id,'platform_code'=>Platform::PLATFORM_CODE_EB]);
            if($mailStatistics && $mailStatistics->status == 0){
                $mailStatistics->status = 1;
                $mailStatistics->save(false);
            }
            if(!empty($next_id))
            {
                $response['url'] = Url::toRoute(['/mails/ebayinboxsubject/detail','id'=>$next_id]);
            }else{
                $response['url'] = Url::toRoute(['/mails/ebayinboxsubject/detail','id' => $inboxModel->inbox_subject_id]);
            }
        }
        else
        {
            $transaction->rollBack();
            $response['status'] = 'error';
        }
        echo Json::encode($response);
        \Yii::$app->end();

    }

    //上传图片
    public function actionUploadimage()
    {
        if (\Yii::$app->request->isPost)
        {
            $model = new UploadForm();
            $model->imageFile = UploadedFile::getInstanceByName('ebay_reply_upload_image');
            if ($model->upload()) {
                echo json_encode(['status'=>'success','url'=>$this->request->hostInfo.'/'.str_replace('\\','/',$model->getFilePath())]);
            }
            else
            {
                $errorResponse = ['status'=>'error','info'=>VHelper::getModelErrors($model)];
//                if(!empty($model->getFilePath()))
//                    $errorResponse['url'] = $this->request->hostInfo.'/'.str_replace('\\','/',$model->getFilePath());
                echo json_encode($errorResponse);
            }
            \Yii::$app->end();
        }
    }
    //删除图片
    public function actionDeleteimage()
    {
        $url = trim($this->request->post('url'));
        $host = $this->request->hostInfo;
        if(strpos($url,$host) === false)
        {
            $response = ['status'=>'error','info'=>'参数错误。'];
        }
        else
        {
            $url = str_replace($host.'/','',$url);
            if(file_exists($url))
            {
                unlink($url);
                $response = ['status'=>'success'];
            }
            else
            {
                $response = ['status'=>'error','info'=>'图片不存在。'];
            }
        }
        echo json_encode($response);
        \Yii::$app->end();
    }

}
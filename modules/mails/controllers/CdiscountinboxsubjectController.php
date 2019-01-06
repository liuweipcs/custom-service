<?php

namespace app\modules\mails\controllers;

use app\modules\orders\models\Order;
use Yii;
use yii\helpers\Url;
use app\common\VHelper;
use yii\web\UploadedFile;
use yii\helpers\Json;
use app\components\Controller;
use app\modules\systems\models\Email;
use app\modules\systems\models\Tag;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\MailSubjectTag;
use app\modules\mails\models\CdiscountInboxSubject;
use app\modules\mails\models\MailTemplate;
use app\modules\accounts\models\Account;
use app\modules\mails\models\CdiscountInbox;
use app\modules\services\modules\cdiscount\components\cdiscountApi;
use app\modules\mails\models\CdiscountInboxReply;
use app\modules\mails\models\CdiscountInboxReplyAttachment;
use app\modules\mails\models\MailOutbox;
use app\modules\orders\models\OrderKefu;
use app\modules\accounts\models\UserAccount;
use app\modules\services\modules\amazon\components\Mail;
use app\modules\mails\models\ActiveSendEmail;

class CdiscountinboxsubjectController extends Controller
{
    const UPLOAD_IMAGE_PAHT = 'attachments/reply/';

    /**
     * 列表
     */
    public function actionList()
    {
        $model = new CdiscountInboxSubject();
        $params = Yii::$app->request->getBodyParams();
        //搜索列表
        $dataProvider = $model->searchList($params);

        //获取标签列表统计
        $tagList = CdiscountInboxSubject::getTagsList($params);
        //获取账号列表统计
        $accountList = CdiscountInboxSubject::getAccountCountList($params);

        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'tagList' => $tagList,
            'account_email' => $accountList,
        ]);
    }

    /**
     * 邮件主题
     */
    public function actionView()
    {
        $id = Yii::$app->request->get('id', 0);
        $prev = Yii::$app->request->get('prev', 0);
        $next = Yii::$app->request->get('next', 0);

        if (empty($id)) {
            $this->_showMessage('主题ID不能为空', false);
        }

        //获取上一封邮件主题
        if (!empty($prev)) {
            //客服只能查看自已绑定账号
            $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_CDISCOUNT);
            //主题
            $sub = CdiscountInboxSubject::findOne($id);

            $query = CdiscountInboxSubject::find()
                ->select('id')
                ->andWhere(['in', 'account_id', $accountIds])
                ->andWhere(['is_reply' => 0, 'status' => 'Open'])
                ->orderBy('last_updated_date ASC');

            if (!empty($sub->account_id)) {
                $query->andWhere(['account_id' => $sub->account_id]);
            }

            $res = $query->asArray()->all();
            if (!empty($res)) {
                $arrs = [];
                foreach ($res as $key => $value) {
                    $arrs[] = $value['id'];
                }
                $subscript = array_search($id, $arrs);//当前下标
                if ($subscript != 0) {
                    $subscript -= 1;
                    $id = $arrs[$subscript];
                }
            }

            if (empty($id)) {
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/mails/cdiscountinboxsubject/list') . '");';
                $this->_showMessage('没有上一封邮件主题了!!!', true, null, false, null, $extraJs, true, 'msg');
            }
        }

        //获取下一封邮件主题
        if (!empty($next)) {
            //客服只能查看自已绑定账号
            $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_CDISCOUNT);
            //主题
            $sub = CdiscountInboxSubject::findOne($id);

            $query = CdiscountInboxSubject::find()
                ->select('id')
                ->andWhere(['in', 'account_id', $accountIds])
                ->andWhere(['is_reply' => 0, 'status' => 'Open'])
                ->orderBy('last_updated_date ASC');

            if (!empty($sub->account_id)) {
                $query->andWhere(['account_id' => $sub->account_id]);
            }

            $res = $query->asArray()->all();
            if (!empty($res)) {
                $arrs = [];
                foreach ($res as $key => $value) {
                    $arrs[] = $value['id'];
                }
                $subscript = array_search($id, $arrs);//当前下标
                if ($subscript < count($arrs)) {
                    $subscript += 1;
                    $id = $arrs[$subscript];
                }
            }
            if (empty($id)) {
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/mails/cdiscountinboxsubject/list') . '");';
                $this->_showMessage('没有下一封邮件主题了!!!', true, null, false, null, $extraJs, true, 'msg');
            }
        }

        $subject = CdiscountInboxSubject::findOne($id);

        if (empty($subject)) {
            $this->_showMessage('没有找到该主题', false);
        } else {
            //设置主题为已读
            $subject->is_read = 1;
            $subject->modify_by = Yii::$app->user->identity->user_name;
            $subject->modify_time = date('Y-m-d H:i:s');
            $subject->save();
        }

        //获取当前主题的账号信息
        $account = Account::findOne($subject->account_id);

        //获取该主题打上的标签
        $tags = [];
        $tagIds = MailSubjectTag::find()
            ->select('tag_id')
            ->andWhere(['subject_id' => $subject->id])
            ->andWhere(['platform_code' => Platform::PLATFORM_CODE_CDISCOUNT])
            ->column();
        if (!empty($tagIds)) {
            $tags = Tag::find()
                ->select('id, tag_name as name')
                ->where(['in', 'id', $tagIds])
                ->asArray()
                ->all();
        }
        $subject->setAttribute('tags', $tags);

        //如果是合并的主题,inbox_id有可能会有多个
        $inbox_subject_ids = explode(',', $subject->inbox_id);


        //获取客服回复的
        $reply_d = [];
        foreach ($inbox_subject_ids as $v){
            $replys = CdiscountInboxReply::find()
                ->where(['inbox_subject_id' => $v])
                ->orderBy('reply_time DESC')
                ->asArray()
                ->all();
            if (!empty($replys)) {
                foreach ($replys as $key => $reply) {
                    //获取回复的附件
                    $attachments = CdiscountInboxReplyAttachment::find()
                        ->where(['reply_id' => $reply['id']])
                        ->asArray()
                        ->all();
                    $replys[$key]['attachments'] = $attachments;
                }
            }
            $reply_d[$v] =  $replys;
        }

        //获取该主题下所有往来回复
        $inboxs = CdiscountInbox::find()
            ->where(['in', 'inbox_subject_id', $inbox_subject_ids])
            ->orderBy('timestamp DESC')
            ->asArray()
            ->all();


        if (!empty($inboxs)) {
            foreach ($inboxs as $key => $inbox) {
                if (strtolower(trim($inbox['sender'])) == strtolower(trim($account->account_discussion_name))) {
                    foreach ($reply_d as $reply) {
                        //通过判断内容是否相等，来匹配上附件
                        $replyContent = trim(strip_tags($reply['reply_content']));
                        $replyContent = preg_replace('/\s+/', ' ', $replyContent);
                        $inboxContent = trim(strip_tags($inbox['content']));
                        $inboxContent = preg_replace('/\s+/', ' ', $inboxContent);

                        if ($replyContent == $inboxContent) {
                            $inboxs[$key]['attachments'] = $reply['attachments'];
                            $inboxs[$key]['content_en'] = $reply['reply_content_en'];
                            $inboxs[$key]['reply_by'] = $reply['reply_by'];
                            break;
                        }
                    }
                }
            }
        }


        $subject->setAttribute('inboxs', $inboxs);

        $list = [];


        foreach ($inboxs as $inbox) {
            if (!isset($list[$inbox['inbox_subject_id']])) {
                $list[$inbox['inbox_subject_id']] = [];
            }

            if (strtolower($inbox['sender']) != strtolower(trim($account->account_discussion_name))) {
                $inbox['float'] = 'left';

            } else {
                $inbox['float'] = 'right';
            }

            $list[$inbox['inbox_subject_id']][] = $inbox;
        }


     /*   $list_d = [];
        foreach($list as $kk => $vv){

           foreach ($vv as $k => $item){

               if($item['float'] == 'left'){
                   $list_d[$kk]['left'][] = $item;
               }else{
                   $list_d[$kk]['right'][] = $item;
               }
           }
        }*/


        //只显示最新的客服回复信息
        if (!empty($replys[0]) && !empty($inboxs[0])) {
            //这里的时间比较只精确到分
            //法国与中国时区相差6个小时
            $replyTime = date('Y-m-d H:i:00', strtotime($replys[0]['reply_time']) - (6 * 3600));
            $timestamp = date('Y-m-d H:i:00', strtotime($inboxs[0]['timestamp']));
            //如果客服回复时间大于最新的邮件，则显示该回复
            if (strtotime($replyTime) > strtotime($timestamp)) {
                $subject->setAttribute('replys', [$replys[0]]);
            }
        }

        //谷歌翻译
        $googleLangCode = VHelper::googleLangCode();

        //获取历史订单
        $historyOrders = [];
        //当前主题的订单信息
        $orderInfo = [];
        if (!empty($subject->platform_order_id)) {
            $orderInfo = OrderKefu::getOrderStack(Platform::PLATFORM_CODE_CDISCOUNT, $subject->platform_order_id);
            if (!empty($orderInfo)) {
                $orderInfo = json_decode(json_encode($orderInfo), true, 512, JSON_BIGINT_AS_STRING);
                if (!empty($orderInfo['info']['buyer_id'])) {
                    //通过买家ID查询该客户的历史订单
                    $historyOrders = OrderKefu::getHistoryOrders(Platform::PLATFORM_CODE_CDISCOUNT, $orderInfo['info']['buyer_id'], '', $account->old_account_id);
                }
            }
        }

        //如果历史订单为空，则把当前订单信息加入历史订单
        if (empty($historyOrders) && !empty($orderInfo)) {
            $tmpOrderInfo = $orderInfo['info'];
            $tmpOrderInfo['orderPackage'] = !empty($orderInfo['orderPackage']) ? $orderInfo['orderPackage'] : [];
            $tmpOrderInfo['trade'] = !empty($orderInfo['trade']) ? $orderInfo['trade'] : [];
            $tmpOrderInfo['profit'] = !empty($orderInfo['profit']) ? $orderInfo['profit'] : [];
            $historyOrders[] = $tmpOrderInfo;
        }

        //获取当前主题下所有的邮件
     /*   $noReplyInboxs['all_no_reply'] = '未回复的邮件';
        if (!empty($inboxs)) {
            $noReplyId = 0;
            $noReplyIds = [];
            foreach ($inboxs as $inbox) {
                if (strtolower(trim($inbox['sender'])) != strtolower(trim($account->account_discussion_name))) {
                   if (empty($inbox['is_reply'])) {
                        $content = '(未回复)' . $content;
                        $noReplyIds[] = $inbox['id'];
                    } else {
                        $content = '(已回复)' . $content;
                    }
                    $noReplyInboxs[$inbox['id']] = $content;
                }
            }
            if (!empty($noReplyIds)) {
                rsort($noReplyIds);
                $noReplyId = !empty($noReplyIds[0]) ? $noReplyIds[0] : 0;
            }
        }*/


        //获取当前主题下 所有合并主题
        $noReplyInboxs['all_no_reply'] = '未回复的主题';
        $subject_reply_ids = [];
        if(!empty($inbox_subject_ids)){
            $noReplyId = 0;
            $noReplyIds = [];
            foreach ($inbox_subject_ids as $inbox){
                $inbox_replay = CdiscountInbox::find()
                    ->where(['inbox_subject_id' => $inbox])
                    ->andWhere(['<>', 'sender', trim($account->account_discussion_name)])
                    ->orderBy('timestamp DESC')
                    ->asArray()
                    ->one();
                $subject_reply_ids[$inbox] = $inbox_replay;
                if (empty($inbox_replay['is_reply'])) {
                    $content = '(未回复)' . $inbox_replay['inbox_subject_id'];
                    $noReplyIds[] = $inbox;
                } else {
                    $content = '(已回复)' . $inbox_replay['inbox_subject_id'];
                }
                $noReplyInboxs[$inbox] = $content;
            }
        }

        if (!empty($noReplyIds)) {
            rsort($noReplyIds);
            $noReplyId = !empty($noReplyIds[0]) ? $noReplyIds[0] : 0;
        }
         
        foreach ($historyOrders as $key => $value) {
            if($value['platform_order_id']==$subject->platform_order_id){ 
                $historyOrders[$key]=$historyOrders[0];
                $historyOrders[0]=$value;              
             }   
        }


        return $this->renderList('view', [
            'subject' => $subject,
            'list_d' => $list,
            'reply_d' => $reply_d,
            'account' => $account,
            'googleLangCode' => $googleLangCode,
            'historyOrders' => $historyOrders,
            'orderInfo' => $orderInfo,
            'noReplyId' => $noReplyId,
            'noReplyInboxs' => $noReplyInboxs,
            'subject_reply_ids' => $subject_reply_ids,
        ]);
    }

    /**
     * 设置邮件备注
     */
    public function actionSetremark()
    {
        $id = Yii::$app->request->post('id', 0);
        $remark = Yii::$app->request->post('remark', '');

        if (empty($id)) {
            die(json_encode([
                'code' => 0,
                'message' => '邮件ID不能为空',
            ]));
        }

        $inbox = CdiscountInbox::findOne($id);
        if (empty($inbox)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到该邮件',
            ]));
        }

        $inbox->remark = $remark;
        if ($inbox->save() !== false) {
            die(json_encode([
                'code' => 1,
                'message' => '成功',
            ]));
        } else {
            die(json_encode([
                'code' => 0,
                'message' => '失败',
            ]));
        }
    }

    /**
     * 创建回复
     */
    public function actionCreatereply()
    {
        $params = Yii::$app->request->post();

        if (empty($params['account_id'])) {
            $this->_showMessage("账号ID不能为空", false);
        }

        $account = Account::findOne($params['account_id']);
        if (empty($account->email)) {
            $this->_showMessage("请设置账号{$account->account_name}的邮箱", false);
        }

        //邮箱配置
        $email = Email::findOne(['emailaddress' => $account->email]);
        if (empty($email)) {
            $this->_showMessage("请配置该{$account->email}邮箱的SMTP", false);
        }

        //邮件主题
        $subject = CdiscountInboxSubject::findOne($params['subject_id']);

        if (empty($subject)) {
            $this->_showMessage('没有找到邮件主题信息', false);
        }

        //回复内容
        if (empty($params['reply_content']) && empty($params['reply_content_en'])) {
            $this->_showMessage('发送给客户的内容不能为空', false);
        }



        $transaction = CdiscountInboxReply::getDb()->beginTransaction();


        //设为主题为已读
        $subject->is_read = 1;

        if (!empty($params['inbox_ids']) && is_array($params['inbox_ids'])) {
            $replyIds= [];
            $inbox_subject_ids = [];
            foreach ($params['inbox_ids'] as $id) {
                //如果用户选择了所有未回复的邮件
                if ($id == 'all_no_reply') {
                    //如果是合并的主题,inbox_id有可能会有多个
                    $inbox_subject_ids = explode(',', $subject->inbox_id);

                    $noReplyIds = CdiscountInbox::find()
                        ->select('id')
                        ->andWhere(['is_reply' => 0])
                        ->andWhere(['in', 'inbox_subject_id', $inbox_subject_ids])
                        ->andWhere(['<>', 'sender', trim($account->account_discussion_name)])
                        ->orderBy('timestamp DESC')
                        ->column();

                    $replyIds = array_merge($replyIds, $noReplyIds);
                } else {
                    //取出该主题所有消息
                    $noReplyIds = CdiscountInbox::find()
                        ->select('id')
                        ->andWhere(['inbox_subject_id' => $id])
                        ->andWhere(['<>', 'sender', trim($account->account_discussion_name)])
                        ->orderBy('timestamp DESC')
                        ->column();
                    $inbox_subject_ids[] = $id;
                    $replyIds = array_merge($replyIds, $noReplyIds);
                }
            }


            //去重
            $replyIds = array_unique($replyIds);
            if (!empty($replyIds)) {
                //更新邮件回复状态
                CdiscountInbox::updateAll([
                    'is_reply' => 1,
                    'reply_by' => Yii::$app->user->identity->user_name,
                    'reply_time' => date('Y-m-d H:i:s'),
                ], ['in', 'id', $replyIds]);

                //获取子类主题
                $son_inbox_ids = explode(',', $subject->inbox_id);

                //获取未回复子类主题邮件ID
                $son_no_reply = CdiscountInbox::find()
                    ->select('id')
                    ->andWhere(['is_reply' => 0])
                    ->andWhere(['in', 'inbox_subject_id', $son_inbox_ids])
                    ->andWhere(['<>', 'sender', trim($account->account_discussion_name)])
                    ->orderBy('timestamp DESC')
                    ->column();

                //当所有主题都已回复 再更新回复状态
                if(empty($son_no_reply)){
                    $subject->is_reply = 1;
                    $subject->modify_by = Yii::$app->user->identity->user_name;
                    $subject->modify_time = date('Y-m-d H:i:s');
                }

                $subject->save();


                //保存附件到回复附件表
                $uploadImages = UploadedFile::getInstancesByName('uploadImage');

                $attachments = [];
                $uploadFiles = [];
                if (!empty($uploadImages)) {
                    $filePath = self::UPLOAD_IMAGE_PAHT . date('Ymd') . '/';
                    //判断文件上传路径，如果不存在，则创建
                    if (!file_exists($filePath)) {
                        @mkdir($filePath, 0777, true);
                        @chmod($filePath, 0777);
                    }

                    foreach ($uploadImages as $uploadedFile) {
                        //文件名，我们通过md5文件名加上扩展名
                        $fileName = md5($uploadedFile->baseName . microtime(true)) . '.' . $uploadedFile->extension;
                        $file = $filePath . $fileName;
                        //保存文件到我们的服务器上
                        if (!$uploadedFile->saveAs($file)) {
                            $transaction->rollBack();
                            $this->_showMessage('保存附件失败', false);
                        }
                        $file_path = '/' . ltrim($file, '/');
                        $attachments[] = Yii::getAlias('@webroot') . $file_path;
                        $uploadFiles[] = [
                            'name' => $uploadedFile->name,
                            'file_path' => $file_path,
                        ];
                    }
                }
                //同一个主题只用回复一次
                $reply_ids = [];
                foreach($inbox_subject_ids as $v){
                    $reply = CdiscountInbox::find()
                        ->select('id')
                        ->andWhere(['inbox_subject_id' => $v])
                        ->andWhere(['<>', 'sender', trim($account->account_discussion_name)])
                        ->orderBy('timestamp DESC')
                        ->one();
                    $reply_ids[] = $reply['id'];
                }


                foreach ($reply_ids as $replyId) {
                    $inbox = CdiscountInbox::findOne($replyId);
                    //获取讨论邮箱地址
                    $discussionMail = CdiscountInboxSubject::getDiscussionMail($account, $inbox->inbox_subject_id);
                    if (empty($discussionMail)) {
                        $transaction->rollBack();
                        $this->_showMessage('讨论邮箱地址获取失败', false);
                    }

                    $reply = new CdiscountInboxReply();
                    $reply->account_id = $account->id;
                    $reply->inbox_subject_id = $inbox->inbox_subject_id;
                    $reply->subject_id = $subject->id;
                    $reply->inbox_id = $replyId;
                    $reply->reply_content = $params['reply_content'];
                    $reply->reply_content_en = $params['reply_content_en'];
                    $reply->reply_by = Yii::$app->user->identity->user_name;
                    $reply->reply_time = date('Y-m-d H:i:s');

                    //保存邮件回复信息
                    if (!$reply->save()) {
                        $transaction->rollBack();
                        $this->_showMessage('保存邮件回复信息失败', false);
                    }

                    if (!empty($uploadFiles)) {
                        foreach ($uploadFiles as $uploadFile) {
                            $attachment = new CdiscountInboxReplyAttachment();
                            $attachment->reply_id = $reply->id;
                            $attachment->name = $uploadFile['name'];
                            $attachment->file_path = $uploadFile['file_path'];
                            $attachment->save();
                        }
                    }

                    //发件内容
                    if(!empty($reply->reply_content_en) && !empty($reply->reply_content)){
                        $content = $reply->reply_content."\n\n".$reply->reply_content_en;
                    }
                    if (empty($reply->reply_content_en)) {
                        $content = $reply->reply_content;
                    }
                    if(empty($reply->reply_content)){
                        $content = $reply->reply_content_en;
                    }

                    //保存回复到发件箱
                    $mail = new MailOutbox();
                    $mail->platform_code = Platform::PLATFORM_CODE_CDISCOUNT;
                    $mail->account_id = $reply->account_id;
                    $mail->inbox_id = $reply->inbox_id;
                    $mail->reply_id = $reply->id;
                    $mail->subject = mb_substr($content, 0, 72, 'UTF-8');
                    $mail->content = $content;
                    $mail->send_status = MailOutbox::SEND_STATUS_WAITTING;
                    $mail->buyer_id = $inbox->sender;
                    $mail->receive_email = $discussionMail;
                    $mail->platform_order_id = $subject->platform_order_id;
                    $mail->send_params = Json::encode([
                        'sender_email' => $account->email,
                        'receive_email' => $discussionMail,
                        'order_id' => $subject->platform_order_id,
                        'attachments' => $attachments,
                    ]);

                    if (!$mail->save()) {
                        $transaction->rollBack();
                        $this->_showMessage('保存发件箱失败', false);
                    }
                }
            }
        }else{
            $this->_showMessage('还未选择未回复邮件主题', false);
        }

        $transaction->commit();

        if(empty($son_no_reply)){
            $this->_showMessage('成功！', true, 'next', false, null);
        }else{
            $this->_showMessage('成功！', true, 'no', false, null);
        }

    }

    /**
     * 关闭问题
     */
    public function actionClosediscussion()
    {
        $id = Yii::$app->request->post('id', 0);

        $subject = CdiscountInboxSubject::findOne($id);
        if (empty($subject)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到邮件主题',
            ]));
        }

        $account = Account::findOne($subject->account_id);
        if (empty($account)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到账号信息',
                'data' => [
                    'inbox_id' => $subject->inbox_id,
                ]
            ]));
        }

        $cdApi = new cdiscountApi($account->refresh_token);
        $inbox_ids = explode(',', $subject->inbox_id);
        $result = $cdApi->CloseDiscussionList($inbox_ids);

        if (empty($result) || empty($result['CloseDiscussionListResponse']['CloseDiscussionListResult']['CloseDiscussionResultList'])) {
            die(json_encode([
                'code' => 0,
                'message' => '接口请求失败',
                'data' => [
                    'inbox_id' => $subject->inbox_id,
                ]
            ]));
        }
        $result = $result['CloseDiscussionListResponse']['CloseDiscussionListResult']['CloseDiscussionResultList']['CloseDiscussionResult'];
        if ($result['OperationStatus'] == 'DiscussionNotFound') {
            die(json_encode([
                'code' => 0,
                'message' => '该问题没有找到',
                'data' => [
                    'inbox_id' => $subject->inbox_id,
                ]
            ]));
        }

        //修改主题当前状态为关闭状态
        $subject->status = 'Closed';
        $subject->save();

        die(json_encode([
            'code' => 1,
            'message' => '成功',
            'data' => [
                'inbox_id' => $subject->inbox_id,
            ]
        ]));
    }

    /**
     * 批量标记为已回复
     */
    public function actionBatchmark()
    {
        $ids = Yii::$app->request->post('ids', []);

        if (empty($ids)) {
            $this->_showMessage('请选中标记项', false);
        }

        $result = CdiscountInboxSubject::updateAll(['is_reply' => 2], ['in', 'id', $ids]);
        if ($result) {
            $this->_showMessage('标记为已回复成功', true, Url::toRoute('/mails/cdiscountinboxsubject/list'), true);
        } else {
            $this->_showMessage('标记为已回复失败', false);
        }
    }

    /**
     * 搜索邮件模板
     */
    public function actionSearchmailtemplate()
    {
        $platformCode = Yii::$app->request->post('platform_code', '');
        $name = Yii::$app->request->post('name', '');

        $data = MailTemplate::searchMailTemplate($name, $platformCode);
        if (empty($data)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有找到邮件模板',
            ]));
        } else {
            die(json_encode([
                'code' => 1,
                'message' => '成功',
                'data' => $data,
            ]));
        }
    }

    /**
     * 批量添加标签
     */
    public function actionAddtags()
    {
        $type = $this->request->get('type', 'list');
        $subject_ids = $this->request->get('ids', '');

        if (empty($subject_ids)) {
            $this->_showMessage('请选中标记项', false);
        }

        $this->isPopup = true;
        $model = new MailSubjectTag();
        //获取标签数据
        $tags_data = Tag::getTagAsArray(Platform::PLATFORM_CODE_CDISCOUNT);

        //该平台该消息已经有的标签数据
        $exist_data = MailSubjectTag::get_tag_ids_by_platformcode_and_subject(Platform::PLATFORM_CODE_CDISCOUNT, explode(',', $subject_ids));

        if ($this->request->getIsAjax()) {

            $post_data = $this->request->post();

            //没有勾选标签
            if (empty($post_data['MailTag']['tag_id'])) {
                $this->_showMessage('没有勾选标签', false);
            }

            $this->save_mail_tag($post_data, Platform::PLATFORM_CODE_CDISCOUNT);
        }

        return $this->render('tags', [
            'model' => $model,
            'subject_ids' => $subject_ids,
            'tags_data' => $tags_data,
            'exist_data' => $exist_data,
            'type' => $type
        ]);
    }

    /**
     * 移除指定标签
     */
    public function actionRemovetags()
    {
        $subject_id = $this->request->get('id', 0);
        $type = $this->request->get('type', 'list');

        if (empty($subject_id)) {
            $this->_showMessage('ID不能为空', false);
        }

        $this->isPopup = true;
        $model = new MailSubjectTag();
        $tags_data = MailSubjectTag::get_tags_by_platformcode_and_subject(Platform::PLATFORM_CODE_CDISCOUNT, $subject_id);

        if ($this->request->getIsAjax()) {

            $post_data = $this->request->post();

            //没有勾选标签
            if (empty($post_data['MailTag']['tag_id'])) {
                $this->_showMessage('没有勾选标签', false);
            }
            $result = MailSubjectTag::delete_mail_tag(Platform::PLATFORM_CODE_CDISCOUNT, $post_data['MailTag']['inbox_id'], $post_data['MailTag']['tag_id']);
            if (!$result) {
                $this->_showMessage('移除标签失败', false);
            }

            //成功后跳转的url
            $url = $this->get_loation_url($post_data);
            $this->_showMessage('移除标签成功', true, $url, false, null, null);
        }

        return $this->render('tags', [
            'model' => $model,
            'subject_ids' => $subject_id,
            'tags_data' => $tags_data,
            'exist_data' => [],
            'type' => $type
        ]);
    }

    /**
     * 保存邮件标签数据
     */
    protected function save_mail_tag($post_data, $platform_code)
    {
        //消息id
        $inbox_ids = explode(',', $post_data['MailTag']['inbox_id']);

        //存取mail_tag表的数据
        list($result, $message) = MailSubjectTag::batch_save_mail_tags($platform_code, $post_data['MailTag']['tag_id'], $inbox_ids);

        if (!$result) {
            $this->_showMessage($message, false);
        }

        //成功后跳转的url
        $url = $this->get_loation_url($post_data);

        $this->_showMessage('添加标签成功', true, $url, false, null, null);
    }

    /**
     * 跳转地址
     */
    protected function get_loation_url($post_data)
    {
        //成功后跳转的url
        switch ($post_data['MailTag']['type']) {
            case 'list':
                $url = Url::toRoute('/mails/cdiscountinboxsubject/list');
                break;
            case 'detail':
                $url = Url::toRoute(['/mails/cdiscountinboxsubject/view', 'id' => $post_data['MailTag']['inbox_id']]);
                break;
            default:
                $url = Url::toRoute('/mails/cdiscountinboxsubject/list');
                break;
        }
        return $url;
    }

    /**
     * 跟进账号ID 站点获取站点邮箱
     * @author allen <2018-03-28>
     */
    public function actionGetsendemail()
    {
        //账号ID(客服系统的)
        $accountId = !empty($_REQUEST['account_id']) ? trim($_REQUEST['account_id']) : 0;
        //收件箱
        $toemail = !empty($_REQUEST['toemail']) ? trim($_REQUEST['toemail']) : '';
        //平台订单ID
        $platformOrderId = !empty($_REQUEST['platform_order_id']) ? trim($_REQUEST['platform_order_id']) : '';

        $account = Account::findOne($accountId);

        //post请求
        if (Yii::$app->request->isPost) {
            die(json_encode($account->email));
        }

        //谷歌翻译
        $googleLangCode = VHelper::googleLangCode();

        //get请求
        return $this->render('send_email', [
            'fromEmail' => $account->email,
            'toEmail' => $toemail,
            'platformOrderId' => $platformOrderId,
            'accouontId' => $account->id,
            'googleLangCode' => $googleLangCode,
        ]);
    }

    /**
     * 批量跟进账号ID 站点获取站点邮箱
     * @author allen <2018-03-28>
     */
    public function actionGetsendemails()
    {
        //账号ID(客服系统的)
        $accountId = !empty($_REQUEST['account_id']) ? trim($_REQUEST['account_id']) : 0;
        //收件箱
        $toemail = !empty($_REQUEST['toemail']) ? trim($_REQUEST['toemail']) : '';
        //平台订单ID
        $platformOrderId = !empty($_REQUEST['platform_order_id']) ? trim($_REQUEST['platform_order_id']) : '';

        $fromEmail = [];
        $accountArr = explode(';', $accountId);
        foreach ($accountArr as &$v) {
            $account = Account::find()->where(['old_account_id' => $v, 'platform_code' => 'CDISCOUNT'])->one();
            $fromEmail[] = $account->email ? $account->email : 'null';
        }
        //post请求
        if (Yii::$app->request->isPost) {
            echo json_encode($fromEmail);
            exit;
        }
    }

    /**
     * 发邮件
     * @author allen <2018-03-28>
     */
    public function actionSendemail()
    {
        set_time_limit(0);
        $sku = Yii::$app->request->post('sku', '');
        $senderEmail = Yii::$app->request->post('sender_email', '');
        $receiveEmail = Yii::$app->request->post('receive_email', '');
        $subject = Yii::$app->request->post('subject', '');
        $tag = Yii::$app->request->post('tag', []);
        $replyContentEn = Yii::$app->request->post('reply_content_en', '');
        $replyContent = Yii::$app->request->post('reply_content', '');
        //账号ID(客服系统的)
        $accountId = Yii::$app->request->post('account_id', '');
        $platformOrderId = Yii::$app->request->post('platform_order_id', '');

        if (empty($senderEmail)) {
            die(json_encode([
                'bool' => 0,
                'msg' => '发件人邮箱不能为空',
            ]));
        }
        if (empty($receiveEmail)) {
            die(json_encode([
                'bool' => 0,
                'msg' => '收件人邮箱不能为空',
            ]));
        }
        if (empty($subject)) {
            die(json_encode([
                'bool' => 0,
                'msg' => '主题不能为空',
            ]));
        }
        if (empty($replyContentEn)) {
            die(json_encode([
                'bool' => 0,
                'msg' => '回复内容(英文)不能为空',
            ]));
        }
        $email = Mail::instance($senderEmail);
        if (empty($email)) {
            die(json_encode([
                'bool' => 0,
                'msg' => "实例化{$senderEmail}邮箱失败,请检查邮箱SMTP配置",
            ]));
        }

        $uploadImages = UploadedFile::getInstancesByName('uploadImage');

        //附件
        $attachments = [];
        if (!empty($uploadImages)) {
            $filePath = self::UPLOAD_IMAGE_PAHT . date('Ymd') . '/';
            //判断文件上传路径，如果不存在，则创建
            if (!file_exists($filePath)) {
                @mkdir($filePath, 0777, true);
                @chmod($filePath, 0777);
            }

            foreach ($uploadImages as $uploadImage) {
                //文件名，我们通过md5文件名加上扩展名
                $fileName = md5($uploadImage->baseName . microtime(true)) . '.' . $uploadImage->extension;
                $file = $filePath . $fileName;
                //保存文件到我们的服务器上
                if ($uploadImage->saveAs($file)) {
                    $attachments[] = Yii::getAlias('@webroot') . '/' . ltrim($file, '/');
                }
            }
        }

        //如果回复内容为空，则将回复内容(英文)发送
        $content = $replyContent;
        if (empty($content)) {
            $content = $replyContentEn;
        }

        $email->setTo($receiveEmail)->setSubject($subject)->seHtmlBody($content)->setFrom($senderEmail);
        //添加附件
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $email->addAttach($attachment);
            }
        }
        //发送
        $result = $email->sendmail();

        if (!$result) {
            die(json_encode([
                'bool' => 0,
                'msg' => '错误原因:' . current(Mail::$errorMsg),
            ]));
        }

        //保存主动发送邮件信息
        $active = new ActiveSendEmail();
        $active->account_id = $accountId;
        $active->platform_code = Platform::PLATFORM_CODE_CDISCOUNT;
        $active->platform_order_id = $platformOrderId;
        $active->sender_email = $senderEmail;
        $active->receive_email = $receiveEmail;
        $active->title = $subject;
        $active->content = $content;
        $active->sku = $sku;
        $active->tag = implode(',', $tag);
        $active->attachments = json_encode($attachments);
        $active->create_by = Yii::$app->user->identity->user_name;
        $active->create_time = date('Y-m-d H:i:s');
        $active->modify_by = Yii::$app->user->identity->user_name;
        $active->modify_time = date('Y-m-d H:i:s');
        $active->save();

        die(json_encode([
            'bool' => 1,
            'msg' => '发送成功',
        ]));
    }

    /**
     * 批量发邮件
     * @author allen <2018-03-28>
     */
    public function actionSendemails()
    {
        set_time_limit(0);
        $cd_all_value = Yii::$app->request->post('cd_all_value', '');
        $subject = Yii::$app->request->post('subject', '');
        $replyContentEn = Yii::$app->request->post('reply_content_en', '');
        $replyContent = Yii::$app->request->post('reply_content', '');

        //如果回复内容为空，则将回复内容(英文)发送
        $content = $replyContent;
        if (empty($content)) {
            $content = $replyContentEn;
        }

        if (empty($subject)) {
            die(json_encode([
                'bool' => 0,
                'msg' => '主题不能为空',
            ]));
        }
        if (empty($replyContentEn)) {
            die(json_encode([
                'bool' => 0,
                'msg' => '回复内容(英文)不能为空',
            ]));
        }

        //组装数据
        $sendmailData = explode(',', $cd_all_value);

        $returnArr = ['bool' => 1, 'msg' => '发送成功!'];
        foreach ($sendmailData as $key => $value) {

            $yb = new MailOutbox();
            $account_id = explode('&', $value)[0];
            $recipientenmail = explode('&', $value)[1];
            $order_id = explode('&', $value)[2];
            $accountInfo = Account::findOne($account_id);
            if (!$accountInfo->email) {
                $returnArr = ['bool' => 0, 'msg' => '发件人邮箱不能为空 '];
                echo json_encode($returnArr);
                exit;
            }
            $send_email = $accountInfo->email;
            $yb->platform_code = Platform::PLATFORM_CODE_CDISCOUNT;
            $yb->account_id = $account_id;
            $yb->subject = $subject;
            $yb->content = $content;
            $yb->send_time = date('Y-m-d H:i:s', strtotime(" +4 minute"));//
            $yb->send_status = 0;//默认等待发送
            $yb->send_params = json_encode(
                [
                    'sender_email' => $send_email,
                    'receive_email' => $recipientenmail,
                    'order_id' => $order_id,
                    'attachments' => []
                ]
            );
            $yb->create_by = Yii::$app->user->identity->user_name;
            $yb->create_time = date('Y-m-d H:i:s');
            $res = $yb->save();
        }
        if (!$res) {
            $returnArr = ['bool' => 0, 'msg' => '批量发送失败 '];
        }
        echo json_encode($returnArr);
        exit;
    }

    /**
     * 测试发送邮件
     */
    public function actionTestsendemail()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 'On');

        try {
            $email = !empty($_REQUEST['email']) ? trim($_REQUEST['email']) : '';
            $content = !empty($_REQUEST['content']) ? trim($_REQUEST['content']) : 'test';
            $mail = Mail::instance($email);
            if (empty($mail)) {
                die("实例化{$email}失败!");
            }

            $mail->setTo('lackone@126.com')->setSubject($content)->setTextBody($content)->setFrom($email);
            $result = $mail->sendmail();

            echo '<pre>';
            var_dump($result);
            var_dump(current(Mail::$errorMsg));
        } catch (\Exception $e) {
            echo $e->getMessage();
            echo $e->getFile();
            echo $e->getLine();
        }
    }
}
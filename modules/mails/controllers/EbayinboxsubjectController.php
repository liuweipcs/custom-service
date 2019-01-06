<?php


namespace app\modules\mails\controllers;

use app\modules\accounts\models\UserAccount;
use app\modules\aftersales\models\AfterSalesRedirect;
use app\modules\mails\models\EbayInbox;
use app\modules\mails\models\EbayReply;
use app\modules\mails\models\EbayInboxSubject;
use app\components\Controller;
use app\modules\orders\models\OrderKefu;
use app\modules\products\models\EbaySites;
use app\modules\mails\models\MailTag;
use app\modules\mails\models\MailSubjectTag;
use app\modules\systems\models\Keyboard;
use app\modules\systems\models\Rule;
use app\modules\systems\models\Tag;
use app\modules\accounts\models\Platform;
use PhpImap\Exception;
use yii\helpers\Url;
use yii\helpers\Json;
use app\modules\mails\models\EbayInboxContentMongodb;
use app\modules\mails\models\EbayFeedback;
use app\modules\accounts\models\Account;
use Yii;
use app\components\GoogleTranslation;
use app\common\VHelper;
use app\modules\reports\models\MailStatistics;
use app\modules\orders\models\EbayOnlineListing;

class EbayinboxsubjectController extends Controller
{
    public $storeSites = [];

    public function getSite($siteid)
    {
        if (!isset($this->storeSites[$siteid])) {
            $this->storeSites[$siteid] = EbaySites::findOne(['siteid' => $siteid])->attributes;
        }
        return $this->storeSites[$siteid];
    }


    public function actionList()
    {
        $model         = new EbayInboxSubject();
        $params        = \Yii::$app->request->getBodyParams();
        $tagList       = EbayInboxSubject::getTagsList();
        $account_email = EbayInboxSubject::getAccountEmail('EB');
        $sort               = new \yii\data\Sort([
            'attributes' => [
                'is_replied',
                'receive_date',
            ]
        ]);
        $sort->defaultOrder = array(
            'is_replied'   => SORT_DESC,
            'receive_date' => SORT_ASC,
        );
        $dataProvider       = $model->searchList($params, $sort);
        $queryModel         = new EbayInboxSubject();
        $queryParams        = $queryModel->getSearchQuerySession();
        $session            = \Yii::$app->session;
        $sessionKey         = EbayInboxSubject::PLATFORM_CODE . '_INBOX_SUBJECT_PROCESSED_LIST';
        $session->set($sessionKey, $queryParams);
        $sessionKeyWhere = $sessionKey . '_WHERE';
        $session->set($sessionKeyWhere, $queryParams['query']->where);

        // 查询用户设置的快捷键
        $keyboards = json_encode(Keyboard::getKeyboardsAsArray(Platform::PLATFORM_CODE_EB));
        return $this->renderList('list', [
            'model'         => $model,
            'dataProvider'  => $dataProvider,
            'tagList'       => $tagList,
            'account_email' => $account_email,
            'keyboards'     => $keyboards,
        ]);
    }

    /**
     * ebay邮件详情
     * @param $id
     * @return \yii\base\string
     */
    public function actionDetail($id)
    {
        $idddx = $id;
        //加载页面的时候先翻译已有客户留言
        $currentModel = EbayInboxSubject::findOne((int)$id);
        $display      = TRUE;
        if ($currentModel->buyer_id == 'eBay') {
            $display = FALSE;
        }
        $models       = EbayInbox::find()->where(['inbox_subject_id' => $id])->orderBy('receive_date DESC')->asArray()->all();
        $newArr       = array();
        $orgialImage  = array();
        $new_inbox_id = 0;
        if (!empty($models)) {
            EbayInbox::updateAll(['is_read' => 1], ['inbox_subject_id' => $currentModel->id]);
            $currentModel->is_read = 1;
            if ($currentModel->buyer_id == 'ebay') {
                $currentModel->is_replied = 1;
                EbayInbox::updateAll(['is_replied' => 3], ['inbox_subject_id' => $currentModel->id, 'is_replied' => 0]);
            }
            $currentModel->save();

            $new_inbox_id = $models[0]['id'];
            foreach ($models as $key => $value) {
                $id                    = $value['id'];
                $account               = Account::findOne($value['account_id']);
                $value['account_name'] = $account->account_name;
                //从mogo中取产品图片
                if ($value['img_exists'] == 1) {
                    $contentMongodb = EbayInboxContentMongodb::findOne(['ueb_ebay_inbox_id' => (int)$id]);
                    if ($contentMongodb) {
                        $imgBox         = $contentMongodb->image_url;
                        $value['image'] = str_replace('href', '#', html_entity_decode($imgBox));
                        array_push($orgialImage, $value['orgial_img']);
                    } else {
                        $value['image'] = '';
                    }
                } else
                    $value['image'] = '';
                //如果newmessage无内容则去匹配mongodb中的content
                if (empty($value['new_message'])) {
                    $result = EbayInbox::getMongoContent($id);
                    $result > 0 && $this->redirect(['ebayinboxsubject/detail', 'id' => $currentModel->id]);
                }

                if (strpos($value['new_message'], '<div id="UserInputtedText">') !== false && $display) {
                    ini_set("pcre.recursion_limit", "4194");
                    preg_match("/<div id=\"UserInputtedText\">(.|\n)*?<\/div>/", $value['new_message'], $mat);
                    $value['new_message'] = isset($mat[0]) ? $mat[0] : "";
                }

                $value['new_message']    = $value['new_message'];
                $value['new_message_en'] = $value['new_message_en'];

                $Reply                          = EbayReply::find()->select('id,inbox_id,sender,item_id,account_id,reply_title,reply_content_en,reply_content,create_time,recipient_id,create_by,remark')->where('inbox_id=:iid and is_delete = :isdel', [':iid' => $id, ':isdel' => 0])->with('pictures')->asArray()->all();
                $newArr[$value['receive_date']] = $value;
                if (!empty($Reply)) {
                    foreach ($Reply as $reKey => $reVal) {
                        if (empty($reVal['reply_content_en'])) {
                            $reVal['reply_content_en'] = $reVal['reply_content'];
                        }
                        $reVal['id']                   = $reVal['inbox_id'];
                        $newArr[$reVal['create_time']] = $reVal;
                    }
                    krsort($newArr);
                }
            }
        }

        if (!empty($orgialImage)) {
            $orgialImage = array_unique($orgialImage);
            $orgialImage = implode(',', $orgialImage);  //图片存在
        }
        //是否卖家主动发送第一封邮件，查出主动发送的第一封邮件
        $firstReplyModels = EbayReply::find()->where(['inbox_id' => 0, 'parent_message_id' => '', 'account_id' => $currentModel->account_id, 'recipient_id' => $currentModel->buyer_id, 'is_draft' => 0, 'is_delete' => 0, 'is_send' => 1])->asArray()->all();
        if ($firstReplyModels) {
            foreach ($firstReplyModels as $firstReplyModel) {
                if ($firstReplyModel['reply_content_en'] == "") {
                    $firstReplyModel['reply_content_en'] = $firstReplyModel['reply_content'];
                }
                $newArr[$firstReplyModel['create_time']] = $firstReplyModel;
            }
            krsort($newArr);
        }
        if (empty($newArr)) {
            $this->_showMessage('Not Find Email!', false);
        }
        //用于生成回复表单(草稿)
        $replyModel = EbayReply::findOne(['inbox_id' => $new_inbox_id, 'is_send' => 0, 'is_delete' => 0, 'is_draft' => 1]);

        $replyModel = empty($replyModel) ? (new EbayReply()) : $replyModel;
        /*获取历史订单*/
        $Historica = [];
        if ($currentModel->buyer_id) {
            $account_id   = $currentModel->account_id;
            $accountModel = Account::findOne($account_id);
            $accouontId   = $accountModel->old_account_id ? $accountModel->old_account_id : "";
            $Historica    = OrderKefu::getHistoryOrders(Platform::PLATFORM_CODE_EB, $currentModel->buyer_id, '', $accouontId);
            if (!empty($Historica)) {
                $Historica = Json::decode(Json::encode($Historica), true);
                foreach ($Historica as $historKey => &$historVal) {
                    $comment_type = 6;
                    $feed_id      = '';
                    foreach ($historVal['detail'] as $Hdetail) {
                        $feedbackinfo = EbayFeedback::getCommentByTransactionID($Hdetail['transaction_id'], $Hdetail['item_id']);
                        if (isset($feedbackinfo->comment_type) && $feedbackinfo->comment_type < $comment_type) {
                            $comment_type = $feedbackinfo->comment_type;
                            $feed_id      = $feedbackinfo->id;
                        }
                        // 查询重寄单
                        $after_sale_redirect              = AfterSalesRedirect::find()->where(['order_id' => $Hdetail['order_id']])->asArray()->all();
                        $historVal['after_sale_redirect'] = $after_sale_redirect;
                    }
                    // 给当前订单数据添加评价等级
                    $historVal['comment_type'] = $comment_type;
                    $historVal['feed_id']      = $feed_id;
                }
            }

        }

        array_multisort(array_column($Historica, 'paytime'), SORT_DESC, $Historica);
        $sortArr = [];
        foreach ($Historica as $key => $value) {
            $arr = array();
            foreach ($value['detail'] as $val) {
                $arr[$key] = $val['item_id'];
            }
            if (in_array($currentModel->item_id, $arr)) {
                unset($Historica[$key]);
                $sortArr[] = $value;
            }
        }
        $Historica = array_merge($sortArr, $Historica);

        // 获取已标记标签
        $subject_id    = (int)$this->request->getQueryParam('id');
        $platform_code = Platform::PLATFORM_CODE_EB;

        $tags_data = MailSubjectTag::get_tags_by_platformcode_and_subject($platform_code, $subject_id);

        //获取订单 sku
        $sku = [];
        if (empty($Historica)) {
            $item_id = array_column($models, 'item_id');
            if (empty($item_id)) {
                $sku = '因未获取到itemId，暂无sku';
            } else {
                $sku_data = EbayOnlineListing::find()->select('sku')->where(['in', 'itemid', $item_id])->asArray()->all();
                $sku = array_column($sku_data, 'sku');
                $sku = array_flip(array_flip($sku));
                $sku = $sku ? $sku : '暂无SKU';
            }
        }

        // 查询用户设置的快捷键
        $keyboards = json_encode(Keyboard::getKeyboardsAsArray(Platform::PLATFORM_CODE_EB, \Yii::$app->user->identity->id));
        //获取所有google语言Code
        $googleLangCode = VHelper::googleLangCode();
//        if (in_array(Yii::$app->user->identity->login_name, ['何贞'])) {
//            echo '<pre>';
//            var_dump($newArr);
//            echo '</pre>';
//            
//        }
        if ($display) {
            return $this->renderList('detail', [
                'new_inbox_id'    => $new_inbox_id,
                'currentModel'    => $currentModel,
                'models'          => $newArr,
                'firstReplyModel' => $firstReplyModels,
                'replyModel'      => $replyModel,
                'Historica'       => $Historica,
                'tags_data'       => $tags_data,
                'orgialImage'     => $orgialImage,
                'keyboards'       => $keyboards,
                'googleLangCode'  => $googleLangCode,
                'idddx'           => $idddx,
                'sku'             => $sku
            ]);
        } else {
            return $this->renderList('ebay_detail', [
                'new_inbox_id'    => $new_inbox_id,
                'currentModel'    => $currentModel,
                'models'          => $newArr,
                'firstReplyModel' => $firstReplyModels,
                'replyModel'      => $replyModel,
                'Historica'       => $Historica,
                'tags_data'       => $tags_data,
                'orgialImage'     => $orgialImage,
                'keyboards'       => $keyboards,
                'googleLangCode'  => $googleLangCode,
                'sku'             => $sku
            ]);
        }
    }

    /**
     * 快捷键批量或者单条添加或删除消息标签
     */
    public function actionAddretags()
    {
        if ($this->request->getIsAjax()) {

            $platform_code = Platform::PLATFORM_CODE_EB;
            $post_data     = $this->request->post();
            //判断按键下的标签是否存在
            $tag_data = MailSubjectTag::get_tag_by_platformcode_and_subject($platform_code, $post_data['MailTag']['inbox_id'], $post_data['MailTag']['tag_id']);
            if ($tag_data) {
                //标签存在就删除
                $result = MailSubjectTag::delete_mail_tag($platform_code, $post_data['MailTag']['inbox_id'], $post_data['MailTag']['tag_id']);

                if (!$result) {
                    $this->_showMessage(\Yii::t('system', 'operation fail'), false);
                }
                $tag_id = $post_data['MailTag']['tag_id'][0];

                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, 'del', false, null, $tag_id);
                exit;
            } else {
                //消息id
                $inbox_ids = explode(',', $post_data['MailTag']['inbox_id']);

                //存取mail_tag表的数据

                list($result, $message) = MailSubjectTag::batch_save_mail_tags($platform_code, $post_data['MailTag']['tag_id'], $inbox_ids);
                //获取打上标签数据
                $tags_data = MailSubjectTag::get_tags_by_platformcode_and_subject($platform_code, $inbox_ids);
                if (!$result) {
                    $this->_showMessage($message, false);
                }
                //成功后跳转的url
                $url = $this->get_loation_url($post_data);

                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, 'add', false, $tags_data, null);
                exit;
            }

        }
    }

    /**
     * 批量或者单条添加消息标签
     */
    public function actionAddtags()
    {
        $this->isPopup = true;
        $model         = new MailTag();
        $subject_ids   = $this->request->getQueryParam('ids');
        $type          = $this->request->getQueryParam('type');
        //根据平台code获取标签数据
        $platform_code = Platform::PLATFORM_CODE_EB;
        //所有该平台下的标签数据
        $tags_data = Tag::getTagAsArray($platform_code);
        //该平台该消息已经有的标签数据
        $exist_data = MailSubjectTag::get_tag_ids_by_platformcode_and_subject($platform_code, explode(',', $subject_ids));


        if ($this->request->getIsAjax()) {
            $post_data = $this->request->post();
            //没有勾选标签
            if (empty($post_data['MailTag']['tag_id'])) {
                $this->_showMessage(\Yii::t('system', 'no tag Data'), false);
            }
            $this->save_mail_tag($post_data, $platform_code);
        }

        return $this->render('tags', [
            'model'       => $model,
            'subject_ids' => $subject_ids,
            'tags_data'   => $tags_data,
            'exist_data'  => $exist_data,
            'type'        => $type
        ]);
    }

    /**
     * 维护消息和标签的关系
     * @param array $post_data 表单数据
     * @param string $platform_code 平台code
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

        $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, $url, false, null, null);


    }

    protected function get_loation_url($post_data)
    {
        //成功后跳转的url
        switch ($post_data['MailTag']['type']) {
            case 'list':
                $url = \yii\helpers\Url::toRoute('/mails/ebayinboxsubject/list');
                break;
            case 'detail':
                $url = \yii\helpers\Url::toRoute(['/mails/ebayinboxsubject/detail', 'id' => $post_data['MailTag']['inbox_id']]);
                break;
            default:
                $url = \yii\helpers\Url::toRoute('/mails/ebayinboxsubject/list');
                break;
        }
        return $url;
    }

    /**
     * 移除指定消息的标签
     */
    public function actionRemovetags()
    {
        $this->isPopup = true;
        $model         = new MailTag();
        $subject_id    = (int)$this->request->getQueryParam('id');
        $type          = $this->request->getQueryParam('type');
        $platform_code = Platform::PLATFORM_CODE_EB;

        $tags_data = MailSubjectTag::get_tags_by_platformcode_and_subject($platform_code, $subject_id);

        if ($this->request->getIsAjax()) {

            $post_data = $this->request->post();

            //没有勾选标签
            if (empty($post_data['MailTag']['tag_id'])) {
                $this->_showMessage(\Yii::t('system', 'no tag Data'), false);
            }
            $result = MailSubjectTag::delete_mail_tag($platform_code, $post_data['MailTag']['inbox_id'], $post_data['MailTag']['tag_id']);

            if (!$result) {
                $this->_showMessage(\Yii::t('system', 'operation fail'), false);
            }
            //成功后跳转的url
            $url = $this->get_loation_url($post_data);
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, $url, false, null, null);
        }
        return $this->render('tags', [
            'model'       => $model,
            'subject_ids' => $subject_id,
            'tags_data'   => $tags_data,
            'exist_data'  => array(),
            'type'        => $type
        ]);
    }

    /*
     * @desc 批量更新已回复
     **/
    public function actionSignreplied()
    {
        $ids   = $this->request->post('ids');
        $count = 0;
        foreach ($ids as $id) {
            $subject_model = EbayInboxSubject::findOne($id);
            $inboxs        = EbayInbox::find()->where(['inbox_subject_id' => $subject_model->id])->one();
            //处理邮件
            if ($inboxs) {
                $mailStatistics = MailStatistics::findOne(['message_id' => (string)$inboxs->message_id, 'platform_code' => Platform::PLATFORM_CODE_EB]);
                if ($mailStatistics && $mailStatistics->status == 0) {
                    $mailStatistics->status = 1;
                    $mailStatistics->save(false);
                }
            }
            $subject_model->is_read    = 1;
            $subject_model->is_replied = 2;
            if ($subject_model->save())
                $count++;
        }

        if ($count > 0)
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, 'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebayinboxsubject/list') . '");', false);
        else
            $this->_showMessage('状态更新失败', false);

    }

    //标记已回复或下一封
    public function actionMark()
    {
        $id   = trim($this->request->post('subject_id'));
        $type = trim($this->request->post('type'));

        $session         = \Yii::$app->session;
        $sessionKey      = EbayInboxSubject::PLATFORM_CODE . '_INBOX_SUBJECT_PROCESSED_LIST';
        $sessionKeyWhere = $sessionKey . '_WHERE';

        if (is_numeric($id) && $id > 0 && $id % 1 === 0) {
            $model = EbayInboxSubject::findOne((int)$id);
            if (!empty($model)) {
                switch ($type) {
                    case 'replied':
                        try {
                            $model->is_replied = 2;
                            $flag              = $model->save();
                            if (!$flag)
                                $response['info'] = '标记回复不成功。';
                            // 查找消息
                            $inboxs = EbayInbox::find()->where(['inbox_subject_id' => $model->id])->andWhere(['is_replied' => 0])->one();
                            if ($inboxs) {
                                $flag = EbayInbox::updateAll(['is_replied' => 3], ['inbox_subject_id' => $model->id, 'is_replied' => 0]);
                                if (!$flag) {
                                    $response['info'] = '标记回复不成功。';
                                } else {
                                    //处理邮件
                                    $mailStatistics = MailStatistics::findOne(['message_id' => (string)$inboxs->message_id, 'platform_code' => Platform::PLATFORM_CODE_EB]);
                                    if ($mailStatistics && $mailStatistics->status == 0) {
                                        $mailStatistics->status = 1;
                                        $mailStatistics->save(false);
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            $flag             = false;
                            $response['info'] = $e->getMessage();
                        }
                        if ($flag) {
                            $queryParams      = $session->get($sessionKey);
                            $queryParamsWhere = $session->get($sessionKeyWhere);
                            $next_id          = '';
                            if ($queryParams) {
                                $result = EbayInboxSubject::find()
                                    ->from(EbayInboxSubject::tableName() . ' as t');

                                if ($queryParams['query']->join) {
                                    $result->join = $queryParams['query']->join;
                                    $result->addParams([':platform_code' => EbayInboxSubject::PLATFORM_CODE]);
                                }
                                $result = $result->where($queryParamsWhere)
                                    ->andWhere(['>', 'receive_date', $model->receive_date])
                                    ->orderBy('is_replied DESC,receive_date ASC')
                                    ->limit(1)
                                    ->column();

                                if ($result)
                                    $next_id = $result[0];
                            }
                            if (empty($next_id)) {
                                $flag             = false;
                                $response['info'] = '已是最后一封。';
                            } else {
                                $response['url'] = Url::toRoute(['/mails/ebayinboxsubject/detail', 'id' => $next_id]);
                            }
                        }
                        $response['status'] = $flag ? 'success' : 'error';
                        break;
                    case 'next':
                        $queryParams      = $session->get($sessionKey);
                        $queryParamsWhere = $session->get($sessionKeyWhere);
                        $next_id          = '';
                        if ($queryParams) {
                            $result = EbayInboxSubject::find()
                                ->from(EbayInboxSubject::tableName() . ' as t');

                            if ($queryParams['query']->join) {
                                $result->join = $queryParams['query']->join;
                                $result->addParams([':platform_code' => EbayInboxSubject::PLATFORM_CODE]);
                            }
                            $result = $result->where($queryParamsWhere)
                                ->andWhere(['>', 'receive_date', $model->receive_date])
                                ->orderBy('is_replied DESC,receive_date ASC')
                                ->limit(1)
                                ->column();

                            if ($result)
                                $next_id = $result[0];
                        }
                        if (empty($next_id))
                            $response = ['status' => 'error', 'info' => '已是最后一封。'];
                        else
                            $response = ['status' => 'success', 'url' => Url::toRoute(['/mails/ebayinboxsubject/detail', 'id' => $next_id])];
                        break;
                    case 'last':
                        $queryParams      = $session->get($sessionKey);
                        $queryParamsWhere = $session->get($sessionKeyWhere);
                        $last_id          = '';
                        if ($queryParams) {
                            $result = EbayInboxSubject::find()
                                ->from(EbayInboxSubject::tableName() . ' as t');

                            if ($queryParams['query']->join) {
                                $result->join = $queryParams['query']->join;
                                $result->addParams([':platform_code' => EbayInboxSubject::PLATFORM_CODE]);
                            }
                            $result = $result->where($queryParamsWhere)
                                ->andWhere(['<', 'receive_date', $model->receive_date])
                                ->orderBy('is_replied DESC,receive_date DESC')
                                ->limit(1)
                                ->column();

                            if ($result)
                                $last_id = $result[0];
                        }
                        if (empty($last_id))
                            $response = ['status' => 'error', 'info' => '已是最后一封。'];
                        else
                            $response = ['status' => 'success', 'url' => Url::toRoute(['/mails/ebayinboxsubject/detail', 'id' => $last_id])];
                        break;
                    default:
                        $response = ['status' => 'error', 'info' => 'type值错误。'];
                }
            } else {
                $response = ['status' => 'error', 'info' => 'inbox_id值错误。'];
            }
        } else {
            $response = ['status' => 'error', 'info' => 'inbox_id格式错误。'];
        }
        echo json_encode($response);
        \Yii::$app->end();
    }

    /**
     * 导出excel
     */
    public function actionToexcel()
    {
        $params = \Yii::$app->request->get();
        extract($params);
        $start_time = $params['start_time'];
        if (!isset($start_time) || empty($start_time)) {
            $this->_showMessage('未选择开始时间', false);
        }
        if (!isset($params['end_time']) || empty($params['end_time'])) {
            $end_time = date('Y-m-d H:i:s');
        }
        if (strtotime($end_time) < strtotime($start_time)) {
            $this->_showMessage('时间选择有误', false);
        }
        if (empty($params['account_id'])) {
            $account_id = array_column(UserAccount::find()->where(['platform_code' => Platform::PLATFORM_CODE_EB, 'user_id' => \Yii::$app->user->identity->id])->asArray()->all(), 'account_id');
            $account_id = implode(',', $account_id);
        }
        $sql = "SELECT `t2`.`account_name`, count(*) as counts FROM {{%ebay_inbox_subject}} as t LEFT JOIN {{%account}} as t2 ON `t2`.`id` = `t`.`account_id` WHERE `t`.`is_replied` = 0 AND `t`.`buyer_id` <> 'eBay' AND `t`.`receive_date` BETWEEN :start_time AND :endtime AND `t`.`account_id` IN (" . $account_id . ") GROUP BY `t`.`account_id`";

        $model = EbayInboxSubject::findBySql($sql, [':start_time' => $start_time, ':endtime' => $end_time])
            ->all();

        $columns = ['account_name', 'counts'];
        $headers = ['account_name' => '帐号名', 'counts' => '数量'];

        \moonland\phpexcel\Excel::widget([
            'models'  => $model,
            'mode'    => 'export',
            'columns' => $columns,
            'headers' => $headers,
        ]);
    }

    /**
     * 翻译客户回复的邮件信息
     * @author allen <2018-1-04>
     */
    public function actionTranslate()
    {
        $request          = Yii::$app->request->post();
        $sl               = trim($request['sl']);
        $tl               = trim($request['tl']);
        $returnLang       = isset($request['returnLang']) ? 1 : 0;
        $content          = trim($request['content']);
        $content          = VHelper::ContentConversion($content);
        $afterTranslation = GoogleTranslation::translate($content, $sl, $tl);
        if ($returnLang) {
            $arr = json_decode($afterTranslation, true);
            if (!empty($arr)) {
                $ranslationText = $arr[0];
                $ranslationText = VHelper::ReContentConversion($ranslationText);
                $ranslationCode = isset($arr[1]) ? VHelper::googleLangCode($arr[1]) : "";
            } else {
                $ranslationText = '';
                $ranslationCode = '';
            }

            $ranslationText   = str_replace('\"', '"', $ranslationText);
            $afterTranslation = [
                'googleCode' => isset($arr[1]) ? $arr[1] : "",
                'code'       => $ranslationCode,
                'text'       => html_entity_decode(str_replace('\n', '&#10;', $ranslationText))
            ];
        } else {
            $afterTranslation = str_replace('\"', '"', $afterTranslation);
            $afterTranslation = html_entity_decode(str_replace('\n', '&#10;', $afterTranslation));
        }
        echo json_encode($afterTranslation);
        die;
    }


    /**
     * 添加站内信备注操作功能
     * @author allen <2018-02-10>
     */
    public function actionOperationremark()
    {
        $arr     = ['status' => TRUE, 'info' => '操作成功!'];
        $request = Yii::$app->request->post();
        $id      = trim($request['id']);
        $remark  = trim($request['remark']);
        if (EbayInbox::updateAll(['remark' => $remark], 'id=:id', [':id' => $id]) === FALSE) {
            $arr = ['status' => FALSE, 'info' => '操作失败!'];
        }
        echo json_encode($arr);
        die;
    }

    /**
     * 获取ebay邮件提醒
     */
    public function actionGetebaymailnotify()
    {
        $params = Yii::$app->request->post();

        $query = EbayInboxSubject::find()
            ->alias('s')
            ->select('s.id, s.now_subject, s.buyer_id, s.item_id, s.is_read, s.is_replied, t.tag_name')
            ->distinct()
            ->leftJoin(['m' => MailSubjectTag::tableName()], 'm.subject_id = s.id AND m.platform_code = :platform_code1', ['platform_code1' => Platform::PLATFORM_CODE_EB])
            ->leftJoin(['t' => Tag::tableName()], 't.id = m.tag_id AND t.platform_code = :platform_code2', ['platform_code2' => Platform::PLATFORM_CODE_EB])
            ->leftJoin(['r' => Rule::tableName()], 'r.relation_id = t.id AND r.platform_code = :platform_code3', ['platform_code3' => Platform::PLATFORM_CODE_EB])
            ->andWhere(['t.status' => 1])
            ->andWhere(['r.status' => 1, 'r.mail_notify' => 1])
            ->andWhere(['s.is_replied' => 0])
            ->andWhere(['s.buyer_id' => 'eBay'])
            ->orderBy('s.id DESC, s.high_priority ASC');

        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 's.receive_date', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 's.receive_date', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 's.receive_date', $params['end_time']]);
        }

        //获取当前客服绑定的账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);
        $query->andWhere(['in', 's.account_id', $accountIds]);

        $data = $query->asArray()->all();

        if (!empty($data)) {
            die(json_encode([
                'code'    => 1,
                'message' => '成功',
                'data'    => $data,
            ]));
        } else {
            die(json_encode([
                'code'    => 0,
                'message' => '失败',
            ]));
        }
    }

    /**
     * 导出ebay邮件内容
     */
    public function actionExportmailcontent()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        //获取get参数
        $get = YII::$app->request->get();
        //id数组
        $ids = !empty($get['ids']) ? $get['ids'] : [];
        //导出数据
        $data = [];

        //只能查询到客服绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);

        if (is_array($ids) && !empty($ids)) {
            $data = EbayInboxSubject::find()
                ->alias('i')
                ->select('i.*, a.account_name')
                ->leftJoin(['a' => Account::tableName()], 'a.id = i.account_id')
                ->andWhere(['in', 'i.id', $ids])
                ->andWhere(['in', 'i.account_id', $accountIds])
                ->andWhere(['i.buyer_id' => 'eBay'])
                ->orderBy('i.receive_date DESC')
                ->asArray()
                ->all();
        } else {
            $query = EbayInboxSubject::find()
                ->alias('i')
                ->select('i.*, a.account_name')
                ->leftJoin(['a' => Account::tableName()], 'a.id = i.account_id')
                ->andWhere(['in', 'i.account_id', $accountIds])
                ->andWhere(['i.buyer_id' => 'eBay'])
                ->orderBy('i.receive_date DESC');

            if (!empty($get['start_time']) && !empty($get['end_time'])) {
                $query->andWhere(['between', 'i.receive_date', $get['start_time'], $get['end_time']]);
            } else if (!empty($get['start_time'])) {
                $query->andWhere(['>=', 'i.receive_date', $get['start_time']]);
            } else if (!empty($get['end_time'])) {
                $query->andWhere(['<=', 'i.receive_date', $get['end_time']]);
            }

            $options = (new EbayInboxSubject())->filterOptions();
            if (!empty($options)) {
                foreach ($options as $option) {
                    if ($option['type'] == 'hidden' || $option['name'] == 'is_ebay') {
                        continue;
                    }

                    $field = !empty($option['name']) ? $option['name'] : '';
                    if (empty($field)) {
                        continue;
                    }

                    $value = array_key_exists($field, $get) ? $get[$field] : (isset($option['value']) ? $option['value'] : '');
                    if ($value == '') {
                        continue;
                    } else {
                        $value = trim($value);
                    }
                    $field  = 'i.' . $field;
                    $search = !empty($option['search']) ? $option['search'] : '=';
                    switch ($search) {
                        case '=':
                            $query->andWhere([$field => $value]);
                            break;
                        case 'LIKE':
                            $query->andWhere(['like', $field, $value . '%', false]);
                            break;
                        case 'FULL LIKE':
                            $query->andWhere(['like', $field, $value]);
                            break;
                    }
                }
            }

            $data = $query->asArray()->all();
        }

        //标题数组
        $fieldArr = [
            '帐号',
            '刊登id',
            '邮件主题',
            '收件时间',
        ];
        //导出数据数组
        $dataArr = [];

        //扩展的列数
        $extColumnNum = 1;

        if (!empty($data)) {
            foreach ($data as $item) {
                $line = [
                    $item['account_name'],
                    $item['item_id'],
                    VHelper::removeEmoji($item['now_subject']),
                    $item['receive_date'],
                ];

                $contents = EbayInbox::find()
                    ->select('id, new_message')
                    ->andWhere(['inbox_subject_id' => $item['id']])
                    ->orderBy('receive_date DESC')
                    ->asArray()
                    ->all();

                if (count($contents) > $extColumnNum) {
                    $extColumnNum = count($contents);
                }

                if (!empty($contents)) {
                    foreach ($contents as $content) {
                        $message = $content['new_message'];
                        if (empty($message)) {
                            $message = EbayInbox::getMongoNewMessage($content['id']);
                        }
                        //删除内容中的html标签，css样式
                        $message = VHelper::removeTags($message);
                        $message = ltrim($message, '*{}-@[]/ ');

                        $line[] = $message;
                    }
                }

                $dataArr[] = $line;
            }
        }

        if (!empty($extColumnNum)) {
            for ($ix = 1; $ix <= $extColumnNum; $ix++) {
                $fieldArr[] = "邮件内容{$ix}";
            }
        }

        VHelper::exportExcel($fieldArr, $dataArr, 'ebaymailcontent_' . date('Y-m-d'));
    }
}
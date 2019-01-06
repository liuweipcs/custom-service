<?php

namespace app\modules\mails\controllers;

use app\components\Controller;
use app\modules\mails\models\AliexpressInbox;
use app\modules\mails\models\AliexpressReply;
use app\modules\mails\models\MailTemplate;
use app\modules\orders\models\OrderKefu;
use app\common\VHelper;
use app\modules\orders\models\Order;
use app\modules\products\models\ProductAliexpress;
use app\modules\products\models\ProductAliexpressDetail;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\Country;
use yii\helpers\Json;
use app\modules\mails\models\MailTag;
use app\modules\systems\models\Tag;
use app\modules\mails\models\AliexpressDisputeList;
use app\modules\mails\models\AliexpressExpression;
use app\modules\accounts\models\Account;
use Yii;
use app\modules\services\modules\aliexpress\models\AliexpressMessage;
use app\modules\systems\models\Keyboard;
use app\modules\orders\models\OrderAliexpressKefu;
use app\modules\orders\models\Logistic;

class AliexpressController extends Controller
{
    private $platform = 'ALI';

    /**
     * 列表
     */
    public function actionIndex()
    {
        $params = Yii::$app->request->getBodyParams();
        $model = new AliexpressInbox();
        $dataProvider = $model->searchList($params);
        $tagList = AliexpressInbox::getTagsList();
        return $this->renderList('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'tagList' => $tagList,
        ]);
    }

    /*
     * 退款、退货
     * */
    public function actionRefund()
    {
        if (!empty($_REQUEST['save'])) {
            $platform = $_REQUEST['platform_code'];
            //$data = json_encode($_REQUEST);
            /*            $string = "token=5E17C4488C2AC591&data={$data}";
                        $retuelt = VHelper::setRefund($string,$platform); */
            $identity = \Yii::$app->user->getIdentity();
            $username = $identity->login_name;
            $_REQUEST['create_user'] = $username;
            $retuelt = Order::setOrderRefund($platform, $_REQUEST);
            if (!empty($retuelt) && $retuelt == 'OK') {
                $this->_showMessage('保存成功！', true, null, false, null);
            } else {
                $this->_showMessage('保存失败！', false);
            }
        } else {
            $order_id = $this->request->getQueryParam('order_id');
            $platform = $this->request->getQueryParam('platform');
            $this->isPopup = true;
            $info = [];
            if ($platform && $order_id) {
                $orderinfo = Order::getOrderStack($platform, $order_id);
                if (!empty($orderinfo)) {
                    $orderinfo = Json::decode(Json::encode($orderinfo), true);
                    $info = $orderinfo;
                }
            }
        }
        return $this->render('refund', [
            'order_id' => $order_id,
            'platform' => $platform,
            'info' => $info
        ]);
    }

    /**
     * 订单详情
     */
    public function actionOrder()
    {
        $this->isPopup = true;
        $order_id = $this->request->getQueryParam('order_id');
        $platform = $this->request->getQueryParam('platform');
        $orderinfo = [];
        if ($platform && $order_id) {
            $orderinfo = Order::getOrderStack($platform, $order_id);
            if (!empty($orderinfo))
                $orderinfo = Json::decode(Json::encode($orderinfo), true);
            else
                $orderinfo = [];
        }

        //组装库存和在途数
        if (!empty($orderinfo)) {
            if (!empty($orderinfo['product'])) {
                $result = isset($orderinfo['wareh_logistics']) && isset($orderinfo['wareh_logistics']['warehouse']);
                foreach ($orderinfo['product'] as $key => $value) {
                    list($stock, $on_way_count) = [null, null];
                    if ($result) {
                        $data = VHelper::getProductStockAndOnCount($value['sku'], $orderinfo['wareh_logistics']['warehouse']['warehouse_code']);
                        $stock = $data['available_stock'];
                        $on_way_count = $data['on_way_stock'];
                    }
                    $orderinfo['product'][$key]['stock'] = $stock;
                    $orderinfo['product'][$key]['on_way_stock'] = $on_way_count;
                }
            }
        }
        return $this->render('order', [
            'order_id' => $order_id,
            'info' => $orderinfo
        ]);
    }

    /**
     * 消息详情
     * @return string
     */
    public function actionDetails()
    {
        set_time_limit(0);
        $platOrderId = $this->request->getQueryParam('platform_order_id');
        $id = $this->request->getQueryParam('id');
        $model = new AliexpressInbox();
        $reply = new AliexpressReply();
        if (!empty($platOrderId)) {
            $pid = $reply->getInboxIdByPlatOrderId($platOrderId);
            if (!empty($pid)) {
                $id = $pid;
            } else {
                echo '未找到该订单信息';
                exit;
            }
        }
        $orderlist = [];
        if (empty($id)) {
            AliexpressInbox::destroyProcessedList();
            $this->_showMessage('没有未处理的消息了', false);
        }
        $nextInboxId = $model->getNextInboxId($id);
        $model = $model->getOne($id);
        if (empty($model))
            $this->_showMessage(\Yii::t('system', 'Invalid Param'), false);
        AliexpressInbox::pushProccessedList($id);
        $expressionModel = new AliexpressExpression();
        $replyList = $reply->getReplyList($model->account_id, $model->channel_id); 
        //查询订单 产品sku
        if ((!empty($_GET['test'])) && $_GET['test'] == 'list1') {
            VHelper::dump($replyList);
            exit;
        }
        if ($replyList['orderIds'] && (!$replyList['productIds'])) {
            $orderIds = $replyList['orderIds'];
            $orderIds = max($orderIds);
            $platform = 'ALI';
            $orderinfo = OrderKefu::getOrderStack($platform, $orderIds, $platOrderId);
            if (!empty($orderinfo)) {
                $orderinfo = Json::decode(Json::encode($orderinfo), true);
                if (isset($orderinfo['product']))
                    $sku = array_column($orderinfo['product'], 'sku');
                else
                    $sku = null;
            } else {
                $sku = null;
            }
            $replyList['sku'] = $sku ? $sku : '暂无sku请联系管理员';
        } elseif ($replyList['productIds'] && (!$replyList['orderIds'])) {
         /**   $productIds = max($replyList['productIds']);
            $productInfo = ProductAliexpressDetail::find()->select('sku,aeop_ae_product_skus')->where(['product_id' => $productIds])->asArray()->all();
            $sku = array_column($productInfo, 'sku');
            if (in_array('AU', $sku) || !isset($sku)) {
                $sku = Json::decode($productInfo[0]['aeop_ae_product_skus']);
                $sku = array_column($sku, 'sku_code');
                $sku = array_flip(array_flip($sku));
            }
            $replyList['sku'] = !empty($sku[0]) ? $sku : '暂无sku请联系管理员'; 
          * 
          */
            $replyList['sku'] = 1;
        } elseif ($replyList['productIds'] && $replyList['orderIds']) {
            $productIds = $replyList['productIds'];
            $productIds = array_flip(array_flip($productIds));
            $productIds = implode('', $productIds);
            $productInfo = ProductAliexpressDetail::find()->select('sku,aeop_ae_product_skus')->where(['product_id' => $productIds])->asArray()->all();    
            $sku = array_column($productInfo, 'sku');
          
            if (in_array('AU', $sku) || !isset($sku)) {
                $sku = Json::decode($productInfo[0]['aeop_ae_product_skus']);
                $sku = array_column($sku, 'sku_code');
                $sku = array_flip(array_flip($sku));
            }
            $replyList['sku'] = $sku ? $sku : '暂无sku请联系管理员';
        } elseif (!$replyList['productIds'] && !$replyList['orderIds']) {
            $replyList['sku'] = '因无订单号也无产品编号，请联系管理员';
        }
            
        $currentOrderId = null;
        if (!empty($replyList['orderIds']))
            $currentOrderId = max($replyList['orderIds']);
        $expressionList = $expressionModel->getList();
        /*站内信/订单留言更新已读 */
        if ($model->read_stat == 0 || $model->read_stat == 2) {
            $aliexpressMessage = new AliexpressMessage();
            $flag = $aliexpressMessage->markMessageBeenRead($model->account_id, $model->channel_id);
        }
        /*站内信/普通(member类型)留言增加备注 */
        if(!empty($replyList['list'][0]['reply_from'])) {
            $memberRemark = $reply->getRemark($id);
        }else{
            $memberRemark = [];
        }

        // 获取已标记标签
        $inbox_id = (int)$this->request->getQueryParam('id');
        $platform_code = Platform::PLATFORM_CODE_ALI;

        $tags_data = MailTag::get_tags_by_platformcode_and_inbox($platform_code, $inbox_id);

        //查询组合快捷键
        $keyboards = json_encode(Keyboard::getKeyboardsAsArray(Platform::PLATFORM_CODE_ALI, \Yii::$app->user->identity->id));
        $accountM = Account::findById($model->account_id);
        $accountName = '';
        if (!empty($accountM)) {
            $accountName = $accountM->account_short_name;
        }
        $orderModel = new OrderAliexpressKefu();
        $googleLangCode = VHelper::googleLangCode();
        if($replyList['orderIds']){
                $orderIds = $replyList['orderIds'];
                $orderIds = max($orderIds);
                $platform = 'ALI';
                $orderlist = OrderKefu::getOrderStack($platform, $orderIds, $platOrderId);
            }

        return $this->render('details', [
            'model' => $model,
            'id' => $id,
            'accountName' => $accountName,
            'replyList' => $replyList['list'],
            'sku' => $replyList['sku'],
            'next' => $nextInboxId,
            'keyboards' => $keyboards,
            'expressionList' => $expressionList,
            'tags_data' => $tags_data,
            'currentOrderId' => $currentOrderId,
            'googleLangCode' => $googleLangCode,
            'orderModel' => $orderModel,
            'order_info' => $orderlist,
            'memberRemark'=>$memberRemark
        ]);
    }

    /**
     * 批量标记回复
     */

    public function actionSignreplied()
    {
        $ids   = $this->request->post('ids');
        $count = 0;
        foreach ($ids as $id) {
            $subject_model = AliexpressInbox::findOne($id);
            if($subject_model->read_stat != 1){
                $subject_model->read_stat   = 2;
            }
            $subject_model->is_replied = 3;
            if ($subject_model->save())
                $count++;
        }

        if ($count > 0)
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, 'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/aliexpress/index') . '");', false);
        else
            $this->_showMessage('操作失败', false);

    }

    /**
     * 获取邮件模板
     */
    public function actionGetmailtemplatelist()
    {
        $categoryId = Yii::$app->request->post('category_id', 0);

        $data = MailTemplate::getMyMailTemplate(Platform::PLATFORM_CODE_ALI, $categoryId);
        if (empty($data)) {
            die(json_encode([
                'code' => 0,
                'message' => '该分类邮件模板为空',
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
     * 批量回复邮件
     */
    public function actionBatchreply()
    {
        $model = new AliexpressInbox();
        $this->isPopup = true;
        if ($this->request->getIsPost()) {
            $reply = new AliexpressReply();
            //获取account_id
            $ids = $_REQUEST['ids'];
            $ids_arr = explode(',', $ids);
            $content = $_REQUEST['content'];
            $content_en = $_REQUEST['content_en'];
            $imgPath = isset($_REQUEST['imgPath']) ? $_REQUEST['imgPath'] : '';
            $reply_data = [];
            foreach ($ids_arr as $k => &$id) {
                $model = $model->getOne($id);
                if (empty($model))
                    $this->_showMessage(\Yii::t('system', 'Invalid Param'), false);
                AliexpressInbox::pushProccessedList($id);
                $replyInfo = $reply->getReplyList($model->account_id, $model->channel_id);
                if ($replyInfo['orderIds']) {
                    $orderIds = $replyInfo['orderIds'];
                    $orderIds = max($orderIds);
                    $platform = 'ALI';
                    $platOrderId = '';
                    $order_info = OrderKefu::getOrderStack($platform, $orderIds, $platOrderId);
                    if ($order_info) {
                        $countryList = Country::getCodeNamePairsList('en_name');

                        if ($order_info['info']['real_ship_code']) {
                            $logistic = Logistic:: getSendWayEng($order_info['info']['real_ship_code']);
                            if (empty($logistic)) {
                                $logistic = Logistic:: getSendWayEng($order_info['info']['ship_code']);
                            }
                        } else {
                            $logistic = '';
                        }
                        if ($order_info['info']['track_number']) {
                            $track = 'http://www.17track.net/zh-cn/track?nums=' . $order_info['info']['track_number'];
                            $track_number = $order_info['info']['track_number'];
                        } else {
                            $track = '';
                            $track_number = '';
                        }

                        if ($order_info['info']['ship_country']) {
                            $country = $order_info['info']['ship_country'];
                            $ship_country = array_key_exists($country, $countryList) ? $countryList[$country] : '';
                        } else {
                            $ship_country = '';
                        }
                    } else {
                        $track_number = '';
                        $logistic = '';
                        $track = '';
                        $ship_country = '';
                    }

                } else {
                    $track_number = '';
                    $logistic = '';
                    $track = '';
                    $ship_country = '';
                }
                $content = str_replace('{buyer_id}', $model->other_name, $content);
                $content = str_replace('{track_number}', $track_number, $content);
                $content = str_replace('{logistic}', $logistic, $content);
                $content = str_replace('{track}', $track, $content);
                $content = str_replace('{ship_country}', $ship_country, $content);
                $content_en = str_replace('{buyer_id}', $model->other_name, $content);
                $content_en = str_replace('{track_number}', $track_number, $content);
                $content_en = str_replace('{logistic}', $logistic, $content);
                $content_en = str_replace('{track}', $track, $content);
                $content_en = str_replace('{ship_country}', $ship_country, $content);
                $replyList = $replyInfo['list'];
                if (!empty($replyList)) {
                    foreach ($replyList as $one_reply) {
                        if (empty($one_reply[0])) {
                            $type_id = $one_reply['type_id'];
                            $message_type = $one_reply['message_type'];
                        } else {
                            $type_id = $one_reply[0]['type_id'];
                            $message_type = $one_reply[0]['message_type'];
                        }

                    }
                }
                $reply_data['content'] = $content;
                $reply_data['content_en'] = $content_en;
                $reply_data['account_id'] = $model->account_id;
                $reply_data['channel_id'] = $model->channel_id;
                $reply_data['type_id'] = $type_id;
                $reply_data['message_type'] = $message_type;
                $reply_data['imgPath'] = $imgPath;
                $Expression = new AliexpressExpression();
                $reply_data['content'] = $Expression->replyContentReplace($content);
                //如果翻译后内容为空 则保存翻译前的内容
                if (empty($reply_data['content_en'])) {
                    $reply_data['content_en'] = $content;
                } else {
                    $reply_data['content_en'] = $Expression->replyContentReplace($content_en);
                }
                $flag = $reply->getAdd($reply_data);
            }
            if ($flag) {
                $data = [
                    'reply_content' => $Expression->queryExpression($reply->reply_content),
                    'create_by' => $reply->create_by,
                    'create_time' => $reply->create_time,
                    'imgPath' => isset($imgPath) ? $imgPath . '_350x350.jpg' : '',
                ];
                $this->_showMessage('回复成功', true, null, false, $data);
            } else
                $this->_showMessage('回复失败！', false);
        }
        $ids = $_REQUEST['ids'];
        $id_arr = explode(',', $ids);
        //$account_ids
        $account_ids = '';
        $data = $model->getListById($id_arr);
        $expressionModel = new AliexpressExpression();
        $expressionList = $expressionModel->getList();
        $buyer_names = [];//账号名称
        if (!empty($data)) {
            foreach ($data as $k => &$value) {
                $buyer_names[$k]['buyer_name'] = $value['other_name'];
                $buyer_names[$k]['account_id'] = $value['account_id'];
                $buyer_names[$k]['channel_id'] = $value['channel_id'];
                $account_ids .= $value['account_id'] . ',';
            }
        }
        $account_ids = rtrim($account_ids, ',');
        $googleLangCode = VHelper::googleLangCode();
        return $this->render('batchreply', [
            'expressionList' => $expressionList,
            'ids' => $ids,
            'googleLangCode' => $googleLangCode,
            'buyer_names' => $buyer_names,
            'account_ids' => $account_ids
        ]);
    }


    /*批量标记已处理*/
    public function actionBatchprocessing()
    {
        $model = new AliexpressInbox();
        $aliexpressMessage = new AliexpressMessage();
        $id = (int)$this->request->getQueryParam('id');
        $ids = [$id];

        if (!$id) {
            $ids = $_REQUEST['ids'];
        }

        $data = $model->getListById($ids);
        if (!empty($data)) {
            foreach ($data as $value) {
                //更新消息为已读
                $aliexpressMessage->markMessageBeenRead($value['account_id'], $value['channel_id']);

                //更新消息为已处理
                $retuelt = $aliexpressMessage->updateMessageProcessingState($value['account_id'], $value['channel_id'], 1);
            }
        }

        if (!$retuelt) {
            $this->_showMessage('操作失败！', false);
        }

        $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/aliexpress/index') . '");';
        $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, $refreshUrl);
    }

    /*批量标记未处理*/
    public function actionUntreated()
    {
        $model = new AliexpressInbox();
        $aliexpressMessage = new AliexpressMessage();
        $id = (int)$this->request->getQueryParam('id');
        if ($id) {
            $ids = [$id];
        } else {
            $ids = $_REQUEST['ids'];
        }
        $data = $model->getListById($ids);
        if (!empty($data)) {
            foreach ($data as $value) {
                $retuelt = $aliexpressMessage->updateMessageProcessingState($value['account_id'],
                    $value['channel_id'], 1);
            }
        }
        if ($retuelt) {
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/aliexpress/index') . '");');
        } else {
            $this->_showMessage('操作失败！', false);
        }
    }

    /*
     *标记未处理状态
     **/
    public function actionMarkerreply()
    {
        $data = \Yii::$app->request->getBodyParams();

//        $orderObj = new UpdateMsgProcessed();
//        $retuelt = $orderObj->getProcessed($data['account_id'],$data['channel_id'],$data['deal_stat']);

        //直接更改数据库字段
        $box = new AliexpressInbox;
        $retuelt = $box->updateDealstatById($data['id']);

        if ($retuelt) {
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null);
        } else {
            $this->_showMessage('标记失败！', false);
        }
    }

    /*标记已读*/
    public function actionReadstat()
    {
        $aliexpressMessage = new AliexpressMessage();
        $data = \Yii::$app->request->getBodyParams();
        $retuelt = $aliexpressMessage->markMessageBeenRead($data['account_id'], $data['channel_id']);
        if ($retuelt) {
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null);
        } else {
            $this->_showMessage('标记失败！', false);
        }
    }

    /*
     *回复信息
     **/
    public function actionReply()
    {
        $data = \Yii::$app->request->getBodyParams();
        $id = intval($data['id']);
        $model = new AliexpressInbox();
        $nextInboxId = $model->getNextInboxId($id);

        $Expression = new AliexpressExpression();
        $reply = new AliexpressReply();
        $data['content'] = $Expression->replyContentReplace($data['content']);
        if (!isset($data['message_type']) || empty($data['message_type']))
            $this->_showMessage('必须选择一个进行回复', false);
        //如果翻译后内容为空 则保存翻译前的内容
        if (empty($data['content_en'])) {
            $data['content_en'] = $data['content'];
        } else {
            $data['content_en'] = $Expression->replyContentReplace($data['content_en']);
        }
        $flag = $reply->getAdd($data);
        if ($flag) {
            $data = [
                'reply_content' => $Expression->queryExpression($reply->reply_content),
                'create_by' => $reply->create_by,
                'create_time' => $reply->create_time,
                'imgPath' => isset($data['imgPath']) ? $data['imgPath'] . '_350x350.jpg' : '',
            ];
            $this->_showMessage('回复成功', true, '/mails/aliexpress/details?id=' . $nextInboxId, false, $data);
        } else
            $this->_showMessage('回复失败！', false);
    }

    /**
     * 快捷键批量或者单条添加或删除消息标签
     */
    public function actionAddretags()
    {
        if ($this->request->getIsAjax()) {

            $platform_code = Platform::PLATFORM_CODE_ALI;
            $post_data = $this->request->post();
            //判断按键下的标签是否存在
            $tag_data = MailTag::get_tag_by_platformcode_and_subject($platform_code, $post_data['MailTag']['inbox_id'], $post_data['MailTag']['tag_id']);

            if ($tag_data) {
                //标签存在就删除
                $result = MailTag::delete_mail_tag($platform_code, $post_data['MailTag']['inbox_id'], $post_data['MailTag']['tag_id']);

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

                list($result, $message) = MailTag::batch_save_mail_tags($platform_code, $post_data['MailTag']['tag_id'], $inbox_ids);
                //获取打上标签数据
                $tags_data = MailTag::get_tags_by_platformcode_and_inbox($platform_code, $inbox_ids);
                if (!$result) {
                    $this->_showMessage($message, false);
                }
                /*  //成功后跳转的url
                  $url = $this->get_loation_url($post_data);*/

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
        $model = new MailTag();
        $inbox_ids = $this->request->getQueryParam('ids');
        $type = $this->request->getQueryParam('type');

        //根据平台code获取标签数据
        $platform_code = Platform::PLATFORM_CODE_ALI;
        //所有该平台下的标签数据
        $tags_data = Tag::getTagAsArray($platform_code);

        //该平台该消息已经有的标签数据
        $exist_data = MailTag::get_tag_ids_by_platformcode_and_inbox($platform_code, explode(',', $inbox_ids));

        if ($this->request->getIsAjax()) {

            $post_data = $this->request->post();

            //没有勾选标签
            if (empty($post_data['MailTag']['tag_id'])) {
                $this->_showMessage(\Yii::t('system', 'no tag Data'), false);
            }

            $this->save_mail_tag($post_data, $platform_code);
        }

        return $this->render('tags', [
            'model' => $model,
            'inbox_ids' => $inbox_ids,
            'tags_data' => $tags_data,
            'exist_data' => $exist_data,
            'type' => $type
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
        list($result, $message) = MailTag::batch_save_mail_tags($platform_code, $post_data['MailTag']['tag_id'], $inbox_ids);

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
                $url = \yii\helpers\Url::toRoute('/mails/aliexpress/index');
                break;
            case 'detail':
                $url = \yii\helpers\Url::toRoute(['/mails/aliexpress/details', 'id' => $post_data['MailTag']['inbox_id']]);
                break;
            default:
                $url = \yii\helpers\Url::toRoute('/mails/aliexpress/index');
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
        $model = new MailTag();
        $inbox_id = (int)$this->request->getQueryParam('id');
        $type = $this->request->getQueryParam('type');
        $platform_code = Platform::PLATFORM_CODE_ALI;

        $tags_data = MailTag::get_tags_by_platformcode_and_inbox($platform_code, $inbox_id);

        if ($this->request->getIsAjax()) {
            $post_data = $this->request->post();
            //没有勾选标签
            if (empty($post_data['MailTag']['tag_id'])) {
                $this->_showMessage(\Yii::t('system', 'no tag Data'), false);
            }
            $result = MailTag::delete_mail_tag($platform_code, $post_data['MailTag']['inbox_id'], $post_data['MailTag']['tag_id']);

            if (!$result) {
                $this->_showMessage(\Yii::t('system', 'operation fail'), false);
            }
            //成功后跳转的url
            $url = $this->get_loation_url($post_data);

            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, $url, false, null, null);

        }
        return $this->render('tags', [
            'model' => $model,
            'inbox_ids' => $inbox_id,
            'tags_data' => $tags_data,
            'exist_data' => array(),
            'type' => $type
        ]);
    }

    public function actionShowordercopy()
    {
        $this->isPopup = true;
        $order_id = $this->request->getQueryParam('order_id');
        $model = new AliexpressDisputeList();
        $dispute_detail = $model->getdisputedetail($order_id);
        $dispute_negotiate = $model->getdisputenegotiate($order_id);
        return $this->render('showorder',
            [
                'order_id' => $order_id,
                'dispute_detail' => $dispute_detail,
                'dispute_negotiate' => $dispute_negotiate
            ]
        );
    }

    /*图片上传*/
    public function actionUploadpictures()
    {
        if (!empty($_FILES['upload_file'])) {
            $account_id = $_REQUEST['account_id'];
            //$fp = fopen($_FILES['upload_file']['tmp_name'],'rb');
            //$fileData = base64_encode(fread($fp,$_FILES['upload_file']['size']));
            $aliexpressMessage = new AliexpressMessage();
            $result = $aliexpressMessage->uploadImage($account_id, $_FILES['upload_file']['name'],
                $_FILES['upload_file']['tmp_name']);
            //$picturesModel = new UploadPictures();
            //$result = $picturesModel->queryAccount($account_id,$_FILES['upload_file']['name'],$fileData);
            if ($result) {
                $this->_showMessage('上传成功', true, null, false, $result);
            } else {
                $this->_showMessage('图片上传失败', false);
            }
        } else {
            $this->_showMessage('图片上传失败', false);
        }
    }

    /**
     * 多个账号id上传图片
     */
    public function actionUploadpicture()
    {
        if (!empty($_FILES['upload_file'])) {
            $account_ids = $_REQUEST['account_ids'];
            $account_ids = explode(',', $account_ids);
            $account_ids = array_unique($account_ids);
            foreach ($account_ids as $account_id) {
                $aliexpressMessage = new AliexpressMessage();
                $result = $aliexpressMessage->uploadImage($account_id, $_FILES['upload_file']['name'],
                    $_FILES['upload_file']['tmp_name']);
                if ($result) {
                    $this->_showMessage('上传成功', true, null, false, $result);
                } else {
                    $this->_showMessage('图片上传失败', false);
                }
            }

        } else {
            $this->_showMessage('图片上传失败', false);
        }
    }


    /**
     * 添加站内信备注操作功能
     * @author huwenjun <2018-05-22>
     */
    public function actionOperationremark()
    {
        $arr = ['status' => TRUE, 'info' => '操作成功!'];
        $request = Yii::$app->request->post();
        $id = trim($request['id']);
        $remark = trim($request['remark']);
        if (AliexpressReply::updateAll(['remark' => $remark], 'id=:id', [':id' => $id]) === FALSE) {
            $arr = ['status' => FALSE, 'info' => '操作失败!'];
        }
        echo json_encode($arr);
        die;
    }
}

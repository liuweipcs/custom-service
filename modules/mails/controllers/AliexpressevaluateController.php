<?php

namespace app\modules\mails\controllers;

use app\modules\mails\models\FeedbackTemplate;
use app\modules\services\modules\aliexpress\models\AliexpressOrder;
use Yii;
use app\common\VHelper;
use app\modules\accounts\models\Account;
use yii\helpers\Url;
use app\components\Controller;
use app\modules\accounts\models\UserAccount;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\AliexpressEvaluateList;
use app\modules\orders\models\OrderAliexpressSearch;
use app\modules\mails\models\AliexpressDisputeList;
use app\components\GoogleTranslation;

class AliexpressevaluateController extends Controller
{
    /**
     * 速卖通评价列表
     */
    public function actionIndex()
    {
        $params = Yii::$app->request->get();
        if (empty($params)) {
            $params = Yii::$app->request->getBodyParams();
        }

        $model = new AliexpressEvaluateList();

        $dataProvider = $model->searchList($params);
        $commentTypeNum = $model->countCommentTypeNum($params);

        return $this->renderList('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'commentTypeNum' => $commentTypeNum,
        ]);
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

        $result = AliexpressEvaluateList::updateAll(['reply_status' => 2], ['in', 'id', $ids]);
        if ($result !== false) {
            $extraJs = "$(\"input[name='id']:checked\").each(function() {
                            var tr = $(this).parent('td').parent('tr');
                            tr.find(\"span:contains('已超时')\").text('-').removeAttr('style');
                            tr.find(\"span:contains('否'),span:contains('是')\").text('标记回复').removeAttr('style');
                        });";
            $this->_showMessage('标记为已回复成功', true, null, false, null, $extraJs);
        } else {
            $this->_showMessage('标记为已回复失败', false);
        }
    }

    /**
     * 刷新统计评价类型数量
     */
    public function actionFlushcountcommenttypenum()
    {
        $params = Yii::$app->request->getBodyParams();
        $model = new AliexpressEvaluateList();
        $commentTypeNum = $model->countCommentTypeNum($params);

        die(json_encode([
            'code' => 1,
            'message' => '成功',
            'data' => $commentTypeNum,
        ]));
    }

    /**
     * 回复评价
     */
    public function actionReplyfeedback()
    {
        if (Yii::$app->request->isPost) {

            $id = Yii::$app->request->post('id', 0);
            $seller_reply = Yii::$app->request->post('seller_reply', '');

            if (empty($id)) {
                $this->_showMessage('ID不能为空', false);
            }

            if (empty($seller_reply)) {
                $this->_showMessage('回复内容不能为空', false);
            }

            $info = AliexpressEvaluateList::findOne($id);

            if (empty($info)) {
                $this->_showMessage('没有找到评价信息', false);
            }
            if ($info->reply_status == 1) {
                $this->_showMessage('该评价已回复', false);
            }

            $account = Account::findOne($info['account_id']);
            if (empty($account)) {
                $this->_showMessage('没有找到账号信息', false);
            }


            $result = AliexpressOrder::replyFeedback($info['platform_order_id'], $info['platform_parent_order_id'], $account->old_account_id, $seller_reply);
            if ($result === true) {
                $info->seller_reply = $seller_reply;
                $info->reply_status = 1;
                $info->save();
                //自动跳转到下一封
                $extraJs = 'location.href="' . Url::toRoute(['/mails/aliexpressevaluate/replyfeedback', 'account_id' => $info['account_id'], 'next' => '1']) . '"';
                $this->_showMessage('回复成功', true, null, false, null, $extraJs, true, 'msg');
            } else {
                $this->_showMessage($result, false);
            }

        } else {
            $id = Yii::$app->request->get('id');
            $next = Yii::$app->request->get('next', 0);
            $accountId = Yii::$app->request->get('account_id');

            if (empty($id)) {
                if (empty($next)) {
                    $this->_showMessage('ID不能为空', false);
                } else {
                    //只能查询到客服绑定账号的评价
                    $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_ALI);
                    $query = AliexpressEvaluateList::find()
                        ->select('id')
                        ->andWhere(['reply_status' => 0])
                        ->andWhere(['seller_reply' => ''])
                        ->andWhere(['in', 'buyer_evaluation', [1, 2, 3]])
                        ->andWhere(['in', 'account_id', $accountIds])
                        ->orderBy('buyer_evaluation ASC, gmt_create ASC');

                    if (!empty($accountId)) {
                        $query->andWhere(['account_id' => $accountId]);
                    }

                    $id = $query->limit(1)->scalar();

                    if (empty($id)) {
                        $message = '已没有需要回复的评价，请重新设置筛选条件！！！';
                        $account = Account::findOne($accountId);
                        if (!empty($account)) {
                            $message = "该{$account->account_name}账号下," . $message;
                        }
                        $extraJs = 'top.layer.closeAll("iframe");';
                        $this->_showMessage($message, true, null, false, null, $extraJs, true, 'msg');
                    }
                }
            }

            $info = AliexpressEvaluateList::findOne($id);
            if (empty($info)) {
                $this->_showMessage('没有找到评价信息', false);
            }

            $this->isPopup = true;

            //获取回评模板
            $replyContent = FeedbackTemplate::find()->where(['platform_code' => Platform::PLATFORM_CODE_ALI])->asArray()->all();

            if (!empty($replyContent)) {
                $tmp = [' ' => '全部'];
                foreach ($replyContent as $item) {
                    if (mb_strlen(strip_tags(trim($item['template_content'])), 'UTF-8') > 160) {
                        $tmp[$item['id']] = mb_substr(strip_tags(trim($item['template_content'])), 0, 160, 'UTF-8') . '...';
                    } else {
                        $tmp[$item['id']] = strip_tags(trim($item['template_content']));
                    }
                }
                $replyContent = $tmp;
            }

            return $this->render('replyfeedback', [
                'info' => $info,
                'replyContent' => $replyContent,
            ]);
        }
    }

    /**
     * 评价
     */
    public function actionFeedback()
    {
        if (Yii::$app->request->isPost) {
            $id = Yii::$app->request->post('id', 0);
            $seller_evaluation = Yii::$app->request->post('seller_evaluation', 0);
            $seller_feedback = Yii::$app->request->post('seller_feedback', '');

            if (empty($id)) {
                $this->_showMessage('ID不能为空', false);
            }
            if (empty($seller_evaluation)) {
                $this->_showMessage('评价星级不能为0', false);
            }
            if (empty($seller_feedback)) {
                $this->_showMessage('评价内容不能为空', false);
            }

            $info = AliexpressEvaluateList::findOne($id);
            if (empty($info)) {
                $this->_showMessage('没有找到评价信息', false);
            }
            if (!empty($info->seller_evaluation) && !empty($info->seller_feedback)) {
                $this->_showMessage('已经评价过了', false);
            }

            $account = Account::findOne($info['account_id']);
            if (empty($account)) {
                $this->_showMessage('没有找到账号信息', false);
            }

            $session = Yii::$app->session;
            $session_info = $session->get('info');
            if(!empty($session_info)){
                $o = "";
                foreach ( $session_info as $k => $v )
                {
                    $o.= "$k=" . $v . "&" ;
                }
            }

            $post_data = substr($o,0,-1);

            $result = AliexpressOrder::addFeedback($info['platform_order_id'], $account->old_account_id, $seller_evaluation, $seller_feedback);
            if ($result === true) {
                $info->seller_evaluation = $seller_evaluation;
                $info->seller_feedback = $seller_feedback;
                $info->save();
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/mails/aliexpressevaluate/index?'.$post_data) . '");';
                $this->_showMessage('评价成功', true, null, false, null, $extraJs, true, 'msg');
            } else {
                $this->_showMessage($result, false);
            }

        } else {
            $id = Yii::$app->request->get('id');

            if (empty($id)) {
                $this->_showMessage('ID不能为空', false);
            }

            $info = AliexpressEvaluateList::findOne($id);
            if (empty($info)) {
                $this->_showMessage('没有找到评价信息', false);
            }

            $this->isPopup = true;

            return $this->render('feedback', [
                'info' => $info,
            ]);
        }
    }

    /**
     * 随机获取模板内容
     */
    public function actionRandgetreplycontent()
    {
        $id = Yii::$app->request->post('id', 0);

        if (empty($id)) {
            die(json_encode([
                'code' => 0,
                'message' => '回评模板ID不能为空',
            ]));
        }

        $template = FeedbackTemplate::findOne($id);
        die(json_encode([
            'code' => 1,
            'message' => '成功',
            'data' => !empty($template['template_content']) ? $template['template_content'] : '',
        ]));
    }

    /**
     * 标记为已回复
     */
    public function actionMark()
    {
        $id = Yii::$app->request->get('id', 0);

        if (empty($id)) {
            $this->_showMessage('请选中标记项', false);
        }

        $session = Yii::$app->session;
        $session_info = $session->get('info');
        if(!empty($session_info)){
            $o = "";
            foreach ( $session_info as $k => $v )
            {
                $o.= "$k=" . $v . "&" ;
            }
        }

        $post_data = substr($o,0,-1);


        $evaluate = AliexpressEvaluateList::findOne($id);
        $evaluate->reply_status = 2;

        if ($evaluate->save()) {
            $this->_showMessage('标记为已回复成功', true, Url::toRoute('/mails/aliexpressevaluate/index?'.$post_data), true);
        } else {
            $this->_showMessage('标记为已回复失败', false);
        }
    }

    /**
     * 翻译评价
     */
    public function actionTranslate()
    {
        $sl = Yii::$app->request->post('sl', 'auto');
        $tl = Yii::$app->request->post('tl', 'en');
        $returnLang = Yii::$app->request->post('returnLang', 1);
        $content = Yii::$app->request->post('content', '');
        $content = VHelper::ContentConversion($content);
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
            $ranslationText = str_replace('\"', '"', $ranslationText);
            $afterTranslation = [
                'googleCode' => isset($arr[1]) ? $arr[1] : "",
                'code' => $ranslationCode,
                'text' => html_entity_decode(str_replace('\n', '&#10;', $ranslationText))
            ];
        } else {
            $afterTranslation = str_replace('\"', '"', $afterTranslation);
            $afterTranslation = html_entity_decode(str_replace('\n', '&#10;', $afterTranslation));
        }
        die(json_encode($afterTranslation));
    }

    /**
     * 导出Excel
     */
    public function actionExport()
    {
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        //获取get参数
        $get = YII::$app->request->get();
        //id数组
        $ids = !empty($get['ids']) ? $get['ids'] : [];
        //导出数据
        $data = [];

        //只能查询到客服绑定账号的纠纷
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_ALI);

        if (is_array($ids) && !empty($ids)) {
            //取出选中的评价数据
            $data = AliexpressEvaluateList::find()
                ->select('e.*, a.account_name')
                ->alias('e')
                ->leftJoin(['a' => Account::tableName()], 'a.id = e.account_id')
                ->andWhere(['in', 'e.id', $ids])
                ->andWhere(['in', 'e.account_id', $accountIds])
                ->orderBy('e.gmt_order_complete DESC')
                ->asArray()
                ->all();
        } else {
            //取出筛选的评价数据
            $query = AliexpressEvaluateList::find()
                ->select('e.*, a.account_name')
                ->alias('e')
                ->leftJoin(['a' => Account::tableName()], 'a.id = e.account_id')
                ->leftJoin(['d' => AliexpressDisputeList::tableName()], 'd.platform_order_id = e.platform_order_id')
                ->andWhere(['in', 'e.account_id', $accountIds]);

            //平台订单号
            if (!empty($get['platform_order_id'])) {
                $query->andWhere(['e.platform_order_id' => $get['platform_order_id']]);
            }
            //订单号
            if (!empty($get['order_id'])) {
                $platform_order_id = OrderAliexpressSearch::getPlatform($get['order_id']);
                if (!empty($platform_order_id)) {
                    $query->andWhere(['e.platform_order_id' => $platform_order_id]);
                }
            }
            //买家ID
            if (!empty($get['buyer_id'])) {
                $plat_order_id = OrderAliexpressSearch::getPlatOrderId($get['buyer_id']);
                if (!empty($plat_order_id)) {
                    $query->andWhere(['in', 'e.platform_order_id', $plat_order_id]);
                }
            }
            //产品SKU
            if (!empty($get['sku'])) {
                $order_id = OrderAliexpressSearch::getOrder_id($get['sku']);
                if (!empty($order_id)) {
                    $platform_order_id = OrderAliexpressSearch::getPlatformOrders($order_id);
                    if ($platform_order_id) {
                        $query->andWhere(['in', 'e.platform_order_id', $platform_order_id]);
                    }
                }
            }
            //账号简称
            if (!empty($get['account_id'])) {
                $query->andWhere(['e.account_id' => $get['account_id']]);
            }
            //是否回复
            if (isset($get['reply_status']) && $get['reply_status'] != '') {
                $query->andWhere(['e.reply_status' => $get['reply_status']]);
            }
            //纠纷状态
            if (!empty($get['issue_status'])) {
                $query->andWhere(['d.issue_status' => $get['issue_status']]);
            }
            //买家评价星级
            if (isset($get['buyer_evaluation']) && $get['buyer_evaluation'] != '') {
                $query->andWhere(['e.buyer_evaluation' => $get['buyer_evaluation']]);
            }
            //订单完成时间
            if (!empty($get['gmt_order_complete'])) {
                $start_time = 0;
                $end_time = 0;
                if ($get['gmt_order_complete'] == 'today') {
                    $start_time = date('Y-m-d 00:00:00');
                    $end_time = date('Y-m-d 23:59:59');
                } else if ($get['gmt_order_complete'] == 'yesterday') {
                    $start_time = date('Y-m-d 00:00:00', strtotime('-1 day'));
                    $end_time = date('Y-m-d 23:59:59', strtotime('-1 day'));
                } else if ($get['gmt_order_complete'] == 'past30day') {
                    $start_time = date('Y-m-d 00:00:00', strtotime('-30 day'));
                    $end_time = date('Y-m-d 23:59:59');
                } else if ($get['gmt_order_complete'] == 'custom') {
                    $start_time = $get['start_time'];
                    $end_time = $get['end_time'];
                }
                $query->andWhere(['between', 'e.gmt_order_complete', $start_time, $end_time]);
            }
            //itemID
            if (!empty($get['platform_product_id'])) {
                $query->andWhere(['e.platform_product_id' => $get['platform_product_id']]);
            }
            //查询评价类型(好评，中评，差评)
            if (!empty($get['comment_type'])) {
                if ($get['comment_type'] == 'positive') {
                    $query->andWhere(['in', 'e.buyer_evaluation', [4, 5]]);
                } else if ($get['comment_type'] == 'neutral') {
                    $query->andWhere(['e.buyer_evaluation' => 3]);
                } else if ($get['comment_type'] == 'negative') {
                    $query->andWhere(['in', 'e.buyer_evaluation', [1, 2]]);
                }
            }

            $data = $query->orderBy('e.gmt_order_complete DESC')->asArray()->all();
        }

        //标题数组
        $fieldArr = [
            '平台订单号',
            '产品SKU',
            '产品名称',
            '买家ID',
            '订单完成时间',
            '买家评价星级',
            '买家评价内容',
            '账号简称',
            '回复评价剩余时间',
            '是否回复',
            'itemID',
        ];
        //导出数据数组
        $dataArr = [];

        if (!empty($data)) {
            //平台订单ID数组
            $orderIds = [];
            //平台产品ID数组
            $productIds = [];

            foreach ($data as $item) {
                $orderIds[] = $item['platform_order_id'];
                $productIds[] = $item['platform_product_id'];
            }

            //获取订单ID和买家ID
            $orderIdAndBuyerIds = OrderAliexpressSearch::getOrderIdAndBuyerId($orderIds);

            //获取产品sku，产品名称，产品图片
            $products = OrderAliexpressSearch::getProductSkuAndTitle($orderIds, $productIds);

            foreach ($data as $item) {

                //设置回复评价剩余时间
                $reply_day = AliexpressEvaluateList::REPLY_DAY;
                $reply_end_time = strtotime("+{$reply_day} day", strtotime($item['gmt_order_complete']));
                $reply_last_time = $reply_end_time - time();
                if ($reply_last_time > 0) {
                    $reply_last_time = VHelper::sec2day($reply_last_time);
                    $reply_last_time = "还剩: {$reply_last_time} 天";
                } else {
                    $reply_last_time = '已超时';
                }

                //是否回复
                $reply_status = '';
                switch ($item['reply_status']) {
                    case '0':
                        $reply_status = '否';
                        break;
                    case '1':
                        $reply_status = '是';
                        break;
                    case '2':
                        $reply_status = '标记回复';
                        break;
                }

                $dataArr[] = [
                    $item['platform_order_id'],
                    array_key_exists($item['platform_order_id'], $products) ? $products[$item['platform_order_id']]['sku'] : '',
                    array_key_exists($item['platform_order_id'], $products) ? $products[$item['platform_order_id']]['picking_name'] : '',
                    array_key_exists($item['platform_order_id'], $orderIdAndBuyerIds) ? $orderIdAndBuyerIds[$item['platform_order_id']]['buyer_id'] : '',
                    $item['gmt_order_complete'],
                    $item['buyer_evaluation'] . '星',
                    VHelper::removeEmoji($item['buyer_feedback']),
                    $item['account_name'],
                    $reply_last_time,
                    $reply_status,
                    $item['platform_product_id'],
                ];
            }
        }

        VHelper::exportExcel($fieldArr, $dataArr, 'aliexpressevaluate_' . date('Y-m-d'));
    }
}

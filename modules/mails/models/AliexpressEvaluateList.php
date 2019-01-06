<?php

namespace app\modules\mails\models;

use app\modules\aftersales\models\AfterSalesRefund;
use Yii;
use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\orders\models\Order;
use app\modules\orders\models\OrderAliexpressSearch;
use app\modules\accounts\models\UserAccount;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\db\Query;
use  yii\web\Session;

class AliexpressEvaluateList extends MailsModel
{
    const PLATFORM_CODE = Platform::PLATFORM_CODE_ALI;

    //订单回复评价时间
    const REPLY_DAY = 30;

    /**
     * 返回操作表名
     */
    public static function tableName()
    {
        return '{{%aliexpress_evaluate_list}}';
    }

    /**
     * 根据平台订单id查询订单评价信息
     */
    public static function getFindOne($platform_order_id)
    {
        return self::findOne(['platform_order_id' => $platform_order_id]);
    }

    /**
     * 返回属性字段
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $extAttributes = [
            'order_id',
            'sku',
            'product_name',
            'product_image',
            'buyer_id',
            'reply_last_time',
            'feedback_status',
            'issue_status',
            'refund_status'
        ];
        return array_merge($attributes, $extAttributes);
    }

    /**
     * 搜索筛选项
     */
    public function filterOptions()
    {

        $getEvaluate = Yii::$app->request->get();


        return [
            [
                'name' => 'platform_order_id',
                'alias' => 'e',
                'type' => 'text',
                'search' => '=',
                'value' => $getEvaluate['platform_order_id'] ? $getEvaluate['platform_order_id'] : null,
            ],
            [
                'name' => 'order_id',
                'type' => 'text',
                'search' => '=',
                'value' => $getEvaluate['order_id'] ? $getEvaluate['order_id'] : null,
            ],
            [
                'name' => 'buyer_id',
                'type' => 'text',
                'search' => '=',
                'value' => $getEvaluate['buyer_id'] ? $getEvaluate['buyer_id'] : null,
            ],
            [
                'name' => 'sku',
                'type' => 'text',
                'search' => '=',
                'value' => $getEvaluate['sku'] ? $getEvaluate['sku'] : null,
            ],
            [
                'name' => 'account_id',
                'alias' => 'e',
                'type' => 'search',
                'data' => self::accountDropdown(),
                'search' => '=',
                'value' => $getEvaluate['account_id'] ? $getEvaluate['account_id'] : null,
            ],
            [
                'name' => 'reply_status',
                'alias' => 'e',
                'type' => 'dropDownList',
                'data' => self::replyStatusDropdown(),
                'htmlOptions' => [],
                'search' => '=',
                'value' => $getEvaluate['reply_status'] ? $getEvaluate['account_id'] : null,
            ],
          /*  [
                'name' => 'issue_status',
                'alias' => 'd',
                'type' => 'dropDownList',
                'data' => self::issueStatusDropdown(),
                'htmlOptions' => [],
                'search' => '=',
                'value' => $getEvaluate['issue_status'] ? $getEvaluate['issue_status'] : null,
            ],*/
         /*   [
                'name' => 'refund_status',
                'type' => 'dropDownList',
                'data' => self::refundStatusDropdown(),
                'htmlOptions' => [],
                'search' => '=',
                'value' => $getEvaluate['refund_status'] ? $getEvaluate['refund_status'] : null,
            ],*/
            [
                'name' => 'buyer_evaluation',
                'alias' => 'e',
                'type' => 'dropDownList',
                'data' => self::buyerEvaluationDropdown(),
                'htmlOptions' => [],
                'search' => '=',
                'value' => $getEvaluate['buyer_evaluation'] ? $getEvaluate['buyer_evaluation'] : null,
            ],
            [
                'name' => 'gmt_order_complete',
                'type' => 'dropDownList',
                'data' => self::orderCompleteDropdown(),
                'htmlOptions' => [],
                'search' => '=',
                'value' => $getEvaluate['gmt_order_complete'] ? $getEvaluate['gmt_order_complete'] : null,
            ],
            [
                'name' => 'start_time',
                'type' => 'date_picker',
                'htmlOptions' => ['width:320px'],
                'search' => '>',
                'value' => $getEvaluate['start_time'] ? $getEvaluate['start_time'] : null,
            ],
            [
                'name' => 'end_time',
                'type' => 'date_picker',
                'htmlOptions' => ['width:320px'],
                'search' => '<',
                'value' => $getEvaluate['end_time'] ? $getEvaluate['end_time'] : null,
            ],
            [
                'name' => 'platform_product_id',
                'alias' => 'e',
                'type' => 'text',
                'htmlOptions' => [],
                'search' => '=',
                'value' => $getEvaluate['platform_product_id'] ? $getEvaluate['platform_product_id'] : null,
            ],
        ];
    }

    public function dynamicChangeFilter(&$filterOptions, $query, $params)
    {
        foreach ($filterOptions as $key=>$row) {
            if($row['name'] == 'issue_status') {
                $filterOptions[$key]['value'] = null;
            }
            if($row['name'] == 'refund_status'){
                $filterOptions[$key]['value'] = null;
            }
        }
    }

    /**
     * 搜索列表
     */
    public function searchList($params = [])
    {
        $session = Yii::$app->session;
        $arr = [];
        if(!empty($params)){
            foreach ($params as $k => $item){
                if(!empty($params[$k])){
                    $arr[$k] = $item;
                }
            }
            if($session->isActive)
            {
                $session->set('info',$arr);
            }else{
                $session->open();
                $session->set('info',$params);
            }

            $session->close();//关闭Seesion
        }
        $query = self::find()
            ->select('e.*')
            ->alias('e');

        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'gmt_order_complete' => SORT_DESC,
            'id' => SORT_DESC
        );


        //只能查询到客服绑定账号的评价
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_ALI);
        $query->andWhere(['in', 'e.account_id', $accountIds]);

        //查询产品SKU
        if (!empty($params['sku'])) {
            $order_id = OrderAliexpressSearch::getOrder_id($params['sku']);
            if (!empty($order_id)) {
                $platform_order_id = OrderAliexpressSearch::getPlatformOrders($order_id);
                if ($platform_order_id) {
                    $query->andWhere(['in', 'e.platform_order_id', $platform_order_id]);
                }
            }

            unset($params['sku']);
        }

        //查询速卖通订单表得平台订单号
        if (!empty($params['order_id'])) {
            $platform_order_id = OrderAliexpressSearch::getPlatform($params['order_id']);
            if (!empty($platform_order_id)) {
                $query->andWhere(['e.platform_order_id' => $platform_order_id]);
            }
            unset($params['order_id']);
        }

        //纠纷状态
      /*  if (!empty($params['issue_status'])) {
            $tableName = AliexpressDisputeList::tableName();
            if ($params['issue_status'] == 'have_issue') {
                $query->andWhere("EXISTS (SELECT * FROM {$tableName} AS d WHERE d.platform_order_id=e.platform_order_id)");
            } else if ($params['issue_status'] == 'no_have_issue') {
                $query->andWhere("NOT EXISTS (SELECT * FROM {$tableName} AS d WHERE d.platform_order_id=e.platform_order_id)");
            }
            unset($params['issue_status']);
        }*/

        //退款状态
      /*  if (!empty($params['refund_status'])) {
            $tableName = AfterSalesRefund::tableName();
            if ($params['refund_status'] == 'yes') {
                $query->andWhere("EXISTS (SELECT * FROM {$tableName} AS r WHERE r.platform_order_id=e.platform_order_id)");
            } else if ($params['refund_status'] == 'no') {
                $query->andWhere("NOT EXISTS (SELECT * FROM {$tableName} AS r WHERE r.platform_order_id=e.platform_order_id)");
            }

            unset($params['refund_status']);
        }*/


        //查询速卖通订单表的买家ID
        if (!empty($params['buyer_id'])) {
            $plat_order_id = OrderAliexpressSearch::getPlatOrderId($params['buyer_id']);
            if (!empty($plat_order_id)) {
                $query->andWhere(['in', 'e.platform_order_id', $plat_order_id]);
            }
            unset($params['buyer_id']);
        }

        //查询订单完成时间
        if (!empty($params['gmt_order_complete'])) {
            $start_time = 0;
            $end_time = 0;
            if ($params['gmt_order_complete'] == 'today') {
                $start_time = date('Y-m-d 00:00:00');
                $end_time = date('Y-m-d 23:59:59');
            } else if ($params['gmt_order_complete'] == 'yesterday') {
                $start_time = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $end_time = date('Y-m-d 23:59:59', strtotime('-1 day'));
            } else if ($params['gmt_order_complete'] == 'past30day') {
                $start_time = date('Y-m-d 00:00:00', strtotime('-30 day'));
                $end_time = date('Y-m-d 23:59:59');
            } else if ($params['gmt_order_complete'] == 'custom') {
                $start_time = $params['start_time'];
                $end_time = $params['end_time'];
            }
            $query->andWhere(['between', 'e.gmt_order_complete', $start_time, $end_time]);

            unset($params['gmt_order_complete']);
            unset($params['start_time']);
            unset($params['end_time']);
        }

        //查询评价类型(好评，中评，差评)
        if (!empty($params['comment_type'])) {
            if ($params['comment_type'] == 'positive') {
                $query->andWhere(['in', 'e.buyer_evaluation', [4, 5]]);
            } else if ($params['comment_type'] == 'neutral') {
                $query->andWhere(['e.buyer_evaluation' => 3]);
            } else if ($params['comment_type'] == 'negative') {
                $query->andWhere(['in', 'e.buyer_evaluation', [1, 2]]);
            }

            unset($params['comment_type']);
        }

        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * 统计评价类型数量
     */
    public function countCommentTypeNum($params = [])
    {
        //统计所有评价数
        $query = self::find()
            ->alias('e');

        //只能查询到客服绑定账号的评价
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_ALI);
        $query->andWhere(['in', 'e.account_id', $accountIds]);

        //平台订单号
        if (!empty($params['platform_order_id'])) {
            $query->andWhere(['e.platform_order_id' => $params['platform_order_id']]);
        }
        //订单号
        if (!empty($params['order_id'])) {
            $platform_order_id = OrderAliexpressSearch::getPlatform($params['order_id']);
            if (!empty($platform_order_id)) {
                $query->andWhere(['e.platform_order_id' => $platform_order_id]);
            }
        }
        //买家ID
        if (!empty($params['buyer_id'])) {
            $plat_order_id = OrderAliexpressSearch::getPlatOrderId($params['buyer_id']);
            if (!empty($plat_order_id)) {
                $query->andWhere(['in', 'e.platform_order_id', $plat_order_id]);
            }
        }
        //产品SKU
        if (!empty($params['sku'])) {
            $order_id = OrderAliexpressSearch::getOrder_id($params['sku']);
            if (!empty($order_id)) {
                $platform_order_id = OrderAliexpressSearch::getPlatformOrders($order_id);
                if ($platform_order_id) {
                    $query->andWhere(['in', 'e.platform_order_id', $platform_order_id]);
                }
            }
        }
        //账号简称
        if (!empty($params['account_id']) && $params['account_id'] != ' ') {
            $query->andWhere(['e.account_id' => $params['account_id']]);
        }
        //是否回复
        if (isset($params['reply_status']) && $params['reply_status'] != '') {
            $query->andWhere(['e.reply_status' => $params['reply_status']]);
        }
        //纠纷状态
     /*   if (!empty($params['issue_status'])) {
            $tableName = AliexpressDisputeList::tableName();
            if ($params['issue_status'] == 'have_issue') {
                $query->andWhere("EXISTS (SELECT * FROM {$tableName} AS d WHERE d.platform_order_id=e.platform_order_id)");
            } else if ($params['issue_status'] == 'no_have_issue') {
                $query->andWhere("NOT EXISTS (SELECT * FROM {$tableName} AS d WHERE d.platform_order_id=e.platform_order_id)");
            }
        }*/
        //退款状态
     /*   if (!empty($params['refund_status'])) {
            $tableName = AfterSalesRefund::tableName();
            if ($params['refund_status'] == 'yes') {
                $query->andWhere("EXISTS (SELECT * FROM {$tableName} AS r WHERE r.platform_order_id=e.platform_order_id)");
            } else if ($params['refund_status'] == 'no') {
                $query->andWhere("NOT EXISTS (SELECT * FROM {$tableName} AS r WHERE r.platform_order_id=e.platform_order_id)");
            }
        }*/
        //买家评价星级
        if (isset($params['buyer_evaluation']) && $params['buyer_evaluation'] != '') {
            $query->andWhere(['e.buyer_evaluation' => $params['buyer_evaluation']]);
        }
        //订单完成时间
        if (!empty($params['gmt_order_complete'])) {
            $start_time = 0;
            $end_time = 0;
            if ($params['gmt_order_complete'] == 'today') {
                $start_time = date('Y-m-d 00:00:00');
                $end_time = date('Y-m-d 23:59:59');
            } else if ($params['gmt_order_complete'] == 'yesterday') {
                $start_time = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $end_time = date('Y-m-d 23:59:59', strtotime('-1 day'));
            } else if ($params['gmt_order_complete'] == 'past30day') {
                $start_time = date('Y-m-d 00:00:00', strtotime('-30 day'));
                $end_time = date('Y-m-d 23:59:59');
            } else if ($params['gmt_order_complete'] == 'custom') {
                $start_time = $params['start_time'];
                $end_time = $params['end_time'];
            }
            $query->andWhere(['between', 'e.gmt_order_complete', $start_time, $end_time]);
        }
        //itemID
        if (!empty($params['platform_product_id'])) {
            $query->andWhere(['e.platform_product_id' => $params['platform_product_id']]);
        }

        //复制查询对象
        $query1 = clone $query;
        $query2 = clone $query;
        $query3 = clone $query;
        $query4 = clone $query;

        //统计评价总数
        $allComment = $query1->count();
        //统计好评数
        $positiveComment = $query2->andWhere(['in', 'buyer_evaluation', [4, 5]])->count();
        //统计中评数
        $neutralComment = $query3->andWhere(['buyer_evaluation' => 3])->count();
        //统计差评数
        $negativeComment = $query4->andWhere(['in', 'buyer_evaluation', [1, 2]])->count();

        return [
            'allComment' => $allComment ? $allComment : 0,
            'positiveComment' => $positiveComment ? $positiveComment : 0,
            'neutralComment' => $neutralComment ? $neutralComment : 0,
            'negativeComment' => $negativeComment ? $negativeComment : 0,
        ];
    }

    /**
     * 属性的标签
     */
    public function attributeLabels()
    {
        return [
            'account_id' => '账号简称',
            'buyer_evaluation' => '买家评价星级',
            'buyer_fb_date' => '买家已评时间',
            'buyer_feedback' => '买家评价内容',
            'buyer_login_id' => '买家登录帐号',
            'buyer_reply' => '买家回复内容',
            'gmt_create' => '创建时间',
            'gmt_modified' => '最后修改时间',
            'gmt_order_complete' => '订单完成时间',
            'platform_order_id' => '平台订单号',
            'platform_parent_order_id' => '平台父订单号',
            'platform_product_id' => 'itemID',
            'seller_evaluation' => '卖家评价星级',
            'seller_fb_date' => '卖家已评时间',
            'seller_feedback' => '卖家评价内容',
            'seller_login_id' => '卖家登录帐号',
            'seller_reply' => '卖家回复内容',
            'valid_date' => '评价生效日期',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
            'order_id' => '订单号',
            'sku' => '产品SKU',
            'product_name' => '产品名称',
            'product_image' => '产品图片',
            'buyer_id' => '买家ID',
            'reply_last_time' => '回复评价剩余时间',
            'feedback_status' => '是否评价',
            'reply_status' => '是否回复',
            'issue_status' => '纠纷状态',
            'refund_status' => '退款状态',
            'start_time' => '开始时间',
            'end_time' => '结束时间',
        ];
    }

    /**
     * 动态修改模型的数据
     */
    public function addition(&$models)
    {

        //平台订单ID数组
        $orderIds = [];
        //平台产品ID数组
        $productIds = [];

        foreach ($models as $key => $model) {
            $orderIds[] = $model->platform_order_id;
            $productIds[] = $model->platform_product_id;
        }

        //获取订单ID和买家ID
        $orderIdAndBuyerIds = OrderAliexpressSearch::getOrderIdAndBuyerId($orderIds);

        //获取产品sku，产品名称，产品图片
        $products = OrderAliexpressSearch::getProductSkuAndTitle($orderIds, $productIds);

        //获取速卖通所有账号信息
        $accounts = Account::getAccount(Platform::PLATFORM_CODE_ALI, 1);

        foreach ($models as $key => $model) {
            //纠纷状态
            $issueList = AliexpressDisputeList::find()
                ->select('platform_dispute_id')
                ->andWhere(['platform_order_id' => $model->platform_order_id])
                ->andWhere(['account_id' => $model->account_id])
                ->asArray()
                ->all();

            if (empty($issueList)) {
                $model->setAttribute('issue_status', '<span class="label label-success">无</span>');
            } else {
                $issue_status = '';
                foreach ($issueList as $issue) {
                    $issue_status .= '<a class="edit-button label label-danger" _width="90%" _height="90%" href="' . Url::toRoute(['/mails/aliexpressdispute/showorder', 'issue_id' => $issue['platform_dispute_id']]) . '">' . $issue['platform_dispute_id'] . '</a><br>';
                }
                $model->setAttribute('issue_status', $issue_status);
            }

            //设置产品SKU，产品名称，产品图片
            if (array_key_exists($model->platform_order_id, $products)) {
                $model->setAttribute('sku', $products[$model->platform_order_id]['sku']);
                $model->setAttribute('product_name', $products[$model->platform_order_id]['picking_name']);
                $model->setAttribute('product_image', '<img style="border:1px solid #ccc;padding:2px;width:60px;height:60px;" src="' . Order::getProductImageThub($model->sku) . '">');
            }

            //设置订单号和买家ID
            if (array_key_exists($model->platform_order_id, $orderIdAndBuyerIds)) {
                $model->setAttribute('order_id', $orderIdAndBuyerIds[$model->platform_order_id]['order_id']);
                $model->setAttribute('buyer_id', $orderIdAndBuyerIds[$model->platform_order_id]['buyer_id']);
            }

            //设置买家评价星级
            $model->setAttribute('buyer_evaluation', $model->buyer_evaluation . '星');

            //设置账号简称
            if (array_key_exists($model->account_id, $accounts)) {
                $model->setAttribute('account_id', $accounts[$model->account_id]);
            }

            if (!empty($model->seller_feedback)) {
                $model->setAttribute('feedback_status', '是');
            } else {
                $model->setAttribute('feedback_status', '否');
            }

            //设置回复评价剩余时间
            if (empty($model->seller_reply) && empty($model->reply_status)) {
                $reply_day = self::REPLY_DAY;
                $reply_end_time = strtotime("+{$reply_day} day", strtotime($model->gmt_order_complete));
                $reply_end_time_str = date('Y/m/d H:i:s', $reply_end_time);
                $reply_last_time = $reply_end_time - time();
                if ($reply_last_time > 0) {
                    $reply_last_time = VHelper::sec2day($reply_last_time);
                    $reply_last_time = "<span class='reply_last_time' data-endtime='{$reply_end_time_str}'>还剩: {$reply_last_time} 天</span>";
                } else {
                    $reply_last_time = '<span style="color:red;">已超时</span>';
                }
                $model->setAttribute('reply_last_time', $reply_last_time);
            }

            //设置是否回复
            $reply_status = '';
            switch ($model->reply_status) {
                case '0':
                    $reply_status = '<span style="color:red;font-weight:bold;font-size:24px;">否</span>';
                    break;
                case '1':
                    $reply_status = '<span style="color:green;font-weight:bold;font-size:24px;">是</span>';
                    break;
                case '2':
                    $reply_status = '标记回复';
                    break;
            }

            //设置退款状态
     /*       $refund_status = AfterSalesRefund::find()
                ->select('refund_status')
                ->andWhere(['platform_order_id' => $model->platform_order_id])
                ->andWhere(['platform_code' => Platform::PLATFORM_CODE_ALI])
                ->asArray()
                ->one();
            if($refund_status['refund_status'] == 3){
                $model->setAttribute('refund_status', '<span style="color:red;font-weight:bold;font-size:24px;">是</span>');
            }else{
                $model->setAttribute('refund_status', '<span style="color:green;font-weight:bold;font-size:24px;">否</span>');
            }*/

            $model->setAttribute('reply_status', $reply_status);

            //设置itemID
            $model->setAttribute('platform_product_id', Html::a($model->platform_product_id, "https://www.aliexpress.com/item//{$model->platform_product_id}.html", ['target' => '_blank']));

            $platformOrderId = Html::a($model->platform_order_id, Url::toRoute(['/orders/order/orderdetails', 'order_id' => $model->platform_order_id, 'platform' => Platform::PLATFORM_CODE_ALI]),
                ['class' => 'add-button', '_width' => '90%', '_height' => '90%']);

            if ($model->platform_order_id != $model->platform_parent_order_id) {
                $platformOrderId = '<p style="margin:0;">子: ' . Html::a($model->platform_order_id, Url::toRoute(['/orders/order/orderdetails', 'order_id' => $model->platform_order_id, 'platform' => Platform::PLATFORM_CODE_ALI]),
                    ['class' => 'add-button', '_width' => '90%', '_height' => '90%']) . '</p>';

                $platformOrderId .= '<p style="margin:0;">父: ' . Html::a($model->platform_parent_order_id, Url::toRoute(['/orders/order/orderdetails', 'order_id' => $model->platform_parent_order_id, 'platform' => Platform::PLATFORM_CODE_ALI]),
                    ['class' => 'add-button', '_width' => '90%', '_height' => '90%']) . '</p>';
            }

            //设置订单链接
            $model->setAttribute('platform_order_id', $platformOrderId);
        }
    }

    /**
     * 账号列表
     */
    public static function accountDropdown()
    {
        $accounts = UserAccount::find()
            ->alias('u')
            ->select('u.account_id, a.account_name')
            ->leftJoin(['a' => Account::tableName()], 'a.id = u.account_id')
            ->where(['u.user_id' => Yii::$app->user->identity->id, 'u.platform_code' => Platform::PLATFORM_CODE_ALI])
            ->asArray()
            ->all();

        $data = [' ' => '全部'];
        foreach ($accounts as $account) {
            $data[$account['account_id']] = $account['account_name'];
        }
        return $data;
    }

    /**
     * 是否回复列表
     */
    public static function replyStatusDropdown()
    {
        return [
            '0' => '否',
            '1' => '是',
            '2' => '标记回复',
        ];
    }

    /**
     * 纠纷状态列表
     */
    public static function issueStatusDropdown()
    {
        return [
            'have_issue' => '有纠纷',
            'no_have_issue' => '无纠纷',
        ];
    }

    /**
     * 是否退款
     */
    public static function refundStatusDropdown()
    {
        return [
            'yes' => '是',
            'no' => '否',
        ];
    }

    /**
     * 买家评价星级
     */
    public static function buyerEvaluationDropdown()
    {
        return [
            '0' => '0星',
            '1' => '1星',
            '2' => '2星',
            '3' => '3星',
            '4' => '4星',
            '5' => '5星',
        ];
    }

    /**
     * 订单完成时间列表
     */
    public static function orderCompleteDropdown()
    {
        return [
            'today' => '今天',
            'yesterday' => '昨天',
            'past30day' => '过去30天',
            'custom' => '自定义',
        ];
    }

    /**
     * @author alpha
     * @desc 获取评价id
     * @param $platform_order_id
     * @return int
     */
    public static function getCurrentEvaluateIdByPlatformOrderId($platform_order_id)
    {
        $evaluate_id=self::find() ->select('id')->where([ 'platform_order_id'=> $platform_order_id])->one();
        return isset($evaluate_id)&&!empty($evaluate_id)?$evaluate_id['id']:0;
    }

    /**
     * @param $month_date
     * @param $buyer_evaluation
     * 获取速卖通中差评
     */
    public static function getFeedbackPlatform($month_date, $buyer_evaluation = array())
    {
        $data = [];
        //只能查询到客服绑定账号的评价
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_ALI);
        foreach ($month_date as $key => $item) {
            $query = self::find()
                ->andWhere(['between', 'buyer_fb_date', $item['start_time'], $item['end_time']]);

            if(is_array($buyer_evaluation) && !empty($buyer_evaluation)){
                $query->andWhere(['in','buyer_evaluation', $buyer_evaluation]);
            }
            if(!empty($accountIds)){
                $query->andWhere(['in','account_id', $accountIds]);
            }

            $data[$key] = $query->count();
        }

        return $data;

    }

    /**
     * @param $month_date
     * @param $comment_type
     * @param $account
     * 按账号统计好评,差评率
     */
    public static function getFeedbackAccount($month_date,$buyer_evaluation = array(), $account_ids)
    {

        $data = [];

        $init = [];

        $allFeedback = 0;
        $subFeedback = 0;

        if(!empty($account_ids)){
            $account_info = Account::find()->select('id,account_name')->andWhere(['platform_code' => 'ALI'])->andWhere(['in','id',$account_ids])->andWhere(['status' => 1])->asArray()->all();
        }else{
            $account_info = Account::find()->select('id,account_name')->andWhere(['platform_code' => 'ALI'])->andWhere(['status' => 1])->asArray()->all();
        }
        $result = [];
        foreach ($account_info as $key => $value) {
            $result[$value['account_name']] = $value['id'];
        }
        $account = array_values($result);
        foreach ($result as $key => $value){
            $init[$key] = 0;
        }

        foreach ($month_date as $key => $item) {
            $data[$key] = $init;

            $subQuery = self::find()
                ->select('t1.account_name, count(t.id) as cnt')
                ->from('{{%aliexpress_evaluate_list}} t')
                ->join('LEFT JOIN', '{{%account}} t1', 't.account_id = t1.id')
                ->andWhere(['between', 't.buyer_fb_date', $item['start_time'], $item['end_time']])
                ->andWhere(['in','t.buyer_evaluation', $buyer_evaluation]);
            if(!empty($account)){
                $subQuery->andWhere(['in', 't.account_id', $account]);
            }
            $subQuery = $subQuery->groupBy('t.account_id')
                ->orderBy('cnt DESC')
                ->asArray()
                ->all();

            $allQuery = self::find()
                ->select('t1.account_name, count(t.id) as cnt')
                ->from('{{%aliexpress_evaluate_list}} t')
                ->join('LEFT JOIN', '{{%account}} t1', 't.account_id = t1.id')
                ->andWhere(['between', 't.buyer_fb_date', $item['start_time'], $item['end_time']]);
            if(!empty($account)){
                $allQuery->andWhere(['in', 't.account_id', $account]);
            }
            $allQuery = $allQuery->groupBy('t.account_id')
                ->orderBy('cnt DESC')
                ->asArray()
                ->all();

            if (!empty($allQuery)) {
                $allResult = array_column($allQuery,'cnt','account_name');

                $subResult = array_column($subQuery,'cnt','account_name');

                if (!empty($allResult)) {
                    foreach ($allResult as $kk => $vv) {
                        $allFeedback += $vv;
                    }
                }

                if (!empty($subResult)) {
                    foreach ($subResult as $kk => $vv) {
                        $subFeedback += $vv;
                        if (array_key_exists($kk, $data[$key])) {
                            if (!empty($allResult[$kk])) {
                                $data[$key][$kk] = round($vv / $allResult[$kk], 4) * 100;
                            }else{
                                $data[$key][$kk] = 0;
                            }
                        }
                    }
                }
            }

            if(!empty($data[$key])){
                $tmp = $data[$key];
                arsort($tmp);
                $data[$key] = $tmp;
            }
        }

        $res = ['data' => $data,'feedback' => $allFeedback, 'feedback1' => $subFeedback];
        return $res;

    }


    /**
     * @param $month_date
     * @param $comment_type
     * @param $user_name
     * 按客服统计评价率
     */
    public static function getFeedbackKefu($month_date, $buyer_evaluation = array(), $user_name)
    {
        $data = [];

        $feedback = 0;
        $feedback1 = 0;
        if (!empty($user_name)) {
            $user_id = (new Query())->select('id')->from('{{%user}}')->where(['user_name' => $user_name])
                ->createCommand(\Yii::$app->db_system)
                ->queryColumn();
            $account_info = (new Query())
                ->select('t1.user_name, t.account_ids')
                ->from('{{%orderservice}} as t')
                ->join('LEFT JOIN', '{{%user}} t1', 't.user_id = t1.id')
                ->where(['t.user_id' => $user_id, 't.platform_code' => 'ALI'])
                ->createCommand(\Yii::$app->db_system)
                ->queryAll();
        } else {
            $account_info = (new Query())
                ->select('t1.user_name, t.account_ids')
                ->from('{{%orderservice}} as t')
                ->join('LEFT JOIN', '{{%user}} t1', 't.user_id = t1.id')
                ->where(['t.platform_code' => 'ALI'])
                ->createCommand(\Yii::$app->db_system)
                ->queryAll();
        }

        $result = [];
        foreach ($account_info as $key => $value) {
            if (!empty($value['account_ids'])) {
                $arr = explode(',', $value['account_ids']);
                $result[$value['user_name']] = Account::find()->select('id')->where(['in', 'old_account_id', $arr])->column();
            }
        }

        foreach ($month_date as $key => $item) {
            foreach ($result as $kk => $vv) {
                $query = self::find()
                    ->select('count(id) as cnt')
                    ->from('{{%aliexpress_evaluate_list}}')
                    ->andWhere(['between', 'buyer_fb_date', $item['start_time'], $item['end_time']])
                    ->andWhere(['in', 'buyer_evaluation', $buyer_evaluation]);
                if (!empty($vv)) {
                    $query = $query->andWhere(['in', 'account_id', $vv]);
                }
                $res[$key][$kk] = $query->orderBy('cnt DESC')
                    ->asArray()
                    ->one()['cnt'];

                $query1 = self::find()
                    ->select('count(id) as cnt')
                    ->from('{{%aliexpress_evaluate_list}}')
                    ->andWhere(['between', 'buyer_fb_date', $item['start_time'], $item['end_time']]);
                if (!empty($vv)) {
                    $query1 = $query1->andWhere(['in', 'account_id', $vv]);
                }
                $res1[$key][$kk] = $query1->orderBy('cnt DESC')
                    ->asArray()
                    ->one()['cnt'];

            }
            if (!empty($result)) {
                foreach ($result as $kkk => $vvv) {
                    $feedback += $res1[$key][$kkk];
                    $feedback1 += $res[$key][$kkk];
                    if (!empty($res1[$key][$kkk])) {
                        $data[$key][$kkk] = round($res[$key][$kkk] / $res1[$key][$kkk], 4) * 100;
                    } else {
                        $data[$key][$kkk] = 0;
                    }

                }


            }
            if(!empty($data[$key])){
                $tmp = $data[$key];
                arsort($tmp);
                $data[$key] = $tmp;
            }
        }

        $res = ['data' => $data, 'feedback' => $feedback, 'feedback1' => $feedback1];
        return $res;

    }

    /**
     * @param $month_date
     * @param $user_name
     * 数据导出
     */
    public static function getExcelDate($month_date, $user_name)
    {

        if ($user_name != 'null') {
            $user_id = (new Query())->select('id')->from('{{%user}}')->where(['user_name' => $user_name])
                ->createCommand(\Yii::$app->db_system)
                ->queryColumn();
            $account_info = (new Query())
                ->select('t1.user_name, t.account_ids')
                ->from('{{%orderservice}} as t')
                ->join('LEFT JOIN', '{{%user}} t1','t.user_id = t1.id')
                ->where(['t.user_id' => $user_id, 't.platform_code' => 'ALI'])
                ->createCommand(\Yii::$app->db_system)
                ->queryAll();
        } else {
            $account_info = (new Query())
                ->select('t1.user_name, t.account_ids')
                ->from('{{%orderservice}} as t')
                ->join('LEFT JOIN', '{{%user}} t1','t.user_id = t1.id')
                ->where(['t.platform_code' => 'ALI'])
                ->createCommand(\Yii::$app->db_system)
                ->queryAll();
        }

        $result = [];
        foreach ($account_info as $key => $value){
            if(!empty($value['account_ids'])){
                $arr = explode(',',$value['account_ids']);
                $result[$value['user_name']] = Account::find()->select('id')->where(['in','old_account_id',$arr])->column();
            }
        }
        $res = [];

        foreach ($result as $k => $item) {
            $res[$k] = array(
                'res_postive' => 0,
                'res_postive_rate' => 0,
                'res_negative' => 0,
                'res_negative_rate' => 0,
                'res_zong' => 0,
            );
            $query = self::find()
                ->select('count(id) as cnt')
                ->from('{{%aliexpress_evaluate_list}}')
                ->andWhere(['between', 'buyer_fb_date', $month_date['start_time'], $month_date['end_time']])
                ->andWhere(['in', 'buyer_evaluation', [4,5]]);
            if (!empty($item)) {
                $query = $query->andWhere(['in', 'account_id', $item]);
            }
            $res[$k]['res_postive'] = $query->orderBy('cnt DESC')
                ->asArray()
                ->one()['cnt'];

            $query1 = self::find()
                ->select('count(id) as cnt')
                ->from('{{%aliexpress_evaluate_list}}')
                ->andWhere(['between', 'buyer_fb_date', $month_date['start_time'], $month_date['end_time']]);
            if (!empty($item)) {
                $query1 = $query1->andWhere(['in', 'account_id', $item]);
            }
            $res[$k]['res_zong'] = $query1->orderBy('cnt DESC')
                ->asArray()
                ->one()['cnt'];

            $query2 = self::find()
                ->select('count(id) as cnt')
                ->from('{{%aliexpress_evaluate_list}}')
                ->andWhere(['between', 'buyer_fb_date', $month_date['start_time'], $month_date['end_time']])
                ->andWhere(['in', 'buyer_evaluation', [1,2]]);
            if (!empty($item)) {
                $query2 = $query2->andWhere(['in', 'account_id', $item]);
            }
            $res[$k]['res_negative'] = $query2->orderBy('cnt DESC')
                ->asArray()
                ->one()['cnt'];

            if(!empty($res[$k]['res_zong'])){
                $res[$k]['res_postive_rate'] =  round( $res[$k]['res_postive']/ $res[$k]['res_zong'], 4) * 100 .'%';
                $res[$k]['res_negative_rate'] = round( $res[$k]['res_negative']/ $res[$k]['res_zong'], 4) * 100 . '%';
            }else{
                $res_postive_rate[$k] = 0;
                $res_negative_rate[$k] = 0;
            }

        }
        return $res;
    }

}

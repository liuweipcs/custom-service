<?php

namespace app\modules\mails\models;

use app\common\VHelper;
use app\modules\accounts\models\AliexpressAccount;
use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\accounts\models\Account;
use app\modules\users\models\User;
use app\modules\accounts\models\Platform;
use app\modules\orders\models\OrderAliexpressSearch;
use app\modules\accounts\models\UserAccount;

class AliexpressDisputeList extends MailsModel
{

    //默认纠纷响应时间
    const ISSUE_REPONSE_DAY = 5;
    //默认拒绝纠纷上升仲裁时间
    const REFUSE_ISSUE_DAY = 7;

    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes[] = 'order_id';
        $attributes[] = 'buyer_id';
        $attributes[] = 'issue_reponse_last_time';
        $attributes[] = 'refuse_issue_last_time';
        $attributes[] = 'finish_info';
        return $attributes;
    }

    public static function tableName()
    {
        return '{{%aliexpress_dispute_list}}';
    }


    public function searchList($params = [])
    {
        $query = self::find()
            ->alias('l')
            ->select('l.*, d.after_sale_warranty')
            ->leftJoin(['d' => AliexpressDisputeDetail::tableName()], 'd.platform_dispute_id = l.platform_dispute_id');

        //只能查询到客服绑定账号的纠纷
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_ALI);
        $query->andWhere(['in', 'l.account_id', $accountIds]);

        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'gmt_create' => SORT_DESC,
            'id' => SORT_DESC
        );

        if (!empty($params['sku'])) {
            //通过sku查询order_id
            $order_id = OrderAliexpressSearch::getOrder_id($params['sku']);


            if (!empty($order_id)) {
                //通过order_id查询platform_order_id
                $platform_order_id = OrderAliexpressSearch::getPlatformOrders($order_id);
                if ($platform_order_id) {
                    $query->andWhere(['in', 'l.platform_order_id', $platform_order_id]);
                }
            }

            unset($params['sku']);
        }

        //查询速卖通订单表得平台订单号
        if (!empty($params['order_id'])) {
            $platform_order_id = OrderAliexpressSearch::getPlatform($params['order_id']);
            if (!empty($platform_order_id)) {

                $query->andWhere(['l.platform_order_id' => $platform_order_id]);
            }
            unset($params['order_id']);
        }

        //查询速卖通订单表的买家ID
        if (!empty($params['buyer_id'])) {
            $plat_order_id = OrderAliexpressSearch::getPlatOrderId($params['buyer_id']);
            if (!empty($plat_order_id)) {
                $query->andWhere(['in', 'l.platform_order_id', $plat_order_id]);
            }
            unset($params['buyer_id']);
        }


        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    public function attributeLabels()
    {
        return [

            'account_id' => '账号',
            'platform_order_id' => '平台订单号',
            'platform_dispute_id' => '平台纠纷ID',
            'issue_status' => '纠纷状态',
            'gmt_create' => '纠纷创建时间',
            'gmt_modified' => '纠纷修改时间',
            'reason_chinese' => '纠纷原因(CN)',
            'reason_english' => '纠纷原因(EN)',
            'update_time' => '拉取时间',
            'order_id' => '订单号',
            'buyer_id' => '买家ID',
            'issue_reponse_last_time' => '纠纷响应剩余时间',
            'refuse_issue_last_time' => '已拒绝纠纷上升仲裁剩余时间',
            'after_sale_warranty' => '售后宝代处理',
            'platform_parent_order_id' => '父订单ID',
            'is_handle' => '是否处理',
            'finish_info' => '详情',
        ];
    }

    public function rules()
    {
        return [
        ];
    }

    /**
     * 搜索过滤项
     */
    public function filterOptions()
    {
        return [
            [
                'name' => 'account_id',
                'alias' => 'l',
                'type' => 'search',
                'data' => self::dropdown(),
                'search' => '='
            ],
            [
                'name' => 'platform_order_id',
                'alias' => 'l',
                'type' => 'text',
                'htmlOptions' => [],
                'search' => '='
            ],
            [
                'name' => 'platform_parent_order_id',
                'alias' => 'l',
                'type' => 'text',
                'htmlOptions' => [],
                'search' => '='
            ],
            [
                'name' => 'platform_dispute_id',
                'alias' => 'l',
                'type' => 'text',
                'htmlOptions' => [],
                'search' => '='
            ],
            [
                'name' => 'order_id',
                'type' => 'text',
                'htmlOptions' => [],
                'search' => '='
            ],
            [
                'name' => 'sku',
                'type' => 'text',
                'htmlOptions' => [],
                'search' => '='
            ],
            [
                'name' => 'buyer_id',
                'type' => 'text',
                'htmlOptions' => [],
                'search' => '='
            ],
            [
                'name' => 'issue_status',
                'alias' => 'l',
                'type' => 'dropDownList',
                'data' => self::issueStatusDropdown(),
                'htmlOptions' => [],
                'search' => '='
            ],
            [
                'name' => 'reason_chinese',
                'alias' => 'l',
                'type' => 'dropDownList',
                'data' => self::reasonChineseDropdown(),
                'htmlOptions' => [],
                'search' => '='
            ],
            [
                'name' => 'reason_english',
                'alias' => 'l',
                'type' => 'dropDownList',
                'data' => self::reasonEnglishDropdown(),
                'htmlOptions' => [],
                'search' => '='
            ],
            [
                'name' => 'after_sale_warranty',
                'alias' => 'd',
                'type' => 'dropDownList',
                'data' => self::afterSaleWarrantyDropdown(),
                'htmlOptions' => [],
                'search' => '='
            ],
            [
                'name' => 'is_handle',
                'alias' => 'l',
                'type' => 'dropDownList',
                'data' => self::handleDropdown(),
                'htmlOptions' => [],
                'search' => '='
            ],
        ];
    }

    public static function getFindOne($id)
    {
        return self::findOne(['id' => $id]);
    }

    /**
     * 修改模型数据
     */
    public function addition(&$models)
    {
        //获取速卖通所有账号信息
        $accounts = Account::getAccount(Platform::PLATFORM_CODE_ALI, 1);

        $timeList = AliexpressHolidayResponseTime::find()
            ->andWhere(['status' => 1])
            ->orderBy('id DESC')
            ->asArray()
            ->all();

        //纠纷ID数组
        $issueIds = [];
        //平台订单ID数组
        $orderIds = [];

        foreach ($models as $key => $model) {
            $issueIds[] = $model->platform_dispute_id;
            $orderIds[] = $model->platform_order_id;
            $orderIds[] = $model->platform_parent_order_id;
        }

        //获取订单ID和买家ID
        $orderIdAndBuyerIds = OrderAliexpressSearch::getOrderIdAndBuyerId($orderIds);

        //获取纠纷的协商方案
        $issueSolution = AliexpressDisputeSolution::find()
            ->select('platform_dispute_id,solution_id,status,reached_type')
            ->where(['in', 'platform_dispute_id', $issueIds])
            ->asArray()
            ->all();
        //纠纷是否上升到仲裁
        $issueArbitrate = [];
        if (!empty($issueSolution)) {
            foreach ($issueSolution as $solution) {
                //协商方案状态为reach_cancle说明该纠纷已经上升到仲裁
                if ($solution['status'] == 'reach_cancle') {
                    $issueArbitrate[$solution['platform_dispute_id']] = 1;
                }
            }
        }

        foreach ($models as $key => $model) {

            $orderId = '';
            $buyerId = '';
            //注意这里，判断子订单号和父订单号
            if (array_key_exists($model->platform_order_id, $orderIdAndBuyerIds)) {
                $orderId = $orderIdAndBuyerIds[$model->platform_order_id]['order_id'];
                $buyerId = $orderIdAndBuyerIds[$model->platform_order_id]['buyer_id'];
            } else if (array_key_exists($model->platform_parent_order_id, $orderIdAndBuyerIds)) {
                $orderId = $orderIdAndBuyerIds[$model->platform_parent_order_id]['order_id'];
                $buyerId = $orderIdAndBuyerIds[$model->platform_parent_order_id]['buyer_id'];
            }
            $model->setAttribute('order_id', $orderId);
            $model->setAttribute('buyer_id', $buyerId);


            $platformOrderId = Html::a($model->platform_order_id, Url::toRoute(['/orders/order/orderdetails', 'order_id' => $model->platform_order_id, 'platform' => Platform::PLATFORM_CODE_ALI]),
                    ['class' => 'add-button', '_width' => '90%', '_height' => '90%']);

            //如果子订单与父订单不同，则显示父订单
            if ($model->platform_order_id != $model->platform_parent_order_id) {
                $platformOrderId = '<p style="margin:0;">子: ' . Html::a($model->platform_order_id, Url::toRoute(['/orders/order/orderdetails', 'order_id' => $model->platform_order_id, 'platform' => Platform::PLATFORM_CODE_ALI]),
                        ['class' => 'add-button', '_width' => '90%', '_height' => '90%']) . '</p>';

                $platformOrderId .= '<p style="margin:0;">父: ' . Html::a($model->platform_parent_order_id, Url::toRoute(['/orders/order/orderdetails', 'order_id' => $model->platform_parent_order_id, 'platform' => Platform::PLATFORM_CODE_ALI]),
                        ['class' => 'add-button', '_width' => '90%', '_height' => '90%']) . '</p>';
            }

            //显示平台订单
            $model->setAttribute('platform_order_id', $platformOrderId);

            $accountName = array_key_exists($model->account_id, $accounts) ? $accounts[$model->account_id] : '';
            //如果账号名称还为空，则查询客服系统中的信息
            if (empty($accountName)) {
                $accountName = $this->getAccountName($model->account_id);
            }

            $issue_reponse_last_time = '-';
            $refuse_issue_last_time = '-';
            //纠纷详情
            $issueDetail = AliexpressDisputeDetail::findOne(['platform_dispute_id' => $model->platform_dispute_id, 'account_id' => $model->account_id]);
            //只有纠纠状在处理中的才显示
            if ($model->issue_status == 'processing') {
                //纠纷响应时间
                $issue_reponse_day = self::ISSUE_REPONSE_DAY;
                //拒绝纠纷上升仲裁时间
                $refuse_issue_day = self::REFUSE_ISSUE_DAY;
                $time = [];
                if (!empty($timeList)) {
                    $gmt_create = strtotime($model->gmt_create);
                    foreach ($timeList as $item) {
                        if (strtotime($item['start_time']) <= $gmt_create && $gmt_create <= strtotime($item['end_time'])) {
                            $time = $item;
                            break;
                        }
                    }
                }
                if (!empty($time)) {
                    $issue_reponse_day = $time['issue_reponse_day'];
                    $refuse_issue_day = $time['refuse_issue_day'];
                }
                if (empty($issueDetail->after_sale_warranty)) {
                    //纠纷响应剩余时间

                    //无忧物流问题判断
                    $process = AliexpressDisputeProcess::find()
                        ->andWhere(['platform_dispute_id' => $model->platform_dispute_id])
                        ->andWhere(['account_id' => $model->account_id])
                        ->asArray()
                        ->all();

                    //是否无忧物流问题
                    $isAliExpress = 0;
                    if (!empty($process)) {
                        foreach ($process as $key => $item) {
                            //找到操作类型为fpl_authenticate并且内容为AliExpress accepted
                            if ($item['action_type'] == 'fpl_authenticate' && $item['content'] == 'AliExpress accepted') {
                                $next = $key + 1;
                                //如果该操作记录下一条紧接着平台给出方案，可以判断为无忧物流问题
                                if (array_key_exists($next, $process)) {
                                    if ($process[$next]['action_type'] == 'platform_give_solution') {
                                        $isAliExpress = 1;
                                    }
                                }
                            }
                        }
                    }

                    if ($isAliExpress) {
                        $issue_reponse_last_time = '无忧物流问题(仅供参考)';
                    } else {

                        //默认为纠纷创建时间
                        $gmtTime = $model->gmt_create;
                        $issue_reponse_desc = '';

                        //判断买家是否重新发启了纠纷
                        $initiate = AliexpressDisputeProcess::find()
                            ->andWhere(['platform_dispute_id' => $model->platform_dispute_id])
                            ->andWhere(['account_id' => $model->account_id])
                            ->andWhere(['action_type' => 'initiate'])
                            ->andWhere(['submit_member_type' => 'buyer'])
                            ->orderBy('gmt_create DESC')
                            ->limit(1)
                            ->one();

                        //如果最新发启的纠纷时间大于纠纷列表的时间，说明买家又重新发启了纠纷
                        if (!empty($initiate) && (strtotime($initiate->gmt_create) > strtotime($model->gmt_create))) {
                            $issue_reponse_desc = '再开纠纷';
                            $gmtTime = $initiate->gmt_create;
                        }

                        //注意这里的时区问题，接口返回时间是美国时间，这里临时将时区设为美国洛杉矶
                        $tz = date_default_timezone_get();
                        date_default_timezone_set('America/Los_Angeles');

                        $end_issue_reponse_last_time = strtotime("+{$issue_reponse_day} day", strtotime($gmtTime));
                        $end_issue_reponse_last_time_str = date('Y/m/d H:i:s', $end_issue_reponse_last_time);
                        $issue_reponse_last_time = $end_issue_reponse_last_time - time();

                        //把当前时区设回原来时区
                        date_default_timezone_set($tz);

                        if ($issue_reponse_last_time > 0) {
                            $issue_reponse_last_time = VHelper::sec2string($issue_reponse_last_time);
                            $issue_reponse_last_time = "{$issue_reponse_desc}<span class='issue_reponse_last_time' data-endtime='{$end_issue_reponse_last_time_str}'>{$issue_reponse_last_time}</span>";
                        } else {
                            $issue_reponse_last_time = '<span style="color:red;">已超时</span>';
                        }

                        //如果卖家方案时间大于买家方案时间，则表示已经响应过，不显示纠纷响应剩余时间
                        $sellerSoulution = AliexpressDisputeSolution::find()
                            ->andWhere(['platform_dispute_id' => $model->platform_dispute_id])
                            ->andWhere(['account_id' => $model->account_id])
                            ->andWhere(['solution_owner' => 'seller'])
                            ->orderBy('gmt_modified DESC')
                            ->limit(1)
                            ->one();

                        $buyerSoulution = AliexpressDisputeSolution::find()
                            ->andWhere(['platform_dispute_id' => $model->platform_dispute_id])
                            ->andWhere(['account_id' => $model->account_id])
                            ->andWhere(['solution_owner' => 'buyer'])
                            ->orderBy('gmt_modified DESC')
                            ->limit(1)
                            ->one();

                        if (!empty($sellerSoulution) && !empty($buyerSoulution)) {
                            if (strtotime($sellerSoulution->gmt_modified) > strtotime($buyerSoulution->gmt_modified)) {
                                $issue_reponse_last_time = '已响应';
                            }
                        }
                    }
                } else {
                    $issue_reponse_last_time = '售后宝处理中';
                }

                //已拒绝纠纷上升仲裁剩余时间
                if (array_key_exists($model->platform_dispute_id, $issueArbitrate)) {

                    //注意这里的时区问题，接口返回时间是美国时间，这里临时将时区设为美国洛杉矶
                    $tz = date_default_timezone_get();
                    date_default_timezone_set('America/Los_Angeles');

                    $end_refuse_issue_last_time = strtotime("+{$refuse_issue_day} day", strtotime($model->gmt_create));
                    $end_refuse_issue_last_time_str = date('Y/m/d H:i:s', $end_refuse_issue_last_time);
                    $refuse_issue_last_time = $end_refuse_issue_last_time - time();

                    //把当前时区设回原来时区
                    date_default_timezone_set($tz);

                    if ($refuse_issue_last_time > 0) {
                        $refuse_issue_last_time = VHelper::sec2string($end_refuse_issue_last_time);
                        $refuse_issue_last_time = "<span class='refuse_issue_last_time' data-endtime='{$end_refuse_issue_last_time_str}'>{$refuse_issue_last_time}</span>";
                    } else {
                        $refuse_issue_last_time = '<span style="color:red;">已超时</span>';
                    }
                }
            }

            //是否处理
            $handleText = '';
            switch ($model->is_handle) {
                case 0:
                    $handleText = '<span style="color:red;">未处理</span>';
                    break;
                case 1:
                    $handleText = '<span style="color:green;">已处理</span>';
                    break;
                case 2:
                    $handleText = '<span>标记处理</span>';
                    break;
            }
            $model->setAttribute('is_handle', $handleText);

            //解纷完结信息
            $finishInfo = '';
            $solution_owner = $issueDetail->solution_owner;
            $refund_money_post_currency = $issueDetail->refund_money_post_currency;
            $refund_money_post = $issueDetail->refund_money_post;

            if ($model->issue_status == 'finish') {
                if (!empty($issueDetail->after_sale_warranty) && ($solution_owner == 'platform')) {
                    //售后宝处理
                    $finishInfo = '<span>纠纷结束<br/>仅退款，'.$refund_money_post_currency.' $ '.$refund_money_post.'<br/>由AE平台出资</span>';
                }elseif(empty($issueDetail->after_sale_warranty) && ($solution_owner == 'platform')) {
                    //由无忧物流处理
                    $finishInfo = '<span>纠纷结束<br/>仅退款，'.$refund_money_post_currency.' $ '.$refund_money_post.'</span>';
                }elseif(empty($issueDetail->after_sale_warranty) && ($solution_owner == 'seller')) {
                    //买家处理
                    $finishInfo = '<span>纠纷结束<br/>仅退款，'.$refund_money_post_currency.' $ '.$refund_money_post.'<br/>由买家出资</span>';
                }else{
                    $finishInfo = '<span>信息不全</span>';
                }
            }elseif($model->issue_status == 'canceled_issue') {
                $finishInfo = '<span>纠纷已取消</span>';
            }else{
                $finishInfo = '<span>纠纷未结束</span>';
            }
            $model->setAttribute('finish_info',$finishInfo );

            $model->setAttribute('issue_reponse_last_time', $issue_reponse_last_time);
            $model->setAttribute('refuse_issue_last_time', $refuse_issue_last_time);
            $model->setAttribute('account_id', $accountName);
        }
    }


    /**
     * 纠纷状态列表
     */
    public static function issueStatusList()
    {
        $data = [
            'processing' => ' 纠纷处理中',
            'WAIT_SELLER_RECEIVE_GOODS' => '等待卖家收货',
            'canceled' => '纠纷已取消',
            'finish' => '纠纷已结束',
            'WAIT_SELLER_CONFIRM_REFUND' => '等待卖家确认',
            'OBTAIN_PLATFORM_REFUND' => '物流已赔付订单',
            'LOGISTICS_ISSUE' => '速卖通物流纠纷'
        ];
        return $data;
    }

    /**
     * 纠纷状态下拉框
     */
    public static function issueStatusDropdown()
    {
        $data = [
            'processing' => '纠纷处理中(processing)',
            'canceled_issue' => '纠纷取消(canceled_issue)',
            'finish' => '纠纷完结(finish)',
        ];
        return $data;
    }

    /**
     * 账号列表
     */
    public static function dropdown()
    {

        $accountmodel = new Account();
        $all_account = $accountmodel->findAll(['status' => 1, 'platform_code' => Platform::PLATFORM_CODE_ALI]);
        $arr = [' ' => '全部'];
        foreach ($all_account as $key => $value) {
            $arr[$value['id']] = $value['account_name'];
        }
        return $arr;
    }

    /**
     * 账号查询搜索下拉框
     */
    public static function accountDropdown()
    {
        $accounts = AliexpressAccount::getAccounts();
        $data = [' ' => '全部'];
        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                $data[$account['id']] = $account['account'];
            }
        }
        return $data;
    }

    /**
     * 获取账号名称
     */
    public static function getAccountName($account_id)
    {
        $accountModel = new Account();
        $accountModel = $accountModel->find()->where(['id' => $account_id])->select('account_name')->asArray()->one();
        return $accountModel['account_name'];
    }

    /**
     * 查询订单是否在纠纷表里面
     */
    public function queryOrderId($data = [])
    {
        $queryData = [];
        if (!empty($data)) {
            foreach ($data as $value) {
                $dispute = self::find()->where(['orderId' => $value['platform_order_id']])->select('caseid')->asArray()->one();
                /*如果存在，则是纠纷订单*/
                if (!empty($dispute)) {
                    $value['is_dispute'] = 1;
                } else {
                    $value['is_dispute'] = 0;
                }
                $queryData[] = $value;
            }
            $name = [];
            foreach ($queryData as $key => $value) {

                $name[$key] = $value['order_id'];
            }
            array_multisort($name, SORT_DESC, $queryData);
        }
        return $queryData;
    }

    /**
     * 新增数据
     */
    public function newlyAdded($account_id, $data = null)
    {
        $AliexpressDisputeList = new AliexpressDisputeList();
        if (!empty($data)) {
            $AliexpressDisputeList->account_id = $account_id;
            $AliexpressDisputeList->platform_dispute_id = $data->id;
            $AliexpressDisputeList->gmt_modified = VHelper::_toDate($data->gmtModified);
            $AliexpressDisputeList->issue_status = isset($data->issueStatus) ? $data->issueStatus : '';
            $AliexpressDisputeList->gmt_create = VHelper::_toDate($data->gmtCreate);
            $AliexpressDisputeList->reason_chinese = isset($data->reasonChinese) ? $data->reasonChinese : '';
            $AliexpressDisputeList->platform_order_id = isset($data->orderId) ? $data->orderId : '';
            $AliexpressDisputeList->reason_english = isset($data->reasonEnglish) ? $data->reasonEnglish : '';
            $AliexpressDisputeList->status = 0;
            $AliexpressDisputeList->create_by = 'admin';
            $AliexpressDisputeList->create_time = date('Y-m-d H:i:s');
            $AliexpressDisputeList->save(false);
            return $AliexpressDisputeList->primaryKey;
        }
        return false;
    }

    /**
     * 纠纷原因中文下拉框
     */
    public static function reasonChineseDropdown()
    {
        $data = [];
        $reason = self::find()->select('reason_chinese as reason_chinese_key,reason_chinese')
            ->groupBy('reason_chinese')
            ->asArray()
            ->all();

        if (!empty($reason)) {
            $reason = array_column($reason, 'reason_chinese', 'reason_chinese_key');
            $data = array_merge($data, $reason);
        }
        return $data;
    }

    /**
     * 纠纷原因英文下拉框
     */
    public static function reasonEnglishDropdown()
    {
        $data = [];
        $reason = self::find()->select('reason_english as reason_english_key,reason_english')
            ->groupBy('reason_english')
            ->asArray()
            ->all();

        if (!empty($reason)) {
            $reason = array_column($reason, 'reason_english', 'reason_english_key');
            $data = array_merge($data, $reason);
        }
        return $data;
    }

    /**
     * 是否售后宝代处理下拉框
     */
    public static function afterSaleWarrantyDropdown()
    {
        $data = [
            '1' => '是',
            '0' => '否',
        ];
        return $data;
    }

    /**
     * 是否处理
     */
    public static function handleDropdown()
    {
        $data = [
            '0' => '未处理',
            '1' => '已处理',
            '2' => '标记处理',
        ];
        return $data;
    }

    /**
     * 判断指定订单是否有纠纷
     */
    public static function whetherExist($platform_order_id)
    {
        if (empty($platform_order_id)) {
            return false;
        }

        $model = self::find()->where(['platform_order_id' => $platform_order_id])->one();

        //指定订单存在纠纷
        if (!empty($model)) {
            return true;
        }

        //指定订单不存在纠纷
        return false;
    }

    /**
     * 获取订单的纠纷ID
     */
    public static function getOrderDisputes($platformOrderId)
    {
        if (empty($platformOrderId)) {
            return false;
        }

        $data = self::find()
            ->select('platform_dispute_id')
            ->where(['platform_order_id' => $platformOrderId])
            ->asArray()
            ->all();

        return $data;
    }

    /**
     * 获取订单的纠纷状态
     */
    public static function getOrderDisputesIssueStatus($platformOrderId)
    {
        if (empty($platformOrderId)) {
            return false;
        }

        $data = self::find()
            ->select('issue_status', 'platform_dispute_id')
            ->where(['platform_order_id' => $platformOrderId])
            ->asArray()
            ->all();

        return $data;
    }
}

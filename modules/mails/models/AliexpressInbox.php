<?php

namespace app\modules\mails\models;

use app\modules\orders\models\OrderAliexpressDetail;
use app\modules\orders\models\OrderAliexpressKefu;
use app\modules\products\models\Product;
use Yii;
use app\common\VHelper;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\UserAccount;
use app\modules\systems\models\Tag;
use app\modules\systems\models\Rule;
use app\modules\services\modules\aliexpress\models\AliexpressOrder;
use app\modules\systems\models\Condition;
use app\modules\systems\models\RuleCondtion;
use app\modules\services\modules\aliexpress\models\AliexpressMessage;

class AliexpressInbox extends Inbox
{
    const PLATFORM_CODE = Platform::PLATFORM_CODE_ALI;
    const PROCESS_STATUS_YES = 1;      //已处理
    const PROCESS_STATUS_NO = 0;       //未处理

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%aliexpress_inbox}}';
    }

    /**
     * @desc 搜索过滤项
     * @return multitype:multitype:string multitype:  multitype:string multitype:string
     */
    public function filterOptions()
    {
        return [
            [
                'name' => 'msg_sources',
                'type' => 'dropDownList',
                'data' => ['message_center' => '站内信', 'order_msg' => '订单留言'],
                'search' => '=',
            ],
            [
                'name' => 'read_stat',
                'type' => 'dropDownList',
                'data' => ['未读', '已读'],
                'search' => '=',
            ],
            [
                'name' => 'is_replied',
                'type' => 'dropDownList',
                'data' => ['未回复', '已回复未同步', '已回复已同步','标记回复'],
                'search' => '=',
            ],
            [
                'name' => 'deal_stat',
                'type' => 'dropDownList',
                'data' => ['未处理', '已处理'],
                'search' => '=',
                'value' => self::PROCESS_STATUS_NO,
            ],
            [
                'name' => 'channel_id',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'other_name',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'tag_id',
                'type' => 'hidden',
                'search' => false,
                'alias' => 't1',
            ],
            [
                'name' => 'account_id',
                'type' => 'search',
                'data' => self::getAccountList(self::STATUS_VALID),
                'search' => '=',
            ],
            [
                'name' => 'start_time',
                'type' => 'date_picker',
                'search' => '<',
            ],
            [
                'name' => 'end_time',
                'type' => 'date_picker',
                'search' => '>',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'msg_sources' => '信息类型',
            'unread_count' => '是否已读',
            'channel_id' => '通道ID',
            'last_message_id' => '最后一条消息ID',
            'read_stat' => '未读状态',
            'last_message_content' => '最后一条消息内容',
            'last_message_is_own' => '最后一条消息是否自己这边发送',
            'child_name' => '消息所属账号',
            'receive_date' => '最后一条消息时间',
            'child_id' => '消息所属账号 ID',
            'other_name' => '买家名字',
            'other_login_id' => '买家账号',
            'deal_stat' => '处理状态',
            'rank' => '标签值',
            'is_replied' => '回复状态',
//            'account_id' => '店铺账号',
            'account_id' => '账号简称'
        ];
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \app\modules\mails\models\Inbox::addition()
     */
    public function addition(&$models)
    {
        foreach ($models as $key => $model) {
            $models[$key]->setAttribute('other_name', str_replace(' ', '&nbsp;', $model->other_name));
            $models[$key]->setAttribute('read_stat', self::getReadStat($model->read_stat));
            $models[$key]->setAttribute('deal_stat', self::getDealStat($model->deal_stat));
            $models[$key]->setAttribute('msg_sources', self::getMsgSources($model->msg_sources));
            $models[$key]->setAttribute('is_replied', self::getIsReplied($model->is_replied));
            $account_short_name = Account::getAccountNameAndShortName($model->account_id, 'ALI');
            $account_short_name = empty($account_short_name) ? '-' : $account_short_name['account_short_name'];
            $models[$key]->setAttribute('account_id', $account_short_name);
            $models[$key]->setAttribute('last_message_content',
                '<a target="_blank" href="/mails/aliexpress/details?id=' . $model->id . '">'
                . mb_substr($model->last_message_content, 0, 80) . '</a>');
        }
    }

    /*回复状态*/
    public static function getIsReplied($is_replied)
    {
        $read_stat = ['未回复', '已回复未同步', '已回复已同步','标记回复'];
        return $read_stat[$is_replied];
    }

    /*未读状态*/
    public static function getReadStat($readStat)
    {
        $read_stat = ['未读', '已读已同步', '已读未同步'];
        return $read_stat[$readStat];
    }

    /*处理状态*/
    public static function getDealStat($readStat)
    {
        $read_stat = ['未处理', '已处理'];
        return $read_stat[$readStat];
    }

    /*查询类型*/
    public static function getMsgSources($msg_sources)
    {
        $message = ['message_center' => '站内信', 'order_msg' => '订单留言'];
        return $message[$msg_sources];
    }

    public static function getOne($id)
    {
        return self::findOne(['id' => $id]);
    }

    /*下一封*/
    public function getNextSeal($id, $account_id)
    {
        $arrData = [];
        $arrData['href'] = "<a href='javascript:;' style='text-decoration:none;'>";
        $arrData['title'] = '没有了！~';
        $data = self::find()->where(['>', 'id', $id])->andWhere(['=', 'account_id', $account_id])->asArray()->one();
        if (!empty($data)) {
            $arrData['href'] = "<a href='/mails/aliexpress/details?id=" . $data['id'] . "' style='text-decoration:none;'>";
            $arrData['title'] = '下一封';
        }
        return $arrData;
    }

    /*根据id数组获取列表*/
    public function getListById($ids)
    {

        return self::find()->select('channel_id,account_id,other_name')->where(['in', 'id', $ids])->asArray()->all();
    }

    /*根据id将数据中的 处理状态 标记为已处理*/
    public function updateDealstatById($id)
    {
        $inbox = self::findOne($id);
        $inbox->deal_stat = 1;
        if ($inbox->update() !== false) {
            $aliexpressMessage = new AliexpressMessage();
            $aliexpressMessage->updateMessageProcessingState($inbox->account_id, $inbox->channel_id, 1);
        }
        return $inbox;
    }

    /**
     * @desc 获取订单
     * @return string
     */
    public function getOrderId()
    {
        return $this->msg_sources == 'order_msg' ? $this->channel_id : '';
    }

    /**
     * @desc 获取消息标题
     * @return string
     */
    public function getSubject()
    {
        return '';
    }

    /**
     * @desc 获取消息内容
     */
    public function getContent()
    {
        return $this->last_message_content;
    }

    /**
     * @desc 获取账号ID
     * @return number
     */
    public function getAccountId()
    {
        return $this->account_id;
    }

    /**
     * 重写获取待匹配标签的消息列表
     */
    public function getWattingMatchTagList($limit = 100)
    {
        return self::find()
            ->limit($limit)
            ->orderBy(['id' => SORT_ASC])
            ->all();
    }

    /**
     * 重写父类的查询列表方法
     */
    public function searchList($params = [], $sort = null)
    {
        //清除已处理消息列表
        $session = Yii::$app->session;
        self::destroyProcessedList();
        $query = self::find()->alias('t')->distinct();
        //添加时间查询
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 't.receive_date', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 't.receive_date', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 't.receive_date', $params['end_time']]);
        }

        //如果用户选中了标签
        if (isset($params['tag_id']) && !empty($params['tag_id'])) {

            //根据标签查询
            $query->innerJoin(['t1' => MailTag::tableName()], 't1.inbox_id = t.id AND t1.platform_code = :platform_code', [':platform_code' => Platform::PLATFORM_CODE_ALI]);
            $query->andWhere(['t1.tag_id' => $params['tag_id']]);
        }

        if (isset($params['account_id']) && !empty($params['account_id'])) {
            //将搜索账号ID存入Session
            $session->set('search_account_id', $params['account_id']);
        } else {
            $session->remove('search_account_id');
        }

        //只能查询到客服绑定账号的邮件
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);
        if (!empty($accountIds)) {
            $query->andWhere(['in', 't.account_id', $accountIds]);
        }

        if (empty($sort)) {
            $sort = new \yii\data\Sort([
                'attributes' => [
                    'id',
                    'receive_date'
                ],
            ]);
            $sort->defaultOrder = array(
                'receive_date' => SORT_DESC,
                'id' => SORT_DESC,
            );
        }
        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * 重写父类方法，匹配标签，给符合标签规则的消息打上标签
     */
    public function matchTags($inbox = null)
    {
        if (empty($inbox)) {
            return false;
        }

        //获取标签对应的规则列表
        $tagRuleList = Tag::find()
            ->select('t.id tag_id,
                      t.tag_name,
                      r.id rule_id,
                      r.rule_name,
                      rc.id rule_condtion_id,
                      rc.oprerator rule_condtion_oprerator,
                      group_concat(rc.option_value) rule_condtion_value,
                      rc.input_type rule_condition_input_type,
                      rc.condition_name rule_condition_name,
                      rc.condition_key rule_condition_key,
                      c.id condtion_id')
            ->alias('t')
            ->leftJoin(['r' => Rule::tableName()], 'r.relation_id = t.id AND r.platform_code = :platform_code1 AND r.status = :status1', [':platform_code1' => Platform::PLATFORM_CODE_ALI, ':status1' => Rule::RULE_STATUS_VALID])
            ->leftJoin(['rc' => RuleCondtion::tableName()], 'rc.rule_id = r.id')
            ->leftJoin(['c' => Condition::tableName()], 'c.id = rc.condtion_id')
            ->andWhere(['t.platform_code' => Platform::PLATFORM_CODE_ALI])
            ->andWhere(['t.status' => Tag::TAG_STATUS_VALID])
            ->groupBy('tag_id,condtion_id')
            ->asArray()
            ->all();

        if (empty($tagRuleList)) {
            return true;
        } else {
            $tmp = [];
            //构建数组，一个标签可能对应多个规则的情况
            foreach ($tagRuleList as $item) {
                if (!empty($item['rule_condtion_value']) && !empty($item['rule_condition_key'])) {
                    $conditionKey = explode('.', $item['rule_condition_key']);
                    $item['rule_condition_key'] = !empty($conditionKey[1]) ? $conditionKey[1] : '';
                    $tmp[$item['tag_id']][$item['condtion_id']] = $item;
                }
            }
            $tagRuleList = $tmp;
        }

        //要打的标签数组
        $tagIds = [];
        //先把消息关联的标签全部删除
        MailTag::deleteMialTags(Platform::PLATFORM_CODE_ALI, $inbox->id);

        if (!empty($tagRuleList)) {

            foreach ($tagRuleList as $tagId => $tagRule) {
                if (is_array($tagRule) && !empty($tagRule)) {

                    //是否符合标签规则
                    $flag = true;
                    //规则查询
                    $query = AliexpressReply::find()->andWhere(['inbox_id' => $inbox->id]);

                    //循环处理标签的规则
                    foreach ($tagRule as $rule) {
                        switch ($rule['rule_condition_input_type']) {
                            case 1://input类型
                            case 2://radio类型

                                //判断操作符
                                //1:大于,2:小于,3:等于,4:包含,5:不包含,6:范围,7:包含
                                if ($rule['rule_condtion_oprerator'] == 1) {
                                    if ($rule['rule_condition_key'] == 'content') {
                                        $query->andWhere(['>', 'reply_content', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'subject') {
                                        $query->andWhere(['>', 'reply_title', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'sender') {
                                        $query->andWhere(['>', 'reply_by', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'sender_email') {

                                    } else if ($rule['rule_condition_key'] == 'total_price') {
                                        if (!empty($inbox->channel_id)) {
                                            if($inbox->msg_sources == 'order_msg'){
                                                $exists = AliexpressOrder::find()->where([
                                                    'and',
                                                    ['platform_order_id' => $inbox->channel_id],
                                                    ['>', 'total_price', $rule['rule_condtion_value']],
                                                ])->exists();

                                                if (!$exists) {
                                                    $flag = false;
                                                }
                                            }else if($inbox->msg_sources == 'message_center'){
                                                $ali_replay =  AliexpressReply::find()
                                                    ->where(['channel_id' => $inbox->channel_id])
                                                    ->andWhere(['reply_content' => $inbox->last_message_content])
                                                    ->andWhere(['message_type' => 'order'])
                                                    ->one();

                                                if(!empty($ali_replay)){
                                                    $exists = AliexpressOrder::find()->where([
                                                        'and',
                                                        ['platform_order_id' => $ali_replay->type_id],
                                                        ['>', 'total_price', $rule['rule_condtion_value']],
                                                    ])->exists();
                                                }else{
                                                    $exists = '';
                                                }

                                                if (!$exists) {
                                                    $flag = false;
                                                }
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    } else if ($rule['rule_condition_key'] == 'account_id') {
                                        $query->andWhere(['>', 'account_id', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'receive_date') {
                                        if (strtotime($inbox->receive_date) < strtotime($rule['rule_condtion_value'])) {
                                            $flag = false;
                                        }
                                    } else if ($rule['rule_condition_key'] == 'product_subject') {
                                        //产品标题搜索
                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $orderInfo = AliexpressOrder::findOne(['platform_order_id' => $inbox->channel_id]);
                                            if (!empty($orderInfo)) {
                                                //判断产品英文是否匹配
                                                $titleOk = false;
                                                //判断产品中文是否匹配
                                                $pickingNameOk = false;

                                                //查询订单产品的标题
                                                $details = OrderAliexpressDetail::find()
                                                    ->select('title, sku')
                                                    ->andWhere(['order_id' => $orderInfo->order_id])
                                                    ->asArray()
                                                    ->all();

                                                if (!empty($details)) {
                                                    $skus = array_column($details, 'sku');
                                                    $products = Product::find()
                                                        ->select('picking_name')
                                                        ->andWhere(['in', 'sku', $skus])
                                                        ->asArray()
                                                        ->all();

                                                    foreach ($details as $detail) {
                                                        if ($detail['title'] == $rule['rule_condtion_value']) {
                                                            $titleOk = true;
                                                            break;
                                                        }
                                                    }

                                                    if (!empty($products)) {
                                                        foreach ($products as $product) {
                                                            if ($product['picking_name'] == $rule['rule_condtion_value']) {
                                                                $pickingNameOk = true;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }

                                                //如果产品英文与中文都匹配不上，则为false
                                                if (!($titleOk || $pickingNameOk)) {
                                                    $flag = false;
                                                }
                                            } else {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    }

                                } else if ($rule['rule_condtion_oprerator'] == 2) {
                                    if ($rule['rule_condition_key'] == 'content') {
                                        $query->andWhere(['<', 'reply_content', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'subject') {
                                        $query->andWhere(['<', 'reply_title', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'sender') {
                                        $query->andWhere(['<', 'reply_by', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'sender_email') {

                                    } else if ($rule['rule_condition_key'] == 'total_price') {
                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $exists = AliexpressOrder::find()->where([
                                                'and',
                                                ['platform_order_id' => $inbox->channel_id],
                                                ['<', 'total_price', $rule['rule_condtion_value']],
                                            ])->exists();

                                            if (!$exists) {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    } else if ($rule['rule_condition_key'] == 'account_id') {
                                        $query->andWhere(['<', 'account_id', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'receive_date') {
                                        if (strtotime($inbox->receive_date) > strtotime($rule['rule_condtion_value'])) {
                                            $flag = false;
                                        }
                                    }
                                } else if ($rule['rule_condtion_oprerator'] == 3) {
                                    if ($rule['rule_condition_key'] == 'content') {
                                        $query->andWhere(['reply_content' => $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'subject') {
                                        $query->andWhere(['reply_title' => $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'sender') {
                                        $query->andWhere(['reply_by' => $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'sender_email') {

                                    } else if ($rule['rule_condition_key'] == 'total_price') {
                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $exists = AliexpressOrder::find()->where([
                                                'and',
                                                ['platform_order_id' => $inbox->channel_id],
                                                ['total_price' => $rule['rule_condtion_value']],
                                            ])->exists();

                                            if (!$exists) {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    } else if ($rule['rule_condition_key'] == 'account_id') {
                                        $query->andWhere(['account_id' => $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'receive_date') {
                                        if (date('Y-m-d', strtotime($inbox->receive_date)) != $rule['rule_condtion_value']) {
                                            $flag = false;
                                        }
                                    }
                                } else if ($rule['rule_condtion_oprerator'] == 4) {
                                    $rule['rule_condtion_value'] = explode(',', $rule['rule_condtion_value']);

                                    if ($rule['rule_condition_key'] == 'content') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $cond[] = 'or';
                                            foreach ($rule['rule_condtion_value'] as $value) {
                                                $cond[] = ['like', 'reply_content', trim($value)];
                                            }
                                            $query->andWhere($cond);
                                        } else {
                                            $query->andWhere(['like', 'reply_content', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'subject') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $cond[] = 'or';
                                            foreach ($rule['rule_condtion_value'] as $value) {
                                                $cond[] = ['like', 'reply_title', trim($value)];
                                            }
                                            $query->andWhere($cond);
                                        } else {
                                            $query->andWhere(['like', 'reply_title', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'sender') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $query->andWhere(['in', 'reply_by', $rule['rule_condtion_value']]);
                                        } else {
                                            $query->andWhere(['like', 'reply_by', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'sender_email') {

                                    } else if ($rule['rule_condition_key'] == 'total_price') {
                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $exists = AliexpressOrder::find()->where([
                                                'and',
                                                ['platform_order_id' => $inbox->channel_id],
                                                ['in', 'total_price', $rule['rule_condtion_value']],
                                            ])->exists();

                                            if (!$exists) {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    } else if ($rule['rule_condition_key'] == 'account_id') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $query->andWhere(['in', 'account_id', $rule['rule_condtion_value']]);
                                        } else {
                                            $query->andWhere(['like', 'account_id', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'receive_date') {
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            if (strpos(date('Y-m-d', strtotime($inbox->receive_date)), $value) === false) {
                                                $flag = false;
                                                break;
                                            }
                                        }
                                    } else if ($rule['rule_condition_key'] == 'product_subject') {
                                        //产品标题搜索
                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $orderInfo = AliexpressOrder::findOne(['platform_order_id' => $inbox->channel_id]);
                                            if (!empty($orderInfo)) {
                                                //判断产品英文是否匹配
                                                $titleOk = false;
                                                //判断产品中文是否匹配
                                                $pickingNameOk = false;

                                                //查询订单产品的标题
                                                $details = OrderAliexpressDetail::find()
                                                    ->select('title, sku')
                                                    ->andWhere(['order_id' => $orderInfo->order_id])
                                                    ->asArray()
                                                    ->all();

                                                if (!empty($details)) {
                                                    $skus = array_column($details, 'sku');
                                                    $products = Product::find()
                                                        ->select('picking_name')
                                                        ->andWhere(['in', 'sku', $skus])
                                                        ->asArray()
                                                        ->all();

                                                    foreach ($details as $detail) {
                                                        foreach ($rule['rule_condtion_value'] as $value) {
                                                            if (stripos($detail['title'], $value) !== false) {
                                                                $titleOk = true;
                                                                break;
                                                            }
                                                        }
                                                    }

                                                    if (!empty($products)) {
                                                        foreach ($products as $product) {
                                                            foreach ($rule['rule_condtion_value'] as $value) {
                                                                if (stripos($product['picking_name'], $value) !== false) {
                                                                    $pickingNameOk = true;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }

                                                //如果产品英文与中文都匹配不上，则为false
                                                if (!($titleOk || $pickingNameOk)) {
                                                    $flag = false;
                                                }
                                            } else {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    }

                                } else if ($rule['rule_condtion_oprerator'] == 5) {
                                    $rule['rule_condtion_value'] = explode(',', $rule['rule_condtion_value']);

                                    if ($rule['rule_condition_key'] == 'content') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $query->andWhere(['not in', 'reply_content', $rule['rule_condtion_value']]);
                                        } else {
                                            $query->andWhere(['not like', 'reply_content', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'subject') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $query->andWhere(['not in', 'reply_title', $rule['rule_condtion_value']]);
                                        } else {
                                            $query->andWhere(['not like', 'reply_title', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'sender') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $query->andWhere(['not in', 'reply_by', $rule['rule_condtion_value']]);
                                        } else {
                                            $query->andWhere(['not like', 'reply_by', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'sender_email') {

                                    } else if ($rule['rule_condition_key'] == 'total_price') {
                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $exists = AliexpressOrder::find()->where([
                                                'and',
                                                ['platform_order_id' => $inbox->channel_id],
                                                ['not in', 'total_price', $rule['rule_condtion_value']],
                                            ])->exists();

                                            if (!$exists) {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    } else if ($rule['rule_condition_key'] == 'account_id') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $query->andWhere(['not in', 'account_id', $rule['rule_condtion_value']]);
                                        } else {
                                            $query->andWhere(['not like', 'account_id', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'receive_date') {
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            if (strpos(date('Y-m-d', strtotime($inbox->receive_date)), $value) !== false) {
                                                $flag = false;
                                                break;
                                            }
                                        }
                                    } else if ($rule['rule_condition_key'] == 'product_subject') {
                                        //产品标题搜索
                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $orderInfo = AliexpressOrder::findOne(['platform_order_id' => $inbox->channel_id]);
                                            if (!empty($orderInfo)) {
                                                //判断产品英文是否匹配
                                                $titleOk = false;
                                                //判断产品中文是否匹配
                                                $pickingNameOk = false;

                                                //查询订单产品的标题
                                                $details = OrderAliexpressDetail::find()
                                                    ->select('title, sku')
                                                    ->andWhere(['order_id' => $orderInfo->order_id])
                                                    ->asArray()
                                                    ->all();

                                                if (!empty($details)) {
                                                    $skus = array_column($details, 'sku');
                                                    $products = Product::find()
                                                        ->select('picking_name')
                                                        ->andWhere(['in', 'sku', $skus])
                                                        ->asArray()
                                                        ->all();

                                                    foreach ($details as $detail) {
                                                        foreach ($rule['rule_condtion_value'] as $value) {
                                                            if (stripos($detail['title'], $value) !== false) {
                                                                $titleOk = true;
                                                                break;
                                                            }
                                                        }
                                                    }

                                                    if (!empty($products)) {
                                                        foreach ($products as $product) {
                                                            foreach ($rule['rule_condtion_value'] as $value) {
                                                                if (stripos($product['picking_name'], $value) !== false) {
                                                                    $pickingNameOk = true;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }

                                                //如果产品英文与中文只要匹配上，就为false
                                                if ($titleOk || $pickingNameOk) {
                                                    $flag = false;
                                                }
                                            } else {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    }

                                } else if ($rule['rule_condtion_oprerator'] == 7) {
                                    $rule['rule_condtion_value'] = explode(',', $rule['rule_condtion_value']);

                                    if ($rule['rule_condition_key'] == 'content') {
                                        $query->andWhere(['in', 'reply_content', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'subject') {
                                        $query->andWhere(['in', 'reply_title', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'sender') {
                                        $query->andWhere(['in', 'reply_by', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'sender_email') {

                                    } else if ($rule['rule_condition_key'] == 'total_price') {
                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $exists = AliexpressOrder::find()->where([
                                                'and',
                                                ['platform_order_id' => $inbox->channel_id],
                                                ['in', 'total_price', $rule['rule_condtion_value']],
                                            ])->exists();

                                            if (!$exists) {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    } else if ($rule['rule_condition_key'] == 'account_id') {
                                        $query->andWhere(['in', 'account_id', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'receive_date') {
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            if (strpos(date('Y-m-d', strtotime($inbox->receive_date)), $value) === false) {
                                                $flag = false;
                                                break;
                                            }
                                        }
                                    } else if ($rule['rule_condition_key'] == 'product_subject') {
                                        //产品标题搜索
                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $orderInfo = AliexpressOrder::findOne(['platform_order_id' => $inbox->channel_id]);
                                            if (!empty($orderInfo)) {
                                                //判断产品英文是否匹配
                                                $titleOk = false;
                                                //判断产品中文是否匹配
                                                $pickingNameOk = false;

                                                //查询订单产品的标题
                                                $details = OrderAliexpressDetail::find()
                                                    ->select('title, sku')
                                                    ->andWhere(['order_id' => $orderInfo->order_id])
                                                    ->asArray()
                                                    ->all();

                                                if (!empty($details)) {
                                                    $skus = array_column($details, 'sku');
                                                    $products = Product::find()
                                                        ->select('picking_name')
                                                        ->andWhere(['in', 'sku', $skus])
                                                        ->asArray()
                                                        ->all();

                                                    foreach ($details as $detail) {
                                                        foreach ($rule['rule_condtion_value'] as $value) {
                                                            if ($detail['title'] == $value) {
                                                                $titleOk = true;
                                                                break;
                                                            }
                                                        }
                                                    }

                                                    if (!empty($products)) {
                                                        foreach ($products as $product) {
                                                            foreach ($rule['rule_condtion_value'] as $value) {
                                                                if ($product['picking_name'] == $value) {
                                                                    $pickingNameOk = true;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }

                                                //如果产品英文与中文都匹配不上，则为false
                                                if (!($titleOk || $pickingNameOk)) {
                                                    $flag = false;
                                                }
                                            } else {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    }
                                }

                                break;
                            case 3://select类型
                            case 4://checkbox类型

                                //select和checkbox可能有多选，规则的值转换成数组
                                $rule['rule_condtion_value'] = explode(',', $rule['rule_condtion_value']);
                                //where条件表达式
                                $cond = [];

                                //判断操作符
                                //1:大于,2:小于,3:等于,4:包含,5:不包含,6:范围
                                if ($rule['rule_condtion_oprerator'] == 1) {
                                    if ($rule['rule_condition_key'] == 'content') {
                                        $cond[] = 'or';
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            $cond[] = ['>', 'reply_content', $value];
                                        }
                                        $query->andWhere($cond);
                                    } else if ($rule['rule_condition_key'] == 'subject') {
                                        $cond[] = 'or';
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            $cond[] = ['>', 'reply_title', $value];
                                        }
                                        $query->andWhere($cond);
                                    } else if ($rule['rule_condition_key'] == 'sender') {
                                        $cond[] = 'or';
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            $cond[] = ['>', 'reply_by', $value];
                                        }
                                        $query->andWhere($cond);
                                    } else if ($rule['rule_condition_key'] == 'sender_email') {

                                    } else if ($rule['rule_condition_key'] == 'total_price') {
                                        $cond[] = 'or';
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            $cond[] = ['>', 'total_price', $value];
                                        }

                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $exists = AliexpressOrder::find()->where([
                                                'and',
                                                ['platform_order_id' => $inbox->channel_id],
                                                $cond,
                                            ])->exists();

                                            if (!$exists) {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    } else if ($rule['rule_condition_key'] == 'account_id') {
                                        $cond[] = 'or';
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            $cond[] = ['>', 'account_id', $value];
                                        }
                                        $query->andWhere($cond);
                                    } else if ($rule['rule_condition_key'] == 'receive_date') {
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            if (strtotime($inbox->receive_date) < strtotime($value)) {
                                                $flag = false;
                                                break;
                                            }
                                        }
                                    }

                                } else if ($rule['rule_condtion_oprerator'] == 2) {
                                    if ($rule['rule_condition_key'] == 'content') {
                                        $cond[] = 'or';
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            $cond[] = ['<', 'reply_content', $value];
                                        }
                                        $query->andWhere($cond);
                                    } else if ($rule['rule_condition_key'] == 'subject') {
                                        $cond[] = 'or';
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            $cond[] = ['<', 'reply_title', $value];
                                        }
                                        $query->andWhere($cond);
                                    } else if ($rule['rule_condition_key'] == 'sender') {
                                        $cond[] = 'or';
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            $cond[] = ['<', 'reply_by', $value];
                                        }
                                        $query->andWhere($cond);
                                    } else if ($rule['rule_condition_key'] == 'sender_email') {

                                    } else if ($rule['rule_condition_key'] == 'total_price') {
                                        $cond[] = 'or';
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            $cond[] = ['<', 'total_price', $value];
                                        }

                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $exists = AliexpressOrder::find()->where([
                                                'and',
                                                ['platform_order_id' => $inbox->channel_id],
                                                $cond,
                                            ])->exists();

                                            if (!$exists) {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    } else if ($rule['rule_condition_key'] == 'account_id') {
                                        $cond[] = 'or';
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            $cond[] = ['<', 'account_id', $value];
                                        }
                                        $query->andWhere($cond);
                                    } else if ($rule['rule_condition_key'] == 'receive_date') {
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            if (strtotime($inbox->receive_date) > strtotime($value)) {
                                                $flag = false;
                                                break;
                                            }
                                        }
                                    }
                                } else if ($rule['rule_condtion_oprerator'] == 3) {
                                    if ($rule['rule_condition_key'] == 'content') {
                                        $cond[] = 'or';
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            $cond[] = ['reply_content' => $value];
                                        }
                                        $query->andWhere($cond);
                                    } else if ($rule['rule_condition_key'] == 'subject') {
                                        $cond[] = 'or';
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            $cond[] = ['reply_title' => $value];
                                        }
                                        $query->andWhere($cond);
                                    } else if ($rule['rule_condition_key'] == 'sender') {
                                        $cond[] = 'or';
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            $cond[] = ['reply_by' => $value];
                                        }
                                        $query->andWhere($cond);
                                    } else if ($rule['rule_condition_key'] == 'sender_email') {

                                    } else if ($rule['rule_condition_key'] == 'total_price') {
                                        $cond[] = 'or';
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            $cond[] = ['total_price' => $value];
                                        }

                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $exists = AliexpressOrder::find()->where([
                                                'and',
                                                ['platform_order_id' => $inbox->channel_id],
                                                $cond,
                                            ])->exists();

                                            if (!$exists) {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    } else if ($rule['rule_condition_key'] == 'account_id') {
                                        $cond[] = 'or';
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            $cond[] = ['account_id' => $value];
                                        }
                                        $query->andWhere($cond);
                                    } else if ($rule['rule_condition_key'] == 'receive_date') {
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            if (date('Y-m-d', strtotime($inbox->receive_date)) != $value) {
                                                $flag = false;
                                                break;
                                            }
                                        }
                                    }
                                } else if ($rule['rule_condtion_oprerator'] == 4) {
                                    if ($rule['rule_condition_key'] == 'content') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $query->andWhere(['in', 'reply_content', $rule['rule_condtion_value']]);
                                        } else {
                                            $query->andWhere(['like', 'reply_content', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'subject') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $query->andWhere(['in', 'reply_title', $rule['rule_condtion_value']]);
                                        } else {
                                            $query->andWhere(['like', 'reply_title', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'sender') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $query->andWhere(['in', 'reply_by', $rule['rule_condtion_value']]);
                                        } else {
                                            $query->andWhere(['like', 'reply_by', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'sender_email') {

                                    } else if ($rule['rule_condition_key'] == 'total_price') {
                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $exists = AliexpressOrder::find()->where([
                                                'and',
                                                ['platform_order_id' => $inbox->channel_id],
                                                ['in', 'total_price', $rule['rule_condtion_value']],
                                            ])->exists();

                                            if (!$exists) {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    } else if ($rule['rule_condition_key'] == 'account_id') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $query->andWhere(['in', 'account_id', $rule['rule_condtion_value']]);
                                        } else {
                                            $query->andWhere(['like', 'account_id', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'receive_date') {
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            if (strpos(date('Y-m-d', strtotime($inbox->receive_date)), $value) === false) {
                                                $flag = false;
                                                break;
                                            }
                                        }
                                    } else if ($rule['rule_condition_key'] == 'customer_country') {
                                        //客户国家检索
                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $exists = AliexpressOrder::find()->where([
                                                'and',
                                                ['platform_order_id' => $inbox->channel_id],
                                                ['in', 'ship_country', $rule['rule_condtion_value']],
                                            ])->exists();

                                            if (!$exists) {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    } else if ($rule['rule_condition_key'] == 'logistics_mode') {
                                        //物流方式检索
                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $exists = AliexpressOrder::find()
                                                ->andWhere(['platform_order_id' => $inbox->channel_id])
                                                ->andWhere([
                                                    'or',
                                                    ['in', 'real_ship_code', $rule['rule_condtion_value']],
                                                    ['in', 'ship_code', $rule['rule_condtion_value']],
                                                ])
                                                ->exists();

                                            if (!$exists) {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    }

                                } else if ($rule['rule_condtion_oprerator'] == 5) {
                                    if ($rule['rule_condition_key'] == 'content') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $query->andWhere(['not in', 'reply_content', $rule['rule_condtion_value']]);
                                        } else {
                                            $query->andWhere(['not like', 'reply_content', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'subject') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $query->andWhere(['not in', 'reply_title', $rule['rule_condtion_value']]);
                                        } else {
                                            $query->andWhere(['not like', 'reply_title', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'sender') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $query->andWhere(['not in', 'reply_by', $rule['rule_condtion_value']]);
                                        } else {
                                            $query->andWhere(['not like', 'reply_by', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'sender_email') {

                                    } else if ($rule['rule_condition_key'] == 'total_price') {
                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $exists = AliexpressOrder::find()->where([
                                                'and',
                                                ['platform_order_id' => $inbox->channel_id],
                                                ['not in', 'total_price', $rule['rule_condtion_value']],
                                            ])->exists();

                                            if (!$exists) {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    } else if ($rule['rule_condition_key'] == 'account_id') {
                                        if (count($rule['rule_condtion_value']) > 1) {
                                            $query->andWhere(['not in', 'account_id', $rule['rule_condtion_value']]);
                                        } else {
                                            $query->andWhere(['not like', 'account_id', $rule['rule_condtion_value']]);
                                        }
                                    } else if ($rule['rule_condition_key'] == 'receive_date') {
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            if (strpos(date('Y-m-d', strtotime($inbox->receive_date)), $value) !== false) {
                                                $flag = false;
                                                break;
                                            }
                                        }
                                    }
                                } else if ($rule['rule_condtion_oprerator'] == 7) {
                                    if ($rule['rule_condition_key'] == 'content') {
                                        $query->andWhere(['in', 'reply_content', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'subject') {
                                        $query->andWhere(['in', 'reply_title', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'sender') {
                                        $query->andWhere(['in', 'reply_by', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'sender_email') {

                                    } else if ($rule['rule_condition_key'] == 'total_price') {
                                        if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                            $exists = AliexpressOrder::find()->where([
                                                'and',
                                                ['platform_order_id' => $inbox->channel_id],
                                                ['in', 'total_price', $rule['rule_condtion_value']],
                                            ])->exists();

                                            if (!$exists) {
                                                $flag = false;
                                            }
                                        } else {
                                            $flag = false;
                                        }
                                    } else if ($rule['rule_condition_key'] == 'account_id') {
                                        $query->andWhere(['in', 'account_id', $rule['rule_condtion_value']]);
                                    } else if ($rule['rule_condition_key'] == 'receive_date') {
                                        foreach ($rule['rule_condtion_value'] as $value) {
                                            if (strpos(date('Y-m-d', strtotime($inbox->receive_date)), $value) === false) {
                                                $flag = false;
                                                break;
                                            }
                                        }
                                    }
                                }

                                break;
                            case 5://范围类型

                                $rule['rule_condtion_value'] = explode(',', $rule['rule_condtion_value']);
                                if (count($rule['rule_condtion_value']) < 2) {
                                    break;
                                }

                                //获取范围的最小值和最大值
                                $min = 0;
                                $max = 0;
                                if ($rule['rule_condtion_value'][0] > $rule['rule_condtion_value'][1]) {
                                    $min = $rule['rule_condtion_value'][1];
                                    $max = $rule['rule_condtion_value'][0];
                                } else {
                                    $min = $rule['rule_condtion_value'][0];
                                    $max = $rule['rule_condtion_value'][1];
                                }

                                if ($rule['rule_condition_key'] == 'content') {
                                    $query->andWhere(['between', 'reply_content', $min, $max]);
                                } else if ($rule['rule_condition_key'] == 'subject') {
                                    $query->andWhere(['between', 'reply_title', $min, $max]);
                                } else if ($rule['rule_condition_key'] == 'sender') {
                                    $query->andWhere(['between', 'reply_by', $min, $max]);
                                } else if ($rule['rule_condition_key'] == 'sender_email') {

                                } else if ($rule['rule_condition_key'] == 'total_price') {
                                    if ($inbox->msg_sources == 'order_msg' && !empty($inbox->channel_id)) {
                                        $exists = AliexpressOrder::find()->where([
                                            'and',
                                            ['platform_order_id' => $inbox->channel_id],
                                            ['between', 'total_price', $min, $max],
                                        ])->exists();

                                        if (!$exists) {
                                            $flag = false;
                                        }
                                    } else {
                                        $flag = false;
                                    }
                                } else if ($rule['rule_condition_key'] == 'account_id') {
                                    $query->andWhere(['between', 'account_id', $min, $max]);
                                } else if ($rule['rule_condition_key'] == 'receive_date') {
                                    if (!($min <= $inbox->receive_date && $inbox->receive_date <= $max)) {
                                        $flag = false;
                                    }
                                }

                                break;
                            default:
                                break;
                        }
                    }

                    //验证消息是否符合规则
                    if (!$query->exists()) {
                        $flag = false;
                    }

                    //添加标签
                    if ($flag) {
                        $tagIds[] = $tagId;
                    }
                    unset($query);
                }
            }

            $result = MailTag::saveMailTags(Platform::PLATFORM_CODE_ALI, $inbox->id, $tagIds);
            //不管有没有打标签成功，都返回true
            if ($result) {
                return true;
            } else {
                return true;
            }
        }
    }

    /**
     * 重写父类的获取标签列表方法
     */
    public static function getTagsList()
    {
        $params = Yii::$app->request->getBodyParams();

        //获取标签列表
        $tagList = Tag::getPlatformTagList(Platform::PLATFORM_CODE_ALI);

        if (empty($tagList)) {
            return [];
        }
        //查询标签数量
        $query = self::find()->alias('t')
            ->select(['t2.id', 'count' => 'count(*)'])
            ->leftJoin(['t1' => MailTag::tableName()], 't1.inbox_id = t.id AND t1.platform_code = :platform_code1', [':platform_code1' => Platform::PLATFORM_CODE_ALI])
            ->leftJoin(['t2' => Tag::tableName()], 't2.id = t1.tag_id AND t2.platform_code = :platform_code2', ['platform_code2' => Platform::PLATFORM_CODE_ALI])
            ->where(['t2.status' => Tag::TAG_STATUS_VALID])
            ->groupBy('t2.id');

        //只能查询到客服绑定账号的邮件
        if (empty($params['account_id'])) {
            $userAccountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_ALI);
            if (!empty($userAccountIds)) {
                $query->andWhere(['in', 'account_id', $userAccountIds]);
            }
        }

        //设置筛选项
        (new static())->setFilterOptions($query, $params);

        //统计标签数量
        $tagCount = $query->asArray()->all();
        if (!empty($tagCount)) {
            $tagCount = array_column($tagCount, 'count', 'id');
        }

        $data = [];
        foreach ($tagList as $tag) {
            $data[] = [
                'id' => $tag->id,
                'name' => $tag->tag_name,
                'count' => array_key_exists($tag->id, $tagCount) ? $tagCount[$tag->id] : 0,
            ];
        }

        return $data;
    }

    public function getNextInboxId($currentId)
    {
        $nextInboxId = '';
        $session = \yii::$app->session;
        $aliexpressInboxListIds = $session->get(static::PLATFORM_CODE . '_INBOX_PROCESSED_LIST');
        if (!empty($aliexpressInboxListIds)) {
            $index = array_search($currentId, $aliexpressInboxListIds);
            if ($index !== false) {
                $nextIndex = $index + 1;
                $nextInboxId = isset($aliexpressInboxListIds[$nextIndex]) ?
                    $aliexpressInboxListIds[$nextIndex] : '';
                return $nextInboxId;
            }
        } else {
            $searchData = unserialize($session->get(get_class($this) . '_model_search_query'));
            if (empty($searchData) || !isset($searchData['query']))
                return $nextInboxId;
            $query = $searchData['query'];
            $sort = $searchData['sort'];
            $query->addOrderBy($sort->getOrders());
            $aliexpressInboxListIds = $query->column();
            $nextInboxId = current($aliexpressInboxListIds);
            if(empty($aliexpressInboxListIds)){
                $aliexpressInboxListIds=$nextInboxId;
            }

            $session->set(static::PLATFORM_CODE . '_INBOX_PROCESSED_LIST', $aliexpressInboxListIds);
        }
        return $nextInboxId;
    }

    /**
     * @desc 清除消息处理列表
     * @return boolean
     */
    public static function destroyProcessedList()
    {
        $sessionKey = static::PLATFORM_CODE . '_INBOX_PROCESSED_LIST';
        $session = \Yii::$app->session;
        $session->remove($sessionKey);
        return true;
    }
}

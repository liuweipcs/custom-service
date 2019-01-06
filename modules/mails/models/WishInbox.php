<?php

namespace app\modules\mails\models;

use Yii;
use yii\helpers\Url;
use app\common\VHelper;
use app\modules\systems\models\Tag;
use app\modules\mails\models\WishInboxInfo;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use app\modules\orders\models\OrderOtherKefu;
use app\modules\orders\models\OrderWishKefu;
use app\modules\accounts\models\UserAccount;

/**
 * This is the model class for table "{{%wish_inbox}}".
 *
 * @property integer $id
 * @property integer $info_id
 * @property string $transaction_id
 * @property string $platform_id
 * @property integer $account_id
 * @property string $merchant_id
 * @property string $label
 * @property string $sublabel
 * @property string $open_date
 * @property string $state
 * @property string $subject
 * @property integer $photo_proof
 * @property string $user_locale
 * @property string $user_id
 * @property string $user_name
 * @property string $create_by
 * @property string $create_time
 * @property string $modify_by
 * @property string $modify_time
 */
class WishInbox extends Inbox
{
    const PLATFORM_CODE = Platform::PLATFORM_CODE_WISH;
    public static $isReadMap = [0 => '否', 1 => '是'];
    public static $isRepliedMap = [0 => '未回复', 1 => '已回复', 2 => '标记回复'];
    public static $status = ['Awaiting buyer response' => '等待客户回复', 'Awaiting your response' => '等待我们回复', 'Closed' => '过期关闭'];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wish_inbox}}';
    }

    public function attributes()
    {
        $attributes = parent::attributes();
        $extAttributes = [
            'modify_by_time',
            'last_updated',
            'remain_replay_time',
            'order_id',
        ];
        return array_merge($attributes, $extAttributes);
    }

    /**
     * @desc 搜索过滤项
     * @return multitype:multitype:string multitype:  multitype:string multitype:string
     */
    public function filterOptions()
    {
        return [
            [
                'name' => 'user_name',
                'type' => 'text',
                'search' => 'LIKE',
            ],
            [
                'name' => 'order_id',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'buyer_id',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'platform_id',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'label',
                'type' => 'text',
                'search' => 'LIKE',
            ],
            [
                'name' => 'is_replied',
                'type' => 'dropDownList',
                'data' => self::$isRepliedMap,
                'search' => '=',
                'value' => '0',
            ],
            [
                'name' => 'read_stat',
                'type' => 'dropDownList',
                'data' => self::$isReadMap,
                'search' => '=',
            ],
            [
                'name' => 'status',
                'type' => 'dropDownList',
                'data' => self::$status,
                'search' => '=',
                'alias' => 't',
                'value' => 'Awaiting your response',
            ],
            [
                'name' => 'start_time',
                'type' => 'date_picker',
                'search' => '<',
                'value' => '',
            ],
            [
                'name' => 'end_time',
                'type' => 'date_picker',
                'search' => '>',
                'value' => '',
            ],
            [
                'name' => 'account_id',
                'type' => 'hidden',
                'alias' => 't',
                'search' => '=',
            ],
            [
                'name' => 'tag_id',
                'type' => 'hidden',
                'search' => false,
                'alias' => 't1',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['open_date', 'create_time', 'modify_time', 'last_updated'], 'safe'],
            [['transaction_id', 'platform_id', 'merchant_id', 'user_locale', 'user_id', 'user_name'], 'string', 'max' => 30],
            [['account_id'], 'integer'],
            [['label', 'subject'], 'string', 'max' => 150],
            [['create_by', 'modify_by'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => '平台订单ID',
            'info_id' => '订单消息ID',
            'transaction_id' => '交易信息',
            'platform_id' => '站内信编号',
            'account_id' => '店铺',
            'merchant_id' => '商家ID',
            'label' => '标题',
            'is_wish' => '是否wish消息',
            'sublabel' => '简短标题',
            'open_date' => '创建日期',
            'status' => '平台状态',
            'subject' => 'Subject',
            'photo_proof' => '是否有图片',
            'user_locale' => 'User Locale',
            'user_id' => '用户ID',
            'user_name' => '买家姓名',
            'create_by' => '创建人',
            'last_updated' => '最新邮件时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
            'read_stat' => '是否已读',
            'is_replied' => '是否回复',
            'start_time' => '最新邮件开始时间',
            'end_time' => '最新邮件结束时间',
            'remain_replay_time' => '剩余回复时间',
            'modify_by_time' => '回复人/时间',
            'buyer_id' => '买家ID',
        ];
    }

    /**
     * @desc search list
     * @param unknown $params
     * @param string $query
     */
    public function searchList($params = [], $sort = NULL)
    {
        //清除已处理消息列表
        $session = Yii::$app->session;
        self::destroyProcessedList();
        $query = self::find()->alias('t')->distinct();
        $query->innerJoin(['t2' => WishInboxInfo::tableName()], 't2.info_id = t.info_id');

        //添加时间查询
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 't2.last_updated', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 't2.last_updated', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 't2.last_updated', $params['end_time']]);
        }

        //客服只能查看自已绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);
        $query->andWhere(['in', 't.account_id', $accountIds]);

        //自定义标签
        if (!empty($params['tag_id'])) {
            //根据标签查询
            $query->innerJoin(['t1' => MailTag::tableName()], 't1.inbox_id = t.id AND t1.platform_code = :platform_code', [':platform_code' => self::PLATFORM_CODE]);
            $query->andWhere(['t1.tag_id' => $params['tag_id']]);
            unset($params['tag_id']);
        }
        //平台订单ID
        if (!empty($params['order_id'])) {
            $query->andWhere(['t2.order_id' => $params['order_id']]);
            unset($params['order_id']);
        }
        //买家ID
        if (!empty($params['buyer_id'])) {
            $platformOrderIds = OrderWishKefu::find()
                ->select('platform_order_id')
                ->andWhere(['platform_code' => static::PLATFORM_CODE])
                ->andWhere(['buyer_id' => $params['buyer_id']])
                ->column();
            if (!empty($platformOrderIds)) {
                $query->andWhere(['in', 't2.order_id', $platformOrderIds]);
            }
            unset($params['buyer_id']);
        }

        $sort = new \yii\data\Sort([
            'attributes' => [
                'id',
                't2.last_updated'
            ],
        ]);
        $sort->defaultOrder = array(
            't2.last_updated' => SORT_DESC,
            'id' => SORT_DESC,
        );

        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }


    public static function getTagsList($params = [])
    {
        $tagList = Tag::find()
            ->select('id, tag_name as name')
            ->andWhere(['platform_code' => static::PLATFORM_CODE, 'status' => 1])
            ->orderBy('sort_order ASC')
            ->asArray()
            ->all();

        $query = self::find();
        $query->from(static::tableName() . ' as t')
            ->innerJoin(WishInboxInfo::tableName() . ' as t3', 't3.info_id = t.info_id')
            ->leftJoin(MailTag::tableName() . ' as t1', 't.id = t1.inbox_id and t1.platform_code = :platform_code1', ['platform_code1' => static::PLATFORM_CODE])
            ->leftJoin(Tag::tableName() . ' as t2', 't2.id = t1.tag_id and t1.platform_code = :platform_code2', ['platform_code2' => static::PLATFORM_CODE])
            ->select(['t2.id', 'count' => 'count(*)'])
            ->where('t2.status = 1')
            ->groupBy('t2.id')
            ->orderBy(null);

        //客服只能查看自已绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);
        $query->andWhere(['in', 't.account_id', $accountIds]);

        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 't3.last_updated', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 't3.last_updated', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 't3.last_updated', $params['end_time']]);
        }

        //平台订单ID
        if (!empty($params['order_id'])) {
            $query->andWhere(['t3.order_id' => $params['order_id']]);
            unset($params['order_id']);
        }
        //买家ID
        if (!empty($params['buyer_id'])) {
            $platformOrderIds = OrderWishKefu::find()
                ->select('platform_order_id')
                ->andWhere(['platform_code' => static::PLATFORM_CODE])
                ->andWhere(['buyer_id' => $params['buyer_id']])
                ->column();
            if (!empty($platformOrderIds)) {
                $query->andWhere(['in', 't3.order_id', $platformOrderIds]);
            }
            unset($params['buyer_id']);
        }
        //账号ID
        if (!empty($params['account_id'])) {
            $query->andWhere(['t.account_id' => $params['account_id']]);
            unset($params['account_id']);
        }

        $options = (new static())->filterOptions();
        if (!empty($options)) {
            foreach ($options as $key => $option) {
                //单独把account_id排除，避免冲突
                if ($option['type'] == 'hidden' || $option['name'] == 'account_id') {
                    continue;
                }

                if ($option['name'] == 'is_wish') {
                    switch ($params['is_wish']) {
                        case 'yes':
                            $query->andWhere(['user_name' => 'Koko Wish']);
                            break;
                        case 'no':
                            $query->andWhere(['<>', 'user_name', 'Koko Wish']);
                            break;
                    }
                    unset($options[$key]);
                    continue;
                }


                $field = !empty($option['name']) ? $option['name'] : '';
                if (empty($field)) {
                    continue;
                }

                $value = array_key_exists($field, $params) ? $params[$field] : (isset($option['value']) ? $option['value'] : '');
                if ($value == '') {
                    continue;
                } else {
                    $value = trim($value);
                }
                $field = 't.' . $field;
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
        if (!empty($data)) {
            $data = array_column($data, 'count', 'id');
        }

        if (!empty($tagList)) {
            foreach ($tagList as $key => &$tag) {
                $count = array_key_exists($tag['id'], $data) ? $data[$tag['id']] : 0;
                //如果标签数量为0，则删除该标签，不显示
                if (empty($count)) {
                    unset($tagList[$key]);
                } else {
                    $tag['count'] = $count;
                }
            }
        }
        return $tagList;
    }

    /**
     * 获取账号列表统计
     */
    public static function getAccountCountList($params = [])
    {
        //获取账号列表
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);
        $accountList = Account::find()
            ->select('id, account_short_name as name')
            ->where(['platform_code' => static::PLATFORM_CODE, 'status' => 1])
            ->andWhere(['in', 'id', $accountIds])
            ->asArray()
            ->all();

        $query = WishInbox::find();
        $query->from(static::tableName() . ' as t')
            ->innerJoin(WishInboxInfo::tableName() . ' as t1', 't1.info_id = t.info_id')
            ->select('t.account_id as id, count(*) as count')
            ->groupBy('t.account_id');

        //客服只能查看自已绑定账号
        $query->andWhere(['in', 't.account_id', $accountIds]);

        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 't1.last_updated', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 't1.last_updated', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 't1.last_updated', $params['end_time']]);
        }
        //自定义标签
        if (isset($params['tag_id']) && $params['tag_id'] != '') {
            $query->innerJoin(MailTag::tableName() . ' as t2', 't2.inbox_id = t.id')
                ->andWhere(['t2.tag_id' => $params['tag_id']]);
            unset($params['tag_id']);
        }
        //平台订单ID
        if (!empty($params['order_id'])) {
            $query->andWhere(['t1.order_id' => $params['order_id']]);
            unset($params['order_id']);
        }
        //买家ID
        if (!empty($params['buyer_id'])) {
            $platformOrderIds = OrderWishKefu::find()
                ->select('platform_order_id')
                ->andWhere(['platform_code' => static::PLATFORM_CODE])
                ->andWhere(['buyer_id' => $params['buyer_id']])
                ->column();
            if (!empty($platformOrderIds)) {
                $query->andWhere(['in', 't1.order_id', $platformOrderIds]);
            }
            unset($params['buyer_id']);
        }

        $options = (new static())->filterOptions();
        if (!empty($options)) {
            foreach ($options as $key => $option) {
                //单独把account_id排除，避免冲突
                if ($option['type'] == 'hidden' || $option['name'] == 'account_id') {
                    continue;
                }

                if ($option['name'] == 'is_wish') {
                    switch ($params['is_wish']) {
                        case 'yes':
                            $query->andWhere(['user_name' => 'Koko Wish']);
                            break;
                        case 'no':
                            $query->andWhere(['<>', 'user_name', 'Koko Wish']);
                            break;
                    }
                    unset($options[$key]);
                    continue;
                }
                $field = !empty($option['name']) ? $option['name'] : '';
                if (empty($field)) {
                    continue;
                }
                $value = array_key_exists($field, $params) ? $params[$field] : (isset($option['value']) ? $option['value'] : '');
                if ($value == '') {
                    continue;
                } else {
                    $value = trim($value);
                }
                $field = 't.' . $field;
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
        if (!empty($data)) {
            $data = array_column($data, 'count', 'id');
        }
        if (!empty($accountList)) {
            foreach ($accountList as &$account) {
                $account['count'] = array_key_exists($account['id'], $data) ? $data[$account['id']] : 0;
            }
        }

        return $accountList;
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \app\modules\mails\models\Inbox::addition()
     */
    public function dynamicChangeFilter(&$filterOptions, &$query, &$params)
    {
        foreach ($filterOptions as $key => $filterOption) {
            if ($filterOption['name'] == 'is_wish') {
                $value = isset($params['is_wish']) ? $params['is_wish'] : (isset($filterOption['value']) ? $filterOption['value'] : '');
                switch ($value) {
                    case 'yes':
                        $query->andWhere(['user_name' => 'Koko Wish']);
                        break;
                    case 'no':
                        $query->andWhere(['<>', 'user_name', 'Koko Wish']);
                        break;
                }
                unset($filterOptions[$key]);
                unset($params['is_wish']);
            }
        }
    }

    public function addition(&$models)
    {
        foreach ($models as $key => $model) {
            $models[$key]->setAttribute('photo_proof', self::photoProof($model->photo_proof));
            $models[$key]->setAttribute('read_stat', self::getReadStat((int)$model->read_stat));
            $models[$key]->setAttribute('is_replied', self::getIsReplied((int)$model->is_replied));
            $account_name = Account::getAccountNameAndShortName($model->account_id, 'WISH');
            $account_name = empty($account_name) ? '-' : $account_name['account_name'];
            $reply_info = WishReply::find()->where(['platform_id' => $model->platform_id])->orderBy(['id' => SORT_DESC])->one();
            if ($reply_info->type == 'merchant') {
                $reply_by = $reply_info->reply_by ? $reply_info->reply_by : '';
                $modify_time = $reply_info->modify_time ? $reply_info->modify_time : '';
            } else {
                $reply_by = '';
                $modify_time = '';
            }
            $models[$key]->setAttribute('modify_by_time', $reply_by . '<br />' . $modify_time);
            $models[$key]->setAttribute('account_id', $account_name);
            $models[$key]->setAttribute('status', self::$status[$model->status]);
            $info_list = WishInboxInfo::findOne($models[$key]->info_id);
            $last_updated = date('Y-m-d H:i:s', strtotime($info_list->last_updated) + 8 * 3600);//修改北京时间时间
            $models[$key]->setAttribute('last_updated', $last_updated);
            //剩余回复时间
            $end_issue_reponse_last_time = strtotime("+2 day", strtotime($last_updated));
            $end_issue_reponse_last_time_str = date('Y/m/d H:i:s', $end_issue_reponse_last_time);
            $time = time();
            $issue_reponse_last_time = $end_issue_reponse_last_time - $time;
            if ($issue_reponse_last_time > 0) {
                $issue_reponse_last_time = VHelper::sec2string($issue_reponse_last_time);
                $issue_reponse_last_time = "<span class='issue_reponse_last_time' data-endtime='{$end_issue_reponse_last_time_str}' data-endsec='{$time}'>{$issue_reponse_last_time}</span>";
            } else {
                $issue_reponse_last_time = '<span style="color:red;">已超时</span>';
            }
            if ($model->is_replied == '未回复' && $model->status == '等待我们回复') {
                $models[$key]->setAttribute('remain_replay_time', $issue_reponse_last_time);
            } else {
                $models[$key]->setAttribute('remain_replay_time', '-');
            }
            $models[$key]->setAttribute('platform_id',
                '<a target="_blank" href="/mails/wish/details?id=' . $model->info_id . '">'
                . $model->platform_id . '</a>');

            //买家ID
            $orderInfo = OrderWishKefu::findOne([
                'platform_order_id' => $info_list->order_id,
                'platform_code' => Platform::PLATFORM_CODE_WISH
            ]);
            if (!empty($orderInfo)) {
                $models[$key]->setAttribute('buyer_id', $orderInfo->buyer_id);
            }

            //平台订单ID
            $orderInfoUrl = Url::toRoute([
                '/orders/order/orderdetails',
                'platform' => Platform::PLATFORM_CODE_WISH,
                'order_id' => $info_list->order_id
            ]);
            $models[$key]->setAttribute('order_id', "<a class='edit-button' _width='80%' _height='80%' href='" . $orderInfoUrl . "'>{$info_list->order_id}</a>");
        }
    }

    public static function AccountList()
    {
        $accountList = Account::getCurrentUserPlatformAccountList(Platform::PLATFORM_CODE_WISH, 1);
        $list = [];
        if (!empty($accountList)) {
            foreach ($accountList as $value) {
                $list[$value->attributes['id']] = $value->attributes['account_name'];
            }
        }
        return $list;
    }

    public static function photoProof($photo_proof)
    {
        $data = ['有图', '无图'];
        return $data[$photo_proof];
    }

    /*未读状态*/
    public static function getReadStat($readStat)
    {
        $read_stat = ['未读', '已读'];
        return $read_stat[$readStat];
    }

    /*是否回复*/
    public static function getIsReplied($is_replied)
    {
        $read_stat = ['未回复', '已回复', '标记回复'];
        return $read_stat[$is_replied];
    }

    /**
     * 判断是否跟订单有关联,有则返回订单号,无则返回null
     */
    public function getOrderId()
    {
        return WishInboxInfo::getOrderIdByInfoId($this->info_id);
    }

    public function getInboxInfo($info_id)
    {
        return WishInboxInfo::findOne(['info_id' => $info_id]);
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
            $arrData['href'] = "<a href='/mails/wish/details?id=" . $data['id'] . "' style='text-decoration:none;'>";
            $arrData['title'] = '下一封';
        }
        return $arrData;
    }


    /**
     * @desc 将消息id添加到处理列表
     * @param unknown $inboxId
     * @return boolean
     */
    public static function pushProccessedList($inboxId)
    {
        $sessionKey = static::PLATFORM_CODE . '_INBOX_PROCESSED_LIST';
        $session = \Yii::$app->session;
        $processedList = $session->get($sessionKey);
        if (empty($processedList))
            $processedList = [];
        if (!in_array($inboxId, $processedList))
            $processedList[] = $inboxId;
        $session->set($sessionKey, $processedList);
        return true;
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

    /**
     * @desc 获取所有未处理消息ids
     */
    public static function getNoProcessInboxIds()
    {
        $session = \Yii::$app->session;
        $query = self::find();
        $query->select(['id']);
        $currentUserAccountId = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);
        if (!empty($currentUserAccountId)) {
            //如果有搜索指定账号 则只查询当前搜索账号需要处理的消息
            $account_id = $session->get('search_account_id');
            if ($account_id) {
                $query->where(['account_id' => $account_id, 'is_replied' => 0]);
            } else {
                $query->where(['in', 'account_id', $currentUserAccountId])->andWhere(['is_replied' => 0]);
            }
        }
        //默认排序 按最后一条消息时间倒序,ID 倒序(跟列表保持一致)
        if (!empty($queryParams['sort'])) {
            $query->addOrderBy($queryParams['sort']->getOrders());
        } else {
            $query->addOrderBy('id DESC');
        }
        $model = $query->column();
        return $model;
    }

    public static function getNextNoProcessId($current_id)
    {
        $current_key = 0;
        //需要处理的消息ID集合
        $noProcessIds = self::getNoProcessInboxIds();
        $keys = array_keys($noProcessIds);
        $maxKey = max($keys);
        $session = \Yii::$app->session;
        if (in_array($current_id, $noProcessIds)) {
            $key = array_search($current_id, $noProcessIds);//获取当前消息ID下标
            //如果当前下标小于最大下标 继续下一条消息,将当前消息ID存入Session并返回下一条消息ID 否则返回FALSE
            if ($key < $maxKey) {
                $current_key = $key + 1;
                $nextInboxId = $noProcessIds[$current_key];
                $session->set('current_id', $nextInboxId);
                return $noProcessIds[$current_key];
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }


}

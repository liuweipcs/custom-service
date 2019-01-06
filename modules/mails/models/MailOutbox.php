<?php
/**
 * @desc 发件箱模型
 * @author Fun
 */

namespace app\modules\mails\models;

use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\mails\components\MessageSenderAbstract;
use app\modules\orders\models\OrderAliexpressKefu;
use app\modules\orders\models\OrderAmazonKefu;
use app\modules\orders\models\OrderEbayKefu;
use app\modules\orders\models\OrderOtherKefu;
use app\modules\orders\models\OrderWishKefu;
use app\modules\systems\models\MailAutoManage;
use app\modules\users\models\Role;
use app\modules\users\models\User;

class MailOutbox extends MailsModel
{
    public $send_type;
    const SEND_STATUS_FAILED = -1;          //发送失败的消息
    const SEND_STATUS_WAITTING = 0;         //等待发送的消息
    const SEND_STATUS_SENDING = 1;          //发送中的消息
    const SEND_STATUS_SUCCESS = 2;          //发送成功的消息
    public $platform;

    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%mail_outbox}}';
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\base\Model::rules()
     */
    public function rules()
    {
        return [
            [['platform_code', 'content', 'send_status'], 'required'],
            [['subject', 'send_failure_reason', 'send_params'], 'string'],
            [['send_time', 'response_time'], 'safe'],
            [['send_failure_times', 'reply_id', 'account_id'], 'integer'],
            [['inbox_id', 'create_by', 'modify_by', 'order_id', 'platform_order_id', 'rule_id', 'buyer_id', 'receive_email', 'send_rule_id'], 'safe']
        ];
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\ActiveRecord::attributes()
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = ['send_status_text', 'buyer_id', 'account_short_name', 'send_rule_name', 'system_order_id'];              //状态
        return array_merge($attributes, $extraAttributes);
    }

    /**
     * @desc search list
     * @param unknown $params
     * @param string $query
     */
    public function searchList($params = [])
    {
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'id' => SORT_DESC,
        );
        $query = self::find();
        $query->from(self::tableName() . ' as t');
        //发送时间查询
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 't.send_time', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 't.send_time', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 't.send_time', $params['end_time']]);
        }
        //发送类型
        if (!empty($params['created_by'])) {
            if (trim($params['created_by']) == 'system') {
                $query->andWhere(['=', 't.create_by', 'system']);
            } elseif (trim($params['created_by'] == 'other')) {
                $query->andWhere(['<>', 't.create_by', 'system']);
            }
        }
        //规则类型
        if (!empty($params['send_rule_id'])) {
            if ($params['send_rule_id'] == 'send') {
                $query->andWhere(['>', 't.send_rule_id', 0]);
            } else {
                $query->andWhere(['=', 't.send_rule_id', $params['send_rule_id']]);
            }
        }
        //系统订单号
        if (!empty($params['system_order_id']) && !empty($params['platform_code'])) {
            $model = null;
            switch ($params['platform_code']) {
                case Platform::PLATFORM_CODE_ALI:
                    $model = OrderAliexpressKefu::find();
                    break;
                case Platform::PLATFORM_CODE_AMAZON:
                    $model = OrderAmazonKefu::find();
                    break;
                case Platform::PLATFORM_CODE_EB:
                    $model = OrderEbayKefu::find();
                    break;
                case Platform::PLATFORM_CODE_WISH:
                    $model = OrderWishKefu::find();
                    break;
                default:
                    $model = OrderOtherKefu::find();
                    break;
            }
            if (!empty($model)) {
                $data = $model->where(['order_id' => $params['system_order_id'], 'platform_code' => $params['platform_code']])->asArray()->one();
                if (!empty($data)) {
                    $query->andWhere(['platform_order_id' => $data['platform_order_id']]);
                }
            }

            unset($params['system_order_id']);
        }
        //账号
        unset($params['created_by']);
        if (!empty($params['account_id'])) {
            $query->andWhere(['=', 't.account_id', $params['account_id']]);
        }
        unset($params['account_id']);
        //账号简称
        if (!empty($params['account_short_name'])) {
            $query->andWhere(['=', 't.account_id', $params['account_short_name']]);
        }
        unset($params['account_short_name']);
        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        $this->addition($models);
        foreach ($models as $key => $model) {
            $models[$key]->setAttribute('send_status_text', self::getSendStatusList($model->send_status));
            $models[$key]->setAttribute('content', nl2br($model->content));
            $send_rule_name = MailAutoManage::findone($model->send_rule_id);
            $models[$key]->setAttribute('send_rule_name', is_null($send_rule_name) ? '' : $send_rule_name->rule_name);
        }
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    public function dynamicChangeFilter(&$filterOptions, &$query, &$params)
    {
        foreach ($filterOptions as $key => $filterOption) {
            if ($filterOption['name'] == 'buyer_id') {
                $value = isset($params['buyer_id']) ? $params['buyer_id'] : (isset($filterOption['value']) ? $filterOption['value'] : '');
                if (!empty(trim($value)))
                    $query->innerJoin('{{%ebay_reply}} as t2', 't2.id = t.reply_id')->andWhere(['t2.recipient_id' => $value]);
                unset($filterOptions[$key]);
                unset($params['is_ebay']);
            }
        }

        $role_info = \Yii::$app->user->identity->role;
        $role_code = $role_info->role_code;
        $parent_id = $role_info->parent_id;
        $role_id = \Yii::$app->user->identity->role_id;
        $create_by = \Yii::$app->user->identity->login_name;
        $create_by_array[] = $create_by;
        if ($parent_id != 0 && isset($params['create_by'])) {
            // 查询子角色的所有帐号
            $next_level = User::find()
                ->select('login_name')
                ->from(User::tableName() . ' as t')
                ->innerJoin(Role::tableName() . ' as t1', 't1.id = t.role_id')
                ->where(['t1.parent_id' => $role_id])
                ->column();

            $create_by_array = array_merge($create_by_array, $next_level, array('system'));

            if (!empty($params['create_by']) && in_array($params['create_by'], $create_by_array)) {
                $query->andWhere('t.create_by = "' . $params['create_by'] . '"');
            } else {
                $query->andWhere(['in', 't.create_by', $create_by_array]);
            }
            unset($params['create_by']);
        }

    }

    public function addition(&$models)
    {
        foreach ($models as &$model) {
            if ($model->platform_code == Platform::PLATFORM_CODE_EB) {
                $reply_info = EbayReply::findOne($model->reply_id);
                $buyer_id = empty($reply_info) ? '' : $reply_info->recipient_id;
                $model->setAttribute('buyer_id', $buyer_id);
            }

            $account_short_name = Account::getAccountNameAndShortName($model->account_id, $this->platform);
            $account_short_name = empty($account_short_name) ? '' : $account_short_name['account_short_name'];
            $model->setAttribute('account_short_name', $account_short_name);
        }
        return $models;
    }

    public static function getSendStatusList($key = null)
    {
        $list = [
            self::SEND_STATUS_FAILED => '发送失败',
            self::SEND_STATUS_WAITTING => '等待发送',
            self::SEND_STATUS_SENDING => '发送中',
            self::SEND_STATUS_SUCCESS => '发送成功',
        ];
        if (!is_null($key)) {
            if (array_key_exists($key, $list))
                return $list[$key];
            else
                return '';
        }
        return $list;
    }

    public static function getSendList($key = null)
    {
        $list = [
            'system' => '系统发送',
            'other' => '客服发送',
        ];
        if (!is_null($key)) {
            if (array_key_exists($key, $list))
                return $list[$key];
            else
                return '';
        }
        return $list;
    }

    public function attributeLabels()
    {
        return [
            'platform_code' => '平台',
            'subject' => '主题',
            'content' => '内容',
            'send_status' => '发送状态',
            'send_failure_reason' => '发送失败原因',
            'send_time' => '发送时间',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
            'send_status_text' => '发送状态',
            'send_type' => '发送类型',
            'buyer_id' => '收件人',
            'account_short_name' => '账号简称',
            'created_by' => '发送类型',
            'send_rule_name' => '规则名称',
            'platform_order_id' => '平台订单号',
            'system_order_id' => '系统订单号',
            'receive_email' => '收件人邮箱',
            'account_id' => '账号',
            'start_time' => '开始时间',
            'end_time' => '结束时间',
        ];
    }

    public function filterOptions()
    {
        return [
            [
                'name' => 'send_status',
                'type' => 'hidden',
                'search' => '=',
            ],
            [
                'name' => 'platform_code',
                'type' => 'hidden',
                'search' => '=',
            ],
            [
                'name' => 'subject',
                'type' => 'text',
                'search' => 'FULL LIKE',
            ],
            [
                'name' => 'content',
                'type' => 'text',
                'search' => 'LIKE',
            ],
            [
                'name' => 'platform_order_id',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'system_order_id',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'buyer_id',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'receive_email',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'create_by',
                'type' => 'text',
                'alias' => 't',
                'search' => '=',
            ],
            [
                'name' => 'send_status',
                'type' => 'dropDownList',
                'data' => self::getSendStatusList(),
                'htmlOptions' => [],
                'search' => '=',
            ],
            [
                'name' => 'send_failure_reason',
                'type' => 'text',
                'htmlOptions' => [],
                'search' => 'LIKE',
            ],
            [
                'name' => 'created_by',
                'type' => 'dropDownList',
                'data' => self::getSendList(),
                'search' => '=',
            ],
            [
                'name' => 'account_id',
                'type' => 'search',
                'data' => $this->getAccountLists(),
                'search' => '=',
            ],
            [
                'name' => 'account_short_name',
                'type' => 'search',
                'data' => $this->getAccountShortLists(),
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
     * 当前平台账号列表
     * @return array
     */
    public function getAccountLists()
    {
        $accountList = Account::getCurrentUserPlatformAccountList($this->platform, 1);
        $list = [];
        if (!empty($accountList)) {
            foreach ($accountList as $value) {
                $list[$value->attributes['id']] = $value->attributes['account_name'];
            }
        }
        $list[0] = '全部';
        ksort($list);
        return $list;
    }

    /**
     * 当前平台账号简称列表
     * @return array
     */
    public function getAccountShortLists()
    {
        $accountList = Account::getCurrentUserPlatformAccountList($this->platform, 1);
        $list = [];
        if (!empty($accountList)) {
            foreach ($accountList as $value) {
                $list[$value->attributes['id']] = $value->attributes['account_short_name'];
            }
        }
        $list[0] = '全部';
        ksort($list);
        return $list;
    }

    /**
     * @desc 获取待发送的消息列表
     * @param string $platformCode
     * @param number $limit
     * @param string $maxfailureTime
     * @param $modNumber integer|null 按主键ID分组取模值
     * @param $modRemain integer|null 取模后余数
     * @return Ambigous <\yii\db\array, multitype:\yii\db\ActiveRecord >
     */
    public static function getWaittingSendList($platformCode = null, $limit = 1000, $maxfailureTime = null,
                                               $sortOrder = 'ASC', $modNumber = null, $modRemain = null)
    {
        $query = self::find();
        $query->where('send_status = ' . self::SEND_STATUS_WAITTING);
        $query->orWhere('send_status = ' . self::SEND_STATUS_FAILED);
        //$query->andWhere("id = 2301665");
        if ($platformCode != null)
            $query->andWhere('platform_code = :platform_code', ['platform_code' => $platformCode]);
        if ($maxfailureTime !== null)
            $query->andWhere('send_failure_times <= ' . (int)$maxfailureTime);
        if ($sortOrder == 'ASC')
            $query->orderBy(['id' => SORT_ASC]);
        else
            $query->orderBy(['id' => SORT_DESC]);
        //add by fun date:2018-04-23
//        if (date('Y-m-d') <> '2018-04-24')
        if ($platformCode == 'EB') {
            $query->andWhere("create_time > '2018-05-08'");
        }
        //亚马逊自动发信测试测试
        if ($platformCode == 'AMAZON') {
            $query->andWhere("(create_by <> 'system') or (account_id not in (642) and create_by = 'system')");
            //$query->andWhere("(create_by <> 'system')");
        }
        //按取模后的余数分组
        if (!is_null($modNumber) && !is_null($modRemain)) {
            $modNumber = (int)$modNumber;
            $modRemain = (int)$modRemain;
            if ($modNumber > 0 && $modRemain >= 0 && $modRemain < $modNumber) {
                $query->andWhere("id%" . $modNumber . '=' . $modRemain);
            }
        }

        //if($platformCode == 'AMAZON'){
        //$query->andWhere("create_by <> 'system'");
        //}
        $query->limit($limit);
        //$query->createCommand()->getRawSql();exit;
        $xx = $query->all();
        //echo $query->createCommand()->getRawSql();//输出sql语句
        //die;
        return $xx;
    }

    /**
     * @desc 发送消息后的回调方法
     * @param unknown $event
     */
    public static function afterSend($event)
    {
        /**
         * @var $outBox \yii\db\ActiveRecord
         */
        try {
            $messageSender = $event->sender;
            $outBox = $messageSender->getMessageEntity();
            $outBox->response_time = date('Y-m-d H:i:s');
            if (!$messageSender->sendFlag) {
                //发送失败
                $data = [
                    'send_status' => self::SEND_STATUS_FAILED,
                    'send_failure_reason' => $messageSender->getException(),
                    'send_failure_times' => $outBox->send_failure_times + 1
                ];
                $outBox->send_status = self::SEND_STATUS_FAILED;
                $outBox->send_failure_reason = $messageSender->getException();
                $outBox->send_failure_times = $outBox->send_failure_times + 1;
                $event->message = $messageSender->getException();
                $event->isValid = false;
                $outBox->getDb()->createCommand()
                    ->update($outBox->tableName(), $data, ['id' => $outBox->id])
                    ->execute();
                return false;
            } else {
                //发送成功
                $outBox->send_status = self::SEND_STATUS_SUCCESS;
                $flag = $outBox->save();
                if (!$flag)
                    throw new \Exception('Save Message Failed');
            }
        } catch (\Exception $e) {
            $event->message = $e->getMessage();
            $event->isValid = false;
            return false;
        }
        $dbTransaction = self::getDb()->beginTransaction();
        try {
            //更新回复表
            $inboxId = $outBox->inbox_id;
            $replyId = $outBox->reply_id;
            $platformCode = $outBox->platform_code;
            if (!empty($replyId)) {
                $modelReply = Reply::getReplyModel($platformCode);
                if (!$modelReply)
                    throw new \Exception('Invalid Platform Code');
                $modelReply->setHadSync($replyId);
            }
            /*             if (!$modelReply->setHadSync($replyId))
                            throw new \Exception('Set Reply Sync Status Failed'); */
            //更新消息表
            if (!empty($inboxId)) {
                $inboxModel = Inbox::getInboxModel($platformCode);
                if (!$inboxModel)
                    throw new \Exception('Invalid Platform Code');
                $inboxModel->setReplyStatus($inboxId, Inbox::REPLY_YES_SYNC);
            }
            /*             if (!$inboxModel->setReplyStatus($inboxId, Inbox::REPLY_YES_SYNC))
                            throw new \Exception('Set Reply Sync Status Failed'); */
            $dbTransaction->commit();
            $event->isValid = true;
        } catch (\Exception $e) {
            $dbTransaction->rollBack();
            $event->message = 'Send Message Successful, But Other Error Occurred, ' . $e->getMessage();
            $event->isValid = false;
            //发送成功，但是更新数据失败
            $outBox->send_failure_reason = $event->message;

            $outBox->save(false, ['send_failure_reason']);
            return false;
        }
        $event->isValid = true;
    }

    /**
     * @desc 发送消息前的回调方法
     * @param unknown $event
     */
    public static function beforeSend($event)
    {
        $messageSender = $event->sender;
        $outBox = $messageSender->getMessageEntity();
        //再次查询消息，避免重复发送
        $outBox = self::findOne($outBox->id);
        if (empty($outBox)) {
            $event->message = 'Message Not Found';
            $event->isValid = false;
            return;
        }
        //检查是否可以发送
        if ($outBox->send_status != MailOutbox::SEND_STATUS_WAITTING && $outBox->send_status != MailOutbox::SEND_STATUS_FAILED && !($outBox->send_status == MailOutbox::SEND_STATUS_SENDING && (time() - strtotime($outBox->create_time) > '300'))) {
            $event->message = 'Message Send Status is Not Send Watting';
            $event->isValid = false;
            return;
        }
        $outBox->send_status = self::SEND_STATUS_SENDING;
        $outBox->send_time = date('Y-m-d H:i:s');
        $outBox->send_failure_reason = '';
        $outBox->response_time = '';
        $flag = $outBox->save();
        if (!$flag) {
            $event->message = 'Save Message Failed';
            $event->isValid = false;
            return;
        }
        $event->isValid = true;
    }

    /**
     * @desc 发送消息
     * @return boolean
     */
    public function sendMessage()
    {
        $platformCode = $this->platform_code;
        $messageSender = MessageSenderAbstract::getSender($platformCode);
        if (!$messageSender)
            return false;
        $messageSender->on(MessageSenderAbstract::EVENT_BEFORE_SEND, ['\app\modules\mails\models\MailOutbox',
            'beforeSend']);
        $messageSender->on(MessageSenderAbstract::EVENT_AFTER_SEND, ['\app\modules\mails\models\MailOutbox',
            'afterSend']);
        $flag = $messageSender->setMessageEntity($this)
            ->sendMessage();
        if (!$flag)
            return false;
        return true;
    }

}
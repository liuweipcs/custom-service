<?php

namespace app\modules\mails\models;

use app\modules\accounts\models\Account;
use app\modules\systems\models\MailAutoManage;

class Sendingmail extends MailsModel
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
            [['inbox_id', 'create_by', 'modify_by', 'order_id', 'platform_order_id', 'rule_id', 'buyer_id', 'receive_email'], 'safe']
        ];
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\ActiveRecord::attributes()
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = ['send_status_text', 'buyer_id', 'account_short_name', 'send_rule_name'];              //状态
        return array_merge($attributes, $extraAttributes);
    }

    /**
     * 查询列表
     * @param array $params
     * @return \yii\data\ActiveDataProvider
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
        if (empty($params['send_rule_id'])) {
            $query->andWhere(['>', 't.send_rule_id', 0]);
        } else {
            $query->andWhere(['=', 't.send_rule_id', $params['send_rule_id']]);
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


    public function addition(&$models)
    {
        foreach ($models as &$model) {
            $reply_info = EbayReply::findOne($model->reply_id);
            $buyer_id = empty($reply_info) ? '' : $reply_info->recipient_id;
            $model->setAttribute('buyer_id', $buyer_id);

            $account_short_name = Account::getAccountNameAndShortName($model->account_id, $this->platform);
            $account_short_name = empty($account_short_name) ? '' : $account_short_name['account_short_name'];
            $model->setAttribute('account_short_name', $account_short_name);
        }
        return $models;
    }


    public static function getSendStatusList($key = null)
    {
        $list = [
            self::SEND_STATUS_FAILED   => '发送失败',
            self::SEND_STATUS_WAITTING => '等待发送',
            self::SEND_STATUS_SENDING  => '发送中',
            self::SEND_STATUS_SUCCESS  => '发送成功',
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
            'other'  => '客服发送',
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
            'platform_code'       => \Yii::t('mail_outbox', 'Platform Code'),
            'subject'             => \Yii::t('mail_outbox', 'Subject'),
            'content'             => \Yii::t('mail_outbox', 'Content'),
            'send_status'         => \Yii::t('mail_outbox', 'Send Status'),
            'send_failure_reason' => \Yii::t('mail_outbox', 'Send Failure Reason'),
            'send_time'           => \Yii::t('mail_outbox', 'Send Time'),
            'create_by'           => \Yii::t('system', 'Create By'),
            'create_time'         => \Yii::t('system', 'Create Time'),
            'modify_by'           => \Yii::t('system', 'Modify By'),
            'modify_time'         => \Yii::t('system', 'Modify Time'),
            'send_status_text'    => \Yii::t('mail_outbox', 'Send Status'),
            'send_type'           => '发送类型',
            'buyer_id'            => '收件人',
            'account_short_name'  => '账号简称',
            'created_by'          => '发送类型',
            'send_rule_id'        => '规则',
            'send_rule_name'      => '规则名称'
        ];
    }

    public function filterOptions()
    {
        return [
            [
                'name'   => 'send_status',
                'type'   => 'hidden',
                'search' => '=',
            ],
            [
                'name'   => 'platform_code',
                'type'   => 'hidden',
                'search' => '=',
            ],
            [
                'name'   => 'subject',
                'type'   => 'text',
                'search' => 'FULL LIKE',
            ],
            [
                'name'   => 'content',
                'type'   => 'text',
                'search' => 'LIKE',
            ],
            [
                'name'   => 'buyer_id',
                'type'   => 'text',
                'search' => '=',
            ],
            [
                'name'   => 'create_by',
                'type'   => 'text',
                'alias'  => 't',
                'search' => '=',
            ],
            [
                'name'        => 'send_status',
                'type'        => 'dropDownList',
                'data'        => self::getSendStatusList(),
                'htmlOptions' => [],
                'search'      => '=',
            ],
            [
                'name'   => 'created_by',
                'type'   => 'dropDownList',
                'data'   => self::getSendList(),
                'search' => '=',
            ],
            [
                'name'   => 'account_id',
                'type'   => 'search',
                'data'   => $this->getAccountLists(),
                'search' => '=',
            ],
            [
                'name'   => 'account_short_name',
                'type'   => 'search',
                'data'   => $this->getAccountShortLists(),
                'search' => '=',
            ],
            [
                'name'   => 'start_time',
                'type'   => 'date_picker',
                'search' => '<',
            ],
            [
                'name'   => 'end_time',
                'type'   => 'date_picker',
                'search' => '>',
            ],
            [
                'name'        => 'send_rule_id',
                'type'        => 'search',
                'data'        => $this->getSendRuleIdName(),
                'htmlOptions' => ['class' => 'col-lg-5'],
                'search'      => '=',
            ],
        ];
    }

    /**
     *  查询所有的邮件拦截规则
     */
    public function getSendRuleIdName()
    {
        $ruleList = MailAutoManage::getRule();
        ksort($ruleList);
        return $ruleList;
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
     * 查询匹配到的规则邮件总数
     * @param $rule_id
     * @return int|string
     */
    public static function getMailCount($rule_id)
    {
        $count = self::find()->where(['send_rule_id' => $rule_id])->count();
        return $count;
    }
}
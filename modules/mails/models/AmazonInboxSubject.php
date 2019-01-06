<?php

namespace app\modules\mails\models;

use app\modules\systems\models\SiteManage;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\Tag;
use app\modules\accounts\models\UserAccount;
use app\modules\accounts\models\Account;
use app\modules\systems\models\Rule;
use app\modules\systems\models\MailFilterManage;
use app\modules\systems\models\MailFilterRule;

/**
 * 亚马逊站邮件主题
 */
class AmazonInboxSubject extends InboxSubject
{
    //const IS_REPLIED_NO = 0; //未回复
    //const IS_REPLIED_YES_NO_SYNCHRO = 1; //已回复未同步
    //const IS_REPLIED_YES_YES_SYNCHRO = 2; //已回复已同步

    const PLATFORM_CODE = Platform::PLATFORM_CODE_AMAZON;

    /**
     * 返回操作表名
     */
    public static function tableName()
    {
        return '{{%amazon_inbox_subject}}';
    }

    public function attributes()
    {
        $attributes    = parent::attributes();
        $extAttributes = [
            'reply_by_and_time'
        ];
        return array_merge($attributes, $extAttributes);
    }

    /**
     * 返回规则
     */
    public function rules()
    {
        return [
            [['receive_date', 'create_by', 'create_time', 'modify_by', 'modify_time'], 'safe'],
            [['id', 'is_read', 'is_replied', 'account_id', 'is_attached', 'type_mark'], 'integer'],
            [['first_subject', 'now_subject'], 'string', 'max' => 500],
            [['buyer_id', 'sender_email', 'receive_email'], 'string', 'max' => 255],
            [['order_id',], 'string', 'max' => 30],
        ];
    }

    /**
     * 返回属性标签
     */
    public function attributeLabels()
    {
        return [
            'id'                => 'ID',
            'order_id'          => '订单id',
            'now_subject'       => '主题',
            'buyer_id'          => '买家id',
            'sender_email'      => '买家邮箱',
            'account_id'        => '账号id',
            'account_id_search' => '账号id',
            'receive_email'     => '接收邮箱',
            'is_read'           => '是否已读',
            'is_replied'        => '是否已回复',
            'receive_date'      => '最新收件时间',
            'type_mark'         => '邮件类型',
            'create_by'         => '创建人',
            'create_time'       => '创建时间',
            'modify_by'         => '修改人',
            'modify_time'       => '修改时间',
            'start_time'        => '最新邮件开始时间',
            'end_time'          => '最新邮件结束时间',
            'reply_by_and_time' => '回复人/时间',
        ];
    }

    /**
     * 返回筛选项
     */
    public function filterOptions()
    {
        return [
            [
                'name'   => 'now_subject',
                'type'   => 'text',
                'search' => 'LIKE',
            ],
            [
                'name'   => 'order_id',
                'type'   => 'text',
                'search' => 'LIKE',
                'alias'  => 't'
            ],
            [
                'name'   => 'is_read',
                'type'   => 'dropDownList',
                'data'   => ['0' => '未读', '1' => '已读'],
                'search' => '=',
            ],
            [
                'name'   => 'is_replied',
                'type'   => 'dropDownList',
                'data'   => ['0' => '未回复', '1' => '已回复', '2' => '标记回复'],
                'search' => '=',
                'value'  => '0',
            ],
            [
                'name'   => 'buyer_id',
                'type'   => 'text',
                'search' => 'LIKE',
            ],
            [
                'name'   => 'sender_email',
                'type'   => 'text',
                'search' => 'LIKE',
            ],
            [
                'name'   => 'account_id_search',
                'type'   => 'search',
                'data'   => Account::getIdNameKVList(Platform::PLATFORM_CODE_AMAZON),
                'search' => '=',
            ],
            [
                'name'   => 'receive_email',
                'type'   => 'text',
                'search' => 'LIKE',
            ],
            [
                'name'   => 'type_mark',
                'type'   => 'dropDownList',
                'data'   => MailFilterManage::getMailTypeList(Platform::PLATFORM_CODE_AMAZON),
                'search' => '=',
            ],
            [
                'name'   => 'start_time',
                'type'   => 'date_picker',
                'search' => '<',
                'value'  => '',
            ],
            [
                'name'   => 'end_time',
                'type'   => 'date_picker',
                'search' => '>',
                'value'  => '',
            ],
            [
                'name'   => 'tag_id',
                'type'   => 'hidden',
                'search' => false,
                'alias'  => 't1',
            ],
            [
                'name'   => 'site_id',
                'type'   => 'hidden',
                'search' => false,
                'alias'  => 'm',
            ],
            [
                'name'   => 'account_id',
                'type'   => 'hidden',
                'search' => '=',
            ],
        ];
    }

    public function addition(&$models)
    {
        foreach ($models as $key => &$model) {
            $attchType = '';
            if ($model->is_attached == 1) {
                $attchType = '<i class="fa fa-file-archive-o" style="color:#000; font-size:18px;"></i>';
            }
            $model->setAttribute('now_subject', Html::a($model->now_subject . $attchType, Url::toRoute(['/mails/amazoninboxsubject/view', 'id' => $model->id]), [
                'target' => '_blank',
            ]));
            $account = Account::findOne($model->account_id);
            if (!empty($account)) {
                $model->setAttribute('account_id', $account->account_name);
            }

            //是否已读
            $model->setAttribute('is_read', $model->is_read ? '是' : '否');

            //是否回复
            $repliedStatus = '';
            switch ($model->is_replied) {
                case 0:
                    $repliedStatus = '<span style="color:red;font-weight:bold;">否</span>';
                    break;
                case 1:
                    $repliedStatus = '<span style="color:green;font-weight:bold;">是</span>';
                    break;
                case 2:
                    $repliedStatus = '标记回复';
                    break;
            }
            $model->setAttribute('is_replied', $repliedStatus);

            //回复人/时间
/*             $inboxIds = AmazonInbox::find()
                ->select('id')
                ->where(['inbox_subject_id' => $model->id])
                ->asArray()
                ->column();
            if (!empty($inboxIds)) {
                $reply = AmazonReply::find()
                    ->select('reply_by, modify_time')
                    ->where(['in', 'inbox_id', $inboxIds])
                    ->orderBy('modify_time DESC')
                    ->asArray()
                    ->one();
                if (!empty($reply)) {
                    $model->setAttribute('reply_by_and_time', $reply['reply_by'] . '/' . $reply['modify_time']);
                }
            } */
        }
    }

    /**
     * 搜索列表
     */
    public function searchList($params = [], $sort = null)
    {
        //清除已处理消息列表
        //self::destroyProcessedList();
        $query = self::find()->alias('t');

        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 't.receive_date', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 't.receive_date', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 't.receive_date', $params['end_time']]);
        }

        if (!empty($params['account_id_search']) && $params['account_id_search'] != ' ') {
            $query->andWhere(['t.account_id' => $params['account_id_search']]);
            unset($params['account_id_search']);
        }

        if (isset($params['tag_id']) && !empty($params['tag_id'])) {
            $query->innerJoin(['t1' => MailSubjectTag::tableName()], 't1.subject_id = t.id AND t1.platform_code = :platform_code1', ['platform_code1' => static::PLATFORM_CODE])
                ->andWhere('t1.tag_id = ' . $params['tag_id']);
        }

        if (isset($params['site_id']) && !empty($params['site_id'])) {
            $query->innerJoin(['m' => InboxSubjectSite::tableName()], 'm.inbox_subject_id = t.id AND m.platform_code = :platform_code2', ['platform_code2' => static::PLATFORM_CODE])
                ->andWhere('m.site_id = ' . $params['site_id']);
        }

        //客服只能查看自已绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);
        $query->andWhere(['in', 't.account_id', $accountIds]);

        if (empty($sort)) {
            $sort               = new \yii\data\Sort([
                'attributes' => ['t.id', 't.receive_date']
            ]);
            $sort->defaultOrder = array(
                't.receive_date' => SORT_DESC,
            );
        }
        $dataProvider = parent::search($query, $sort, $params);
        $models       = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * 获取标签列表
     */
    public static function getTagsList($params = [])
    {
        $tagList = Tag::find()
            ->select('id, tag_name as name')
            ->andWhere(['platform_code' => static::PLATFORM_CODE, 'status' => 1])
            ->orderBy('sort_order ASC')
            ->asArray()
            ->all();

        $query = MailSubjectTag::find()
            ->alias('s')
            ->select('s.tag_id as id, count(*) as count')
            ->innerJoin(['i' => AmazonInboxSubject::tableName()], 'i.id = s.subject_id')
            ->andWhere(['s.platform_code' => static::PLATFORM_CODE])
            ->groupBy('s.tag_id');

        //客服只能查看自已绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);
        $query->andWhere(['in', 'i.account_id', $accountIds]);

        if (isset($params['site_id']) && !empty($params['site_id'])) {
            $query->innerJoin(['m' => InboxSubjectSite::tableName()], 'm.inbox_subject_id = i.id AND m.platform_code = :platform_code', ['platform_code' => static::PLATFORM_CODE])
                ->andWhere('m.site_id = ' . $params['site_id']);
        }

        if (!empty($params['account_id'])) {
            $query->andWhere(['i.account_id' => $params['account_id']]);
        }

        if (!empty($params['account_id_search'])) {
            $query->andWhere(['i.account_id' => $params['account_id_search']]);
        }

        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 'i.receive_date', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 'i.receive_date', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 'i.receive_date', $params['end_time']]);
        }

        $options = (new static())->filterOptions();
        if (!empty($options)) {
            foreach ($options as $option) {
                //单独把account_id排除，避免冲突
                if ($option['type'] == 'hidden' || $option['name'] == 'account_id' || $option['name'] == 'account_id_search') {
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
     * 获取站点列表统计
     */
    public static function getSiteList($params = [])
    {
        //获取所有站点
        $sites = SiteManage::find()
            ->select('id, site_name as name')
            ->andWhere(['platform_code' => static::PLATFORM_CODE, 'status' => 1])
            ->orderBy('sort ASC, id DESC')
            ->asArray()
            ->all();

        $query = InboxSubjectSite::find()
            ->alias('s')
            ->select('s.site_id as id, count(*) as count')
            ->innerJoin(['i' => AmazonInboxSubject::tableName()], 'i.id = s.inbox_subject_id')
            ->andWhere(['s.platform_code' => static::PLATFORM_CODE])
            ->groupBy('s.site_id');

        //客服只能查看自已绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);
        $query->andWhere(['in', 'i.account_id', $accountIds]);

        if (isset($params['tag_id']) && !empty($params['tag_id'])) {
            $query->innerJoin(['t1' => MailSubjectTag::tableName()], 't1.subject_id = i.id AND t1.platform_code = :platform_code', ['platform_code' => static::PLATFORM_CODE])
                ->andWhere('t1.tag_id = ' . $params['tag_id']);
        }

        if (!empty($params['account_id'])) {
            $query->andWhere(['i.account_id' => $params['account_id']]);
        }

        if (!empty($params['account_id_search'])) {
            $query->andWhere(['i.account_id' => $params['account_id_search']]);
        }

        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 'i.receive_date', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 'i.receive_date', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 'i.receive_date', $params['end_time']]);
        }

        $options = (new static())->filterOptions();
        if (!empty($options)) {
            foreach ($options as $option) {
                //单独把account_id排除，避免冲突
                if ($option['type'] == 'hidden' || $option['name'] == 'account_id' || $option['name'] == 'account_id_search') {
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
        if (!empty($data)) {
            $data = array_column($data, 'count', 'id');
        }

        if (!empty($sites)) {
            foreach ($sites as &$site) {
                $site['count'] = array_key_exists($site['id'], $data) ? $data[$site['id']] : 0;
            }
        }
        return $sites;
    }

    /**
     * @desc 拉取邮件时保存主题
     * @param unknown $tmpInbox
     * @return boolean
     */
    public static function getSubjectInfo($inbox_model, $count = 0)
    {
        // 查询主题是否存在
        $order_id      = $inbox_model->order_id;
        $now_subject   = trim($inbox_model->subject);
        $buyer_id      = !empty($inbox_model->sender) ? $inbox_model->sender : $inbox_model->sender_email;
        $first_subject = trim(str_ireplace('Re:', '', $now_subject));
        $first_subject = trim(str_ireplace('aw:', '', $first_subject));

        $query = self::find();
        //添加账号条件
        if (!empty($inbox_model->account_id)) {
            $query->andWhere(['account_id' => $inbox_model->account_id]);
        }
        if (!empty($order_id)) {
            $query->andWhere(['order_id' => $order_id]);
        } else {
            if (!empty($buyer_id)) {
                $query->andWhere(['buyer_id' => $buyer_id]);
            }
            if (!empty($first_subject)) {
                //删除多余的空白字符，避免重新创建邮件主题
                $first_subject = preg_replace('/\s+/', ' ', $first_subject);
                $query->andWhere(['first_subject' => $first_subject]);
            }
        }

        $model = $query->one();
        if (!empty($model)) {
            $first_subject = $model->first_subject;
        } else {
            $model = new AmazonInboxSubject();
        }

        $model->is_attached   = ($count > 0) ? 1 : 0;
        $model->order_id      = $order_id;
        $model->first_subject = $first_subject;
        $model->now_subject   = $now_subject;
        $model->buyer_id      = $buyer_id;
        $model->sender_email  = $inbox_model->sender_email;
        $model->account_id    = $inbox_model->account_id;
        $model->receive_email = $inbox_model->receive_email;
        if (empty($model->receive_date) || strtotime($inbox_model->receive_date) > strtotime($model->receive_date)) {
            $model->receive_date = $inbox_model->receive_date;
            $model->is_read      = $inbox_model->is_read == 0 ? 0 : 1;
            $model->is_replied   = $inbox_model->is_replied == 0 ? 0 : 1;
        }

        if (!$model->save()) {
            return false;
        }

        return $model;
    }

    /**
     * @desc 去除发件人后缀
     * @param unknown $sender
     * @return string
     */
    public static function getBuyerId($sender)
    {
        $count = strrpos($sender, '-');
        if ($count === false) {
            return $sender;
        }
        return trim(substr($sender, 0, $count));
    }

    /**
     * @desc 匹配标签
     * @param unknown $inbox
     * @return boolean
     */
    public function matchTags($inbox)
    {
        $matchClass                = new \stdClass();
        $matchClass->content       = $inbox->getContent();
        $matchClass->subject       = $inbox->getSubject();
        $matchClass->platform_code = static::PLATFORM_CODE;
        $matchClass->account_id    = $inbox->getAccountId();
        $matchClass->order_id      = $inbox->getOrderId();
        $matchClass->sender        = $inbox->getSender();
        $matchClass->sender_email  = $inbox->getSenderEmail();
        $rule                      = new Rule();
        $tagIds                    = $rule->getTagIdByCondition($matchClass);

        if (empty($tagIds))
            return true;
        $tagIds = explode(',', $tagIds);
        //删除已经关联的标签
        MailSubjectTag::deleteMialTags(static::PLATFORM_CODE, $inbox->inbox_subject_id);
        $flag = MailSubjectTag::saveMailTags(static::PLATFORM_CODE, $inbox->inbox_subject_id, $tagIds);
        if (!$flag)
            throw new \Exception('Save Mail Tags Failed');
        return true;
    }

    /**
     * @desc 匹配标签,打标签
     * @param unknown $inbox
     * @return boolean
     */
    public function matchTagsPlat($inbox)
    {
        $matchClass                = new \stdClass();
        $matchClass->platform_code = static::PLATFORM_CODE;
        $matchClass->account_id    = $inbox->account_id;
        $matchClass->order_id      = $inbox->order_id;
        $matchClass->sender        = $inbox->buyer_id;
        $matchClass->sender_email  = $inbox->sender_email;
        $rule                      = new Rule();
        $tagIds                    = $rule->getTagIdByCondition($matchClass);
        if (empty($tagIds))
            return true;
        $tagIds = explode(',', $tagIds);
        //删除已经关联的标签
        MailSubjectTag::deleteMialTags(static::PLATFORM_CODE, $inbox->id);
        $flag = MailSubjectTag::saveMailTags(static::PLATFORM_CODE, $inbox->id, $tagIds);
        if (!$flag)
            throw new \Exception('Save Mail Tags Failed');
        return true;
    }

    /**
     * 获取账号列表统计
     */
    public static function getAccountCountList($params = [])
    {
        //客服只能查看自已绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);
        //获取账号列表
        $accountList = Account::find()
            ->select('id, account_short_name as name')
            ->andWhere(['platform_code' => static::PLATFORM_CODE, 'status' => 1])
            ->andWhere(['in', 'id', $accountIds])
            ->asArray()
            ->all();

        $query = AmazonInboxSubject::find()
            ->alias('i')
            ->select('i.account_id as id, count(*) as count')
            ->groupBy('i.account_id');

        //客服只能查看自已绑定账号
        $query->andWhere(['in', 'i.account_id', $accountIds]);

        if (isset($params['tag_id']) && !empty($params['tag_id'])) {
            $query->innerJoin(['t1' => MailSubjectTag::tableName()], 't1.subject_id = i.id AND t1.platform_code = :platform_code1', ['platform_code1' => static::PLATFORM_CODE])
                ->andWhere('t1.tag_id = ' . $params['tag_id']);
        }

        if (isset($params['site_id']) && !empty($params['site_id'])) {
            $query->innerJoin(['m' => InboxSubjectSite::tableName()], 'm.inbox_subject_id = i.id AND m.platform_code = :platform_code2', ['platform_code2' => static::PLATFORM_CODE])
                ->andWhere('m.site_id = ' . $params['site_id']);
        }

        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 'i.receive_date', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 'i.receive_date', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 'i.receive_date', $params['end_time']]);
        }

        $options = (new static())->filterOptions();
        if (!empty($options)) {
            foreach ($options as $option) {
                //单独把account_id排除，避免冲突
                if ($option['type'] == 'hidden' || $option['name'] == 'account_id' || $option['name'] == 'account_id_search') {
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
     * 给拉取的邮件自动筛选归类
     */
    public static function mailFilterClassify($inboxSubject = null, $isGarbage = 0)
    {
        if (empty($inboxSubject)) {
            return false;
        }

        $inboxSubjectId = $inboxSubject->id;

        //获取最新一条inbox的ID
        $inboxId = AmazonInbox::find()
            ->select('id')
            ->andWhere(['inbox_subject_id' => $inboxSubjectId])
            ->orderBy('id DESC')
            ->limit(1)
            ->scalar();
        if (empty($inboxId)) {
            return false;
        }

        $mailFilterManageList = MailFilterManage::getMailFilterManageList(Platform::PLATFORM_CODE_AMAZON);
        if (!empty($mailFilterManageList)) {
            //邮件是否满足过滤器条件
            $flag = false;

            foreach ($mailFilterManageList as $filterManage) {
                //如果规则为空，则跳过
                if (empty($filterManage['filter_rule_list'])) {
                    continue;
                }

                //规则查询
                $query = AmazonInbox::find()->andWhere(['id' => $inboxId]);

                //条件表达式
                $cond = [];
                switch ($filterManage['cond_type']) {
                    case MailFilterManage::COND_TYPE_ALL:
                        //满足所有规则
                        $cond[] = 'and';
                        break;
                    case MailFilterManage::COND_TYPE_ANY:
                        //满足任一规则
                        $cond[] = 'or';
                        break;
                }

                foreach ($filterManage['filter_rule_list'] as $rule) {
                    //规则值数组
                    $ruleValueArr = explode(',', trim($rule['value']));
                    switch ($rule['type']) {
                        //发件人包含
                        case MailFilterRule::RULE_TYPE_SEND_CONTAIN:
                            if (count($ruleValueArr) > 1) {
                                $subCond   = [];
                                $subCond[] = 'or';
                                foreach ($ruleValueArr as $value) {
                                    $subCond[] = ['like', 'sender_email', $value];
                                }
                                $cond[] = $subCond;
                            } else {
                                $cond[] = ['like', 'sender_email', $ruleValueArr[0]];
                            }
                            break;
                        //发件人不包含
                        case MailFilterRule::RULE_TYPE_SEND_NOT_CONTAIN:
                            if (count($ruleValueArr) > 1) {
                                $subCond   = [];
                                $subCond[] = 'or';
                                foreach ($ruleValueArr as $value) {
                                    $subCond[] = ['not like', 'sender_email', $value];
                                }
                                $cond[] = $subCond;
                            } else {
                                $cond[] = ['not like', 'sender_email', $ruleValueArr[0]];
                            }
                            break;
                        //收件人包含
                        case MailFilterRule::RULE_TYPE_RECEIVE_CONTAIN:
                            if (count($ruleValueArr) > 1) {
                                $subCond   = [];
                                $subCond[] = 'or';
                                foreach ($ruleValueArr as $value) {
                                    $subCond[] = ['like', 'receive_email', $value];
                                }
                                $cond[] = $subCond;
                            } else {
                                $cond[] = ['like', 'receive_email', $ruleValueArr[0]];
                            }
                            break;
                        //收件人不包含
                        case MailFilterRule::RULE_TYPE_RECEIVE_NOT_CONTAIN:
                            if (count($ruleValueArr) > 1) {
                                $subCond   = [];
                                $subCond[] = 'or';
                                foreach ($ruleValueArr as $value) {
                                    $subCond[] = ['not like', 'receive_email', $value];
                                }
                                $cond[] = $subCond;
                            } else {
                                $cond[] = ['not like', 'receive_email', $ruleValueArr[0]];
                            }
                            break;
                        //主题包含
                        case MailFilterRule::RULE_TYPE_SUBJECT_CONTAIN:
                            if (count($ruleValueArr) > 1) {
                                $subCond   = [];
                                $subCond[] = 'or';
                                foreach ($ruleValueArr as $value) {
                                    $subCond[] = ['like', 'subject', $value];
                                }
                                $cond[] = $subCond;
                            } else {
                                $cond[] = ['like', 'subject', $ruleValueArr[0]];
                            }
                            break;
                        //主题不包含
                        case MailFilterRule::RULE_TYPE_SUBJECT_NOT_CONTAIN:
                            if (count($ruleValueArr) > 1) {
                                $subCond   = [];
                                $subCond[] = 'or';
                                foreach ($ruleValueArr as $value) {
                                    $subCond[] = ['not like', 'subject', $value];
                                }
                                $cond[] = $subCond;
                            } else {
                                $cond[] = ['not like', 'subject', $ruleValueArr[0]];
                            }
                            break;
                        //正文包含
                        case MailFilterRule::RULE_TYPE_BODY_CONTAIN:
                            if (count($ruleValueArr) > 1) {
                                $subCond   = [];
                                $subCond[] = 'or';
                                foreach ($ruleValueArr as $value) {
                                    $subCond[] = ['like', 'body', $value];
                                }
                                $cond[] = $subCond;
                            } else {
                                $cond[] = ['like', 'body', $ruleValueArr[0]];
                            }
                            break;
                        //正文不包含
                        case MailFilterRule::RULE_TYPE_BODY_NOT_CONTAIN:
                            if (count($ruleValueArr) > 1) {
                                $subCond   = [];
                                $subCond[] = 'or';
                                foreach ($ruleValueArr as $value) {
                                    $subCond[] = ['not like', 'body', $value];
                                }
                                $cond[] = $subCond;
                            } else {
                                $cond[] = ['not like', 'body', $ruleValueArr[0]];
                            }
                            break;
                        //发件人等于
                        case MailFilterRule::RULE_TYPE_SEND_EQUAL:
                            if (count($ruleValueArr) > 1) {
                                $subCond   = [];
                                $subCond[] = 'or';
                                foreach ($ruleValueArr as $value) {
                                    $subCond[] = ['sender_email' => $value];
                                }
                                $cond[] = $subCond;
                            } else {
                                $cond[] = ['sender_email' => $ruleValueArr[0]];
                            }
                            break;
                    }
                }

                $query->andWhere($cond);

                if ($query->exists()) {
                    $flag = true;

                    //满足邮件过滤器规则条件
                    if (!empty($filterManage['move_site_ids'])) {
                        $siteIdArr = explode(',', $filterManage['move_site_ids']);

                        InboxSubjectSite::deleteAll([
                            'and',
                            ['inbox_subject_id' => $inboxSubjectId],
                            ['in', 'site_id', $siteIdArr],
                            ['platform_code' => Platform::PLATFORM_CODE_AMAZON],
                        ]);

                        if (!empty($siteIdArr)) {
                            foreach ($siteIdArr as $siteId) {
                                $model                   = new InboxSubjectSite();
                                $model->platform_code    = Platform::PLATFORM_CODE_AMAZON;
                                $model->site_id          = $siteId;
                                $model->inbox_subject_id = $inboxSubjectId;
                                $model->save(false);
                            }
                        }
                    }

                    if (!empty($filterManage['type_mark'])) {
                        $inboxSubject->type_mark = $filterManage['type_mark'];
                        $inboxSubject->save(false);
                    }

                    if (!empty($filterManage['mark_read'])) {
                        $inboxSubject->is_read = 1;
                        $inboxSubject->save(false);
                    }
                }
            }

            if (!$flag) {
                if ($isGarbage) {
                    //如果是垃圾邮件，仍不满足规则，依旧归到垃圾邮件分类
                    $inboxSubject->type_mark = 17;
                } else {
                    //如果是普通邮件，不满足规则，自动归类到收件箱中
                    //邮件类型定义，可看MailFilterManage::getMailTypeList方法
                    $inboxSubject->type_mark = 16;
                }
                $inboxSubject->save(false);
            }
        }
    }

    /**
     * 获取邮件类型
     * @param $key
     * @return array|mixed|string
     */
    public static function amazonMailType($key)
    {
        $list = [
            '11' => '客户来信',
            '12' => 'AZ',
            '13' => 'QA',
            '14' => '警告信',
            '15' => 'VAT',
            '16' => '收件箱',
            '17' => '垃圾邮件',
        ];
        if (!is_null($key)) {
            if (array_key_exists($key, $list))
                return $list[$key];
            else
                return '';
        }
        return $list;
    }
}

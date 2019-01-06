<?php

namespace app\modules\mails\models;

use yii\data\Sort;
use app\components\Model;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\UserAccount;
use app\modules\systems\models\Tag;
use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\orders\models\OrderOtherDetail;
use app\modules\orders\models\OrderOtherKefu;
use app\modules\services\modules\cdiscount\components\cdiscountApi;

class CdiscountInboxSubject extends Model
{

    const PLATFORM_CODE = Platform::PLATFORM_CODE_CDISCOUNT;

    /**
     * 表名
     */
    public static function tableName()
    {
        return '{{%cdiscount_inbox_subject}}';
    }

    /**
     * 属性
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $extAttributes = [
            'buyer_id',
            'start_time',
            'end_time',
            'reply_by_and_time',
            'account_name',
            'tags',
            'inboxs',
            'replys',
        ];
        return array_merge($attributes, $extAttributes);
    }

    /**
     * 属性标签
     */
    public function attributeLabels()
    {
        return [
            'account_id' => '账号',
            'inbox_id' => '站内信ID',
            'inbox_type' => '站内信类型',
            'claim_type' => '纠纷类型',
            'platform_order_id' => '平台订单ID',
            'product_ean' => 'itemID',
            'product_seller_reference' => '产品sku',
            'subject' => '主题',
            'close_date' => '关闭时间',
            'creation_date' => '创建时间',
            'last_updated_date' => '最后更新时间(中国)',
            'status' => '状态',
            'is_read' => '是否已读',
            'is_reply' => '是否回复',
            'reply_by' => '回复人',
            'reply_time' => '回复时间',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '数据更新时间(中国)',
            'buyer_id' => '买家ID',
            'start_time' => '最新邮件开始时间',
            'end_time' => '最新邮件结束时间',
            'reply_by_and_time' => '回复人/时间',
            'account_name' => '账号',
            'account_id_search' => '账号',
        ];
    }

    /**
     * 返回筛选项
     */
    public function filterOptions()
    {
        return [
            [
                'name' => 'account_id_search',
                'type' => 'search',
                'data' => Account::getIdNameKVList(static::PLATFORM_CODE),
                'search' => '=',
            ],
            [
                'name' => 'buyer_id',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'platform_order_id',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'subject',
                'type' => 'text',
                'search' => 'FULL LIKE',
            ],
            [
                'name' => 'is_reply',
                'type' => 'dropDownList',
                'data' => ['' => '全部', '0' => '未回复', '1' => '已回复', '2' => '标记回复'],
                'search' => '=',
                'value' => '0',
            ],
            [
                'name' => 'is_read',
                'type' => 'dropDownList',
                'data' => ['' => '全部', '0' => '否', '1' => '是'],
                'search' => '=',
            ],
            [
                'name' => 'product_ean',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'status',
                'type' => 'dropDownList',
                'data' => self::statusDropDown(),
                'search' => '=',
                'value' => 'Open',
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
                'name' => 'inbox_type',
                'type' => 'dropDownList',
                'data' => self::inboxTypeDropDown(),
                'search' => '=',
            ],
            [
                'name' => 'tag_id',
                'type' => 'hidden',
                'search' => false,
            ],
            [
                'name' => 'account_id',
                'type' => 'hidden',
                'search' => '=',
            ],
        ];
    }

    /**
     * 状态列表
     */
    public static function statusDropDown()
    {
        return [
            '' => '全部',
            'Open' => 'Open',
            'Closed' => 'Closed',
            'NotProcessed' => 'NotProcessed',
        ];
    }

    /**
     * 站内信类型
     */
    public static function inboxTypeDropDown()
    {
        return [
            'claim' => '纠纷问题',
            'claimupgrade' => '升级问题',
            'offerquestion' => '售前产品咨询',
            'orderquestion' => '订单咨询',
        ];
    }

    /**
     * 搜索
     */
    public function searchList($params = [], $sort = null)
    {
        $query = self::find()->alias('s');

        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 's.last_updated_date', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 's.last_updated_date', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 's.last_updated_date', $params['end_time']]);
        }

        //买家ID
        if (!empty($params['buyer_id'])) {
            $buyer_id = trim($params['buyer_id']);
            if (preg_match('/^[a-z0-9]{32}$/i', $buyer_id)) {
                $platformOrderIds = OrderOtherKefu::find()
                    ->select('platform_order_id')
                    ->andWhere(['buyer_id' => $buyer_id])
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_CDISCOUNT])
                    ->column();
                if (!empty($platformOrderIds)) {
                    $query->andWhere(['in', 'platform_order_id', $platformOrderIds]);
                }
            } else {
                $tableName = CdiscountInbox::tableName();
                $query->andWhere("EXISTS (SELECT * FROM {$tableName} WHERE sender = '{$buyer_id}' AND inbox_subject_id = s.inbox_id)");
            }

            unset($params['buyer_id']);
        }

        //itemID
        if (!empty($params['product_ean'])) {
            $product_ean = trim($params['product_ean']);
            if (preg_match('/^[0-9]{10,}$/i', $product_ean)) {
                $query->andWhere(['product_ean' => $product_ean]);
            } else {
                $orderIds = OrderOtherDetail::find()
                    ->select('order_id')
                    ->andWhere(['item_id' => $product_ean])
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_CDISCOUNT])
                    ->column();
                if (!empty($orderIds)) {
                    $platformOrderIds = OrderOtherKefu::find()
                        ->select('platform_order_id')
                        ->andWhere(['in', 'order_id', $orderIds])
                        ->column();
                    if (!empty($platformOrderIds)) {
                        $query->andWhere(['in', 'platform_order_id', $platformOrderIds]);
                    }
                }
            }

            unset($params['product_ean']);
        }

        if (!empty($params['account_id_search']) && $params['account_id_search'] != ' ') {
            $query->andWhere(['s.account_id' => $params['account_id_search']]);
            unset($params['account_id_search']);
        }

        if (isset($params['tag_id']) && !empty($params['tag_id'])) {
            $query->innerJoin(['t' => MailSubjectTag::tableName()], 't.subject_id = s.id AND t.platform_code = :platform_code1', ['platform_code1' => static::PLATFORM_CODE])
                ->andWhere('t.tag_id = ' . $params['tag_id']);
        }

        //客服只能查看自已绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);
        $query->andWhere(['in', 's.account_id', $accountIds]);

        if (empty($sort)) {
            $sort = new Sort([
                'attributes' => ['last_updated_date']
            ]);
            $sort->defaultOrder = array(
                'last_updated_date' => SORT_ASC,
            );
        }

        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * 修改模型数据
     */
    public function addition(&$models)
    {
        foreach ($models as $model) {
            $account = Account::findOne($model->account_id);
            $accountName = '';
            if (!empty($account)) {
                $accountName = $account->account_name;
            }
            //店铺
            $model->setAttribute('account_name', $accountName);

            //主题
            $model->setAttribute('subject', Html::a($model->subject, ['/mails/cdiscountinboxsubject/view', 'id' => $model->id], ['target' => '_blank']));

            //站内信类型
            $inboxType = '';
            switch ($model->inbox_type) {
                case 'claim':
                    $inboxType = '纠纷问题';
                    break;
                case 'claimupgrade':
                    $inboxType = '升级问题';
                    break;
                case 'offerquestion':
                    $inboxType = '售前产品咨询';
                    break;
                case 'orderquestion':
                    $inboxType = '订单咨询';
                    break;
            }
            $model->setAttribute('inbox_type', $inboxType);

            //买家ID和itemID
            if (!empty($model->platform_order_id)) {
                //如果平台订单ID不为空，则通过订单ID来查询买家ID和itemID
                $orderInfo = OrderOtherKefu::findOne(['platform_code' => Platform::PLATFORM_CODE_CDISCOUNT, 'platform_order_id' => $model->platform_order_id]);
                if (!empty($orderInfo)) {
                    $model->setAttribute('buyer_id', $orderInfo->buyer_id);
                }
                $itemIds = OrderOtherDetail::find()
                    ->select('item_id')
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_CDISCOUNT])
                    ->andWhere(['order_id' => $orderInfo['order_id']])
                    ->column();
                if (!empty($itemIds)) {
                    $product_ean = '';
                    foreach ($itemIds as $itemId) {
                        $product_ean .= "<p><a href='https://www.cdiscount.com/f-1650601-{$itemId}.html' target='_blank'>{$itemId}</a></p>";
                    }
                    $model->setAttribute('product_ean', $product_ean);
                }
            } else {
                //获取买家ID
                $senders = CdiscountInbox::find()
                    ->select('sender')
                    ->andWhere(['inbox_subject_id' => $model->inbox_id])
                    ->andWhere(['<>', 'sender', $account->account_discussion_name])
                    ->andWhere(['<>', 'sender', ''])
                    ->column();
                if (!empty($senders)) {
                    $senders = array_unique($senders);
                    $model->setAttribute('buyer_id', implode(',', $senders));
                }
                $model->setAttribute('product_ean', $model->product_ean . "<br>SKU: {$model->product_seller_reference}");
            }

            //最后更新时间转换成中国时间
            if (!empty($model->last_updated_date)) {
                $model->setAttribute('last_updated_date', date('Y-m-d H:i:s', strtotime($model->last_updated_date) + (6 * 3600)));
            }

            //是否已读
            if (empty($model->is_read)) {
                $model->setAttribute('is_read', '<span style="color:red;">否</span>');
            } else {
                $model->setAttribute('is_read', '<span style="color:green;">是</span>');
            }

            //回复人/回复时间
            if (!empty($model->is_reply)) {
                $reply = CdiscountInboxReply::find()
                    ->where(['inbox_subject_id' => $model->inbox_id])
                    ->orderBy('reply_time DESC')
                    ->limit(1)
                    ->one();
                if (!empty($reply)) {
                    $model->setAttribute('reply_by_and_time', $reply->reply_by . '/' . $reply->reply_time);
                }
            }

            //是否回复
            if (empty($model->is_reply)) {
                $model->setAttribute('is_reply', '<span style="color:red;">否</span>');
            } else if ($model->is_reply == 1) {
                $model->setAttribute('is_reply', '<span style="color:green;">是</span>');
            } else if ($model->is_reply == 2) {
                $model->setAttribute('is_reply', '<span>标记回复</span>');
            }

            //平台订单ID
            if (!empty($model->platform_order_id)) {
                $model->setAttribute('platform_order_id', Html::a($model->platform_order_id, Url::toRoute(['/orders/order/orderdetails', 'order_id' => $model->platform_order_id, 'platform' => Platform::PLATFORM_CODE_CDISCOUNT]),
                    ['class' => 'add-button', '_width' => '90%', '_height' => '90%']));
            }
        }
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
            ->innerJoin(['i' => CdiscountInboxSubject::tableName()], 'i.id = s.subject_id')
            ->andWhere(['s.platform_code' => static::PLATFORM_CODE])
            ->groupBy('s.tag_id');

        //客服只能查看自已绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);
        $query->andWhere(['in', 'i.account_id', $accountIds]);

        if (!empty($params['account_id'])) {
            $query->andWhere(['i.account_id' => $params['account_id']]);
        }

        if (!empty($params['account_id_search'])) {
            $query->andWhere(['i.account_id' => $params['account_id_search']]);
        }

        //买家ID
        if (!empty($params['buyer_id'])) {
            $buyer_id = trim($params['buyer_id']);
            if (preg_match('/^[a-z0-9]{32}$/i', $buyer_id)) {
                $platformOrderIds = OrderOtherKefu::find()
                    ->select('platform_order_id')
                    ->andWhere(['buyer_id' => $buyer_id])
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_CDISCOUNT])
                    ->column();
                if (!empty($platformOrderIds)) {
                    $query->andWhere(['in', 'i.platform_order_id', $platformOrderIds]);
                }
            } else {
                $tableName = CdiscountInbox::tableName();
                $query->andWhere("EXISTS (SELECT * FROM {$tableName} WHERE sender = '{$buyer_id}' AND inbox_subject_id = i.inbox_id)");
            }

            unset($params['buyer_id']);
        }

        //itemID
        if (!empty($params['product_ean'])) {
            $product_ean = trim($params['product_ean']);
            if (preg_match('/^[0-9]{10,}$/i', $product_ean)) {
                $query->andWhere(['i.product_ean' => $product_ean]);
            } else {
                $orderIds = OrderOtherDetail::find()
                    ->select('order_id')
                    ->andWhere(['item_id' => $product_ean])
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_CDISCOUNT])
                    ->column();
                if (!empty($orderIds)) {
                    $platformOrderIds = OrderOtherKefu::find()
                        ->select('platform_order_id')
                        ->andWhere(['in', 'order_id', $orderIds])
                        ->column();
                    if (!empty($platformOrderIds)) {
                        $query->andWhere(['in', 'i.platform_order_id', $platformOrderIds]);
                    }
                }
            }

            unset($params['product_ean']);
        }

        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 'i.last_updated_date', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 'i.last_updated_date', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 'i.last_updated_date', $params['end_time']]);
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
                $field = 'i.' . $field;
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
        //客服只能查看自已绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);

        //获取账号列表
        $accountList = Account::find()
            ->select('id, account_short_name as name')
            ->andWhere(['platform_code' => static::PLATFORM_CODE, 'status' => 1])
            ->andWhere(['in', 'id', $accountIds])
            ->asArray()
            ->all();

        $query = CdiscountInboxSubject::find()
            ->alias('i')
            ->select('i.account_id as id, count(*) as count')
            ->groupBy('i.account_id');

        //客服只能查看自已绑定账号
        $query->andWhere(['in', 'account_id', $accountIds]);

        if (isset($params['tag_id']) && !empty($params['tag_id'])) {
            $query->innerJoin(['t1' => MailSubjectTag::tableName()], 't1.subject_id = i.id AND t1.platform_code = :platform_code1', ['platform_code1' => static::PLATFORM_CODE])
                ->andWhere('t1.tag_id = ' . $params['tag_id']);
        }

        //买家ID
        if (!empty($params['buyer_id'])) {
            $buyer_id = trim($params['buyer_id']);
            if (preg_match('/^[a-z0-9]{32}$/i', $buyer_id)) {
                $platformOrderIds = OrderOtherKefu::find()
                    ->select('platform_order_id')
                    ->andWhere(['buyer_id' => $buyer_id])
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_CDISCOUNT])
                    ->column();
                if (!empty($platformOrderIds)) {
                    $query->andWhere(['in', 'i.platform_order_id', $platformOrderIds]);
                }
            } else {
                $tableName = CdiscountInbox::tableName();
                $query->andWhere("EXISTS (SELECT * FROM {$tableName} WHERE sender = '{$buyer_id}' AND inbox_subject_id = i.inbox_id)");
            }

            unset($params['buyer_id']);
        }

        //itemID
        if (!empty($params['product_ean'])) {
            $product_ean = trim($params['product_ean']);
            if (preg_match('/^[0-9]{10,}$/i', $product_ean)) {
                $query->andWhere(['i.product_ean' => $product_ean]);
            } else {
                $orderIds = OrderOtherDetail::find()
                    ->select('order_id')
                    ->andWhere(['item_id' => $product_ean])
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_CDISCOUNT])
                    ->column();
                if (!empty($orderIds)) {
                    $platformOrderIds = OrderOtherKefu::find()
                        ->select('platform_order_id')
                        ->andWhere(['in', 'order_id', $orderIds])
                        ->column();
                    if (!empty($platformOrderIds)) {
                        $query->andWhere(['in', 'i.platform_order_id', $platformOrderIds]);
                    }
                }
            }

            unset($params['product_ean']);
        }

        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 'i.last_updated_date', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 'i.last_updated_date', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 'i.last_updated_date', $params['end_time']]);
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
                $field = 'i.' . $field;
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
     * 获取讨论的邮件地址
     */
    public static function getDiscussionMail($accountInfo, $discussionId)
    {
        //获取讨论邮箱地址
        $cdApi = new cdiscountApi($accountInfo->refresh_token);
        $mailList = $cdApi->GetDiscussionMailList([$discussionId]);

        if (empty($mailList) || empty($mailList['GetDiscussionMailListResponse']['GetDiscussionMailListResult']['DiscussionMailList'])) {
            return false;
        }
        $mailList = $mailList['GetDiscussionMailListResponse']['GetDiscussionMailListResult']['DiscussionMailList']['DiscussionMail'];
        if (empty($mailList['MailAddress'])) {
            return false;
        }
        return $mailList['MailAddress'];
    }
}
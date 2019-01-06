<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/7 0007
 * Time: 上午 10:25
 */

namespace app\modules\mails\models;

use app\components\Model;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\UserAccount;
use app\modules\systems\models\Rule;
use yii\data\Sort;
use yii\helpers\Url;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\EbayInboxContentMongodb;
use Yii;
use kartik\select2\Select2;

class EbayInboxSubject extends InboxSubject
{
    const PLATFORM_CODE = Platform::PLATFORM_CODE_EB;
    public static $messageTypeMap = array(0 => '', 1 => 'All', 2 => 'AskSellerQuestion', 3 => 'ClassifiedsBestOffer', 4 => 'ClassifiedsContactSeller', 5 => 'ContactEbayMember', 6 => 'ContacteBayMemberViaAnonymousEmail', 7 => 'ContacteBayMemberViaCommunityLink', 8 => 'ContactMyBidder', 9 => 'ContactTransactionPartner', 10 => 'ResponseToASQQuestion', 11 => 'ResponseToContacteBayMember');
    public static $questionTypeMap = array(1 => 'CustomizedSubject', 2 => 'General', 3 => 'MultipleItemShipping', 4 => 'None', 5 => 'Payment', 6 => 'Shipping');
    public static $flaggedMap = [0 => '', 1 => '已标记', 2 => '未标记'];
    public static $highPriorityMap = [1 => '是', 2 => '否'];
    public static $isReadMap = [0 => '否', 1 => '是'];
    public static $isRepliedMap = [0 => '未回复', 1 => '已回复', 2 => '标记回复'];
    public static $responseEnabledMap = [0 => '否', 1 => '是'];

    public static function tableName()
    {
        return '{{%ebay_inbox_subject}}';
    }

    public function attributes()
    {
        $attributes      = parent::attributes();
        $extraAttributes = ['counts', 'account_name'];              //状态

        return array_merge($attributes, $extraAttributes);
    }

    public function attributeLabels()
    {
        return [
            'id'           => 'ID',
            'item_id'      => '刊登id',
            'now_subject'  => '主题',
            'buyer_id'     => '买家id',
            'account_id'   => '帐号id',
            'is_ebay'      => '是否ebay消息',
            'is_read'      => '是否已读',
            'is_replied'   => '是否已回复',
            'receive_date' => '最新收件时间',
            'create_by'    => '创建人',
            'create_time'  => '创建时间',
            'modify_by'    => '修改人',
            'modify_time'  => '修改时间',
        ];
    }

    /**
     * @desc 处理搜索数据
     * @param unknown $models
     */
    public function addition(&$models)
    {
        foreach ($models as $key => $model) {
            $models[$key]->is_replied = self::$isRepliedMap[$model->is_replied];
            $models[$key]->is_read    = self::$isReadMap[$model->is_read];

            $models[$key]->setAttribute('now_subject', '<a target="_blank" href="' . Url::toRoute(['/mails/ebayinboxsubject/detail', 'id' => $model->id]) . '">' . $model->subject . '</a>');
            if (is_numeric($model->account_id)) {
                $accountModel = Account::findOne((int)$model->account_id);
                if (empty($accountModel))
                    $models[$key]->account_id = $model->account_id;
                else
                    $models[$key]->account_id = $accountModel->account_name;
            } else
                $models[$key]->account_id = $model->account_id;
        }
    }

    public static function getAccountIdList()
    {
        $accountsArray = UserAccount::find()->select('account_id')->where(['user_id' => \Yii::$app->user->getId(), 'platform_code' => Platform::PLATFORM_CODE_EB])->asArray()->all();
        if (empty($accountsArray))
            return array();
        else
            return array_column(Account::find()->select('id,account_name')->where(['id' => array_column($accountsArray, 'account_id')])->orderBy('account_name')->asArray()->all(), 'account_name', 'id');
    }

    public function filterOptions()
    {
        return [
            [
                'name'   => 'now_subject',
                'type'   => 'text',
                //'data' => array(1=>'是',2=>'否'),
                'search' => 'FULL LIKE',
            ],
            [
                'name'   => 'account_id',
                'type'   => 'hidden',
//                'data'=>Account::getIdNameKVList(Platform::PLATFORM_CODE_EB),
                'search' => '=',
            ],
            [
                'name'   => 'buyer_id',
                'type'   => 'text',
                'search' => '=',
            ],
            [
                'name'  => 'is_ebay',
                'type'  => 'dropDownList',
                'data'  => ['yes' => 'ebay', 'no' => 'not ebay'],
                'value' => 'no'

            ],
            /*[
                'name' => 'message_type',
                'type' => 'dropDownList',
                'data' => array_slice(self::$messageTypeMap,1),
                'search' => '=',
            ],*/
            [
                'name'   => 'is_replied',
                'type'   => 'dropDownList',
                'data'   => self::$isRepliedMap,
                'value'  => '0',
                'search' => '=',
            ],
            [
                'name'   => 'is_read',
                'type'   => 'dropDownList',
                'data'   => self::$isReadMap,
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
                'name'   => 'tag_id',
                'type'   => 'hidden',
                'search' => false,
                'alias'  => 't1',
            ],
            [
                'name'   => 'item_id',
                'type'   => 'text',
                'search' => '=',
            ],
        ];
    }

    public static function clearExcludeList()
    {
        $session = \Yii::$app->session;
        $session->remove(self::excludeListName());
    }

    public static function setExcludeList($id)
    {
        $session     = \Yii::$app->session;
        $sessionName = self::excludeListName();
        $excludeList = $session->get($sessionName);
        if (empty($excludeList)) {
            $excludeList = [$id];
        } else {
            $excludeList   = unserialize($excludeList);
            $excludeList[] = $id;
        }
        $session->set($sessionName, serialize($excludeList));
    }

    public static function getExcludeList()
    {
        $session     = \Yii::$app->session;
        $excludeList = $session->get(self::excludeListName());
        return empty($excludeList) ? $excludeList : unserialize($excludeList);
    }

    //下一封时session存储排除邮件的key值。
    public static function excludeListName()
    {
        return get_called_class();
    }

    //下一封
    public function nextInbox()
    {
        $queryParams = $this->getSearchQuerySession();

        $queryParams['query']->andWhere(['is_replied' => 0]);
        $excludeList = self::getExcludeList();
        if (!empty($excludeList)) {
            $queryParams['query']->andWhere(['not in', 'id', $excludeList]);
        }
        $sort = $queryParams['sort']->orders;
        if (!empty($sort))
            $queryParams['query']->orderBy($queryParams['sort']->orders);
        $nextModel = $queryParams['query']->one();
        return $nextModel;
    }

    public function getContent()
    {
        $contentMongodb = EbayInboxContentMongodb::findOne(['ueb_ebay_inbox_id' => $this->id]);
        return empty($contentMongodb) ? '' : $contentMongodb->content;
    }

    public function getSubject()
    {
        return $this->now_subject;
    }

    public function getAccountId()
    {
        return $this->account_id;
    }

    public function getOrderId()
    {
        $this->transaction_id = trim($this->transaction_id);
        if ($this->transaction_id === '' || $this->transaction_id === null)
            return '';
        else
            return $this->item_id . '-' . $this->transaction_id;
    }

    public function dynamicChangeFilter(&$filterOptions, &$query, &$params)
    {
        foreach ($filterOptions as $key => $filterOption) {
            if ($filterOption['name'] == 'is_ebay') {
                $value = isset($params['is_ebay']) ? $params['is_ebay'] : (isset($filterOption['value']) ? $filterOption['value'] : '');
                switch ($value) {
                    case 'yes':
                        $query->andWhere(['buyer_id' => 'eBay']);
                        break;
                    case 'no':
                        $query->andWhere(['<>', 'buyer_id', 'eBay']);
                        break;
                }
                unset($filterOptions[$key]);
                unset($params['is_ebay']);
            }
        }
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->andWhere(['between', 'receive_date', $params['start_time'], $params['end_time']]);
        } else if (!empty($params['start_time'])) {
            $query->andWhere(['>=', 'receive_date', $params['start_time']]);
        } else if (!empty($params['end_time'])) {
            $query->andWhere(['<=', 'receive_date', $params['end_time']]);
        }
    }

    public static function summaryByAccount()
    {
        $return     = [];
        $accountIds = array_column(UserAccount::find()->select('account_id')->where(['platform_code' => Platform::PLATFORM_CODE_EB, 'user_id' => \Yii::$app->user->getIdentity()->id])->asArray()->all(), 'account_id');
        $queryInbox = self::find()->select('`account_id`,count(id) `count`')->where('sender <> "eBay" and is_replied = 0');
        if (!empty($accountIds))
            $queryInbox->andWhere(['account_id' => $accountIds]);
        $accountCounts = $queryInbox->groupBy('account_id')->asArray()->all();
        if (!empty($accountCounts)) {
            $accountCounts = array_column($accountCounts, null, 'account_id');
            $accounts      = Account::find()->select('account_name,id')->where(['id' => array_keys($accountCounts)])->orderBy('account_name')->asArray()->all();
            foreach ($accounts as $account) {
                $return[$account['id']] = $account['account_name'] . "({$accountCounts[$account['id']]['count']})";
            }
        }
        return $return;
    }


    /*
     * @desc 同一人同订单的多封未回复消息，回复一份封，其他未回复都标记已回复
     **/

    public static function NoReplySign($account_id, $transaction_id, $receive_date)
    {
        $model = new self();
        return $model->updateAll(array('is_replied' => 1), 'account_id = :aid and transaction_id =:tid and receive_date < :time', [':aid' => $account_id, ':tid' => $transaction_id, ':time' => $receive_date]);

    }


    /*
     * @desc 匹配mongodb content 
     **/

    public static function getMongoContent($id)
    {

        $model = new self();

        $contentMongodb = EbayInboxContentMongodb::findOne(['ueb_ebay_inbox_id' => (int)$id]);

        if (!empty($contentMongodb)) {
            preg_match("/<div id=\"UserInputtedText\">(.|\n)*?<\/div>/", $contentMongodb->content, $mat);

            $model->new_message = empty($mat[0]) ? "" : $mat[0];

            if (strlen($model->new_message) < 2)
                $model->new_message = "没有拉取到数据";

        }

        $return = $model->updateAll(array('new_message' => $model->new_message), 'id=:inbox_id', [':inbox_id' => $id]);

        return $return;
    }

    /**
     * @desc 拉取信息时获取主题
     * @param unknown $tmpInbox
     * @return boolean
     */
    public static function getSubjectInfo($inbox_model, $isNew, $subject_pregs = array())
    {

        $account_id  = $inbox_model->account_id;
        $item_id     = $inbox_model->item_id;
        $now_subject = $inbox_model->subject;
        $buyer_id    = $inbox_model->sender;

        $model = '';

        if ($isNew) {
            if ($buyer_id != 'eBay')
                $model = self::find()->where(['item_id' => $item_id, 'buyer_id' => $buyer_id, 'account_id' => $account_id])->one();

        } else {
            if (!empty($inbox_model->inbox_subject_id))
                $model = self::find()->where(['id' => $inbox_model->inbox_subject_id])->one();
            else
                $model = self::find()->where(['item_id' => $item_id, 'buyer_id' => $buyer_id, 'account_id' => $account_id])->one();
        }

        if (empty($model)) {
            $model                = new self;
            $model->first_subject = trim($now_subject);
            $model->receive_date  = $inbox_model->receive_date;
        } else {
            if ($model->is_replied != InboxSubject::REPLY_NO && strcmp($inbox_model->receive_date, $model->receive_date) > 0)
                $model->receive_date = $inbox_model->receive_date;
        }

        $model->item_id       = $item_id;
        $model->now_subject   = $now_subject;
        $model->buyer_id      = $buyer_id;
        $model->account_id    = $account_id;
        $model->high_priority = $inbox_model->high_priority;

        $model->is_replied = $inbox_model->is_replied;
        if ($buyer_id == 'eBay' && !empty($subject_pregs)) {
            foreach ($subject_pregs as $subject_preg) {
                if (strpos($now_subject, $subject_preg) !== false) {
                    $model->is_replied = 1;
                    break;
                } else
                    continue;
            }
        }

        if (!$model->save()) {
            return false;
        }

        return $model;
    }


    /**
     * 获取订单相关的站内信
     * @author allen <2018-03-15>
     */
    public static function isSetEbayInboxSubject($info)
    {
        $bool    = false;
        $account = Account::find()->select('id')->where(['old_account_id' => $info['account_id'], 'platform_code' => 'EB'])->asArray()->one();
        if (!empty($account)) {
            $account_id = $account['id'];
            $buyer_id   = $info['buyer_id'];
            $details    = isset($info['detail']) ? $info['detail'] : null;
            if (!empty($details)) {
                foreach ($details as $detail) {
                    $item_id[] = $detail['item_id'];
                }
                //查询是否有回复主题
                $model = self::find()->select('id,item_id')->where(['account_id' => $account_id, 'buyer_id' => $buyer_id])->andWhere(['in', 'item_id', $item_id])->asArray()->all();
                if (!empty($model)) {
                    $bool = TRUE;
                }
            }
        }
        if (isset($model) && !empty($model)) {
            $model_return = $model;
        } else {
            $model_return = [];
        }
        return ['bool' => $bool, 'info' => $model_return];
    }


    /**
     * 查询站内信的item_id
     * @param $account_id
     * @param $item_id
     * @param $buyer_id
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function EbayInboxSubject($account_id, $item_id, $buyer_id)
    {
        $account = Account::find()->select('id')->where(['old_account_id' => $account_id, 'platform_code' => 'EB'])->asArray()->one();
        if (!empty($account)) {
            $account_id = $account['id'];
            //查询是否有回复主题
            $model = self::find()
                ->select('id,item_id')
                ->where(['account_id' => $account_id, 'buyer_id' => $buyer_id])
                ->andWhere(['=', 'item_id', $item_id])
                ->asArray()->one();
            if (!empty($model)) {
                return $model;
            }
        }
    }

    /**
     * 查询是否有站内信
     * @param $account_id
     * @param $item_id_arr
     * @param $buyer_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function haveEbayInboxSubject($account_id, $item_id_arr, $buyer_id)
    {
        //查询是否有回复主题
        $model = self::find()
            ->select('id,item_id')
            ->where(['account_id' => $account_id, 'buyer_id' => $buyer_id])
            ->andWhere(['in', 'item_id', $item_id_arr])
            ->asArray()
            ->all();
        if (!empty($model)) {
            return $model;
        } else {
            return '';
        }
    }

}
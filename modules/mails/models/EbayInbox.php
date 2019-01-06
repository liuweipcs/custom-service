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
use yii\data\Sort;
use yii\helpers\Url;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\EbayInboxContentMongodb;
use Yii;
use kartik\select2\Select2;
use Yii\base\Exception;
use app\components\GoogleTranslation;

class EbayInbox extends Inbox
{
    const PLATFORM_CODE = Platform::PLATFORM_CODE_EB;
    const SENDER_EBAY = 'eBay';
    public static $messageTypeMap = array(0=>'',1=>'All',2=>'AskSellerQuestion',3=>'ClassifiedsBestOffer',4=>'ClassifiedsContactSeller',5=>'ContactEbayMember',6=>'ContacteBayMemberViaAnonymousEmail',7=>'ContacteBayMemberViaCommunityLink',8=>'ContactMyBidder',9=>'ContactTransactionPartner',10=>'ResponseToASQQuestion',11=>'ResponseToContacteBayMember');
    public static $questionTypeMap = array(1=>'CustomizedSubject',2=>'General',3=>'MultipleItemShipping',4=>'None',5=>'Payment',6=>'Shipping');
    public static $flaggedMap = [0=>'',1=>'已标记',2=>'未标记'];
    public static $highPriorityMap = [1=>'是',2=>'否'];
    public static $isReadMap = [0=>'否',1=>'是'];
    public static $isRepliedMap = [0=>'未回复',1=>'已回复未发送',2=>'已回复已发送',3=>'标记已回复'];
    public static $responseEnabledMap = [0=>'否',1=>'是'];

    public static function tableName()
    {
        return '{{%ebay_inbox}}';
    }
    
    public function rules()
    {
        return [
            [['expiration_date', 'ch_expiration_date', 'receive_date', 'ch_receive_date', 'create_time', 'modify_time', 'message_time'], 'safe'],
            [['response_url', 'subject', 'new_message', 'orgial_img', 'new_message_en', 'remark'], 'string'],
            [['message_id','recipient_user_id', 'sender', 'send_to_name', 'create_by', 'modify_by'], 'string', 'max' => 50],
            [['message_id'], 'unique'],
        ];
    }
    
    public function attributeLabels()
    {
        return [
            'flagged'                           => '是否标记',
            'message_id'                        => '邮件ID',
            'high_priority'                     => '优先级',
            'content'                           => '内容',
            'item_id'                           => 'itemID',
            'expiration_date'                   => '过期时间',
            'ch_expiration_date'                   => '过期时间',
            'message_type'                      => '消息类型',
            'question_type'                     => '问题类型',
            'is_read'                           => '是否已读',
            'receive_date'                      => '接收邮件时间',
            'ch_receive_date'                      => '接收邮件时间',
            'recipient_user_id'                 => '收件者',
            'is_replied'                        => '回复状态',
            'response_enabled'                  => '可否回复',
            'response_url'                      => '回复地址',
            'sender'                            => '发件者',
            'subject'                           => '主题',
            'platform_id'                       => '平台',
            'account_id'                        => 'Ebay账号',
            'create_by'                         => '创建者',
            'create_time'                       => '创建时间',
            'modify_by'                         => '修改者',
            'modify_time'                       => '修改时间',
            'transaction_id'                    => '交易ID',
            'is_ebay'                           => '是否ebay消息',
        ];
    }
    
    /**
     * @desc 处理搜索数据
     * @param unknown $models
     */
    public function addition(&$models) 
    {
        
        self::clearExcludeList();//清空排除列表，下一封功能时用到
        foreach ($models as  $key => $model)
        {
            $models[$key]->flagged = self::$flaggedMap[$model->flagged];
            //$models[$key]->high_priority = self::$highPriorityMap[$model->high_priority];
            $models[$key]->message_type = self::$messageTypeMap[$model->message_type];
            $models[$key]->question_type = isset(self::$questionTypeMap[$model->question_type]) ?
                self::$questionTypeMap[$model->question_type] : '';
            $models[$key]->is_replied = self::$isRepliedMap[$model->is_replied];
            $models[$key]->is_read = self::$isReadMap[$model->is_read];
            //$models[$key]->question_type = self::$questionTypeMap[$model->question_type];
            //$models[$key]->question_type = self::$questionTypeMap[$model->question_type];
            $models[$key]->setAttribute('subject', '<a target="_blank" href="'.Url::toRoute(['/mails/ebayinbox/detail','id'=>$model->id]).'">'.$model->subject.'</a>');
            if(is_numeric($model->account_id))
            {
                $accountModel = Account::findOne((int)$model->account_id);
                if(empty($accountModel))
                    $models[$key]->account_id = $model->account_id;
                else
                    $models[$key]->account_id = $accountModel->account_name;
            }
            else
                $models[$key]->account_id = $model->account_id;
        }    
    }

    public static function getAccountIdList()
    {
        $accountsArray = UserAccount::find()->select('account_id')->where(['user_id'=>\Yii::$app->user->getId(),'platform_code'=>Platform::PLATFORM_CODE_EB])->asArray()->all();
        if(empty($accountsArray))
            return array();
        else
            return array_column(Account::find()->select('id,account_name')->where(['id'=>array_column($accountsArray,'account_id')])->orderBy('account_name')->asArray()->all(),'account_name','id');
    }

    public function filterOptions()
    {
        return [
            [
                'name' => 'message_id',
                'type' => 'text',
                //'data' => array(1=>'是',2=>'否'),
                'search' => 'LIKE',
            ],
            [
                'name' => 'subject',
                'type' => 'text',
                //'data' => array(1=>'是',2=>'否'),
                'search' => 'FULL LIKE',
            ],
            [
                'name' => 'is_ebay',
                'type' => 'dropDownList',
                'data' => ['yes'=>'ebay','no'=>'not ebay'],
                'value' => 'no',
            ],
            [
                'name' => 'sender',
                'type' => 'text',
//                'data' => self::getFieldList('sender','sender','sender'),
                'search' => '=',
            ],
            [
                'name' => 'recipient_user_id',
                'type' => 'text',
//                'data' => self::getFieldList('recipient_user_id','recipient_user_id','recipient_user_id'),
                'search' => '=',
            ],
            [   
                'name'=>'account_id',
                'type'=>'search',
                'data'=>Account::getIdNameKVList(Platform::PLATFORM_CODE_EB),
                'search'=>'=',
                
            ],
           
            [
                'name' => 'high_priority',
                'type' => 'dropDownList',
                'data' => array(1=>'是',2=>'否'),
                'search' => '=',
            ],
            /*[
                'name' => 'message_type',
                'type' => 'dropDownList',
                'data' => array_slice(self::$messageTypeMap,1),
                'search' => '=',
            ],*/
            [
                'name' => 'is_replied',
                'type' => 'dropDownList',
                'data' => self::$isRepliedMap,
                'value'=> '0',
                'search' => '=',
            ],
            [
                'name' => 'is_read',
                'type' => 'dropDownList',
                'data' => self::$isReadMap,
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

    public static function clearExcludeList()
    {
        $session = \Yii::$app->session;
        $session->remove(self::excludeListName());
    }

    public static function setExcludeList($id)
    {
        $session = \Yii::$app->session;
        $sessionName = self::excludeListName();
        $excludeList = $session->get($sessionName);
        if(empty($excludeList))
        {
            $excludeList = [$id];
        }
        else
        {
            $excludeList = unserialize($excludeList);
            $excludeList[] = $id;
        }
        $session->set($sessionName,serialize($excludeList));
    }

    public static function getExcludeList()
    {
        $session = \Yii::$app->session;
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
        /*if(empty($queryParams))
            throw new Exception('请尝试重新登陆');*/
        $queryParams['query']->andWhere(['is_replied'=>0]);
        $excludeList = self::getExcludeList();
        if(!empty($excludeList))
        {
            $queryParams['query']->andWhere(['not in','t.id',$excludeList]);
        }
        $sort = $queryParams['sort']->orders;
        if(!empty($sort))
            $queryParams['query']->orderBy($queryParams['sort']->orders);
        $nextModel = $queryParams['query']->one();
        return $nextModel;
    }

    public function getContent()
    {
        $contentMongodb = EbayInboxContentMongodb::findOne(['ueb_ebay_inbox_id'=>$this->id]);
        return empty($contentMongodb) ? '':$contentMongodb->content;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getAccountId()
    {
        return $this->account_id;
    }

    public function getOrderId()
    {
        $this->transaction_id = trim($this->transaction_id);
        if($this->transaction_id === '' || $this->transaction_id === null)
            return '';
        else
            return $this->item_id.'-'.$this->transaction_id;
    }

    public function dynamicChangeFilter(&$filterOptions,&$query,&$params)
    {
        foreach($filterOptions as $key=>$filterOption)
        {
            if($filterOption['name'] == 'is_ebay')
            {
                $value = isset($params['is_ebay']) ? $params['is_ebay'] : (isset($filterOption['value']) ? $filterOption['value'] : '');
                switch($value)
                {
                    case 'yes':
                        $query->andWhere(['sender'=>'eBay']);
                        break;
                    case 'no':
                        $query->andWhere(['<>','sender','eBay']);
                        break;
                }
                unset($filterOptions[$key]);
                unset($params['is_ebay']);
            }
        }
    }

    public static function summaryByAccount()
    {
        $return = [];
        $accountIds = array_column(UserAccount::find()->select('account_id')->where(['platform_code'=>Platform::PLATFORM_CODE_EB,'user_id'=>\Yii::$app->user->getIdentity()->id])->asArray()->all(),'account_id');
        $queryInbox = self::find()->select('`account_id`,count(id) `count`')->where('sender <> "eBay" and is_replied = 0');
        if(!empty($accountIds))
            $queryInbox->andWhere(['account_id'=>$accountIds]);
        $accountCounts = $queryInbox->groupBy('account_id')->asArray()->all();
        if(!empty($accountCounts))
        {
            $accountCounts = array_column($accountCounts,null,'account_id');
            $accounts = Account::find()->select('account_name,id')->where(['id'=>array_keys($accountCounts)])->orderBy('account_name')->asArray()->all();
            foreach($accounts as $account)
            {
                $return[$account['id']] = $account['account_name']."({$accountCounts[$account['id']]['count']})";
            }
        }
        return $return;
    }


    /*
     * @desc 同一人同订单的多封未回复消息，回复一份封，其他未回复都标记已回复
     **/

    public static function NoReplySign($account_id,$inbox_subject_id,$receive_date)
    {   
        
        $model = new self();
//        return $model->updateAll(array('is_replied' =>1),'account_id = :aid and transaction_id =:tid and receive_date <= :time',[':aid' =>$account_id,':tid' => $transaction_id ,':time' => $receive_date]);
        return $model->updateAll(array('is_replied' =>1),'account_id = :aid and inbox_subject_id =:tid and receive_date <= :time',[':aid' =>$account_id,':tid' => $inbox_subject_id ,':time' => $receive_date]);

    }



    /*
     * @desc 匹配mongodb content 
     **/

    public static function getMongoContent($id) 
    {
        ini_set("pcre.recursion_limit", "524");     //解决preg_match匹配较大的字符串是出现程序崩溃的问题

        $model = new self();

        $contentMongodb = EbayInboxContentMongodb::findOne(['ueb_ebay_inbox_id'=>(int)$id]);

        if(!empty($contentMongodb))
        {
            preg_match("/<div id=\"UserInputtedText\">(.|\n)*?<\/div>/", $contentMongodb->content,$mat);

            $model->new_message = empty($mat[0])? "":$mat[0];

            if(strlen($model->new_message)<2)
                $model->new_message = $contentMongodb->content;
                
        }
        
        $return = $model->updateAll(array('new_message'=>$model->new_message),'id=:inbox_id',[':inbox_id'=>$id]);
         
        return $return;
    }

    /**
     * 获取mongodb中邮件的内容
     */
    public static function getMongoNewMessage($id)
    {
        ini_set("pcre.recursion_limit", "524");

        $inbox = self::findOne($id);
        $minbox = EbayInboxContentMongodb::findOne(['ueb_ebay_inbox_id' => $id]);
        if (!empty($minbox)) {
            preg_match("/<div id=\"UserInputtedText\">(.|\n)*?<\/div>/", $minbox->content,$match);
            $inbox->new_message = !empty($match[0]) ? $match[0] : '';

            if (strlen($inbox->new_message) < 2) {
                $inbox->new_message = $minbox->content;
            }
        }

        if ($inbox->save()) {
            return $inbox->new_message;
        } else {
            return '';
        }
    }
    
    /**
     * 加载详情页面翻译对应主题下客户留言的消息
     * @param type $id
     * @author allen <2018-1-5>
     */
    public static function loadTranslationData($id){
        $arr = array('<div id="UserInputtedText">' => "");
        $query =  self::find();
        $res = $query->where('inbox_subject_id = '.$id.' and new_message <> "" AND (language_code = "" OR language_code IS NULL)')->all();
//        echo $query->createCommand()->getRawSql();die;
        if($res){
            foreach ($res as $value) {
                    $message =  strtr($value->new_message,$arr);
                    $message = rtrim($message,'</div>');
                    $afterTranslationJson = GoogleTranslation::translate($message,'auto','en');
                    $afterTranslation = json_decode($afterTranslationJson);
                if(is_array($afterTranslation) && !empty($afterTranslation)){
                    $new_message_en = isset($afterTranslation[0]) ? $afterTranslation[0] : "";
                    $language_code = isset($afterTranslation[1]) ? $afterTranslation[1] : "";
                    $value-> new_message_en = $new_message_en;
                    $value->language_code = $language_code;
                    $value->save();
                }
            }
        }
    }
}
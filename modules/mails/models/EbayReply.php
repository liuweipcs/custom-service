<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/11 0011
 * Time: 上午 10:23
 */

namespace app\modules\mails\models;

use app\modules\services\modules\ebay\models\AddMemberMessageAAQToPartner;
use PhpImap\Exception;
use app\modules\accounts\models\Account;

class EbayReply extends Reply
{
    public static $questionTypeMap = array(1=>'CustomizedSubject',2=>'General',3=>'MultipleItemShipping',4=>'None',5=>'Payment',6=>'Shipping');
    public static $isDraftMap = ['否','是'];
    public $reply_content_bk;

    public static function tableName()
    {
        return '{{%ebay_reply}}';
    }

    public function attributeLabels()
    {
        return [
            //'flagged'                           => '是否标记',
            'inbox_id'                          => '收件箱邮件',
            'item_id'                           => '产品',
            'reply_content'                     => '回复内容',
            'question_type'                     => '问题类型',
            'account_id'                        => 'ebay账号',
            'sender'                            => '发件者',
            'recipient_id'                      => '收件者',
            'reply_title'                       => '主题',
            'platform_order_id'                 => '平台订单ID',
            'is_draft'                          => '草稿',
            'is_send'                           => '是否发送',
            'create_by'                         => '创建者',
            'create_time'                       => '创建时间',
            'modify_by'                         => '修改者',
            'modify_time'                       => '修改时间',
            'reply_content_bk'                  => '',
        ];
    }
    public function rules()
    {
        return [
            [['account_id','reply_content','recipient_id',],'required'],
            [['inbox_id','account_id','parent_message_id'],'integer'],
            ['sender','string','max'=>50],
//            ['reply_content','string','length'=>[1,2000]],
            ['reply_content','safe'],
            ['question_type','default','value'=>0],
            ['question_type','checkQuestionType'],
            ['recipient_id','string','min'=>1],
            ['reply_title','string','length'=>[1,255]],
            [['platform_order_id','item_id'],'default','value'=>''],
            ['item_id','string','length'=>[0,15]],
            [['is_draft','is_send'],'default','value'=>0],
            [['is_draft','is_send'],'in','range'=>[0,1]],
        ];
    }

    public function checkQuestionType($attribute)
    {
        $valueMap = array_keys(self::$questionTypeMap);
        $valueMap[] = 0;
        if(!in_array($this->$attribute,$valueMap))
            $this->addError($attribute,'值错误。');
    }

    public function searchList($params = [])
    {
        $query = self::find()->where(['inbox_id'=>0,'parent_message_id'=>'']);
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'id' => SORT_ASC
        );
        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    public function addition(&$models)
    {
        foreach ($models as $model)
        {
            $model->account_id = Account::findOne((int)$model->account_id)->account_name;
        }
    }

    public function filterOptions()
    {
        return [
            [
                'name'=>'reply_title',
                'type' => 'text',
                //'data' => EbayAccount::getIdNameKVList(),
                'search'=> '='
            ],
            [
                'name'=>'account_id',
                'type' => 'dropDownList',
                'data' => Account::getIdNameKVList(),
                'search'=> '='
            ],
            [
                'name'=>'question_type',
                'type' => 'dropDownList',
                'data' => self::$questionTypeMap,
                'search'=> '='
            ],
            [
                'name'=>'recipient_id',
                'type' => 'dropDownList',
                'data' => self::getFieldList('recipient_id','recipient_id','recipient_id',['inbox_id'=>0,'parent_message_id'=>'']),
                'search'=> '='
            ],
        ];
    }

    //主动发送邮件
    public function sendInitiativeSendMessage()
    {
        if($this->is_draft !== 0)
            return ['status'=>false,'info'=>'邮件为草稿，不能发送。'];
        if($this->inbox_id !== 0)
            return ['status'=>false,'info'=>'邮件属于回复邮件，不能使用主动发送功能。'];
        if($this->is_send !== 0)
            return ['status'=>false,'info'=>'邮件已发送，不能重复发送。'];
        if($this->is_delete !== 0)
            return ['status'=>false,'info'=>'邮件已删除，不能发送。'];
        try{
            $model = new AddMemberMessageAAQToPartner($this);
            $model->addMessage();
            $model->handleResponse();
            return ['status'=>$model->handleResponse(),'info'=>'发送失败。'];
        }catch(Exception $e){
            return ['status'=>false,'info'=>$e->getMessage()];
        }
    }

    public function getPictures()
    {
        return $this->hasMany(EbayReplyPicture::className(),['reply_table_id'=>'id']);
    }

    /**
     * @desc 添加自动回复
     * @param unknown $data
     * @return boolean|number
     */
    public function saveReply($inbox, $relyData){
        $this->inbox_id = $inbox->id;
        $this->parent_message_id = $inbox->message_id;
        $this->external_message_id = $inbox->external_message_id;
        $this->reply_content = $relyData['content'];
        $this->create_by = $relyData['reply_by'];
        $this->item_id = $inbox->item_id;
        $this->sender = $inbox->recipient_user_id;
        $this->recipient_id = $inbox->sender;
        $this->is_draft = 0;
        $this->is_delete = 0;
        $this->is_send = 0;
        $this->create_time = date('Y-m-d H:i:s');
        $this->account_id = $inbox->account_id;
        $this->reply_title = $relyData['subject'];
        $this->platform_order_id = $inbox->getOrderId();
        $flag = $this->save();
        if (!$flag)
            return false;
        return $this;
    }

    /**
     * @desc 添加自动回复
     * @param unknown $data
     * @return boolean|number
     */
    public function saveSelfReply($relyData){
        $this->reply_content = $relyData['content'];
        $this->create_by = $relyData['reply_by'];
        $this->modify_by = $relyData['reply_by'];
        $this->item_id = $relyData['item_id'];
        $this->sender = $relyData['sender'];
        $this->recipient_id = $relyData['recipient_id'];
        $this->question_type = 2;
        $this->is_draft = 0;
        $this->is_delete = 0;
        $this->is_send = 0;
        $this->create_time = date('Y-m-d H:i:s');
        $this->modify_time = date('Y-m-d H:i:s');
        $this->account_id = $relyData['account_id'];
        $this->reply_title = $relyData['subject'];
        $this->platform_order_id = $relyData['platform_order_id'];
        $flag = $this->save();
        if (!$flag)
            return false;
        return $this;
    }

    /**
     * @desc 获取添加到发件箱参数
     * @param unknown $inbox
     * @return \yii\helpers\string
     */
    public function getSendParams($type=1)
    {
        $sendParams = '';
        try
        {
            $sendParams = [
                'account_id' => $this->account_id,
                'ItemID' => $this->item_id,
                'ParentMessageID' => $this->parent_message_id,
                'RecipientID' => $this->recipient_id,
                'ExternalMessageID' => $this->external_message_id,
            ];
            if($type!=1)
            {
                $sendParams = [
                    'account_id' => $this->account_id,
                    'ItemID' => $this->item_id,
                    'QuestionType'=>self::$questionTypeMap[$this->question_type],
                    'RecipientID'=>$this->recipient_id,
                ];
            }
            $sendParams = \yii\helpers\Json::encode($sendParams);
        }
        catch (\Exception $e)
        {
            //echo $e->getMessage();exit;
        }
        return $sendParams;
    }

}
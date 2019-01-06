<?php

namespace app\modules\mails\models;

use Yii;
use yii\db\Query;
use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\UserAccount;
use phpDocumentor\Reflection\Types\Static_;

/**
 * This is the model class for table "{{%amazon_inbox}}".
 *
 * @property string $id
 * @property integer $parent_id
 * @property integer $platform_id
 * @property string $order_id
 * @property integer $account_id
 * @property string $subject
 * @property string $body
 * @property integer $mail_type
 * @property string $sender
 * @property string $sender_email
 * @property string $receiver
 * @property string $receive_email
 * @property integer $receive_date
 * @property string $message_time
 * @property integer $is_read
 * @property integer $is_replied
 * @property integer $reply_date
 * @property string $create_by
 * @property string $create_time
 * @property string $modify_by
 * @property string $modify_time
 * @property integer $status
 *
 * @property Account $account
 */
class AmazonInbox extends Inbox
{   
    const IS_REPLIED_NO = 0; //未回复
    const IS_REPLIED_YES_NO_SYNCHRO = 1; //已回复未同步
    const IS_REPLIED_YES_YES_SYNCHRO = 2; //已回复已同步

    const PLATFORM_CODE = Platform::PLATFORM_CODE_AMAZON;
    public $attch = 0;  //默认无附件
    public $account_name;
    public $inbox_id;
    public $reply_content;
    public $reply_title;
    public $reply_by;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%amazon_inbox}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'mail_type','account_id','receive_date','reply_date'], 'safe'],
            [['parent_id', 'account_id', 'mail_type', 'is_read', 'is_replied', 'status', 'content_type','inbox_subject_id'], 'integer'],
            [['body'], 'string'],
            [['message_time', 'create_time', 'modify_time'], 'safe'],
            [['order_id'], 'string', 'max' => 30],
            [['subject', 'receiver', 'receive_email'], 'string', 'max' => 500],
            [['sender', 'sender_email'], 'string', 'max' => 80],
            [['create_by', 'modify_by'], 'string', 'max' => 50],
            [['message_id'], 'string', 'max' => 100],
            [['remark'], 'string', 'max' => 255],
            // [['account_id'], 'exist', 'skipOnError' => true, 'targetClass' => Account::className(), 'targetAttribute' => ['account_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_id' => '父消息ID',
            'order_id' => '订单号',
            'account_id' => '账号',
            'subject' => '主题',
            'body' => '内容',
            'mail_type' => '类型',
            'sender' => '发件人',
            'sender_email' => '发件人邮箱',
            'receiver' => '收件人',
            'receive_email' => '收件人邮箱',
            'receive_date' => '收件时间',
            'message_time' => 'Message Time',
            'is_read' => '阅读状态',
            'is_replied' => '回复状态',
            'reply_date' => '回复时间',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
            'status' => '状态',
            'is_read_text' => '阅读状态',
            'is_replied_text' => '回复状态',
        ];
    }

    public function attributes()
    {
        $attributes =  parent::attributes(); // TODO: Change the autogenerated stub
        $array = ['history','attachments'];
        return array_merge($array,$attributes);
    }

    /**
     * @inheridoc
     * 
     * @return array
     */
    public function filterOptions()
    {
        return [
            [
                'name'   => 'mail_type',
                'type'   => 'dropDownList',
                'data'   => ['1' => '平台邮件', '2' => '买家邮件'],
                'search' => '=',
            ],

            [
                'name'   => 'order_id',
                'type'   => 'text',
                'search' => 'LIKE',
            ],

            [
                'name'   => 'account_id',
                'type'   => 'search',
                'data'   => self::getAccountList(self::STATUS_VALID),
                'search' => '=',
            ],

            [
                'name'   => 'sender',
                'type'   => 'text',
                'search' => 'LIKE',
            ],
            
            [
                'name'   => 'is_read',
                'type'   => 'dropDownList',
                'data'   => ['0' => '未读', '1' => '已读未同步', '2' => '已读'],
                'search' => '=',
                //'value'  => self::READ_STATUS_NO_READ,
            ],

            [
                'name'   => 'is_replied',
                'type'   => 'dropDownList',
                'data'   => ['0' => '未回复', '1' => '已回复未同步', '2' => '已回复'],
                'search' => '=',
                'value' => '0',
            ],
            
            [
            'name' => 'tag_id',
            'type' => 'hidden',
            'search' => false,
            'alias' => 't1',
            ],
        ];
    }
    
    public function addition(&$models)
    {
       
        $accounts     = self::getAccountList();
        $mailType     = ['1' => '平台邮件', '2' => '买家邮件'];
        $readType     = ['0' => '未读', '1' => '已读未同步', '2' => '已读'];
        $replyType    = ['0' => '未回复', '1' => '已回复未同步','2' => '已回复'];
        
        foreach ($models as $key => &$model) {
            $model->setAttribute('account_id', isset($accounts[$model->account_id]) ? $accounts[$model->account_id] : '');
            $model->setAttribute('mail_type', $mailType[$model->mail_type]);
            $model->setAttribute('subject', Html::a($model->subject.self::wherethrAttch($model->id,$model->attch), Url::toRoute(['/mails/amazoninbox/view', 'id' => $model->id]), [
                'target' => '_blank',
            ]));
            $model->setAttribute('is_read', $readType[$model->is_read]);
            $model->setAttribute('is_replied', $replyType[$model->is_replied]);

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
    /*
     *  @desc 是否有附件
    */
    public static function wherethrAttch($id,$attchSign)
    {
       // echo "<pre>";
        $attch=AmazonInboxAttachment::find()
            ->where(['=', 'amazon_inbox_id', $id])
            ->all();
       // var_dump($attch);
        !empty($attch)?$attchSign = 1 : $attchSign = 0;
            
        
        $attchType = ['','<i class="fa fa-file-archive-o" style="color:#000; font-size:18px;"></i>'];

        return $attchType[$attchSign];

    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccount()
    {
        return $this->hasOne(Account::className(), ['id' => 'account_id']);
    }

    /**
     *
     * Get amazon account list
     * 
     * @return array
     */
    public static function getAccountListBak()
    {
        $subQuery = (new Query())
            ->from('{{%crm}}.{{%platform}}');

        $rows = (new Query())
            ->select(['id' => 'a.id', 'account_name'])
            ->from('{{%account}} a')
            ->leftJoin(['b' => $subQuery], 'a.platform_code = b.id')
            ->where('b.platform_code =:code',[':code' => 'AMZ'])
            ->all();

        $data = [];

        foreach ($rows as $key => $value) {
            $data[$value['id']] = $value['account_name'];
        }

        return $data;
    }
    
    /**
     * @desc 获取订单
     * @return string
     */
    public function getOrderId()
    {
        return $this->order_id;
    }
    
    /**
     * @desc 获取消息标题
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
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
     * @desc 获取发件人
     * @return number
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @desc 获取发件邮箱
     * @return number
     */
    public function getSenderEmail()
    {
        return $this->sender_email;
    }

    /**
     * @desc 获取消息内容
     */
    public function getContent()
    {
        return $this->body;
    }


    // 邮件判断是否回复
    public static function getReplied($id)
    {
       $inboxDate = self::find()->select('is_replied')->where(['id'=>$id])->asArray()->scalar();

       return $inboxDate;
    }
}
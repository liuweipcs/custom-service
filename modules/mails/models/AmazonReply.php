<?php

namespace app\modules\mails\models;

use app\modules\accounts\models\Account;
use Yii;

use app\modules\mails\models\AmazonReplyAttachment;

/**
 * This is the model class for table "{{%amazon_reply}}".
 *
 * @property integer $id
 * @property integer $inbox_id
 * @property string $reply_content
 * @property string $reply_title
 * @property string $reply_by
 * @property integer $is_draft
 * @property integer $is_delete
 * @property integer $is_send
 * @property string $create_by
 * @property string $create_time
 * @property string $modify_by
 * @property string $modify_time
 */
class AmazonReply extends Reply
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%amazon_reply}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['reply_content', 'reply_title', 'inbox_id'], 'required'],
            [['id', 'inbox_id', 'is_draft', 'is_delete', 'is_send'], 'integer'],
            [['reply_content_en'], 'string'],
            [['create_time', 'modify_time'], 'safe'],
            [['reply_title'], 'string', 'max' => 255],
            [['reply_by', 'create_by', 'modify_by'], 'string', 'max' => 50],
        ];
    }

    public function attributes()
    {
        $attributes = parent::attributes();
        $extAttributes = [
            'attachments',
        ];
        return array_merge($attributes, $extAttributes);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'inbox_id' => '收件ID',
            'reply_content' => '回复内容',
            'reply_title' => '回复标题',
            'reply_by' => '回复人',
            'is_draft' => '是否为草稿',
            'is_delete' => '是否删除',
            'is_send' => '是否发送成功',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
            'reply_content_en' => '回复内容(英文)',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasMany(AmazonReplyAttachment::className(), ['amazon_reply_id' => 'id']);
    }

    /**
     * @desc 添加自动回复
     * @param unknown $data
     * @return boolean|number
     */
    public function saveSelfReply($relyData){
        try{
            $this->reply_content = $relyData['content'];
            $this->inbox_id = 0;
            $this->create_by = $relyData['reply_by'];
            $this->modify_by = $relyData['reply_by'];
            $this->reply_by = $relyData['reply_by'];
            $this->is_draft = 0;
            $this->is_delete = 0;
            $this->is_send = 0;
            $this->create_time = date('Y-m-d H:i:s');
            $this->modify_time = date('Y-m-d H:i:s');
            $this->reply_title = $relyData['subject'];
            $flag = $this->save();
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }

        if (!$flag)
            return false;
        return $this;
    }

    public function getSendParams($type=1,$order_info = null,$account_id)
    {
        $sendParams = '';
        try
        {
            $accountInfo = Account::find()->where(['id'=>$account_id])->one();
            $sendParams = [
                'sender_email' => $accountInfo->email,
                'receive_email' => $order_info->info->email,
                'order_id' => $order_info->info->platform_order_id,
                'attachments' => array(),
            ];

            $sendParams = \yii\helpers\Json::encode($sendParams);
        }
        catch (\Exception $e)
        {
            //echo $e->getMessage();exit;
        }
        return $sendParams;
    }
}

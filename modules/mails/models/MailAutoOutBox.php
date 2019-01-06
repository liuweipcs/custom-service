<?php
/**
 * @desc 发件箱模型
 *
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

class MailAutoOutBox extends MailsModel
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
        return '{{%mail_auto_outbox}}';
    }


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
}

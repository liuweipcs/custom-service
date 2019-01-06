<?php

namespace app\modules\mails\models;

use app\modules\mails\components\MessageSenderAbstract;

class AmazonMailOutbox extends MailsModel
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
        return '{{%amazon_mail_outbox}}';
    }

    /**
     *  @desc 获取待发送的消息列表
     * @param null $platformCode
     * @param int $limit
     * @param null $maxfailureTime
     * @param string $sortOrder
     * @param null $modNumber
     * @param null $modRemain
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getWaittingSendList($platformCode = null, $limit = 1000, $maxfailureTime = null,
                                               $sortOrder = 'ASC', $modNumber = null, $modRemain = null)
    {
        $query = self::find();
        $query->where(['in','send_status',[0,-1]]);
        //$query->andWhere(['in','account_id',[116,153]]);
        if ($platformCode != null)
            $query->andWhere('platform_code = :platform_code', ['platform_code' => $platformCode]);
        if ($maxfailureTime !== null)
            $query->andWhere('send_failure_times <= ' . (int)$maxfailureTime);
        if ($sortOrder == 'ASC')
            $query->orderBy(['id' => SORT_ASC]);
        else
            $query->orderBy(['id' => SORT_DESC]);
        //亚马逊自动发信测试测试
//        if ($platformCode == 'AMAZON') {
//            $query->andWhere("(create_by <> 'system') or (account_id not in (642) and create_by = 'system')");
//        }
        //按取模后的余数分组
        if (!is_null($modNumber) && !is_null($modRemain)) {
            $modNumber = (int)$modNumber;
            $modRemain = (int)$modRemain;
            if ($modNumber > 0 && $modRemain >= 0 && $modRemain < $modNumber) {
                $query->andWhere("id%" . $modNumber . '=' . $modRemain);
            }
        }
        
        $query->limit($limit);
        $xx = $query->all();
        //echo $query->createCommand()->rawSql;die;
        return $xx;
    }

    /**
     * 发送消息后的回调方法
     * @param $event
     * @return bool
     * @throws \yii\db\Exception
     */
    public static function afterSend($event)
    {
        try {
            $messageSender         = $event->sender;
            $outBox                = $messageSender->getMessageEntity();
            $outBox->response_time = date('Y-m-d H:i:s');
            if (!$messageSender->sendFlag) {
                //发送失败
                $data                        = [
                    'send_status'         => self::SEND_STATUS_FAILED,
                    'send_failure_reason' => $messageSender->getException(),
                    'send_failure_times'  => $outBox->send_failure_times + 1
                ];
                $outBox->send_status         = self::SEND_STATUS_FAILED;
                $outBox->send_failure_reason = $messageSender->getException();
                $outBox->send_failure_times  = $outBox->send_failure_times + 1;
                $event->message              = $messageSender->getException();
                $event->isValid              = false;
                $outBox->getDb()->createCommand()
                    ->update($outBox->tableName(), $data, ['id' => $outBox->id])
                    ->execute();
                return false;
            } else {
                //发送成功
                $outBox->send_status = self::SEND_STATUS_SUCCESS;
                $flag                = $outBox->save();
                if (!$flag)
                    throw new \Exception('Save Message Failed');
            }
        } catch (\Exception $e) {
            $event->message = $e->getMessage();
            $event->isValid = false;
            return false;
        }

        $event->isValid = true;
    }

    /**
     * 发送消息前的回调方法
     * @param $event
     */
    public static function beforeSend($event)
    {
        $messageSender = $event->sender;
        $outBox        = $messageSender->getMessageEntity();
        //再次查询消息，避免重复发送
        $outBox = self::findOne($outBox->id);
        if (empty($outBox)) {
            $event->message = 'Message Not Found';
            $event->isValid = false;
            return;
        }
        //检查是否可以发送
        if ($outBox->send_status != AmazonMailOutbox::SEND_STATUS_WAITTING
            && $outBox->send_status != AmazonMailOutbox::SEND_STATUS_FAILED
            && !($outBox->send_status == AmazonMailOutbox::SEND_STATUS_SENDING
                && (time() - strtotime($outBox->create_time) > '300'))) {
            $event->message = 'Message Send Status is Not Send Watting';
            $event->isValid = false;
            return;
        }
        $outBox->send_status         = self::SEND_STATUS_SENDING;
        $outBox->send_time           = date('Y-m-d H:i:s');
        $outBox->send_failure_reason = '';
        $outBox->response_time       = '';
        $flag                        = $outBox->save();
        if (!$flag) {
            $event->message = 'Save Message Failed';
            $event->isValid = false;
            return;
        }
        $event->isValid = true;
    }

    /**
     * @desc 发送消息
     * @return bool
     * @throws \Exception
     */
    public function sendMessage()
    {
        $platformCode  = $this->platform_code;
        $messageSender = MessageSenderAbstract::getSender($platformCode);
        if (!$messageSender)
            return false;
        $messageSender->on(MessageSenderAbstract::EVENT_BEFORE_SEND, ['\app\modules\mails\models\AmazonMailOutbox',
            'beforeSend']);
        $messageSender->on(MessageSenderAbstract::EVENT_AFTER_SEND, ['\app\modules\mails\models\AmazonMailOutbox',
            'afterSend']);
        $flag = $messageSender->setMessageEntity($this)
            ->sendMessage();
        if (!$flag)
            return false;
        return true;
    }

}
<?php
/**
 * @desc 消息发送事件
 * @author Fun
 */
namespace app\modules\mails\components;
use yii\base\Event;
class MessageSenderEvent extends Event
{
    /**
     * @desc 是否有效标示
     * @var unknown
     */
    public $isValid = true;
    
    /**
     * @desc 事件消息
     * @var unknown
     */
    public $message = null;
}
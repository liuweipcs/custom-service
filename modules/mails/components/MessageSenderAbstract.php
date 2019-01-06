<?php
/**
 * @desc 消息发送者抽象类
 * @author Fun
 */
namespace app\modules\mails\components;
use yii\base\Component;
use app\modules\mails\components\MessageSenderEvent;
use app\modules\accounts\models\Platform;

abstract class MessageSenderAbstract extends Component
{

    /**
     * @desc 发送者名称
     * @var unknown
     */
    public $senderName = null;
    
    /**
     * @desc 发送者类型（api发送，mail server 发送, 短信发送）
     * @var unknown
     */
    public $type = null;
    
    /**
     * @desc 消息实体
     * @var unknown
     */
    public $messageEntity = null;
    
    /**
     * @desc 日志对象
     * @var unknown
     */
    public $logger = null;
    
    /**
     * @desc 发送响应内容
     * @var unknown
     */
    public $response = null;
    
    /**
     * @desc 异常消息
     * @var unknown
     */
    public $exception = null;
    
    /**
     * @desc 发送是否成功标记
     * @var unknown
     */
    public $sendFlag = null;
    
    const EVENT_AFTER_SEND = 'afterSend';
    const EVENT_BEFORE_SEND = 'beforeSend';
    
    /**
     * @desc 运行发送消息
     */
    abstract function runSendMessage();
    
    /**
     * @desc 发送消息
     */
    public function sendMessage()
    {
        if (!$this->messageEntity instanceof \app\modules\mails\models\MailOutbox&&
            !$this->messageEntity instanceof \app\modules\mails\models\AmazonMailOutbox)
            throw new \Exception('Message Entity Must Instance of \app\modules\mails\models\MailOutbox');
        //触发EVENT_BEFORE_SEND
        if (!$this->beforeSend())
            return false;
        static::runSendMessage();
        //触发EVENT_AFTER_SEND
        return $this->afterSend();        
    }

    
    /**
     * @desc 设置消息实体
     * @param unknown $message
     * @return \app\modules\mails\components\MessageSenderAbstract
     */
    public function setMessageEntity($message)
    {
        $this->messageEntity = $message;
        return $this;
    }
    
    /**
     * @desc 消息发送后的事件
     */
    public function afterSend()
    {
        $event = new MessageSenderEvent();
        $event->sender = $this;
        $this->trigger(self::EVENT_AFTER_SEND, $event);
        if (!$event->isValid)
        {
            $this->exception = $event->message;
            return false;
        }
        return true;
    }
    
    /**
     * @desc 消息发送前的事件
     */    
    public function beforeSend()
    {
        $event = new MessageSenderEvent();
        $event->sender = $this;
        $this->trigger(self::EVENT_BEFORE_SEND, $event);
        $this->exception = $event->message;
        if (!$event->isValid)
        {
            $this->exception = $event->message;
            return false;
        }
        return true;
    }
    
    /**
     * @desc 获取发送者实例
     * @param unknown $platformCode
     * @return \app\modules\mails\components\AliexpressMessageSender|boolean
     */
    public static function getSender($platformCode)
    {
        switch ($platformCode)
        {
            case Platform::PLATFORM_CODE_ALI:
                return new AliexpressMessageSender();
            case Platform::PLATFORM_CODE_AMAZON:
                return new AmazonMessageSender();
            case Platform::PLATFORM_CODE_EB:
                return new EbayMessageSender();
            case Platform::PLATFORM_CODE_CDISCOUNT:
                return new CdiscountMessageSender();
            case Platform::PLATFORM_CODE_WALMART:
                return new WalmartMessageSender();
            default:
               return false;
        }
    }
    
    /**
     * @desc 获取消息实体
     * @return \app\modules\mails\components\unknown
     */
    public function getMessageEntity()
    {
        return $this->messageEntity;
    }
    
    /**
     * @desc 获取异常信息
     * @return \app\modules\mails\components\unknown
     */
    public function getException()
    {
        return $this->exception;
    }
}
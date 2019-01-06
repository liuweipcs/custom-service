<?php
namespace app\modules\mails\models;
use app\common\VHelper;
use app\modules\accounts\models\Platform;

class Reply extends MailsModel
{
    const SYNC_STATUS_NO = 0;           //未同步
    const SYNC_STATUS_YES = 1;          //已同步
    
    /**
     * @desc 获取平台对应的回复模型
     * @param unknown $platformCode
     * @return \app\modules\mails\models\AliexpressReply|NULL
     */
    public static function getReplyModel($platformCode)
    {
        switch ($platformCode)
        {
            case Platform::PLATFORM_CODE_ALI:
                return new AliexpressReply();
            case Platform::PLATFORM_CODE_AMAZON:
                return new AmazonReply();
            case Platform::PLATFORM_CODE_EB:
                return new EbayReply();
            case Platform::PLATFORM_CODE_WISH:
                return new WishReply();
            case Platform::PLATFORM_CODE_CDISCOUNT:
                return new CdiscountInboxReply();
            case Platform::PLATFORM_CODE_WALMART:
                return new WalmartReply();
            default:
                return null;
        }
    }
    
    /**
     * @desc 设置回复已同步
     * @param unknown $replyId
     * @return \yii\db\int
     */
    public function setHadSync($replyId)
    {
        return self::updateAll(['is_send' => self::SYNC_STATUS_YES], 'id = :id', ['id' => $replyId]);
    }
    
    /**
     * @desc 将回复添加到发件箱
     * @return boolean
     */
    public function sendToOutBox($inbox)
    {
        $modelOutBox = new MailOutbox();
        $attributes = [
            'platform_code' => $this->getPlatformCode(),
            'reply_id' => $this->id,
            'inbox_id' => $this->inbox_id,
            'account_id' => $this->account_id,
            'content' => $this->getReplyContent(),
            'subject' => $this->getSubject(),
            'send_params' => $this->getSendParams($inbox),
            'send_status' => MailOutbox::SEND_STATUS_WAITTING,
        ];
        $modelOutBox->setAttributes($attributes);
        if (!$modelOutBox->save())
            return false;
        return true;
    }

    /**
     * @desc 将回复添加到发件箱
     * @return boolean
     */
    public function sendToOutBoxWish($inbox)
    {
        $modelOutBox = new MailOutbox();
        $attributes = [
            'platform_code' => $this->getPlatformCode(),
            'reply_id' => $this->id,
            'inbox_id' => $this->inbox_id,
            'account_id' => $inbox->account_id,
            'content' => $this->getReplyContent(),
            'subject' => $this->getSubject(),
            'send_params' => $this->getSendParams($inbox),
            'send_status' => MailOutbox::SEND_STATUS_WAITTING,
        ];
        $modelOutBox->setAttributes($attributes);
        if (!$modelOutBox->save())
            return false;
        return true;
    }
    
    public static function addReply($platformCode, $inbox, $relyData)
    {
        $replyModel = self::getReplyModel($platformCode);
        if (!$replyModel)
            return false;
        return $replyModel->saveReply($inbox, $relyData);
    }

    public static function addSelfReply($platformCode, $relyData)
    {
        $replyModel = self::getReplyModel($platformCode);
        if (!$replyModel)
            return false;
        return $replyModel->saveSelfReply( $relyData);
    }
}
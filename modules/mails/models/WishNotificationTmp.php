<?php

namespace app\modules\mails\models;

use app\components\MongodbModel;

class WishNotificationTmp extends MongodbModel
{
    public $exceptionMessage = '';

    public static function collectionName()
    {
        return DB_TABLE_PREFIX . 'wish_notification_tmp';
    }

    public function attributes()
    {
        return [
            '_id', 'account_id', 'noti_id', 'title', 'message', 'perma_link',
        ];
    }

    /**
     * 获取待处理的列表
     */
    public static function getWaitingProcessList($limit = 100)
    {
        return self::find()->limit($limit)->all();
    }

    /**
     * 将mongodb中保存的通知信息转存入mysql中
     */
    public function processTmpNoti($tmpNoti)
    {
        $dbTransaction = WishNotifaction::getDb()->beginTransaction();
        try {
            //账号ID
            $accountId = $tmpNoti->account_id;
            //通知ID
            $notiId = $tmpNoti->noti_id;
            //通知标题
            $title = $tmpNoti->title;
            //通知消息
            $message = $tmpNoti->message;
            //线上链接
            $permaLink = $tmpNoti->perma_link;

            $noti = WishNotifaction::findOne(['account_id' => $accountId, 'noti_id' => $notiId]);
            if (empty($noti)) {
                $noti = new WishNotifaction();
                $noti->create_by = 'system';
                $noti->create_time = date('Y-m-d H:i:s');
            }
            $noti->account_id = $accountId;
            $noti->noti_id = $notiId;
            $noti->title = $title;
            $noti->message = $message;
            $noti->perma_link = $permaLink;
            $noti->is_view = 0;
            $noti->modify_by = 'system';
            $noti->modify_time = date('Y-m-d H:i:s');
            $noti->save(false);

            //提交事务
            $dbTransaction->commit();
            //删除处理成功的数据
            $tmpNoti->delete();
            return true;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
            $dbTransaction->rollBack();
            $this->exceptionMessage = $e->getMessage();
            return false;
        }
    }
}
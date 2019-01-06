<?php

namespace wish\models;

use app\modules\mails\models\WishNotificationTmp;
use wish\components\MerchantWishApi;

class WishNoti
{
    /**
     * 拉取通知
     */
    public function getAllNotification($accountId)
    {
        try {
            if (empty($accountId)) {
                return false;
            }

            $wish = new MerchantWishApi($accountId);
            if (empty($wish)) {
                return false;
            }

            //获取未查看通知的总数
            $count = $wish->getUnviewedCount();
            if (empty($count)) {
                return false;
            }

            $pageSize = 100;
            $step = ceil($count / $pageSize);
            for ($pageCur = 1; $pageCur <= $step; $pageCur++) {
                $start = ($pageCur - 1) * $pageSize;

                $notis = $wish->fetchUnviewed($start, $pageSize);
                if (empty($notis)) {
                    continue;
                }

                foreach ($notis as $noti) {
                    $noti = !empty($noti['GetNotiResponse']) ? $noti['GetNotiResponse'] : [];
                    if (!empty($noti)) {
                        $tmp = WishNotificationTmp::findOne(['noti_id' => $noti['id']]);
                        if (empty($tmp)) {
                            $tmp = new WishNotificationTmp();
                        }

                        $tmp->account_id = $accountId;
                        $tmp->noti_id = $noti['id'];
                        $tmp->title = $noti['title'];
                        $tmp->message = $noti['message'];
                        $tmp->perma_link = $noti['perma_link'];
                        $tmp->save(false);
                    }
                }
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        }
    }
}
<?php

namespace app\common;

use Yii;
use yii\db\Query;

class MHelper
{

    /**
     * 获取用户id和姓名数据
     */
    public static function getUserPairs()
    {
        static $data = array();
        if (empty($data)) {
            $data = Yii::$app->cache->get('upl');
            if (empty($data)) {
                $data = (new Query())
                    ->select('id, user_full_name')
                    ->from('{{%user}}')
                    ->createCommand(YII::$app->db_system)
                    ->queryAll();

                $tmp = [];
                if (!empty($data)) {
                    foreach ($data as $item) {
                        $tmp[$item['id']] = $item['user_full_name'];
                    }
                    $data = $tmp;
                }
                Yii::$app->cache->set('upl', $data, 3600 * 6);
            }
        }
        return $data;
    }

    /**
     * 获取用户名
     */
    public static function getUsername($id)
    {
        if (empty($id)) {
            return '';
        }
        $data = self::getUserPairs();
        return array_key_exists($id, $data) ? $data[$id] : '';
    }

}
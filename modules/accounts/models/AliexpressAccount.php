<?php
/**
 * @desc account model
 * @author Fun
 */

namespace app\modules\accounts\models;

use app\modules\accounts\models\ErpAccountModel;

class AliexpressAccount extends ErpAccountModel
{
    public static function tableName()
    {
        return '{{%aliexpress_account_qimen}}';
    }

    /**
     * 返回速卖通账号信息
     */
    public static function getAccounts($accountId = 0)
    {
        $query = self::find()->andWhere(['and', ['<>', 'access_token', ''], ['<>', 'refresh_token', '']]);

        if (!empty($accountId)) {

            if (is_string($accountId)) {
                $query->andWhere(['id' => $accountId]);
            } else if (is_array($accountId)) {
                $query->andWhere(['in', 'id', $accountId]);
            }
        }

        $result = $query->asArray()->all();

        if (!empty($result)) {
            $tmp = [];
            foreach ($result as $item) {
                $tmp[$item['id']] = $item;
            }
            $result = $tmp;
        }

        return $result;
    }
}
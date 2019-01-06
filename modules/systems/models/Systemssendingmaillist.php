<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/10
 * Time: 10:55
 */

namespace app\modules\systems\models;
use Yii;
use app\modules\accounts\models\Platform;
use yii\helpers\Html;


class Systemssendingmaillist extends SystemsModel
{
    public static function tableName()
    {
        return '{{%systems_sending_mail_list}}';
    }
    public static function seachList($params=array()){
        //[  => [account_id] => [] => [mail_theme] => [begin_date] => [end_date] => )
        $query = self::find();
        $query->select(['t.*']);
        $query->from(self::tableName() . ' t');
        if (isset($params['platform_code']) && !empty($params['platform_code'])) {
            $query->andWhere(['t.platformcode' => $params['platform_code']]);
        }
        if (isset($params['rule_id']) && !empty($params['rule_id'])) {
            $query->andWhere(['t.rule_id' => $params['rule_id']]);
        }
        if (isset($params['account_id']) && !empty($params['account_id'])) {
            $query->andWhere(['t.account_id' => $params['account_id']]);
        }
        if (isset($params['buyer_id']) && !empty($params['buyer_id'])) {
            $query->andWhere(['t.buyer_id' => $params['buyer_id']]);
        }
        if (isset($params['mail_theme']) && !empty($params['mail_theme'])) {
            $query->andWhere(['like','t.mail_theme', $params['mail_theme']]);
        }

        if (isset($params['begin_date']) && !empty($params['begin_date'])) {
            $start_date = $params['begin_date'];
        }else{
            $start_date = null;
        }
        if (isset($params['end_date']) && !empty($params['end_date'])) {
            $end_date = $params['end_date'];
        }else{
            $end_date = null;
        }
        //发货时间
        if (!empty($start_date) && !empty($end_date)) {
            $query->andWhere(['between', 't.sending_time', $start_date, $end_date]);

        } else if (!empty($start_date)) {
            $query->andWhere(['>=', 't.sending_time', $start_date]);

        } else if (!empty($end_date)) {
            $query->andWhere(['<=', 't.sending_time', $end_date]);

        }

        $count = $query->count();
        $pageCur = $params['pageCur'] ? $params['pageCur']  : 1;
        $pageSize = $params['pageSize'] ? $params['pageSize'] : \Yii::$app->params['defaultPageSize'];
        $offset = ($pageCur - 1) * $pageSize;
        $data_list = $query->offset($offset)->limit($pageSize)->orderBy(['t.id' => SORT_DESC])->asArray()->all();

        if(!empty($data_list)){
            return [
                'count' => $count,
                'data_list' => $data_list,
            ];
        }
        return null;

    }
}


<?php

namespace app\modules\aftersales\models;

use app\components\Model;
use app\modules\orders\models\OrderKefu;
use app\modules\accounts\models\UserAccount;

class Domesticreturngoods extends Model
{

    public static function getDb()
    {
        return \Yii::$app->db;
    }

    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%domestic_returngoods}}';
    }

    /**
     * 查询退件跟进订单
     * @param $parameter
     * @return array|null
     */
    public static function getReceiptList($parameter)
    {
        $platform_code    = $parameter['platform_code'];
        $trackno          = $parameter['trackno'];
        $order_id         = $parameter['order_id'];
        $buyer_id         = $parameter['buyer_id'];
        $return_type      = $parameter['return_type'];
        $return_typesmall = $parameter['return_typesmall'];
        $state            = $parameter['state'];
        $start_date       = $parameter['start_date'];
        $end_date         = $parameter['end_date'];
        $handle_type      = $parameter['handle_type'];
        $return_number    = $parameter['return_number'];
        $source           = $parameter['source'];
        $account          = $parameter['account_id'];
        $pageCur          = $parameter['pageCur'];
        $pageSize         = $parameter['pageSize'];

        if ($platform_code == 'EB') {
            $model = OrderKefu::model('order_ebay');
        } elseif ($platform_code == 'ALI') {
            $model = OrderKefu::model('order_aliexpress');
        } elseif ($platform_code == 'AMAZON') {
            $model = OrderKefu::model('order_amazon');
        } elseif ($platform_code == 'WISH') {
            $model = OrderKefu::model('order_wish');
        } else {
            $model = OrderKefu::model('order_other');
        }

        $query      = self::find();
        $query_copy = self::find();
        $query->select(['t.*'])->alias('t');
        $query_copy->select(['t.*'])->alias('t');

        if (!empty($account)) {
            $query->andWhere(['t.account_id' => $account]);
            $query_copy->andWhere(['t.account_id' => $account]);
        }

        $platformList  = UserAccount::getLoginUserPlatformAccounts();
        $platformarray = array();
        foreach ($platformList as $key => $platform) {
            $platformarray[] = $key;
        }
        if (!empty($platform_code) && in_array($platform_code, $platformarray)) {
            $query->andWhere(['t.platform_code' => $platform_code]);
            $query_copy->andWhere(['t.platform_code' => $platform_code]);

            $accountIds = UserAccount::getCurrentUserPlatformAccountIds($platform_code);
            $query->andWhere(['in', 't.account_id', $accountIds]);
            $query_copy->andWhere(['in', 't.account_id', $accountIds]);

        }
        if (!empty($trackno)) {

            $query->andWhere(['t.trackno' => $trackno]);
            $query_copy->andWhere(['t.trackno' => $trackno]);

        }
        if (!empty($order_id)) {

            $query->andWhere(['t.order_id' => $order_id]);
            $query_copy->andWhere(['t.order_id' => $order_id]);

        }
        if (!empty($buyer_id)) {

            $query->andWhere(['t.buyer_id' => $buyer_id]);
            $query_copy->andWhere(['t.buyer_id' => $buyer_id]);

        }
        if (!empty($return_type)) {

            $query->andWhere(['t.return_type' => $return_type]);
            $query_copy->andWhere(['t.return_type' => $return_type]);

        }
        if (!empty($return_typesmall)) {

            $query->andWhere(['t.return_typesmall' => $return_typesmall]);
            $query_copy->andWhere(['t.return_typesmall' => $return_typesmall]);

        }
        if (!empty($state)) {

            $query->andWhere(['t.state' => $state]);
            $query_copy->andWhere(['t.state' => $state]);

        }

        if (!empty($handle_type)) {

            $query->andWhere(['t.handle_type' => $handle_type]);
            $query_copy->andWhere(['t.handle_type' => $handle_type]);

        }
        if (!empty($return_number)) {

            $query->andWhere(['t.return_number' => $return_number]);
            $query_copy->andWhere(['t.return_number' => $return_number]);

        }
        if (!empty($source)) {

            $query->andWhere(['t.source' => $source]);
            $query_copy->andWhere(['t.source' => $source]);

        }
        //发货时间
        if (!empty($start_date) && !empty($end_date)) {
            $query->andWhere(['between', 't.handle_time', $start_date, $end_date]);
            $query_copy->andWhere(['between', 't.handle_time', $start_date, $end_date]);


        } else if (!empty($start_date)) {
            $query->andWhere(['>=', 't.handle_time', $start_date]);
            $query_copy->andWhere(['>=', 't.handle_time', $start_date]);


        } else if (!empty($end_date)) {
            $query->andWhere(['<=', 't.handle_time', $end_date]);
            $query_copy->andWhere(['<=', 't.handle_time', $end_date]);

        }
        $count          = $query->count();

        $count_copy     = $query_copy->count();
        $pageCur        = $pageCur ? $pageCur : 1;
        $pageSize       = $pageSize ? $pageSize : \Yii::$app->params['defaultPageSize'];
        $offset         = ($pageCur - 1) * $pageSize;
        $data_list      = $query->offset($offset)->limit($pageSize)->orderBy(['t.id' => SORT_DESC])->asArray()->all();
        $data_list_copy = $query_copy->offset($offset)->limit($pageSize)->orderBy(['t.id' => SORT_DESC])->asArray()->all();
        if (!empty($data_list)) {
            foreach ($data_list as $key => $datalist) {
                $orders = $model->where(['order_id' => $datalist['order_id']])->one();
                if (!empty($orders)) {
                    $data_list[$key]['shipped_date']      = $orders->shipped_date;
                    $data_list[$key]['platform_order_id'] = $orders->platform_order_id;
                    $data_list[$key]['email'] = $orders->email;
                    $data_list[$key]['account_id']        = $orders->account_id;//存erp的账号id
                    $data_list[$key]['paytime']           = $orders->paytime;
                    $data_list[$key]['real_ship_code']    = $orders->real_ship_code;
                    $data_list[$key]['warehouse_id']      = $orders->warehouse_id;
                    $data_list[$key]['total_price']       = $orders->total_price;
                    $data_list[$key]['currency']          = $orders->currency;
                    $data_list[$key]['complete_status']   = $orders->complete_status;
                }
            }
            return [
                'count'     => $count,
                'data_list' => $data_list,
            ];

        } elseif (!empty($data_list_copy)) {
            foreach ($data_list_copy as $key => $val) {
                $orders = $model->where(['order_id' => $val['order_id']])->one();
                if (!empty($orders)) {
                    $val[$key]['shipped_date']      = $orders->shipped_date;
                    $val[$key]['platform_order_id'] = $orders->platform_order_id;
                    $val[$key]['account_id']        = $orders->account_id;//存erp的账号id
                    $val[$key]['paytime']           = $orders->paytime;
                    $val[$key]['real_ship_code']    = $orders->real_ship_code;
                    $val[$key]['warehouse_id']      = $orders->warehouse_id;
                    $val[$key]['total_price']       = $orders->total_price;
                    $val[$key]['currency']          = $orders->currency;
                    $val[$key]['complete_status']   = $orders->complete_status;
                }
            }
            return [
                'count'     => $count_copy,
                'data_list' => $data_list_copy,
            ];
        } else {
            return null;
        }

    }

    /**
     * 下载退件跟进列表
     * @param $platform_code
     * @param $trackno
     * @param $order_id
     * @param $buyer_id
     * @param $return_type
     * @param $return_typesmall
     * @param $state
     * @param $start_date
     * @param $end_date
     * @param $handle_type
     * @param $return_number
     * @param $source
     * @param int $pageCur
     * @param int $pageSize
     * @return array|null
     */
    public static function getDownloadList($platform_code, $account_id,$trackno, $order_id, $buyer_id, $return_type, $return_typesmall, $state, $start_date, $end_date, $handle_type, $return_number, $source, $pageCur = 0, $pageSize = 0)
    {
        $query = self::find();
        $query->select(['t.*']);
        $query->from(self::tableName() . ' t');

        if (isset($platform_code) && !empty($platform_code)) {

            $query->andWhere(['t.platform_code' => $platform_code]);
        }
        
        if(isset($account_id) && !empty($account_id)){
            $query->andWhere(['t.account_id' => $account_id]);
        }
        if (isset($trackno) && !empty($trackno)) {

            $query->andWhere(['t.trackno' => $trackno]);
        }
        if (isset($order_id) && !empty($order_id)) {

            $query->andWhere(['t.order_id' => $order_id]);
        }
        if (isset($buyer_id) && !empty($buyer_id)) {

            $query->andWhere(['t.buyer_id' => $buyer_id]);
        }
        if (isset($return_type) && !empty($return_type)) {

            $query->andWhere(['t.return_type' => $return_type]);
        }
        if (isset($return_typesmall) && !empty($return_typesmall)) {

            $query->andWhere(['t.return_typesmall' => $return_typesmall]);
        }
        if (isset($state) && !empty($state)) {

            $query->andWhere(['t.state' => $state]);
        }
        if (isset($handle_type) && !empty($handle_type)) {

            $query->andWhere(['t.handle_type' => $handle_type]);
        }
        if (isset($return_number) && !empty($return_number)) {

            $query->andWhere(['t.return_number' => $return_number]);
        }
        if (isset($source) && !empty($source)) {

            $query->andWhere(['t.source' => $source]);
        }
        //发货时间
        if ($start_date && $end_date) {
            $query->andWhere(['between', 't.handle_time', $start_date, $end_date]);

        } else if (!empty($start_date)) {
            $query->andWhere(['>=', 't.handle_time', $start_date]);

        } else if (!empty($end_date)) {
            $query->andWhere(['<=', 't.handle_time', $end_date]);

        }

        $count     = $query->count();
        $pageCur   = $pageCur ? $pageCur : 1;
        $pageSize  = $pageSize ? $pageSize : \Yii::$app->params['defaultPageSize'];
        $offset    = ($pageCur - 1) * $pageSize;
        $data_list = $query->orderBy(['t.id' => SORT_DESC])->asArray()->all();

        if (!empty($data_list)) {
            return [
                'count'     => $count,
                'data_list' => $data_list,
            ];
        }
        return null;


    }

    /**
     * 把更新的记录保存到退件单列表
     */
    public static function saveRecord($id, $record, $login_name, $handle_type = 0)
    {
        $article  = self::findOne(['id' => $id, 'state' => 1]);
        $datetime = date('Y-m-d H:i:s');
        if ($article !== null) {
            $article->state       = 3;
            $article->handle_user = $login_name;
            $article->handle_time = $datetime;
            $article->handle_type = $handle_type;
            $article->record      = $record;
            if ($article->save())
                return true;
        }
        return false;
    }

    /**
     * @author alpha
     * @desc 国内退件
     * @param $order_id
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function findByOrderId($order_id)
    {
        $query = self::find();
        $model = $query->select("*")->andWhere(['order_id' => $order_id])->asArray()->one();
        //echo $query->createCommand()->getRawSql();
        return $model;
    }
}
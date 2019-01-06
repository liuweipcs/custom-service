<?php

namespace app\modules\aftersales\models;

use app\components\Model;


class ComplaintdetailModel extends Model {

    public static function getDb() {
        return \Yii::$app->db;
    }

    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName() {
        return '{{%warehouse_customer_complaint_detail}}';
    }

    public static function getcomplaindetail($complaint_order){
        
        $complaindetail=self::find()->where(['complaint_order_id'=>$complaint_order])->asArray()->all();
         foreach($complaindetail as $key=>$v){
          
             $complaindetail[$key]['img_url']= explode(',', $v['img_url']);
         }
         
         
         
        return $complaindetail;
        
    }

}

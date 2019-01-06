<?php

namespace app\modules\aftersales\models;
use app\components\Model;
use yii\helpers\Url;
class WarehouseprocessingModel extends Model {

    public static function getDb() {
        return \Yii::$app->db;
    }

    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName() {
        return '{{%warehouse_processing}}';
    }

   
    /**
     * 客诉仓库处理表
     * **/
    public static function getWarehouseprocessing($complaint_order){    
     $processing=self::find()->where(['complaint_order_id'=>$complaint_order])->orderBy(['add_time'=>SORT_DESC])->all();    
      return $processing;  
    }
  
      /*
     * 根据审核状态判断
     */

    public static function getstatus($status,$complaint_order='') {
        switch ($status) {
            case 0:
                return "待仓库返回处理方式";
                break;
            case 1:
                return  '<a _width="30%" _height="60%" class="edit-button"
                               href=' .Url::toRoute(["/aftersales/complaint/getconfirm", "complaint_order"=> $complaint_order]).'>待确认</a>';
                break;
            case 2:
                return "已重新推送仓库处理";
                break;
            case 3:
                return "已完成";
                break;
             case -1:
                return "推送失败<br/>系统繁忙请稍后再试！！！";
                break;
        }
    }
    
    
    
   }



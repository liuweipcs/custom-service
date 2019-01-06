<?php

namespace app\modules\systems\models;

class GbcBlacklist extends SystemsModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%gbc_blacklist}}';
    }
    
    
    
    /***
     * 
     * 获取操作日志表数据
     * **/
   public static function getlog($data){
        $log= self::find()->where(['platform_code'=>$data['platform_code']])
               ->andWhere(['type'=>$data['type']])
               ->andWhere(['account_type'=>$data['account_type']])
               ->asArray()->all(); 
        return $log;   
   }
}




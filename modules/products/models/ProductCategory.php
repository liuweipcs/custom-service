<?php
/**
 * Created by PhpStorm.
 * User: zhangchu
 * Date: 2018/12/01 
 */

namespace app\modules\products\models;


class ProductCategory extends ProductsModel
{
     public static function tableName()
     {
         return '{{%product_category}}'; // TODO: Change the autogenerated stub
     }

     /**
      * 获取所有主类别
      * @return mixed
      */
     public static function getCategory(){
         $category=self::find()->select('id,category_cn_name')->where(["category_parent_id" => 0, "category_status" => 1])->asArray()->all();
         foreach ($category as $k=>$v){
             $data[$v['id']]=$v['category_cn_name'];
         }
         return $data;
     }


}

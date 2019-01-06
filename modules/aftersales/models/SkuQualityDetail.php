<?php

namespace app\modules\aftersales\models;

use yii\helpers\Url;
use Yii;

class SkuQualityDetail extends AfterSalesModel {

	/**
	 * @desc 设置表名
	 * @return string
	 */
	public static function tableName() {
	    return '{{%sku_qlty_detail}}';
	}

	/**
	 * 获取属性标签
	 * @return array
	 */
	public function attributeLabels() {
	    return [
	        'id' => 'ID',
	        'sku' => 'sku',
	        'platform_code' => '产品类别',
	        'order_id' => '订单号',
	        'type' => '售后类型',
	        'shipped_date' => '发货日期',
	        'loss_rmb' => '实际损失人民币',
	        'remark' => '备注',
	        'finish_time' => '完成时间',
	        'after_sale_id'=>'售后单号',
	        'reason_id' => '问题原因',
	    ];
	}

	/**
	 * @desc 查询
	 */
	public function searchList($params = []) {
	    $sort = new \yii\data\Sort();
	    $sort->defaultOrder = array(
	        'shipped_date' => SORT_DESC
	    );
	    $query = self::find();
	    $query->from(self::tableName() . ' as b');
	    $query->leftJoin(SkuQualityAnalysis::tablename(). ' a','a.sku = b.sku');

	    if (!empty($params['sku'])) {
	    	$query->where(['b.sku'=>$sku]);
	    	unset($params['sku']);
	    }
	    
	    if (!empty($params['order_id'])) {
	    	$query->andWhere(['b.order_id'=>$params['order_id']]);
	    	unset($params['order_id']);
	    }
	    $dataProvider = parent::search($query, $sort, $params);
// echo $query->createCommand()->getRawSql();exit;
	    $models = $dataProvider->getModels();
	    //$this->addition($models);
	    $dataProvider->setModels($models);
	    return $dataProvider;
	}


	/**
	 * @desc 搜索过滤项
	 * @return multitype:multitype:string multitype:  multitype:string multitype:string
	 */
	public function filterOptions() {
		return [
		    [
		        'name' => 'order_id',
		        'type' => 'text',
		        'search' => '=',
		    ],
		];    
	}	

	
}

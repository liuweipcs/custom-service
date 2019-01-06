<?php

namespace app\modules\aftersales\controllers;

use app\components\Controller;
use app\modules\accounts\models\Account;
use app\modules\orders\models\Order;
use app\modules\orders\models\OrderKefu;
use yii\helpers\Json;
use app\common\VHelper;
use app\common\MHelper;
use app\modules\systems\models\Country;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\aftersales\models\OrderReturnDetail;
use app\modules\aftersales\models\AfterSalesProduct;
use app\modules\aftersales\models\SkuQualityAnalysis;
use app\modules\aftersales\models\SkuQualityDetail;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\BasicConfig;
use app\modules\users\models\UserRole;
use app\modules\products\models\Product;
use Yii;
use yii\helpers\Url;

class SkuqualityanalysisController extends Controller {

	public function actionList() {
	    $params = \Yii::$app->request->getBodyParams();
	    $model = new SkuQualityAnalysis();
	    $dataProvider = $model->searchList($params);
	    return $this->renderList('list', [
	                'model' => $model,
	                'dataProvider' => $dataProvider,
	    ]);
	}

	public function actionDownload() {
		set_time_limit(0);
		error_reporting(E_ERROR);
		ini_set('memory_limit', '1024M');
		$params = Yii::$app->session->get('sku-quality-analysis');
		if (empty($params)) {
			$this->_showMessage('请选择下载数据条件，并点击搜索！', false);
		}
		$request = Yii::$app->request->get();
		$ids = $request['ids'];
		$query = SkuQualityAnalysis::find()
				->alias('a')
				->select('a.sku,a.picking_name,a.category_cn_name,a.qualityer,a.developer_id,a.reason_id,count(d.id) as abnormal_num,sum(d.loss_rmb) as total_loss_rmb,a.remark')
				->leftJoin(SkuQualityDetail::tableName().' d','d.sku = a.sku');
		if ($params['platform_code']) {
			$query->andWhere(['d.platform_code'=>$params['platform_code']]);
		}
		if ($params['reason_id']) {
			$query->andWhere(['d.reason_id'=>$params['reason_id']]);
		}
		if ($params['sku']) {
			$query->andWhere(['d.sku'=>$params['sku']]);
		}
		if ($params['qualityer']) {
			$query->andWhere(['a.qualityer'=>$params['qualityer']]);
		}
		if (!empty($params['startTime']) && !empty($params['endTime'])) {
		    $query->andWhere(['between', 'd.finish_time', $params['startTime'], $params['endTime']]);
		} else if (!empty($params['startTime'])) {
		    $query->andWhere(['>=', 'd.finish_time', $params['startTime']]);
		} else if (!empty($params['endTime'])) {
		    $query->andWhere(['<=', 'd.finish_time', $params['endTime']]);
		}
		if (!empty($ids)) {
			$id = strpos($ids,',')?explode(',',$ids):$ids;
			$query->where(['in','a.id',$id]);
		}
		$model = $query->groupBy('d.sku')->orderBy(['a.id'=>SORT_ASC])->all();
		// VHelper::dump($model);
		$table = [
		    'sku',
		    '中文简称',
		    '产品类别',
		    '质检人',
		    '开发人',
		    '异常次数',
		    '实际损失金额RMB',
		    '原因',
		    '备注',
		];

		$allConfig = BasicConfig::getAllConfigData();
		$table_head = [];
		if(!empty($model)){
		    foreach($model as $k=>$v)
		    {
		        $table_head[$k][] = $v->sku;
		        if (empty($v->picking_name)) {
		        	$product_info = Product::getDeveloper($v->sku);
		        	$picking_name = $product_info ? $product_info['picking_name'] : '-';
		        	$table_head[$k][] = $picking_name;
		        }else{
		        	$table_head[$k][] = $v->picking_name;	
		        } 

		        $table_head[$k][] = $v->category_cn_name;
		        $table_head[$k][] = !empty($v->qualityer) ? $v->qualityer : '';
		        $table_head[$k][] = !empty($v->developer_id) ? MHelper::getUsername($v->developer_id) : '';
		        $table_head[$k][] = $v->abnormal_num;
		        $table_head[$k][] = $v->total_loss_rmb;
		        $table_head[$k][] = !empty($v->reason_id) ? $allConfig[$v->reason_id] : '';
		        $table_head[$k][] = $v->remark;
		    }
		}else{
		    $this->_showMessage('当前条件查询数据为空', false);
		}
		VHelper::exportExcel($table,$table_head, '产品质量破损分析');



	}

	public function actionAbnormaldetail() {
	    //$order_id = Yii::$app->request->get('order_id') ? :'';
		$sku = Yii::$app->request->get('sku');
	    $detailList = SkuQualityDetail::find()
			        ->alias('b')
			        ->leftJoin(SkuQualityAnalysis::tablename(). ' a','a.sku = b.sku')
			        ->where(['b.sku'=>$sku])
			        ->orderBy('b.shipped_date asc')
			        ->all();
		//$model = new SkuQualityDetail();
        return $this->renderAjax('detail',[
        	'model'=>$detailList,
        	// 'model'=>$model,
        	//'sku' =>$sku,
        ]);
	}

	public function actionSkudetail() {
        $sku = Yii::$app->request->get('sku') ? :'';

    	$params = \Yii::$app->request->getBodyParams();
    	//$sku = \Yii::$app->request->getQueryParams();
    	$params['sku'] = $sku['sku'];
    	$model = new SkuQualityDetail();

    	$dataProvider = $model->searchList($params); 
    	// VHelper::dump($model);
        return $this->renderList('skudetail',[
            'model'=>$skuModel,
            'dataProvider' => $dataProvider,
        ]);
		
	}


	public function actionSetreason() {
		$request = Yii::$app->request->post();
		$id = $request['id'];
		$remark = $request['text'];
		$return_arr = ['status' => 1, 'info' => '操作成功!'];
		$modify_by = Yii::$app->user->identity->user_name;
		$modify_time = date('Y-m-d H:i:s', time()); 
		if (empty($id)) {
            $return_arr = ['status' => 0, 'info' => '设置纠纷原因失败!'];
            echo json_encode($return_arr);
            die;
        }
        $res = SkuQualityAnalysis::updateAll(['remark' => $remark, 'modify_by' => $modify_by, 'modify_time' => $modify_time], 'id = :id', [':id' => $id]);
        if ($res === false) {
            $return_arr = ['status' => 0, 'info' => '设置纠纷原因失败!'];
        }

		echo json_encode($return_arr);
		die;
	}

	public function actionGetsomedata() {
		$startTime = Yii::$app->request->get('startTime','');
		$endTime = Yii::$app->request->get('endTime','');
		if (empty($startTime) || empty($endTime)) {

			return '必须要填开始结束时间';
		}
		$reason_arr = [73,74];
		$type_arr = [1,3];
		$AfterSalesOrder = AfterSalesOrder::find()
							   ->where(['status'=>2])
							   ->andWhere(['in', 'reason_id', $reason_arr])		   
							   ->andWhere(['in', 'type', $type_arr])		   
							   ->andWhere(['between', 'create_time', $startTime, $endTime])
							   ->asArray()				   
							   ->all();	
	   
		foreach ($AfterSalesOrder as $key => $value) {

			$afterSalesId = $value['after_sale_id'];
			$platform_code = $value['platform_code'];
			$order_id = $value['order_id'];
			$type = $value['type'];
			$res = SkuQualityAnalysis::createSkuRecordData($afterSalesId,$platform_code,$order_id,$type);

			echo "<pre>";var_dump($res);
			echo "<br/>";
		}					   

		

	}

}

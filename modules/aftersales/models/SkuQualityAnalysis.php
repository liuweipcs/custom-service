<?php

namespace app\modules\aftersales\models;

use app\common\VHelper;
use app\common\MHelper;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\UserAccount;
use app\modules\mails\components\GridView;
use app\modules\orders\models\OrderKefu;
use yii\helpers\Url;
use Yii;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\BasicConfig;
use app\modules\aftersales\models\AfterSalesProduct;
use app\modules\products\models\Product;
use app\modules\products\models\ProductCategory;
use app\modules\products\models\ProductTask;
use app\modules\products\models\ProductDescription;


class SkuQualityAnalysis extends AfterSalesModel {

	const ORDER_TYPE_REFUND = 1;    //退款单
	const ORDER_TYPE_RETURN = 2;    //退货单
	const ORDER_TYPE_REDIRECT = 3;  //重寄
	const ORDER_SEARCH_CONDITION_FROM_ALL = 'all'; //构造公共查询售后问题的搜索条件的标识

	/**
	 * @desc 设置表名
	 * @return string
	 */
	public static function tableName() {
	    return '{{%sku_qlty_analisis}}';
	}

	/**
	 * 添加表属性
	 * !CodeTemplates.overridecomment.nonjd!
	 * @see \yii\db\ActiveRecord::attributes()
	 */
	public function attributes() {
	    $attributes = parent::attributes();
	    $extraAttributes = ['qualityer','developer']; // 质检员，开发人
	    return array_merge($attributes, $extraAttributes);
	}

	/**
	 * 获取属性标签
	 * @return array
	 */
	public function attributeLabels() {
	    return [
	        'id' => 'ID',
	        'sku' => 'sku',
	        'picking_name' => '中文全称',
	        'category_cn_name' => '产品类别',
	        'qualityer' => '质检人',
	        'developer_id' => '开发人id',
	        'developer' => '开发人',
	        'abnormal_num' => '异常次数',
	        'total_loss_rmb' => '实际损失金额RMB',
	        'reason_id' => '问题原因',
	        'remark' => '备注',
	        'modify_by' => '更新人/时间',
	        'modify_time' => '修改时间',
	    ];
	}

	/**
	 * @desc 查询
	 */
	public function searchList($params = []) {
		$data = $params;
	    $sort = new \yii\data\Sort();
	    $sort->defaultOrder = array(
	        'id' => SORT_DESC
	    );
	    $query = self::find();
	    if (!empty($params['platform_code']) || !empty($params['reason_id']) || !empty($params['category_cn_name']) || !empty($params['startTime']) || !empty($params['endTime'])) {
	    	$query->from(self::tableName() . ' as a');
	    	$query->leftJoin(SkuQualityDetail::tablename(). ' d','d.sku = a.sku');
	    	$query->groupBy('d.sku');
	    }
	    if (!empty($params['platform_code'])) {
	    	$query->andWhere(['d.platform_code'=>$params['platform_code']]);
	    	unset($params['platform_code']);
	    }
	    if (!empty($params['reason_id'])) {
	   		$query->andWhere(['d.reason_id'=>$params['reason_id']]);
	   		unset($params['reason_id']);
	   	}
	   	if (!empty($params['sku'])) {
	   		$query->andWhere(['a.sku'=>$params['sku']]);
	   		unset($params['sku']);
	   	}
	   	if (!empty($params['qualityer'])) {
	   		$query->andWhere(['a.qualityer'=>$params['qualityer']]);
	   		unset($params['qualityer']);
	   	}
	   	if (!empty($params['category_cn_name'])) {
	   		$query->andWhere(['a.product_category_id'=>$params['category_cn_name']]);
	   		unset($params['category_cn_name']);
	   	}
        if (!empty($params['startTime']) && !empty($params['endTime'])) {
            $query->andWhere(['between', 'd.finish_time', $params['startTime'], $params['endTime']]);
            unset($params['startTime']);
            unset($params['endTime']);
        } else if (!empty($params['startTime'])) {
            $query->andWhere(['>=', 'd.finish_time', $params['startTime']]);
            unset($params['startTime']);
        } else if (!empty($params['endTime'])) {
            $query->andWhere(['<=', 'd.finish_time', $params['endTime']]);
            unset($params['endTime']);
        }
       
        // echo $query->createCommand()->getRawSql();exit;

	    Yii::$app->session->set('sku-quality-analysis',$data);
	    
	    $dataProvider = parent::search($query, $sort, $params);
	    $models = $dataProvider->getModels();
	    $this->chgModelData($models,$data);
	    $dataProvider->setModels($models);

	    return $dataProvider;
	}

	/**
	 * 修改模型数据
	 */
	public function chgModelData(&$models,$data) {
		$allConfig = BasicConfig::getAllConfigData();
		foreach ($models as $key => $model) {
			$qualityer = $model->qualityer ? : '无';	
			$model->setAttribute('qualityer', $qualityer);
			if ($model->developer_id) {		
				$developer = MHelper::getUsername($model->developer_id);
				$developer = ($developer ? : '无');
				$model->setAttribute('developer', $developer);
			}else{
				$model->setAttribute('developer', $developer);
			}
			$model->setAttribute('modify_by', $model->modify_by. '<br>' .$model->modify_time);

			$num = '';
			$loss_rmb_total = '';
			//异常次数	
			$num = SkuQualityDetail::find();
			$num->where(['sku'=>$model->sku]);
			//实际损失金额RMB
			$loss_rmb_total = SkuQualityDetail::find();
			$loss_rmb_total->select('sum(loss_rmb) as total');
			$loss_rmb_total->where(['sku'=>$model->sku]);
			
			if (!empty($data['platform_code'])) {
			    $num->andWhere(['platform_code'=>$data['platform_code']]);
			    $loss_rmb_total->andWhere(['platform_code'=>$data['platform_code']]);
			}   
			if (!empty($data['reason_id'])) {
			    $num->andWhere(['reason_id'=>$data['reason_id']]);
			    $loss_rmb_total->andWhere(['reason_id'=>$data['reason_id']]);
			}
			if (!empty($data['platform_code'])) {
			    $num->andWhere(['platform_code'=>$data['platform_code']]);
			    $loss_rmb_total->andWhere(['platform_code'=>$data['platform_code']]);
			}
			if (!empty($data['startTime']) && !empty($data['endTime'])) {
			    $num->andWhere(['between', 'finish_time', $data['startTime'], $data['endTime']]);
			    $loss_rmb_total->andWhere(['between', 'finish_time', $data['startTime'], $data['endTime']]);
			} else if (!empty($data['startTime'])) {
			    $num->andWhere(['>=', 'finish_time', $data['startTime']]);
			    $loss_rmb_total->andWhere(['>=', 'finish_time', $data['startTime']]);
			} else if (!empty($data['endTime'])) {
			    $num->andWhere(['<=', 'finish_time', $data['endTime']]);
			    $loss_rmb_total->andWhere(['<=', 'finish_time', $data['endTime']]);

			}

			$nums = $num->count();

			$loss_rmb_totals = $loss_rmb_total->asArray()->scalar();

			$model->total_loss_rmb = $loss_rmb_totals;

			$model->abnormal_num = '<a class="anbor-detail" data-href="/aftersales/skuqualityanalysis/abnormaldetail?sku='.$model->sku.'" data-sku="'.$model->sku.'">'.$nums.'</a>';

			//中文简称
			if (empty($model->picking_name)) {
				$title = ProductDescription::getProductCnNameBySku($model->sku);
				$picking_name = $title ? $title : '-';
				$model->picking_name = $picking_name;
			} 

			//sku
			$sku = "<a href='http://120.24.249.36/product/index/sku/".$model->sku."' target='_blank'>".$model->sku."</a>";
			$model->setAttribute('sku', $model->sku ? $sku : '');


			//问题原因
			if (!empty($model->reason_id) && array_key_exists($model->reason_id, $allConfig)) {
			    $model->reason_id = $allConfig[$model->reason_id];
			} else {
			    $model->reason_id = '未设置';
			}

			//备注
			if (!empty($model->remark)) {
			    $model->remark = '<span style="cursor:pointer;" data="' . $model->id . '" class="remark">'.$model->remark.'</span>';
			} else {
			    $model->remark = '<span style="cursor:pointer;" data="' . $model->id . '" data1="2" data2="' . $model->remark . '" class="remark">(未设置)</span>';
			}

		}


	}	

	/**
	 * @desc 搜索过滤项
	 * @return multitype:multitype:string multitype:  multitype:string multitype:string
	 */
	public function filterOptions() {
		$platformArray = isset(\Yii::$app->user->identity->role->platform_code) ? explode(',', \Yii::$app->user->identity->role->platform_code) : array();
		$platform = array();
		$allplatform = UserAccount::getLoginUserPlatformAccounts();
		if ($platformArray) {
		    foreach ($platformArray as $value) {
		        $platform[$value] = isset($allplatform[$value]) ? $allplatform[$value] : $value;
		    }
		}
		$getCustomer = Yii::$app->request->get();
		$platform = !empty($platform) ? $platform : $allplatform;
		//所有分类
		$product_cate = ProductCategory::getCategory();

		$allConfig = BasicConfig::getParentList(55);
        return [
            [
                'name' => 'platform_code',
                'type' => 'dropDownList',
                'search' => '=',
                'data' =>  $allplatform,
                'value' => $getCustomer ? $getCustomer['platform_code'] : null,
            ],
            [
                'name' => 'reason_id',
                'type' => 'dropDownList',
                'data' => $allConfig,
                'htmlOptions' => [],
                'search' => '=',
            ],
            [
                'name' => 'sku',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'qualityer',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'category_cn_name',
                'type' => 'dropDownList',
                'search' => '=',
                'data' =>  $product_cate,
            ],
            [
                'name' => 'startTime',
                'type' => 'date_picker',
                'search' => '<',
                'value' => date("Y-m-d H:i:s", strtotime("-1 months")),
            ],
            [
                'name' => 'endTime',
                'type' => 'date_picker',
                'search' => '>',
                'value' => date("Y-m-d H:i:s"),
            ],
        ];
	}	


	/**
	 * 售后审核通过后 保存数据到sku_qtly_analysis sku_qtly_detail
	 * @param  [string] $afterSalesId  [售后单号]
	 * @param  [string] $platform_code [平台号]
	 * @param  [string] $order_id      [订单号]
	 * @param  [string] $type          [售后类型]
	 * @return [bool]              有问题返回true 没有问题返回false
	 */
	public static function createSkuRecord($afterSalesId,$platform_code,$order_id,$type) {

		$afterSalesProduct = AfterSalesProduct::find()->where(['after_sale_id' => $afterSalesId])->asArray()->all();
		$afterSalesOrderInfo = AfterSalesOrder::findById($afterSalesId);
		$orderInfo = OrderKefu::getOrders($platform_code,$order_id);

		if (empty($afterSalesProduct) || empty($afterSalesOrderInfo) || empty($orderInfo)) {
			return true;
		}
		//发货日期
		$shipped_date = $orderInfo->shipped_date ? : '';
		foreach ($afterSalesProduct as $key => $value) {
			$sku = $value['sku'];
			$loss_rmb = $value['refund_redirect_price_rmb'];
			$remark = $value['remark'];
			//产品信息  developer_id picking_name
			$product_info = Product::getDeveloper($sku);
			$developer_id = $product_info ? $product_info['create_user_id'] : '';
			$picking_name = '';
			if (empty($product_info['picking_name'])) {
				$picking_name = ProductDescription::getProductCnNameBySku($sku);
			}else{
				$picking_name = $product_info ? $product_info['picking_name'] : '';
			}
			
			//产品类别 category_cn_name
			$product_cate = Product::getCategory($sku);
			$category_cn_name = $product_cate ? $product_cate['category_cn_name'] : '';
			$product_category_id = $product_cate ? $product_cate['id'] : '';

			//质检人员
			$qualityer = ProductTask::getSkuByQualityer($sku);

			$transaction = Yii::$app->db->beginTransaction();
			try{
				$model = self::find()->where(['reason_id' => $afterSalesOrderInfo->reason_id,'sku'=>$sku, 'product_category_id'=>$product_category_id])->one();
				if ($model) {
					$model->reason_id = $afterSalesOrderInfo->reason_id;
					$model->product_category_id = $product_category_id;
					$res = $model->save();
					if (empty($res)) {
						$transaction->rollBack();
						return true;
					}
					$skuQualityDetail = SkuQualityDetail::find()->where(['after_sale_id' => $afterSalesId,'sku'=>$sku])->one();

					if (empty($skuQualityDetail)) {
						//新增skuQualityDetail数据
						$detailModel = new SkuQualityDetail();
						$detailModel->sku = $sku;
						$detailModel->platform_code = $platform_code;
						$detailModel->order_id = $order_id;
						$detailModel->type = $type;
						if ($detailModel->type==1) {
							$detailModel->finish_time = '';
							$detailModel->loss_rmb = '';
						}elseif ($detailModel->type==3) {
							$detailModel->finish_time = $afterSalesOrderInfo->approve_time;
							$detailModel->loss_rmb = $loss_rmb;
						}
						$detailModel->shipped_date = $shipped_date;
						$detailModel->remark = $afterSalesOrderInfo->remark;
						$detailModel->after_sale_id = $afterSalesId;
						$detailModel->reason_id = $afterSalesOrderInfo->reason_id;
						$flag = $detailModel->save();
						if (empty($flag)) {
							$transaction->rollBack();
							return true;
						}
					}
						
				}else{
					$model = new self();
					$model->sku = $sku; 
					$model->picking_name = $picking_name; 
					$model->product_category_id = $product_category_id;
					$model->category_cn_name = $category_cn_name;
					$model->qualityer = $qualityer;
					$model->developer_id = $developer_id;
					$model->reason_id = $afterSalesOrderInfo->reason_id;
					$res = $model->save();
					if (empty($res)) {
						$transaction->rollBack();
						return true;
					}

					$skuQualityDetail = SkuQualityDetail::find()->where(['after_sale_id' => $afterSalesId,'sku'=>$sku])->one();
					if (empty($skuQualityDetail)) {
						//新增skuQualityDetail数据
						$detailModel = new SkuQualityDetail();
						$detailModel->sku = $sku;
						$detailModel->platform_code = $platform_code;
						$detailModel->order_id = $order_id;
						$detailModel->type = $type;
						$detailModel->shipped_date = $shipped_date;
						if ($detailModel->type==1) {
							$detailModel->finish_time = '';
							$detailModel->loss_rmb = '';
						}elseif ($detailModel->type==3) {
							$detailModel->finish_time = $afterSalesOrderInfo->approve_time;
							$detailModel->loss_rmb = $loss_rmb;
						}
						$detailModel->remark = $afterSalesOrderInfo->remark;
						$detailModel->after_sale_id = $afterSalesId;
						$detailModel->reason_id = $afterSalesOrderInfo->reason_id;
						$flag = $detailModel->save();
						if (empty($flag)) {
							$transaction->rollBack();
							return true;
						}
					}

				}
				$transaction->commit();
			}catch (\Exception $e){		
				$transaction->rollBack();
				 return true;
				 // return $e->getMessage();
			}	
		}

	}

	//跑数据专用方法
	public static function createSkuRecordData($afterSalesId,$platform_code,$order_id,$type) {

		$afterSalesProduct = AfterSalesProduct::find()->where(['after_sale_id' => $afterSalesId])->asArray()->all();
		$afterSalesOrderInfo = AfterSalesOrder::findById($afterSalesId);
		$orderInfo = OrderKefu::getOrders($platform_code,$order_id);

		if (empty($afterSalesProduct) || empty($afterSalesOrderInfo) || empty($orderInfo)) {
			return true;
		}
		//发货日期
		$shipped_date = $orderInfo->shipped_date ? : '';
		foreach ($afterSalesProduct as $key => $value) {
			$sku = $value['sku'];
			$loss_rmb = $value['refund_redirect_price_rmb'];
			$remark = $value['remark'];
			//产品信息  developer_id picking_name
			$product_info = Product::getDeveloper($sku);
			$developer_id = $product_info ? $product_info['create_user_id'] : '';
			$picking_name = '';
			if (empty($product_info['picking_name'])) {
				$picking_name = ProductDescription::getProductCnNameBySku($sku);
			}else{
				$picking_name = $product_info ? $product_info['picking_name'] : '';
			}
			
			//产品类别 category_cn_name
			$product_cate = Product::getCategory($sku);
			$category_cn_name = $product_cate ? $product_cate['category_cn_name'] : '';
			$product_category_id = $product_cate ? $product_cate['id'] : '';

			//质检人员
			$qualityer = ProductTask::getSkuByQualityer($sku);

			$transaction = Yii::$app->db->beginTransaction();
			try{
				$model = self::find()->where(['reason_id' => $afterSalesOrderInfo->reason_id,'sku'=>$sku, 'product_category_id'=>$product_category_id])->one();
				if ($model) {
					$model->reason_id = $afterSalesOrderInfo->reason_id;
					$model->product_category_id = $product_category_id;
					$res = $model->save();
					if (empty($res)) {
						$transaction->rollBack();
						return true;
					}
					$skuQualityDetail = SkuQualityDetail::find()->where(['after_sale_id' => $afterSalesId,'sku'=>$sku])->one();

					if (empty($skuQualityDetail)) {
						//新增skuQualityDetail数据
						$detailModel = new SkuQualityDetail();
						$detailModel->sku = $sku;
						$detailModel->platform_code = $platform_code;
						$detailModel->order_id = $order_id;
						$detailModel->type = $type;
						$detailModel->shipped_date = $afterSalesOrderInfo->approve_time;
						$detailModel->loss_rmb = $loss_rmb;
						$detailModel->shipped_date = $shipped_date;
						$detailModel->remark = $afterSalesOrderInfo->remark;
						$detailModel->after_sale_id = $afterSalesId;
						$detailModel->reason_id = $afterSalesOrderInfo->reason_id;
						$flag = $detailModel->save();
						if (empty($flag)) {
							$transaction->rollBack();
							return true;
						}
					}
						
				}else{
					$model = new self();
					$model->sku = $sku; 
					$model->picking_name = $picking_name; 
					$model->product_category_id = $product_category_id;
					$model->category_cn_name = $category_cn_name;
					$model->qualityer = $qualityer;
					$model->developer_id = $developer_id;
					$model->reason_id = $afterSalesOrderInfo->reason_id;
					$res = $model->save();
					if (empty($res)) {
						$transaction->rollBack();
						return true;
					}

					$skuQualityDetail = SkuQualityDetail::find()->where(['after_sale_id' => $afterSalesId,'sku'=>$sku])->one();
					if (empty($skuQualityDetail)) {
						//新增skuQualityDetail数据
						$detailModel = new SkuQualityDetail();
						$detailModel->sku = $sku;
						$detailModel->platform_code = $platform_code;
						$detailModel->order_id = $order_id;
						$detailModel->type = $type;
						$detailModel->shipped_date = $shipped_date;
						$detailModel->finish_time = $afterSalesOrderInfo->approve_time;
						$detailModel->loss_rmb = $loss_rmb;
						$detailModel->remark = $afterSalesOrderInfo->remark;
						$detailModel->after_sale_id = $afterSalesId;
						$detailModel->reason_id = $afterSalesOrderInfo->reason_id;
						$flag = $detailModel->save();
						if (empty($flag)) {
							$transaction->rollBack();
							return true;
						}
					}

				}
				$transaction->commit();
			}catch (\Exception $e){		
				$transaction->rollBack();
				 return true;
				 // return $e->getMessage();
			}	
		}

	}


	
}

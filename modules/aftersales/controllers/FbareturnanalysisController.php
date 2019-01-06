<?php

namespace app\modules\aftersales\controllers;

use app\modules\aftersales\models\AmazonFbaReturnAnalysis;
use app\components\Controller;
use yii\data\Pagination;
use app\modules\accounts\models\Account;
use app\modules\orders\models\OrderKefu;
use Yii;

class FbareturnanalysisController extends Controller
{
    /**
     * @author alpha
     * @desc
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function actionIndex()
    {
        $params = Yii::$app->request->get();
        $pageCur  = isset($params['pageCur']) ? trim($params['pageCur']) : 1;//当前页
        $pageSize = isset($params['pageSize']) ? trim($params['pageSize']) : 10;//分页大小
        $sku = !empty($params['sku']) ? trim($params['sku']) : "";//平台sku ,公司sku
        $lastQualityControlUser = !empty($params['last_quality_control_user']) ? trim($params['last_quality_control_user']) : "";//最后记录品控的操作人
        $salesTrend = !empty($params['sales_trend']) ? trim($params['sales_trend']) : "";//销量趋势
        $returnTrend = !empty($params['return_trend']) ? trim($params['return_trend']) : "";//退货趋势
        
        $sales = !empty($params['sales']) ? trim($params['sales']) : "";//销售查询
        $salesStr = !empty($params['sales_str']) ? trim($params['sales_str']) : "";//销售额查询开始数据
        $salesEnd = !empty($params['sales_end']) ? trim($params['sales_end']) : "";//销售额查询结束数据
        $refundRate = !empty($params['return_rate']) ? trim($params['return_rate']) : "";//退款率查询
        $refundRateStr = !empty($params['return_rate_str']) ? trim($params['return_rate_str']) : "";//退款率查询开始数据
        $refundRateEnd = !empty($params['return_rate_end']) ? trim($params['return_rate_end']) : "";//退款率查询结束数据
        
        $datas = [
            'pageCur'              => $pageCur,
            'pageSize'              => $pageSize,
            'sku'                  => $sku,
            'lastQualityControlUser'         => $lastQualityControlUser,
            'salesTrend'      => $salesTrend,
            'returnTrend'       => $returnTrend,
            'sales'                 => $sales,
            'salesStr'              => $salesStr,
            'salesEnd'              => $salesEnd,
            'refundRate'           => $refundRate,
            'refundRateStr'        => $refundRateStr,
            'refundRateEnd'        => $refundRateEnd,
        ];
        $result = AmazonFbaReturnAnalysis::getDataList($datas);
        
        $accountLists = Account::getCurrentUserPlatformAccountList('EB');
        $accountList = ['0' => '--请选择--'];
        if(!empty($accountLists)){
            foreach ($accountLists as $value) {
                $accountList[$value['id']] = $value['account_name'];
            }
        }
        //创建分页组件
        $page = new Pagination([
            //总的记录条数
            'totalCount'    => $result['count'],
            //分页大小
            'pageSize'      => $pageSize,
            //设置地址栏当前页数参数名
            'pageParam'     => 'pageCur',
            //设置地址栏分页大小参数名
            'pageSizeParam' => 'pageSize',
        ]);
        
        return $this->render('index', [
            'receipts'         => $result['data_list'],
            'page'             => $page,
            'count'            => $result['count'],
            'datas'             => $datas
        ]);
    }
    
    /**
     * 保存品控问题
     * @author allen <2018-11-21>
     */
    public function actionSavequalitycontrol(){
        if(Yii::$app->request->isPost){
            $data = ['status' => TRUE,'info' => '操作成功'];
            $id = Yii::$app->request->post('id');
            $text = Yii::$app->request->post('text');
            $res = AmazonFbaReturnAnalysis::saveQualityControl($id,$text);
            if(!$res){
                $data = ['status' => FALSE,'info' => '操作失败!'];
            }
            echo json_encode($data);
            die;
        }
    }
    
    /**
     * 查看品控历史
     * @author allen <2018-11-21>
     */
    public function actionViwhistory(){
        if(Yii::$app->request->isPost){
            $id = Yii::$app->request->post('id');
            $res = AmazonFbaReturnAnalysis::viwHistory($id);
            echo json_encode($res);die;
        }
    }
    
    
    public function actionSkustatus(){
        if(Yii::$app->request->isPost){
            $id = Yii::$app->request->post('id');
            $sku = AmazonFbaReturnAnalysis::getSku($id);
            $res = OrderKefu::getSkuStatus($sku);
            echo json_encode($res);die;
        }
    }

}
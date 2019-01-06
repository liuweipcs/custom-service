<?php

namespace app\modules\aftersales\controllers;

use app\modules\aftersales\models\AmazonFbaReturnInfo;
use app\components\Controller;
use yii\data\Pagination;
use app\modules\accounts\models\Account;
use Yii;

class FbareturnController extends Controller
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
        $accountId = !empty($params['account_id']) ? trim($params['account_id']) : '';//账号ID
        $platformOrderId = !empty($params['platform_order_id']) ? trim($params['platform_order_id']) : '';//平台订单号
        $asin = !empty($params['asin']) ? trim($params['asin']) : "";//asin、平台sku ,公司sku
        $returnReason = !empty($params['return_reason']) ? trim($params['return_reason']) : "";//退款原因
        //$companySkuStatus = !empty($params['company_sku_status']) ? trim($params['company_sku_status']) : "";//公司sku状态
        $isAvailableSale = !empty($params['is_available_sale']) ? trim($params['is_available_sale']) : "";//退货是否可售
        $proStatus = !empty($params['pro_status']) ? trim($params['pro_status']) : "";//退货产品状态
        $refundStartDate = !empty($params['refund_start_date']) ? trim($params['refund_start_date']) : "";//退款开始时间
        $refundEndDate = !empty($params['refund_end_date']) ? trim($params['refund_end_date']) : "";//退款结束时间
        $sales = !empty($params['sales']) ? trim($params['sales']) : "";//销售查询
        $salesStr = !empty($params['sales_str']) ? trim($params['sales_str']) : "";//销售额查询开始数据
        $salesEnd = !empty($params['sales_end']) ? trim($params['sales_end']) : "";//销售额查询结束数据
        $refundRate = !empty($params['return_rate']) ? trim($params['return_rate']) : "";//退款率查询
        $refundRateStr = !empty($params['return_rate_str']) ? trim($params['return_rate_str']) : "";//退款率查询开始数据
        $refundRateEnd = !empty($params['return_rate_end']) ? trim($params['return_rate_end']) : "";//退款率查询结束数据
        
        $datas = [
            'pageCur'              => $pageCur,
            'pageSize'              => $pageSize,
            'accountId'             => $accountId,
            'platformOrderId'       => $platformOrderId,
            'asin'                  => $asin,
            'returnReason'         => $returnReason,
            //'companySkuStatus'      => $companySkuStatus,
            'isAvailableSale'       => $isAvailableSale,
            'proStatus'             => $proStatus,
            'refundStartDate'       => $refundStartDate,
            'refundEndDate'         => $refundEndDate,
            'sales'                 => $sales,
            'salesStr'              => $salesStr,
            'salesEnd'              => $salesEnd,
            'refundRate'           => $refundRate,
            'refundRateStr'        => $refundRateStr,
            'refundRateEnd'        => $refundRateEnd,
        ];
        $result = AmazonFbaReturnInfo::getDataList($datas);
        
        $accountLists = Account::getCurrentUserPlatformAccountList('AMAZON');
        $returnProstatusList = AmazonFbaReturnInfo::getReturnProstatusList();//退货产品状态
        $returnReasonList = AmazonFbaReturnInfo::getReturnReasonList();//退货原因
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
            'accountList'      => $accountList,
            'refundReason'       => $returnReasonList,
            'returnProstatusList'  => $returnProstatusList,
            'datas'             => $datas
        ]);
    }

}
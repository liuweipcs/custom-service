<?php

namespace app\modules\aftersales\controllers;

use Yii;
use app\components\Controller;
use yii\data\Pagination;
use app\modules\aftersales\models\ComplaintdetailModel;
use app\modules\aftersales\models\ComplaintskuModel;
use app\modules\accounts\models\Platform;
use app\modules\aftersales\models\ComplaintModel;

/**
 * RefundreturnreasonController implements the CRUD actions for RefundReturnReason model.
 */
class WarehouseController extends Controller {

    //列表
    public function actionIndex() {

        $platformCode = isset($_REQUEST['platform_code']) ? $_REQUEST['platform_code'] : null; //平台code
        $sku = isset($_REQUEST['sku']) ? $_REQUEST['sku'] : null; //sku
        $end_time = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : null; //截止日期
        if (empty($end_time)) {
            //$end_time = '2018-8-07 00:00:00';
            $end_time = date("Y-m-d H:i:s");
        }
        if (!empty($platformCode)) {
            $complaint = ComplaintModel::find()->select('id')->where(['platform_code' => $platformCode])->all();

            if (empty($complaint)) {
                $paran['id'] = null;
            } else {
                foreach ($complaint as $key => $value) {
                    $complaint_id[] = $value->id;
                }

                $paran = ['in', 'complaint_order_id', $complaint_id];
            }
        } else {
            $paran = [];
        }
       
        if(!empty($sku)){
          $paransku=  explode(",", $sku);
        $paransku=['in','sku',$paransku];
        }else{
          $paransku=[];  
        }

        //获取指定时间前30天的数据
        $strat_time30 = date('Y-m-d H:i:s', strtotime($end_time) - 30 * 3600 * 24);
        //30天的客诉量
        $complaintskumunall30 = ComplaintdetailModel::find()->where(['between', 'create_time', $strat_time30, $end_time])->andWhere($paran)->andwhere($paransku)->asArray()->count();

        //获取30天每个sku客诉量
        $complaintskuall30 = ComplaintdetailModel::find()->select("sku,count(sku) as complaintskumun30,title")->where(['between', 'create_time', $strat_time30, $end_time])->andWhere($paran)->andwhere($paransku)->groupBy('sku')->asArray()->all();

        $skuamunall30 = 0;
        foreach ($complaintskuall30 as $key => $v) {
            //获取7天每个sku发货量  
            $skuamun30 = ComplaintskuModel::find()->where(['sku' => $v['sku']])->andwhere(['between', 'shipped_date', $strat_time30, $end_time])->asArray()->count();
            $v['skuamun30'] = $skuamun30;
            $complaintskuall30[$key] = $v;
            $skuamunall30 += $skuamun30;
        }
        $data = $complaintskuall30;
        //获取指定时间前15天的数据
        $strat_time15 = date('Y-m-d H:i:s', strtotime($end_time) - 15 * 3600 * 24);
        //15天的客诉量
        $complaintskumunall15 = ComplaintdetailModel::find()->where(['between', 'create_time', $strat_time15, $end_time])->andWhere($paran)->andwhere($paransku)->asArray()->count();
        //获取15天每个sku客诉量

        $skuamunall15 = 0;
        foreach ($data as $key => $v) {
            //获取15天每个sku发货量  
            $skuamun15 = ComplaintskuModel::find()->where(['sku' => $v['sku']])->andwhere(['between', 'shipped_date', $strat_time15, $end_time])->asArray()->count();
            $complaintskuall15 = ComplaintdetailModel::find()->select("sku,count(sku) as complaintskumun15,title")->where(['between', 'create_time', $strat_time15, $end_time])->andWhere($paran)->andWhere(['sku' => $v['sku']])->andwhere($paransku)->groupBy('sku')->asArray()->one();
            $data[$key]['skuamun15'] = $skuamun15;
            $data[$key]['complaintskumun15'] = $complaintskuall15['complaintskumun15'];

            $skuamunall15 += $skuamun15;
        }

        //获取指定时间前7天的数据
        $strat_time7 = date('Y-m-d H:i:s', strtotime($end_time) - 7 * 3600 * 24);
        //7天的客诉量
        $complaintskumunall7 = ComplaintdetailModel::find()->where(['between', 'create_time', $strat_time7, $end_time])->andWhere($paran)->andwhere($paransku)->asArray()->count();


        $skuamunall7 = 0;
        foreach ($data as $key => $v) {
            //获取15天每个sku发货量  
            $skuamun7 = ComplaintskuModel::find()->where(['sku' => $v['sku']])->andwhere(['between', 'shipped_date', $strat_time7, $end_time])->asArray()->count();
            //获取7天每个sku客诉量
            $complaintskuall7 = ComplaintdetailModel::find()->select("sku,count(sku) as complaintskumun7,title")->where(['between', 'create_time', $strat_time7, $end_time])->andWhere($paran)->andwhere(['sku' => $v['sku']])->andwhere($paransku)->groupBy('sku')->asArray()->one();
            $data[$key]['skuamun7'] = $skuamun7;
            $data[$key]['complaintskumun7'] = $complaintskuall7['complaintskumun7'];
            $skuamunall7 += $skuamun7;
        }





//        echo '<pre>';
//        echo $complaintskumunall7;
//        echo "<br/>";
//        echo $skuamunall7;
//        echo "<br/>";
//        echo $complaintskumunall15;
//        echo "<br/>";
//        echo $skuamunall15;
//        echo "<br/>";
//        echo $complaintskumunall30;
//        echo "<br/>";
//        echo $skuamunall30;
//        echo "<br/>";
//        print_r($data);
//        die;






        $platformList = Platform::getPlatformAsArray();

        //创建分页组件
        //创建分页组件
        $page = new Pagination([
            //总的记录条数
            'totalCount' => count($data),
            //分页大小
            'pageSize' => $pageSize,
            //设置地址栏当前页数参数名
            'pageParam' => 'pageCur',
            //设置地址栏分页大小参数名
            'pageSizeParam' => 'pageSize',
        ]);

        return $this->render('index', [
                    'data' => $data,
                    'page' => $page,
                    'platformList' => $platformList,
                    'count' => count($data),
                    'complaintskumunall7' => $complaintskumunall7,
                    'skuamunall7' => $skuamunall7,
                    'complaintskumunall15' => $complaintskumunall15,
                    'skuamunall15' => $skuamunall15,
                    'complaintskumunall30' => $complaintskumunall30,
                    'skuamunall30' => $skuamunall30,
                    'platformCode' => $platformCode,
                    'end_time' => $end_time,
                   'sku'=>$sku
        ]);
    }

}

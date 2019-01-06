<?php

namespace app\modules\aftersales\controllers;

use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\aftersales\models\AfterSalesReturn;
use app\components\Controller;
use app\modules\aftersales\models\AfterRefundCode;
use app\modules\aftersales\models\RefundCode;

class ReturnController extends Controller {

    /**
     * @author alpha
     * @desc
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function actionIndex() {
        $url = $_SERVER['REQUEST_URI'];
        $params = \Yii::$app->request->getBodyParams();
      
        $_REQUEST['platform_code'] = AfterSalesOrder::ORDER_SEARCH_CONDITION_FROM_ALL;
        $model = new AfterSalesReturn();
        $dataProvider = $model->searchList($params, $url);
        return $this->renderList('index', [
                    'model' => $model,
                    'dataProvider' => $dataProvider,
                    'platform_code' => $_REQUEST['platform_code'],
                    'url' => $url,
        ]);
    }

    /**
     *
     * @throws \yii\db\Exception
     */
    public function actionDeletereturn() {
        $after_sales_id = $this->request->getQueryParam('after_sales_id');
        $dbTransaction = AfterSalesReturn::getDb()->beginTransaction();
        $afterSalesReceiptModel = AfterSalesReturn::findOne_overwrite(['after_sale_id' => $after_sales_id]);
        if (empty($afterSalesReceiptModel)) {
            $this->_showMessage('无退货售后单', false);
        }
        if (!AfterSalesReturn::delete_overwrite($after_sales_id)) {
            $this->_showMessage('退货售后单删除失败', false);
        }
        $dbTransaction->commit();
        $this->_showMessage('删除成功', true, null, false, null, 'top.window.location.reload();');
    }

    /*     * **
     * 获取退货编码
     * ** */

    public function actionRefundcode() {
        $order_id = $this->request->post('order_id');
        if (empty($order_id)) {
            return json_encode(['state' => 0, 'msg' => "订单不存在"]);
        }
        //生产退货编码
        $code = AfterRefundCode::GetRefundcode();   
        //保存数据
        //事物开始
        $connection = \Yii::$app->db->beginTransaction();
        try {
            //插入退货编码关联表数据
            $refundorder = new AfterRefundCode();
            $refundorder->refund_code = $code;
            $refundorder->order_id = $order_id;
            $refundorder->create_time = date('y-m-d H:i:s');
            $refundorder->save();
            //更新已使有退货编码 
            RefundCode::updateAll(['is_use' => 1], ['code' => $code]);
            $connection->commit();
            return json_encode(['state' => 1, 'msg' => "获取成功",'code'=>$code]);
        } catch (\Exception $e) {
            $connection->rollBack();
            return json_encode(['state' => 0, 'msg' => "获取失败"]);
        }
    }

}

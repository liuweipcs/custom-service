<?php
/**
 * @desc 订单接口
 * @author Administrator
 *
 */
namespace app\modules\services\modules\api\models;
use app\modules\services\modules\api\models\ApiAbstract;
use app\modules\aftersales\models\AfterSalesOrder;
class AfterSalesOrderApi extends ApiAbstract
{
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see ApiAbstract::init()
     */
    public function init()
    {
        parent::init();
        $_errorMaps = array(
            '3001' => 'OrderId is Required',
            '3002' => 'PlatformCode is Required',
        );
        foreach ($_errorMaps as $code => $msg)
        {
            $this->addError($code, $msg);
        }
    }
    
    /**
     * @desc 获取订单售后单
     * @param unknown $params
     */
    public function getOrderAfterSalesOrders($params)
    {
        $orderId = isset($params['orderId']) ? trim($params['orderId']) : null;
        $platformCode = isset($params['platformCode']) ? trim($params['platformCode']) : null;
        $afterSalesOrders = [];
        if (empty($orderId))
            $this->triggerError('3001');
        if (empty($platformCode))
            $this->triggerError('3002');
        $afterSalesOrderInfos = AfterSalesOrder::getByOrderId($platformCode, $orderId);
        if (!empty($afterSalesOrderInfos))
        {
           foreach ($afterSalesOrderInfos as $key => $afterSalesOrderInfo)
           {
               $afterSalesOrders[$key] = $afterSalesOrderInfo->attributes;
               $afterSalesOrders[$key]['type_text'] = AfterSalesOrder::getOrderTypeList($afterSalesOrderInfo->type);
               $afterSalesOrders[$key]['status_text'] = AfterSalesOrder::getOrderStatusList($afterSalesOrderInfo->status);
               $extension = [];
               $items = [];
               //查找售后单扩展信息
               $afterSalesModel = AfterSalesOrder::getAfterSalesModel($afterSalesOrderInfo->type);
               if (!empty($afterSalesModel))
               {
                   $afterSalesExtension = $afterSalesModel->findById($afterSalesOrderInfo->after_sale_id);
                   if (!empty($afterSalesExtension))
                   {
                       $extension = $afterSalesExtension->attributes;
                       $afterSalesOrderDetails = $afterSalesModel->getAfterSalesOrderDetails($afterSalesOrderInfo->after_sale_id);
                       if (!empty($afterSalesOrderDetails))
                       {
                           foreach ($afterSalesOrderDetails as $afterSalesOrderDetail)
                           {
                                $items[] = $afterSalesOrderDetail->attributes;
                           }
                       }
                   }
               }
               $afterSalesOrders[$key]['extension'] = $extension;
               $afterSalesOrders[$key]['extension']['items'] = $items;
           } 
        }
        $response = new \stdClass();
        $response->ack = true;
        $response->afterSalesOrders = $afterSalesOrders;
        $this->sendResponse('200', $response);
    }
}
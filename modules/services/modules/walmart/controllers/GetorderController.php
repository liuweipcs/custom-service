<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/11 0011
 * Time: 下午 3:51
 */

namespace app\modules\services\modules\walmart\controllers;


use app\modules\services\modules\walmart\models\GetOrder;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use app\components\Controller;
use app\modules\orders\models\OrderWalmartItem;
class GetorderController extends Controller
{
    private $ebayAccountModel;
    private $accountId;
    private $apiTaskModel;
    private $errorCode = 0;

    private $send_failure_times = 2;

    public function actionGetorder()
    {
        $platformCode = Platform::PLATFORM_CODE_WALMART;
        $platform_order_id = isset($_REQUEST['platform_order_id']) ? $_REQUEST['platform_order_id'] : '';
        $accountName = isset($_REQUEST['account_name']) ? $_REQUEST['account_name'] : '';
        if(empty($platform_order_id))
            $this->_showMessage('无效的订单号',false);
        if (empty($accountName))
            $this->_showMessage('无效订单账号ID', false);
        $accountModel = new Account();
        try{
            $OrderModel = new GetOrder($accountName);
            $OrderModel->setRequest();
//            $url = $OrderModel->url.$platform_order_id;
            $url = "https://marketplace.walmartapis.com/v3/orders?purchaseOrderId={$platform_order_id}";
            
            $result = $OrderModel->handleResponse($url); //获取请求API返回的数据
            if($result){
                $res = OrderWalmartItem::saveData($result);
                echo '<pre>';
                var_dump($res);
                echo '</pre>';
                die;
            }

        }
        catch (\Exception $e)
        {
            $this->_showMessage($e->getMessage(),false);
        }
    }
}
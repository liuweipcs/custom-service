<?php
namespace app\modules\orders\controllers;

use app\modules\systems\models\AftersaleManage;
use Yii;
use app\components\Controller;
use app\modules\orders\models\PlatformRefundOrder;
use app\modules\accounts\models\Account;
use app\modules\services\modules\mall\models\MallGetRefundOrder;
use app\modules\accounts\models\Platform;
use yii\helpers\Url;

class RefundController extends Controller
{

    /**
     * 退款订单
     */
    public function actionList()
    {
        $model = new PlatformRefundOrder();
        //获取查询参数
        $params = Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);
        //echo "<pre>";
       // print_r($dataProvider);die;
        return $this->renderList('list', [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }

    /**
     * MALL平台通过订单号获取退款单
     */
    public function actionGetrefund(){

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();

            $account_id = $data['account_id'];
            $order_id = $data['order_id'];


            if(empty($account_id) || $account_id == ' '){
                $this->_showMessage('请选择账号', false);
            }
            if(empty($order_id)){
                $this->_showMessage('请输入订单号', false);
            }

            $account = Account::findOne($account_id);

            $mall = new MallGetRefundOrder();

            $result = $mall->refundOrder($account, $order_id);

            if (!$result) {
                $this->_showMessage(Yii::t('system', '拉取失败'), false);
            }else{
                $extraJs = 'top.refreshTable("' . Url::toRoute('/orders/refund/list') . '");';
                $this->_showMessage(Yii::t('system', '操作成功'), true, null, false, null, $extraJs);
            }
        }

        $account = PlatformRefundOrder::accountDropdown(Platform::PLATFORM_CODE_MALL);
        $this->isPopup = true;
        return $this->renderList('refund_list', [
            'account' => $account,
        ]);


    }
    /**
     * 匹配售后规则，自动创建售后单
     */
    public function actionAutocreateaftersale()
    {
        $id = Yii::$app->request->post('id', 0);

        if (empty($id)) {
            die(json_encode([
                'code' => 0,
                'message' => 'ID不能为空',
            ]));
        }

        $refundOrder = PlatformRefundOrder::findOne($id);
        if (empty($refundOrder)) {
            die(json_encode([
                'code' => 0,
                'message' => '找不到退款订单信息',
            ]));
        }

        $rules = AftersaleManage::getMatchAfterSaleOrderRule($refundOrder->platform_code, $refundOrder->platform_order_id, $refundOrder->reason);
        if (empty($rules)) {
            die(json_encode([
                'code' => 0,
                'message' => '没有匹配到售后单规则',
                'data' => ['platform_order_id' => $refundOrder->platform_order_id],
            ]));
        }

        $result = AftersaleManage::autoCreateAfterSaleOrder($refundOrder->platform_code, $refundOrder->platform_order_id, $refundOrder->reason, '', $refundOrder->amount, $refundOrder->refund_time);
        if (empty($result)) {
            die(json_encode([
                'code' => 0,
                'message' => '创建售后单失败',
                'data' => ['platform_order_id' => $refundOrder->platform_order_id],
            ]));
        }

        //平台退款订单
        $refundOrder->is_aftersale = 1;
        $refundOrder->is_match_rule = 1;
        $refundOrder->save();

        die(json_encode([
            'code' => 1,
            'message' => '创建售后单成功',
            'data' => ['platform_order_id' => $refundOrder->platform_order_id],
        ]));
    }

    /**
     * 获取平台原因及对应的账号
     */
    public function actionReason()
    {
        $platformCode = Yii::$app->request->post('platform_code', '');
         
        $query = PlatformRefundOrder::find()
            ->select('reason as reason_key,reason')
            ->andWhere(['<>', 'reason', ''])
            ->groupBy('reason');
 
        if (!empty($platformCode)) {
            $query->andWhere(['platform_code' => $platformCode]);
        }

        $reason = $query->asArray()->all();
      
        if (!empty($reason)) {
            $reason = array_column($reason, 'reason', 'reason_key');
        }
      //获取对应的账号
       $account= Account::find()->select('id,account_name')->andWhere(['platform_code' => $platformCode])->asArray()->all();
        
       if(!empty($account)){
           $account = array_column($account, 'account_name', 'id'); 
       }
        die(json_encode([
            'code' => 1,
            'message' => '成功',
            'data' => $reason,
            'account'=>$account,
        ]));
    }
}
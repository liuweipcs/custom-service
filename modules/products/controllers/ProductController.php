<?php

namespace app\modules\products\controllers;

use app\components\Controller;
use app\modules\systems\models\ErpProductApi;
use Yii;
use app\common\VHelper;

class ProductController extends Controller
{
    /**
     * @desc 根据sku获取产品信息
     */
    public function actionGetproduct()
    {
        $sku = $this->request->getQueryParam('sku');
        if (empty($sku)) {
            echo json_encode(['code' => 400,'message'=>'输入sku!']);
            exit;
        }
        $productModel = new ErpProductApi();
        $data = ['sku' => $sku];
        $result = $productModel->getProductData($data);

        if ($result->ack == true && !empty($result->datas)) {
            $data['code'] = 200;
            $data['data'] = json_decode(json_encode($result->datas), true);
        } else {
            $data['code'] = 400;
            $data['message'] = '未获取到sku标题信息';
        }


        echo json_encode($data);
    }

    /**
     * 获取产品的库存和在途数量
     */
    public function actionGetproductstockandoncount()
    {
        $sku = Yii::$app->request->get('sku', '');
        $warehouseCode = Yii::$app->request->get('warehouseCode', '');

        if (empty($sku) || empty($warehouseCode)) {
            die(json_encode([
                'code' => 0,
                'msg' => 'sku和仓库code不能为空',
            ]));
        }

        $data = VHelper::getProductStockAndOnCount($sku, $warehouseCode);
        if (empty($data)) {
            die(json_encode([
                'code' => 0,
                'msg' => '获取数据为空',
            ]));
        } else {
            die(json_encode([
                'code' => 1,
                'msg' => '成功',
                'data' => $data,
            ]));
        }
    }
}

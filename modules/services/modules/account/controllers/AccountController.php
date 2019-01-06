<?php
namespace app\modules\services\modules\account\controllers;

use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\orders\models\Order;
use Yii;
use yii\helpers\Url;
use app\common\VHelper;
use yii\web\Controller;
use yii\web\HttpException;

class AccountController extends Controller
{

    // 接受新的erp帐号并存入数据库
    public function actionAccount()
    {
        $params = Yii::$app->request->post();

        if(!isset($params['secretKey']) || empty($params['secretKey']))
        {
            return false;
        }

        $secretKey_config = include Yii::getAlias('@app') . '/config/erp_api.php';

        if($params['secretKey'] != $secretKey_config['sercetKey'])
        {
            return false;
        }

        $account_model = new Account();
        $account_model->platform_code = $params['platform_code'];
        $account_model->account_name = $params['account_name'];
        $account_model->account_short_name = $params['account_short_name'];
        $account_model->old_account_id = $params['id'];
        $account_model->site_code = isset($params['site_code']) && !empty($params['site_code']) ? $params['site_code'] : null;
        $account_model->email = isset($params['email']) && !empty($params['email']) ? $params['email'] : null;

        if(!$account_model->save())
        {
            return false;
        }
        else
        {
            return true;
        }

    }

    // 获取erp所有帐号并导入到数据库
    public function actionAccountupdate()
    {
        $platform_code = \Yii::$app->request->getQueryParam('platform_code');
        if(empty($platform_code))
            return 'no platform_code';

        $user_name = 'account_name';
        $id = 'id';
        switch($platform_code)
        {
            case Platform::PLATFORM_CODE_AMAZON:
            case Platform::PLATFORM_CODE_WALMART:
                $user_name = 'account_name';
                break;
            case Platform::PLATFORM_CODE_ALI:
            case Platform::PLATFORM_CODE_STREET:
                $user_name = 'account';
                break;
            case Platform::PLATFORM_CODE_WISH:
                $user_name = 'account';
                $id = 'wish_id';
                break;
            case Platform::PLATFORM_CODE_EB:
                $user_name = 'user_name';
                break;
            case Platform::PLATFORM_CODE_MALL:
                $user_name = 'user_name';
                break;
            case Platform::PLATFORM_CODE_CDISCOUNT:
            case Platform::PLATFORM_CODE_LAZADA:
            case Platform::PLATFORM_CODE_SHOPEE:
                $user_name = 'seller_name';
        }

        if(empty($user_name))
            return 'fault platform_code';

        $datas = Account::getPlatformAccountsFromErp($platform_code);
        var_dump(count($datas));

        foreach($datas as $key =>$data)
        {
           /* if($data->status != 1)
            {
                continue;
            }*/
            $accountModel = Account::find()->where(['old_account_id'=>$data->$id,'platform_code'=>$platform_code])->one();
            if(empty($accountModel))
                $accountModel = new Account();
            $accountModel->platform_code = $platform_code;
            $accountModel->account_name = isset($data->$user_name) ? $data->$user_name : '';
            $accountModel->account_short_name = isset($data->short_name) ? $data->short_name : '';
            $accountModel->status = $data->status == 1 ? 1 : 0;
            $accountModel->old_account_id = isset($data->$id) ? $data->$id : 0;
            $accountModel->site_code = isset($data->site) ? $data->site : '';
            if(isset($data->user_token) && !empty($data->user_token))
                $accountModel->user_token = $data->user_token ;

            if(isset($data->email) && !empty($data->emial))
                $accountModel->email = $data->email;

            try{
                $accountModel->save();
            }
            catch (\Exception $e)
            {
                return $e->getMessage();
            }
        }
    }

    /**
     * 补充售后单中帐号信息
     */
    public function actionAddaccount()
    {
        $platform_code = Yii::$app->request->getQueryParam('platform_code');

        $models = AfterSalesOrder::find()->where('account_id is null');
        if(!empty($platform_code))
        {
            $models->andWhere(['platform_code'=>$platform_code]);
        }
        $models = $models->limit(100)->all();

        foreach($models as $model)
        {
            //查询订单信息
            $order_info = Order::getOrderStackByOrderId($model->platform_code,'',$model->order_id);
            if(empty($order_info))
                continue;

            $accountModel = Account::getHistoryAccountInfo($order_info->info->account_id,$model->platform_code);
            if(empty($accountModel))
                continue;

            $model->account_id = $accountModel->id;
            $model->modify_time = $model->modify_time;
            $model->save();

        }
    }

}
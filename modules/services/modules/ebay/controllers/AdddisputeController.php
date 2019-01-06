<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/6 0006
 * Time: 下午 12:15
 */

namespace app\modules\services\modules\ebay\controllers;

use app\modules\mails\models\EbayAddDispute;
use app\modules\services\modules\ebay\models\AddDispute;
use yii\web\Controller;
class AdddisputeController extends Controller
{
    public function actionIndex()
    {
        if(isset($_GET['account']))
        {
            $account = $_GET['account'];
            if(is_numeric($account) && $account > 0 && $account%1 === 0)
            {
                $nowDate = date('Y-m-d H:i:s');
                $responseIds = EbayAddDispute::find()->select('id')->where('account_id='.$account.' and status=0 and (send_status=0 or (send_status=1 and TIMESTAMPDIFF(HOUR,send_date,"'.$nowDate.'")>20))')->asArray()->all();
                if(empty($responseIds))
                    \Yii::$app->end();
                $responseIds = array_column($responseIds,'id');
                EbayAddDispute::updateAll(['send_status'=>1,'send_date'=>$nowDate],['id'=>$responseIds]);
                $responseModels = EbayAddDispute::find()->where(['id'=>$responseIds])->all();
                foreach ($responseModels as $responseModel)
                {
                    $model = new AddDispute($responseModel);
                    $model->handleResponse();
                }
            }
        }
        else
        {
            $accounts = EbayAddDispute::find()->select('account_id')->distinct()->where(['status'=>0])->asArray()->all();
            if(!empty($accounts))
            {
                foreach($accounts as $accountV)
                {
                    VHelper::runThreadSOCKET(Url::toRoute(array('/services/ebay/adddispute/index','account'=>$accountV['account_id'])));
                    sleep(2);
                }
            }
            else
            {
                exit('没有要回复的AddDisputes');
            }
        }

    }

}
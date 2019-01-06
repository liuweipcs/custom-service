<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/29 0029
 * Time: 下午 12:33
 */

namespace app\modules\services\modules\ebay\controllers;

use app\modules\mails\models\EbayFeedbackResponse;
use app\modules\services\modules\ebay\models\RespondToFeedback;
use yii\web\Controller;
use app\common\VHelper;
class RespondtofeedbackController extends Controller
{
    public function actionIndex()
    {
        if(isset($_GET['account']))
        {
            $account = $_GET['account'];
        
            if(is_numeric($account) && $account > 0 && $account%1 === 0)
            {
                ignore_user_abort(true);
                set_time_limit(600);
                $responseModels = EbayFeedbackResponse::find()->where(['account_id'=>$account,'status'=>0])->all();
                foreach ($responseModels as $responseModel)
                {
                    $model = new RespondToFeedback($responseModel);
                    $model->handleResponse();
                }
            }

        }
        else
        {
            $accounts = EbayFeedbackResponse::find()->select('account_id')->distinct()->where(['status'=>0])->asArray()->all();
            if(!empty($accounts))
            {
                foreach($accounts as $accountV)
                {
                    VHelper::runThreadSOCKET(Url::toRoute(array('/services/ebay/respondtofeedback/index','account'=>$accountV['account_id'])));
                    sleep(2);
                }
            }
            else
            {
                exit('没有要回复的feedback');
            }
        }

    }
}
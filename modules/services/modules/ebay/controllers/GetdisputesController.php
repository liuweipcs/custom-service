<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/4 0004
 * Time: 下午 4:09
 */

namespace app\modules\services\modules\ebay\controllers;

use app\modules\mails\models\EbayDisputes;
use app\modules\services\modules\ebay\models\GetUserCases;
use app\modules\services\modules\ebay\models\GetUserDisputes;
use app\modules\services\modules\ebay\models\PostOrderAPI;
use PhpImap\Exception;
use yii\web\Controller;
use app\modules\products\models\EbaySiteMapAccount;
use app\modules\systems\models\EbayAccount;
class GetdisputesController extends Controller
{
//    public function actionIndex()
//    {
//        $model = new GetUserCases(6);
//        $model->fromDate = '2016-06-01T00:00:00.000Z';
//        $model->siteID = 0;
//        $model->handleResponse();
//        $model = new GetUserDisputes(6);
//        $model->siteID = 0;
//        $model->handleResponse();
//    }

    private $site;
    private $ebayAccount;
    public function actionIndex()
    {
        if(isset($_REQUEST['account']))
        {
            $account = $_REQUEST['account'];
//            file_put_contents('F:\testContent\text.txt',$account.PHP_EOL,FILE_APPEND);
            $siteids = EbaySiteMapAccount::find()->select('siteid')->distinct()->where('ebay_account_id=:ebay_account_id',[':ebay_account_id'=>$account])->asArray()->all();
            if(!empty($siteids))
            {
                set_time_limit(0);
                $accountModel = EbayAccount::findOne($account);
                foreach ($siteids as $siteid)
                {
                    $maxReceiveDate = EbayDisputes::find()->select('max(creation_date)')->where('siteid=:siteid and account_id=:account_id',['siteid'=>$siteid['siteid'],':account_id'=>$account])->asArray()->one()['max(creation_date)'];
                    if(empty($maxReceiveDate))
                        $startTime = date('Y-m-d\TH:i:s',strtotime('-60 days'));//'2016-06-01T00:00:00';//date('Y').'-01-01T00:00:00';
                    else
                        $startTime = date('Y-m-d\TH:i:s',strtotime($maxReceiveDate));
                    $endTime = date('Y-m-d\TH:i:s');
                    if(strcmp($endTime,$startTime) < 1)
                        continue;
                    $getCaseModel = new GetUserCases($accountModel);
                    $getCaseModel->fromDate = $startTime.'.000Z';
                    $getCaseModel->toDate = $endTime.'.000Z';
                    $getCaseModel->siteID = $siteid['siteid'];
                    try{
                        $getCaseModel->handleResponse();
                    }catch(Exception $e){
                        echo $e->getMessage();
                        continue;
                    }
                }
            }
        }
        else
        {
            $accounts = EbaySiteMapAccount::find()->select('ebay_account_id')->distinct()->where('is_delete=0')->asArray()->all();
            if(!empty($accounts))
            {
                foreach($accounts as $accountV)
                {
                    VHelper::runThreadSOCKET(Url::toRoute(array('/services/ebay/getdisputes/index','account'=>$accountV['ebay_account_id'])));
                    sleep(2);
                }
            }
            else
            {
                exit('{{%ebay_site_map_account}}没有账号数据');
            }
        }
    }

    public function actionIndex1()
    {
        if(isset($_REQUEST['account']))
        {
            $account = $_REQUEST['account'];
            $siteids = EbaySiteMapAccount::find()->select('siteid')->distinct()->where('ebay_account_id=:ebay_account_id',[':ebay_account_id'=>$account])->asArray()->all();
            if(!empty($siteids))
            {
//                findClass($siteids,1);
                set_time_limit(0);
                $accountModel = EbayAccount::findOne($account);
                $this->ebayAccount = $accountModel->id;
                $serverUrl = 'https://api.ebay.com/post-order/v2/casemanagement/search';
                foreach ($siteids as $siteid)
                {
                    $this->site = $siteid['siteid'];
                    echo $this->site,'<br/>';
                    $maxReceiveDate = EbayDisputes::find()->select('max(creation_date)')->where('siteid=:siteid and account_id=:account_id',['siteid'=>$siteid['siteid'],':account_id'=>$account])->asArray()->one()['max(creation_date)'];
                    if(empty($maxReceiveDate))
                        $startTime = date('Y-m-d\TH:i:s',strtotime('-60 days')).'.000Z';//'2016-06-01T00:00:00';//date('Y').'-01-01T00:00:00';
                    else
                        $startTime = date('Y-m-d\TH:i:s',strtotime($maxReceiveDate)).'.000Z';
                    $endTime = date('Y-m-d\TH:i:s').'.000Z';
                    if(strcmp($endTime,$startTime) < 1)
                        continue;
                    $urlParams = ['case_creation_date_range_from'=>$startTime,'case_creation_date_range_to'=>$endTime,'limit'=>25,'offset'=>1];
                    try{
//                        $this->api($accountModel->user_token,$siteid['siteid'],$serverUrl,$urlParams,'get');
                        $this->searchApi($accountModel->user_token,null,$serverUrl,$urlParams,'get');
                    }catch(Exception $e){
                        echo $e->getMessage();
                        continue;
                    }

                }
            }
        }
        else
        {
            $accounts = EbaySiteMapAccount::find()->select('ebay_account_id')->distinct()->where('is_delete=0')->asArray()->all();
            if(!empty($accounts))
            {
                foreach($accounts as $accountV)
                {
                    VHelper::runThreadSOCKET(Url::toRoute(array('/services/ebay/getdisputes/index','account'=>$accountV['ebay_account_id'])));
                    sleep(2);
                }
            }
            else
            {
                exit('{{%ebay_site_map_account}}没有账号数据');
            }
        }
    }

    protected function searchApi($token,$site,$serverUrl,$params,$method)
    {
        $api = new PostOrderAPI($token,$site,$serverUrl,$method);
        $api->urlParams = $params;
        $response = (array)$api->sendHttpRequest();
//        if($this->site == 100)
            findClass($response,1);
        $this->handleResponse($response);
        if($response['paginationOutput']->totalPages > $params['offset'])
        {
            $params['offset']++;
            $this->searchApi($token,$site,$serverUrl,$params,$method);
        }
    }

    protected function handleResponse($data)
    {
        foreach ($data['members'] as $member)
        {
            $member = (array)$member;
            $ebayDisputesModel = EbayDisputes::findOne(['case_id'=>$member['caseId']]);
            if(empty($ebayDisputesModel))
                $ebayDisputesModel = new EbayDisputes();
            $ebayDisputesModel->buyer = $member['buyer'];
            $ebayDisputesModel->case_id = $member['caseId'];
            $ebayDisputesModel->case_status = $member['caseStatusEnum'];
            $ebayDisputesModel->currency = $member['claimAmount']->currency;
            $ebayDisputesModel->case_amount = $member['claimAmount']->value;
            $ebayDisputesModel->creation_date = $member['creationDate']->value;
            $ebayDisputesModel->item_id = $member['itemId'];
            $ebayDisputesModel->last_modified_date = $member['lastModifiedDate']->value;
            $ebayDisputesModel->seller = $member['seller'];
            $ebayDisputesModel->transaction_id = $member['transactionId'];
            $ebayDisputesModel->account_id = $this->ebayAccount;
            $ebayDisputesModel->siteid = $this->site;
            $flag = $ebayDisputesModel->save();
            if($member['caseId'] == 5134887654)
                findClass($member,1,0);
            //echo $ebayDisputesModel->id,'----',$member['caseId'],'----',$ebayDisputesModel->siteid;findClass($flag,1,0);echo '<hr>';
        }
    }

    public function actionTest($account)
    {
        $accountModel = EbayAccount::findOne($account);
        //$api = new PostOrderAPI($accountModel->user_token,'','https://api.ebay.com/post-order/v2/casemanagement/5135128392','get');
        $api = new PostOrderAPI($accountModel->user_token,'','https://api.ebay.com/post-order/v2/return/search','get');
        $response = $api->sendHttpRequest();
        findClass($response,1);
    }

}
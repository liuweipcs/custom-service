<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/27 0027
 * Time: 下午 12:02
 */

namespace app\modules\services\modules\ebay\controllers;

use app\modules\services\modules\ebay\models\GetFeedback;
use PhpImap\Exception;
use yii\web\Controller;
use app\modules\systems\models\EbayAccount;
use app\modules\products\models\EbaySiteMapAccount;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\accounts\models\Account;
use app\modules\systems\models\EbayApiTask;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\EbayFeedback;
use app\common\VHelper;
use app\modules\reports\models\FeedbackStatistics;

class GetfeedbackController extends Controller
{
    public function actionIndex()
    {
        if (isset($_REQUEST['account'])) {
            $account = $_REQUEST['account'];
            $siteids = EbaySiteMapAccount::find()->select('siteid')->distinct()->where('ebay_account_id=:ebay_account_id', [':ebay_account_id' => $account])->asArray()->all();
//            findClass($siteids,1);
            if (!empty($siteids)) {
                ignore_user_abort(true);
                set_time_limit(36000);
                $accountModel = EbayAccount::find()->where('id=:id', [':id' => $account])->one();
                foreach ($siteids as $siteid) {
                    $model         = new GetFeedback($accountModel);
                    $model->siteID = $siteid['siteid'];
//                    $model->CommentType = ['Neutral'];
                    $model->handleResponse();
//                    findClass($model->response,1);
                }
            }
        } else {
            $accounts = EbaySiteMapAccount::find()->select('ebay_account_id')->distinct()->where('is_delete=0')->asArray()->all();
            if (!empty($accounts)) {
                foreach ($accounts as $accountV) {
                    VHelper::runThreadSOCKET(Url::toRoute(array('/services/ebay/getfeedback/index', 'account' => $accountV['ebay_account_id'])));
                    sleep(2);
                }
            } else {
                exit('{{%ebay_site_map_account}}没有账号数据');
            }
        }
    }

    public function actionFeedback()
    {
        if (isset($_REQUEST['id'])) {
            $account     = trim($_REQUEST['id']);
            $accountName = Account::findById((int)$account)->account_name;
            $erpAccount  = Account::getAccountFromErp(Platform::PLATFORM_CODE_EB, $accountName);
            if (empty($erpAccount))
                exit('无法获取账号信息。');
            if (EbayApiTask::checkIsRunning(AccountTaskQueue::TASK_TYPE_FEEDBACK, $account)) {
                echo "account:{$account};Task Running." . PHP_EOL;

                //继续下一个账号
                $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB,
                    AccountTaskQueue::TASK_TYPE_FEEDBACK);
                if (!empty($accountTask)) {
                    //在队列里面删除该记录
                    $accountId = $accountTask->account_id;
                    $accountTask->delete();
                    VHelper::throwTheader('/services/ebay/getfeedback/feedback', ['id' => $accountId]);
                }
                exit('DONE');
            }
            ignore_user_abort(true);
            set_time_limit(7200);
            $apiTaskModel            = new EbayApiTask();
            $apiTaskModel->task_name = AccountTaskQueue::TASK_TYPE_FEEDBACK;
//            $apiTaskModel->siteid = $siteId;
            $apiTaskModel->account_id  = $account;
            $apiTaskModel->exec_status = 1;
            $apiTaskModel->start_time  = date('Y-m-d H:i:s');
            $apiTaskModel->save();
            //拉数据
            $model                   = new GetFeedback();
            $model->accountId        = $account;
            $model->ebayApiTaskModel = &$apiTaskModel;
            $model->setUserToken($erpAccount->user_token);
            try {
                $model->handleResponse();
            } catch (Exception $e) {
                $errorInfo = $e->getMessage();
            }

            $apiTaskModel->exec_status = 2;
            $apiTaskModel->end_time    = date('Y-m-d H:i:s');
            if (isset($errorInfo)) {
                $apiTaskModel->status = 1;
                $apiTaskModel->error  .= '[错误码：0。' . $errorInfo . ']';
            } else {
                $apiTaskModel->status = empty($apiTaskModel->status) ? 3 : $apiTaskModel->status;
            }
            $apiTaskModel->save();

            $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_EB,
                AccountTaskQueue::TASK_TYPE_FEEDBACK);
            if (!empty($accountTask)) {
                //在队列里面删除该记录
                $accountId = $accountTask->account_id;
                $accountTask->delete();
                VHelper::throwTheader('/services/ebay/getfeedback/feedback', ['id' => $accountId]);
            }
            exit('DONE');
        } else {
            $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_EB, Account::STATUS_VALID);
            if (!empty($accountList)) {
                foreach ($accountList as $account) {
                    echo $account->id, '<br/>';
                    ob_flush();
                    flush();
                    if (AccountTaskQueue::find()->where(['account_id' => $account->id, 'type' => AccountTaskQueue::TASK_TYPE_FEEDBACK, 'platform_code' => $account->platform_code])->exists()) {
                        continue;
                    }

                    $accountTaskQenue                = new AccountTaskQueue();
                    $accountTaskQenue->account_id    = $account->id;
                    $accountTaskQenue->type          = AccountTaskQueue::TASK_TYPE_FEEDBACK;
                    $accountTaskQenue->platform_code = $account->platform_code;
                    $accountTaskQenue->create_time   = time();
                    $accountTaskQenue->save(false);
                }
            }
            $taskList = AccountTaskQueue::getTaskList(['platform_code' => Platform::PLATFORM_CODE_EB, 'type' => AccountTaskQueue::TASK_TYPE_FEEDBACK]);

            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
                    VHelper::throwTheader('/services/ebay/getfeedback/feedback', ['id' => $accountId]);
                    sleep(2);
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
    }

    /**
     * 获取feedback
     * @return bool
     */
    public function actionGetbynotify()
    {
        $content = file_get_contents("php://input");

        if (empty($content))
            return false;

        $content = simplexml_load_string($content)->children('soapenv', true)->Body->children('urn:ebay:apis:eBLBaseComponents')->GetFeedbackResponse;

        $feedbackDetail    = $content->FeedbackDetailArray->FeedbackDetail;
        $feedback_id       = $feedbackDetail->FeedbackID->__toString();
        $ebayFeedbackModel = EbayFeedback::findOne(['feedback_id' => $feedback_id]);
        $isUpdate          = true;
        if (empty($ebayFeedbackModel)) {
            $ebayFeedbackModel = new EbayFeedback();
            $isUpdate          = false;
        }
        $accountId = $content->ExternalUserData->__toString();
        if ($accountId) {
            $accountInfo = Account::find()->where(['old_account_id' => trim($accountId), 'platform_code' => 'EB'])->asArray()->one();
            if ($accountInfo) {
                $accountId = $accountInfo['id'];
            }
        }
        $ebayFeedbackModel->account_id = $accountId;
        $ebayFeedbackModel->siteid     = 0;
        if (isset($feedbackDetail->CommentingUser))
            $ebayFeedbackModel->commenting_user = $feedbackDetail->CommentingUser->__toString();
        if (isset($feedbackDetail->CommentingUserScore))
            $ebayFeedbackModel->commenting_user_score = $feedbackDetail->CommentingUserScore->__toString();
        if (isset($feedbackDetail->CommentText))
            $ebayFeedbackModel->comment_text = $feedbackDetail->CommentText->__toString();
        if (isset($feedbackDetail->CommentTime))
            $ebayFeedbackModel->comment_time = date('Y-m-d H:i:s', strtotime($feedbackDetail->CommentTime->__toString()));
        if (isset($feedbackDetail->CommentType))
            $ebayFeedbackModel->comment_type = array_search($feedbackDetail->CommentType->__toString(), EbayFeedback::$commentTypeMap);
        if (isset($feedbackDetail->ItemID))
            $ebayFeedbackModel->item_id = $feedbackDetail->ItemID->__toString();
        if (isset($feedbackDetail->ItemTitle))
            $ebayFeedbackModel->item_title = $feedbackDetail->ItemTitle->__toString();
        if (isset($feedbackDetail->ItemPrice)) {
            $ebayFeedbackModel->item_price = $feedbackDetail->ItemPrice->__toString();
            $ebayFeedbackModel->currency   = $feedbackDetail->ItemPrice->attributes()['currencyID'];
        }
        if (isset($feedbackDetail->Role))
            $ebayFeedbackModel->role = array_search($feedbackDetail->Role->__toString(), EbayFeedback::$roleMap);
        $ebayFeedbackModel->feedback_id = $feedback_id;
        if (isset($feedbackDetail->TransactionID))
            $ebayFeedbackModel->transaction_id = $feedbackDetail->TransactionID->__toString();
        if (isset($feedbackDetail->OrderLineItemID))
            $ebayFeedbackModel->order_line_item_id = $feedbackDetail->OrderLineItemID->__toString();
        if (isset($feedbackDetail->FeedbackResponse))
            $ebayFeedbackModel->feedback_response = $feedbackDetail->FeedbackResponse->__toString();
        $ebayFeedbackModel->item_title = $feedbackDetail->ItemTitle->__toString();
        if ($isUpdate) {
            $ebayFeedbackModel->modify_by   = 'system';
            $ebayFeedbackModel->modify_time = date('Y-m-d H:i:s');
        } else {
            $ebayFeedbackModel->create_by   = 'system';
            $ebayFeedbackModel->create_time = date('Y-m-d H:i:s');
        }

        if ($ebayFeedbackModel->save()){
            //将评价数据插入到评价统计表
            $feedbackStatistics = FeedbackStatistics::findOne(['feedback_id'=>$feedback_id,'platform_code'=>Platform::PLATFORM_CODE_EB]);
            if(empty($feedbackStatistics)){
                $feedbackStatistics = new FeedbackStatistics();
                $feedbackStatistics->status = 0;
            }
            $feedbackStatistics->platform_code = Platform::PLATFORM_CODE_EB;
            $feedbackStatistics->account_id = $accountId;
            if(isset($feedbackDetail->CommentType))
                $feedbackStatistics->comment_type = array_search($feedbackDetail->CommentType->__toString(),EbayFeedback::$commentTypeMap);
            $feedbackStatistics->create_time = date('Y-m-d H:i:s');
            $feedbackStatistics->feedback_id = $feedback_id;
            if(isset($feedbackDetail->CommentTime))
                $feedbackStatistics->comment_time = date('Y-m-d H:i:s',strtotime($feedbackDetail->CommentTime->__toString()));
            $feedbackStatistics->save(false);
            return true;
        }else{
            return false;
        }

    }

    /**
     * 修改feedback
     * @return bool
     */
    public function actionFeedbackreceive()
    {

        $content = file_get_contents("php://input");

        if (empty($content))
            return false;

        $content = simplexml_load_string($content)->children('soapenv', true)->Body->children('urn:ebay:apis:eBLBaseComponents')->GetFeedbackResponse;

        $feedbackDetail    = $content->FeedbackDetailArray->FeedbackDetail;
        $feedback_id       = $feedbackDetail->FeedbackID->__toString();
        $ebayFeedbackModel = EbayFeedback::findOne(['feedback_id' => $feedback_id]);
        $isUpdate          = true;
        if (empty($ebayFeedbackModel)) {
            $ebayFeedbackModel = new EbayFeedback();
            $isUpdate          = false;
        }
        $accountId = $content->ExternalUserData->__toString();
        if ($accountId) {
            $accountInfo = Account::find()->where(['old_account_id' => trim($accountId), 'platform_code' => 'EB'])->asArray()->one();
            if ($accountInfo) {
                $accountId = $accountInfo['id'];
            }
        }
        $ebayFeedbackModel->account_id = $accountId;
        $ebayFeedbackModel->siteid     = 0;
        if (isset($feedbackDetail->CommentingUser))
            $ebayFeedbackModel->commenting_user = $feedbackDetail->CommentingUser->__toString();
        if (isset($feedbackDetail->CommentingUserScore))
            $ebayFeedbackModel->commenting_user_score = $feedbackDetail->CommentingUserScore->__toString();
        if (isset($feedbackDetail->CommentText))
            $ebayFeedbackModel->comment_text = $feedbackDetail->CommentText->__toString();
        if (isset($feedbackDetail->CommentTime))
            $ebayFeedbackModel->comment_time = date('Y-m-d H:i:s', strtotime($feedbackDetail->CommentTime->__toString()));
        if (isset($feedbackDetail->CommentType))
            $ebayFeedbackModel->comment_type = array_search($feedbackDetail->CommentType->__toString(), EbayFeedback::$commentTypeMap);
        if (isset($feedbackDetail->ItemID))
            $ebayFeedbackModel->item_id = $feedbackDetail->ItemID->__toString();
        if (isset($feedbackDetail->ItemTitle))
            $ebayFeedbackModel->item_title = $feedbackDetail->ItemTitle->__toString();
        if (isset($feedbackDetail->ItemPrice)) {
            $ebayFeedbackModel->item_price = $feedbackDetail->ItemPrice->__toString();
            $ebayFeedbackModel->currency   = $feedbackDetail->ItemPrice->attributes()['currencyID'];
        }
        if (isset($feedbackDetail->Role))
            $ebayFeedbackModel->role = array_search($feedbackDetail->Role->__toString(), EbayFeedback::$roleMap);
        $ebayFeedbackModel->feedback_id = $feedback_id;
        if (isset($feedbackDetail->TransactionID))
            $ebayFeedbackModel->transaction_id = $feedbackDetail->TransactionID->__toString();
        if (isset($feedbackDetail->OrderLineItemID))
            $ebayFeedbackModel->order_line_item_id = $feedbackDetail->OrderLineItemID->__toString();
        if (isset($feedbackDetail->FeedbackResponse))
            $ebayFeedbackModel->feedback_response = $feedbackDetail->FeedbackResponse->__toString();
        $ebayFeedbackModel->item_title = $feedbackDetail->ItemTitle->__toString();
        if ($isUpdate) {
            $ebayFeedbackModel->modify_by   = 'system';
            $ebayFeedbackModel->modify_time = date('Y-m-d H:i:s');
        } else {
            $ebayFeedbackModel->create_by   = 'system';
            $ebayFeedbackModel->create_time = date('Y-m-d H:i:s');
        }

        if ($ebayFeedbackModel->save())
            return true;
        else
            return false;
    }
}
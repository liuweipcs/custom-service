<?php

namespace app\modules\services\modules\aliexpress\controllers;

use app\modules\services\modules\aliexpress\models\AliexpressEvaluate;
use Yii;
use yii\web\Controller;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\services\modules\aliexpress\models\Detail;
use app\modules\services\modules\aliexpress\models\UpdateMsgProcessed;
use app\modules\services\modules\aliexpress\models\UpdateMsgRead;
use app\modules\services\modules\aliexpress\models\AddMsg;
use app\modules\services\modules\aliexpress\models\UpdateMsgRank;
use app\modules\services\modules\aliexpress\models\QueryIssueList;
use app\modules\services\modules\aliexpress\models\Evaluation;
use app\modules\mails\models\AccountTaskQueue;
use app\common\VHelper;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use app\modules\services\modules\aliexpress\models\Findissuedetails;
use app\modules\services\modules\aliexpress\models\AliexpressMessage;
use app\modules\services\modules\aliexpress\models\QueryMsgDetailList;
use app\modules\services\modules\aliexpress\models\app\modules\services\modules\aliexpress\models;
use app\modules\services\modules\amazon\components\Mail;
use app\modules\services\modules\aliexpress\models\AliexpressIssue;
use app\modules\accounts\models\Aliexpressaccountservicescoreinfo;
use app\modules\accounts\models\Aliexpressaccountlevelinfo;
use app\modules\accounts\models\MinxinAliexpressQuerydsrddisputeproductlistRequest;
use app\modules\accounts\models\Aliexpressaccountdisputeproductlist;
use app\modules\mails\models\AliexpressDisputeList;
use app\modules\mails\models\AliexpressDisputeDetail;
use app\modules\mails\models\AliexpressDisputeSolution;

class AliexpressController extends Controller
{
    /**
     * 抓取站内信关系列表
     */
    public function actionMail()
    {
        /*获取速卖通账号*/
        set_time_limit(1800);
        if (isset($_REQUEST['id'])) {
            $account = trim($_REQUEST['id']);
            //$obj = new QueryMsgDetailList();
            //$obj->getMsgList($account);
            $aliexpressMessage = new AliexpressMessage();
            $flag = $aliexpressMessage->getAccountMessage($account);
            //sleep(2);
            //当前账号拉完数据，接着去拉下个账号的数据
            $accountTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_ALI,
                AccountTaskQueue::TASK_TYPE_MESSAGE);
            if (!empty($accountTask)) {
                //在队列里面删除该记录
                $accountId = $accountTask->account_id;
                $accountTask->delete();
                VHelper::throwTheader('/services/aliexpress/aliexpress/mail', ['id' => $accountId]);
            }
            exit('DONE');
        } else {
            //去账号任务队列里面去查询还有没有完成的账号如果有，取若干账号去拉取数据
            //$list = AccountTaskQueue::findByPlatform(Platform::PLATFORM_CODE_ALI, AccountTaskQueue::TASK_TYPE_MESSAGE);
            //if (empty($list))
            //{
            //去账号表获取所有账号插入到账号队列
            $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_ALI, Account::STATUS_VALID);
            if (!empty($accountList)) {
                $list = [];
                foreach ($accountList as $account) {
                    $accountTaskQenue = new AccountTaskQueue();
                    $accountTaskQenue->account_id = $account->id;
                    $accountTaskQenue->type = AccountTaskQueue::TASK_TYPE_MESSAGE;
                    $accountTaskQenue->platform_code = $account->platform_code;
                    $accountTaskQenue->create_time = time();
                    $flag = $accountTaskQenue->save(false);
                    if ($flag)
                        $list[] = $accountTaskQenue;
                }
            }
            //}
            $taskList = AccountTaskQueue::getTaskList(['type' => AccountTaskQueue::TASK_TYPE_MESSAGE,
                'platform_code' => Platform::PLATFORM_CODE_ALI]);
            if (!empty($taskList)) {
                foreach ($taskList as $accountId) {
                    VHelper::throwTheader('/services/aliexpress/aliexpress/mail', ['id' => $accountId]);
                    sleep(2);
                }
            } else {
                die('there are no any account!');
            }
            exit('DONE');
        }
    }


    /**
     * 站内信/订单留言查询详情列表
     */
    public function actionDetail()
    {
        set_time_limit(3600);
        if (isset($_REQUEST['id'])) {

            $id = trim($_REQUEST['id']);
            $orderObj = new Detail();
            $orderObj->getDetailList($id);
        } else {
            $AliAccounts = UebModel::model('AliexpressAccount')->getAccountList();
            if (!empty($AliAccounts)) {
                foreach ($AliAccounts as $id => $val) {
                    MHelper::runThreadSOCKET('/services/aliexpress/aliexpress/detail/id/' . $id);
                    sleep(2);
                }
            } else {
                die('there are no any account!');
            }
        }
    }

    /*站内信/订单留言更新处理状态 */
    public function actionProcessed()
    {
        $account_id = $_REQUEST['account_id'];
        $channel_id = $_REQUEST['channel_id'];
        $deal_stat = $_REQUEST['deal_stat'];
        $orderObj = new UpdateMsgProcessed();
        $orderObj->getProcessed($account_id, $channel_id, $deal_stat);
    }


    /*站内信/订单留言更新已读*/
    public function actionRead()
    {
        $account_id = $_REQUEST['account_id'];
        $channel_id = $_REQUEST['channel_id'];
        $msgSources = $_REQUEST['msg_sources'];
        $orderObj = new UpdateMsgRead();
        $orderObj->getRead($account_id, $channel_id, $msgSources);
    }

    /*新增站内信/订单留言*/
    public function actionAddmsg()
    {
        $data = [
            'account_id' => $_REQUEST['account_id'],//店铺账号ID
            'channel_id' => $_REQUEST['channel_id'],//关系ID
            'content' => $_REQUEST['content']//留言内容
        ];
        $orderObj = new AddMsg();
        $orderObj->getAddMsg($data);
    }

    /*站内信/订单留言打标签 */
    public function actionRank()
    {
        $data = [
            'account_id' => $_REQUEST['account_id'],//店铺账号ID
            'channel_id' => $_REQUEST['channel_id'],//关系ID
            'rank' => $_REQUEST['rank']//留言内容
        ];
        $orderObj = new UpdateMsgRank();
        $orderObj->getRank($data);
    }


    /**
     * 拉取速卖通纠纷列表
     */
    public function actionGetissuelist()
    {
        try {
            //避免服务器拉取信息超时
            set_time_limit(0);
            //客户端关闭，仍继续执行
            ignore_user_abort(true);
            ini_set('memory_limit', '256M');
            //获取账号ID(客服系统的)
            $accountId = Yii::$app->request->get('id', 0);

            if (!empty($accountId)) {
                $issue = new AliexpressIssue();
                //获取速卖通的纠纷详情，协商方案，操作记录，图片附件等
                $issue->getAccountIssue($accountId);
                //从队列中获取下一个任务
                $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_ALI, AccountTaskQueue::LIST_OF_DISPUTES);

                //如果不为空，则请求下一个接口
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    //非阻塞的请求接口
                    VHelper::throwTheader('/services/aliexpress/aliexpress/getissuelist', ['id' => $nextAccountId]);
                }
                //结束拉取信息
                die('GET ISSUE END');
            } else {

                //获取账号信息(客服系统的)
                $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_ALI, Account::STATUS_VALID);
                if (!empty($accountList)) {
                    //循环的把需要拉取纠纷信息的账号加入任务队列中
                    foreach ($accountList as $account) {
                        $accountTaskQenue = new AccountTaskQueue();
                        $accountTaskQenue->account_id = $account->id;
                        $accountTaskQenue->type = AccountTaskQueue::LIST_OF_DISPUTES;
                        $accountTaskQenue->platform_code = $account->platform_code;
                        $accountTaskQenue->create_time = time();
                        $accountTaskQenue->save(false);
                    }
                }
                //默认先从队列中取5条
                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::LIST_OF_DISPUTES,
                    'platform_code' => Platform::PLATFORM_CODE_ALI
                ]);

                //循环的请求接口
                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        VHelper::throwTheader('/services/aliexpress/aliexpress/getissuelist', ['id' => $accountId]);
                        sleep(2);
                    }
                }
                die('RUN GET ISSUE');
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        }
    }

    /**
     * 更新速卖通纠纷列表
     */
    public function actionUpdateissuelist()
    {
        try {
            //避免服务器拉取信息超时
            set_time_limit(0);
            //客户端关闭，仍继续执行
            ignore_user_abort(true);
            ini_set('memory_limit', '256M');
            //获取账号ID(客服系统的)
            $info = Yii::$app->request->get('id', 0);

            if (!empty($info)) {
                $info_arr = explode('-',$info);
                $accountId = $info_arr[0];
                $issueId = $info_arr[1];
                $buyerLoginId = $info_arr[2];
                $issue = new AliexpressIssue();
                //更新已完结的速卖通的纠纷详情
                $sss = $issue->updateissueinfo($accountId, $issueId, $buyerLoginId);

                //从队列中获取下一个任务
                $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_ALI, AccountTaskQueue::UPDATE_LIST_OF_DISPUTES);

                //如果不为空，则请求下一个接口
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    //非阻塞的请求接口
                    VHelper::throwTheader('/services/aliexpress/aliexpress/updateissuelist', ['id' => $nextAccountId]);
                }
                //结束拉取信息
                die('GET ISSUE END');
            } else {

                //获取速卖通纠纷详情的account_id platform_dispute_id buyer_login_id
                $disputeList = AliexpressDisputeDetail::find()->select('account_id,platform_dispute_id,buyer_login_id')->where(['solution_owner'=>null])->asArray()->all();

                if (!empty($disputeList)) {
                    foreach ($disputeList as $key => $value) {
                        $disputeList[$key] = implode('-', $value);
                    }
                    //循环的把需要拉取纠纷信息的账号加入任务队列中
                    foreach ($disputeList as $dispute) {
                        $accountTaskQenue = new AccountTaskQueue();
                        $accountTaskQenue->account_id = $dispute;
                        $accountTaskQenue->type = AccountTaskQueue::UPDATE_LIST_OF_DISPUTES;
                        $accountTaskQenue->platform_code = Platform::PLATFORM_CODE_ALI;
                        $accountTaskQenue->create_time = time();
                        $accountTaskQenue->save(false);
                    }
                }
                //默认先从队列中取5条
                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::UPDATE_LIST_OF_DISPUTES,
                    'platform_code' => Platform::PLATFORM_CODE_ALI
                ]);

                //循环的请求接口
                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        // echo 1;echo "<pre>";print_r($accountId);exit;
                        VHelper::throwTheader('/services/aliexpress/aliexpress/updateissuelist', ['id' => $accountId]);
                        sleep(2);
                    }
                }
                die('RUN GET ISSUE');
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        }
    }

    /**
     * 拉取速卖通评价列表
     */
    public function actionGetevaluatelist()
    {
        try {
            //避免服务器拉取信息超时
            set_time_limit(0);
            //客户端关闭，仍继续执行
            ignore_user_abort(true);

            //获取账号ID(客服系统的)
            $accountId = Yii::$app->request->get('id', 0);

            if (!empty($accountId)) {
                $issue = new AliexpressEvaluate();
                //获取速卖通的评价
                $issue->getAccountEvaluate($accountId);
                //从队列中获取下一个任务
                $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_ALI, AccountTaskQueue::LIST_OF_EVALUATE);

                //如果不为空，则请求下一个接口
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    //非阻塞的请求接口
                    VHelper::throwTheader('/services/aliexpress/aliexpress/getevaluatelist', ['id' => $nextAccountId]);
                }
                //结束拉取信息
                die('GET EVALUATE END');
            } else {
                //获取当前任务队列数
                $count = AccountTaskQueue::find()
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_ALI])
                    ->andWhere(['type' => AccountTaskQueue::LIST_OF_EVALUATE])
                    ->count();

                //如果任务队列中没有任务了才可继续添加任务，避免任务堆积。
                if (empty($count)) {
                    //获取账号信息(客服系统的)
                    $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_ALI, Account::STATUS_VALID);
                    if (!empty($accountList)) {
                        //循环的把需要拉取纠纷信息的账号加入任务队列中
                        foreach ($accountList as $account) {
                            $accountTaskQenue = new AccountTaskQueue();
                            $accountTaskQenue->account_id = $account->id;
                            $accountTaskQenue->type = AccountTaskQueue::LIST_OF_EVALUATE;
                            $accountTaskQenue->platform_code = $account->platform_code;
                            $accountTaskQenue->create_time = time();
                            $accountTaskQenue->save(false);
                        }
                    }
                }

                //默认先从队列中取5条
                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::LIST_OF_EVALUATE,
                    'platform_code' => Platform::PLATFORM_CODE_ALI
                ]);

                //循环的请求接口
                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        VHelper::throwTheader('/services/aliexpress/aliexpress/getevaluatelist', ['id' => $accountId]);
                        sleep(2);
                    }
                }
                die('RUN GET EVALUATE');
            }

        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        }
    }

    /**
     * 拉取速卖通中差评的评价
     */
    public function actionGetmidevaluatelist()
    {
        try {
            //避免服务器拉取信息超时
            set_time_limit(0);
            //客户端关闭，仍继续执行
            ignore_user_abort(true);

            //获取账号ID(客服系统的)
            $accountId = Yii::$app->request->get('id', 0);

            if (!empty($accountId)) {
                $issue = new AliexpressEvaluate();
                //获取速卖通中差评的评价
                $issue->getAccountMidEvaluate($accountId);
                //从队列中获取下一个任务
                $nextTask = AccountTaskQueue::getNextAccountTask(Platform::PLATFORM_CODE_ALI, AccountTaskQueue::LIST_OF_MID_EVALUATE);

                //如果不为空，则请求下一个接口
                if (!empty($nextTask)) {
                    $nextAccountId = $nextTask->account_id;
                    //从队列中删除该任务
                    $nextTask->delete();
                    //非阻塞的请求接口
                    VHelper::throwTheader('/services/aliexpress/aliexpress/getmidevaluatelist', ['id' => $nextAccountId]);
                }
                //结束拉取信息
                die('GET EVALUATE END');
            } else {
                //获取当前任务队列数
                $count = AccountTaskQueue::find()
                    ->andWhere(['platform_code' => Platform::PLATFORM_CODE_ALI])
                    ->andWhere(['type' => AccountTaskQueue::LIST_OF_MID_EVALUATE])
                    ->count();

                //如果任务队列中没有任务了才可继续添加任务，避免任务堆积。
                if (empty($count)) {
                    //获取账号信息(客服系统的)
                    $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_ALI, Account::STATUS_VALID);
                    if (!empty($accountList)) {
                        //循环的把需要拉取纠纷信息的账号加入任务队列中
                        foreach ($accountList as $account) {
                            $accountTaskQenue = new AccountTaskQueue();
                            $accountTaskQenue->account_id = $account->id;
                            $accountTaskQenue->type = AccountTaskQueue::LIST_OF_MID_EVALUATE;
                            $accountTaskQenue->platform_code = $account->platform_code;
                            $accountTaskQenue->create_time = time();
                            $accountTaskQenue->save(false);
                        }
                    }
                }

                //默认先从队列中取5条
                $taskList = AccountTaskQueue::getTaskList([
                    'type' => AccountTaskQueue::LIST_OF_MID_EVALUATE,
                    'platform_code' => Platform::PLATFORM_CODE_ALI
                ]);

                //循环的请求接口
                if (!empty($taskList)) {
                    foreach ($taskList as $accountId) {
                        VHelper::throwTheader('/services/aliexpress/aliexpress/getmidevaluatelist', ['id' => $accountId]);
                        sleep(2);
                    }
                }
                die('RUN GET EVALUATE');
            }

        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        }
    }

    /*获取单个纠纷详情 */
    //local.customer.com/services/aliexpress/aliexpress/getissuedetail
    public function actionGetissuedetail()
    {

        $data = [
            'id' => '8',
            'issueId' => '600216781749011'
        ];

        $orderObj = new Findissuedetails();
        $orderObj->getIssueDetail($data);
    }

    /*
   * 拉取评价
   * */
    public function actionEvaluate()
    {
        if ($_REQUEST['id']) {
            $account_id = $_REQUEST['id'];
            $orderObj = new Evaluation();
            $orderObj->getAccountInformation($account_id);
        }
    }

    public function actionTestmail()
    {
        $email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : '';
        if (empty($email)) exit('Email Empty');
        $mailer = Mail::instance($email)->setTo('361481974@qq.com')->setSubject('Re: Enquiry from Amazon customer Jordan Lee (Order: 204-0949392-8797957)')->setTextBody('Dear  Jordan Lee
thank you for your kindly reply.
i have already issued you full refund through amazon,
it will take about 3 days to 4 days to let the money get into your account, please do not worry,

if you have any problems, please feel free to contact me, i will try my best to help you,.
best wishes ')->setFrom($email);
        $path = '';
        if (!empty($path)) {
            foreach ($path as $v) {
                $mailer->addAttach($v);
            }
        }
        $result = $mailer->sendmail();
        var_dump($mailer->getErrorMsg());
        var_dump($result);
        exit;
    }
    /**
     * 拉取每日服务分
     */
    public function actionServicescoreinfo(){

        $AliAccounts = AliexpressAccount::find()->all();
        foreach($AliAccounts as $AliAccount){

            $evaluate = new AliexpressEvaluate;
            $servicescoreinfo = $evaluate->getServicescoreinfo($AliAccount);
            if(empty($servicescoreinfo)){
                continue;
            }
            $article = Aliexpressaccountservicescoreinfo::findOne(['account_id' => $AliAccount->id]);
            if($article === null) {
                $article = new Aliexpressaccountservicescoreinfo;
                $article->account_id = $AliAccount->id;
            }

            $article->prim_opr_pcate_lv2_id = $servicescoreinfo['prim_opr_pcate_lv2_id'];
            $article->prim_opr_pcate_lv2_name = $servicescoreinfo['prim_opr_pcate_lv2_name'];
            $article->check_mord_cnt = $servicescoreinfo['service_score_info_d_t_o']['check_mord_cnt'];
            $article->stat_start_date = date('Y-m-d',strtotime($servicescoreinfo['service_score_info_d_t_o']['stat_start_date']));
            $article->stat_end_date = date('Y-m-d',strtotime($servicescoreinfo['service_score_info_d_t_o']['stat_end_date']));
            $article->pulltime = date('Y-m-d');
            //服务指标信息
            $article->buy_not_sel_rate = str_replace('%','',$servicescoreinfo['service_score_info_d_t_o']['index_d_t_o']['buy_not_sel_rate']);
            $article->nr_disclaimer_issue_rate = str_replace('%','',$servicescoreinfo['service_score_info_d_t_o']['index_d_t_o']['nr_disclaimer_issue_rate']);
            $article->snad_disclaimer_issue_rate = str_replace('%','',$servicescoreinfo['service_score_info_d_t_o']['index_d_t_o']['snad_disclaimer_issue_rate']);
            //行业平均指标
            $article->average_buy_not_sel_rate = str_replace('%','',$servicescoreinfo['service_score_info_d_t_o']['industry_avg_index_d_t_o']['buy_not_sel_rate']);
            $article->average_nr_disclaimer_issue_rate = str_replace('%','',$servicescoreinfo['service_score_info_d_t_o']['industry_avg_index_d_t_o']['nr_disclaimer_issue_rate']);
            $article->average_snad_disclaimer_issue_rate = str_replace('%','',$servicescoreinfo['service_score_info_d_t_o']['industry_avg_index_d_t_o']['snad_disclaimer_issue_rate']);

            //服务得分信息
            $article->total_score = $servicescoreinfo['service_score_info_d_t_o']['score_d_t_o']['total_score'];
            $article->dsr_prod_score = $servicescoreinfo['service_score_info_d_t_o']['score_d_t_o']['dsr_prod_score'];
            $article->dsr_logis_score = $servicescoreinfo['service_score_info_d_t_o']['score_d_t_o']['dsr_logis_score'];
            $article->dsr_communicate_score = $servicescoreinfo['service_score_info_d_t_o']['score_d_t_o']['dsr_communicate_score'];
            //行业平均得分
            $article->average_total_score = $servicescoreinfo['service_score_info_d_t_o']['industry_avg_score_d_t_o']['total_score'];
            $article->average_dsr_prod_score = $servicescoreinfo['service_score_info_d_t_o']['industry_avg_score_d_t_o']['dsr_prod_score'];
            $article->average_dsr_logis_score = $servicescoreinfo['service_score_info_d_t_o']['industry_avg_score_d_t_o']['dsr_logis_score'];
            $article->average_dsr_communicate_score = $servicescoreinfo['service_score_info_d_t_o']['industry_avg_score_d_t_o']['dsr_communicate_score'];
            $article->save();
        }
    }
    /**
     *  拉取单月服务等级
     */
    public function actionLevelinfo(){
        $AliAccounts = AliexpressAccount::find()->all();
        foreach($AliAccounts as $AliAccount) {

            $evaluate = new AliexpressEvaluate;
            $servicescoreinfo = $evaluate->getQuerylevelinfo($AliAccount);
            if(empty($servicescoreinfo)){
                continue;
            }

            $article = Aliexpressaccountlevelinfo::findOne(['account_id' => $AliAccount->id]);
            if($article === null) {
                $article = new Aliexpressaccountlevelinfo;
                $article->account_id = $AliAccount->id;
            }

            $article->appraise_period = $servicescoreinfo['appraise_period'];
            $article->level = $servicescoreinfo['level'];
            $article->avg_score = $servicescoreinfo['avg_score'];
            $article->check_m_order_count = $servicescoreinfo['check_m_order_count'];
            $article->predict_avg_score = $servicescoreinfo['predict_avg_score'];
            $article->predict_end_date =  date('Y-m-d',strtotime($servicescoreinfo['predict_end_date']));
            $article->predict_level =  $servicescoreinfo['predict_level'];
            $article->predict_start_date =  date('Y-m-d',strtotime($servicescoreinfo['predict_start_date']));
            $article->end_date = date('Y-m-d',strtotime($servicescoreinfo['end_date']));
            $article->start_date = date('Y-m-d',strtotime($servicescoreinfo['start_date']));
            $article->pulltime = date('Y-m-d');

            $article->save();
        }
    }
    /**
     * 获取全店铺考核期内DSR商品描述中低分商品分页列表
     */
    public function actionDsrddisputeproductlist(){

        $AliAccounts = AliexpressAccount::find()->all();
        foreach($AliAccounts as $AliAccount) {

            $evaluate = new AliexpressEvaluate;
            $servicescoreinfo = $evaluate->getDsrddisputeproductlist($AliAccount,1,50);
            if(empty($servicescoreinfo)){
                continue;
            }
            $product = new Aliexpressaccountdisputeproductlist;
            $product->accountdisputeproductlist($servicescoreinfo['list']['ae_dispute_product_dto'],$AliAccount);
            $totnum = ceil($servicescoreinfo['total_size']/50);
            $page = 2;
            while ($page <= $totnum) {
                $services = $evaluate->getDsrddisputeproductlist($AliAccount,$page,50);
                if(empty($services)){
                    break;
                }
                $product->accountdisputeproductlist($services['list']['ae_dispute_product_dto'],$AliAccount);
                $page++;
            }
        }
    }
    public function actionTestimap()
    {
    $stream = @imap_open('{imap.163.com:993/imap/ssl/novalidate-cert}INBOX', 'sococo2014@163.com', 'happy123');
    if (!$stream)
        var_dump(imap_errors());
    var_dump($stream);exit;
    }
}

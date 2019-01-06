<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\db\Query;
use app\components\ConfigFactory;
use app\modules\mails\models\AliexpressDisputeList;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\services\modules\aliexpress\models\AliexpressOrder;
use app\modules\services\modules\aliexpress\components\TaobaoQimenApi;
use app\modules\mails\models\AliexpressIssueDetail;
use app\modules\mails\models\AliexpressIssueTmp;
use app\modules\mails\models\AliexpressInbox;
use app\modules\systems\models\ExtendTimeRule;
use app\modules\systems\models\FeedbackAccountRule;
use app\modules\systems\models\ReminderMsgRule;
use app\modules\services\modules\aliexpress\models\AliexpressTaskRecord;
use app\modules\mails\models\AliexpressEvaluateTmp;

/**
 * 处理速卖通相关的计划任务
 */
class AliexpressController extends Controller
{
    //最大页数
    const MAX_PAGE = PHP_INT_MAX;
    //每页大小
    const PAGE_SIZE = 50;

    //自动留评星级
    const AUTO_FEEDBACK_SCORE = 5;
    //自动留评内容
    const AUTO_FEEDBACK_CONTENT = 'Thank you for your support. Look forward to your next visit.';

    //8小时未付款，留言内容
    const NO_PAY_8_HOUR = 'Hi, Dear {$buyer_name}
I see you have placed this order, but haven\'t paid yet,
But this is great price ,please catch the chance .
If you have any problem,
Please contact me from here,
We would reply you immediately!';

    //24小时未付款，留言内容
    const NO_PAY_24_HOUR = 'Hi, Dear {$buyer_name}
It seems that you are a little hesitant , 
we do not have much stock in store , 
hope you can catch this chance .
any problem , please contact us in here';

    //普通的确认收货时间是60天(单位秒)
    const NORMAL_ACCEPT_GOODS_TIME = 5184000;

    //俄罗斯的确认收货时间是90天(单位秒)
    const RUS__ACCEPT_GOODS_TIME = 7776000;

    /**
     * 未付款订单催付
     * 例子：
     * yii aliexpress/remindermsg
     *
     * @param int $pageSize 每次处理数据大小
     */
    public function actionRemindermsg($pageSize = 500)
    {

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['gatewayUrl']) ? $qimenApiInfo['gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return false;
        }

        //获取速卖通客服系统账号信息
        $accountsKefu = Account::getAccounts(Platform::PLATFORM_CODE_ALI);

        if (empty($accountsKefu)) {
            return false;
        }

        //从表{{%auto_reminder_msg_rule}}中获取需要催付订单规则
        $reminderMsgRuleList = ReminderMsgRule::find()
            ->where(['platform_code' => Platform::PLATFORM_CODE_ALI, 'status' => 1])
            ->orderBy('trigger_time DESC')
            ->asArray()
            ->all();

        if (!empty($reminderMsgRuleList)) {
            foreach ($reminderMsgRuleList as $reminderMsgRule) {
                //速卖通账号id数组
                $accountIds = [];

                if ($reminderMsgRule['account_type'] == 'all') {
                    $accountIds = AliexpressAccount::find()
                        ->select('id')
                        ->andWhere(['and', ['<>', 'access_token', ''], ['<>', 'refresh_token', '']])
                        ->asArray()
                        ->column();
                } else if ($reminderMsgRule['account_type'] == 'custom') {
                    $accountIds = explode(',', $reminderMsgRule['account_ids']);
                }

                //获取速卖通账号信息
                $accounts = AliexpressAccount::getAccounts($accountIds);
                //如果账号信息为空，则跳过
                if (empty($accounts)) {
                    continue;
                }

                $query = AliexpressOrder::find();
                //未付款
                $query->andWhere(['payment_status' => 0]);
                //账号
                $query->andWhere(['in', 'account_id', $accountIds]);

                //添加时间区间，二个月之内的
                $startTime = date('Y-m-d 00:00:00', strtotime('-2 month'));
                $endTime = date('Y-m-d 23:59:59');
                $query->andWhere(['between', 'created_time', $startTime, $endTime]);

                //数据总数
                $count = $query->count();
                //执行次数
                $step = ceil($count / $pageSize);
                //循环处理催付站内信
                for ($pageCur = 1; $pageCur <= $step; $pageCur++) {
                    //计算偏移量
                    $offset = ($pageCur - 1) * $pageSize;
                    //获取数据
                    $data = $query->orderBy('created_time DESC')->offset($offset)->limit($pageSize)->all();
                    //如果数据为空，则跳过
                    if (empty($data)) {
                        continue;
                    }

                    foreach ($data as $order) {
                        //获取该订单的账号信息
                        $account = array_key_exists($order->account_id, $accounts) ? $accounts[$order->account_id] : [];

                        //如果为空，则跳过
                        if (empty($account)) {
                            continue;
                        }

                        //卖家登陆ID
                        $seller_login_id = array_key_exists($order->account_id, $accountsKefu) ? $accountsKefu[$order->account_id]['seller_id'] : '';

                        //如果卖家登陆ID或买家登陆ID为空，则跳过
                        if (empty($seller_login_id) || empty($order->buyer_user_id)) {
                            continue;
                        }

                        //注意这里的时区问题，接口返回时间是美国时间，这里临时将时区设为美国洛杉矶
                        $tz = date_default_timezone_get();
                        date_default_timezone_set('America/Los_Angeles');

                        //计算当前时间与订单创建时间差值
                        $timeDiff = time() - strtotime($order->created_time);

                        //把当前时区设回原来时区
                        date_default_timezone_set($tz);

                        //如果当前未付款时间未超过触发时间，则跳过
                        if ($timeDiff < ($reminderMsgRule['trigger_time'] * 60 * 60)) {
                            continue;
                        }

                        //判断买家ID是否在不执行催付名单中
                        if (!empty($reminderMsgRule['not_reminder_buyer'])) {
                            $notReminderBuyerArr = explode(',', $reminderMsgRule['not_reminder_buyer']);

                            if (in_array($order->buyer_id, $notReminderBuyerArr)) {
                                continue;
                            }
                        }

                        //如果该订单已经催付过一次，则不用再催付
                        $exists = AliexpressTaskRecord::find()
                            ->where([
                                'type' => 2,
                                'platform_order_id' => $order->platform_order_id,
                                'platform_code' => Platform::PLATFORM_CODE_ALI,
                                'account_id' => $account['id'],
                            ])
                            ->exists();
                        if ($exists) {
                            continue;
                        }

                        //判断同一个买家在多少小时内只催付一次
                        $buyer = AliexpressTaskRecord::find()
                            ->where([
                                'type' => 2,
                                'platform_code' => Platform::PLATFORM_CODE_ALI,
                                'account_id' => $account['id'],
                                'buyer_login_id' => $order->buyer_user_id,
                            ])
                            ->orderBy('create_time DESC')
                            ->one();

                        if (!empty($buyer)) {
                            //如果当前时间减去买家上次发送时间小于多少小时只催付一次，则跳过
                            if ((time() - strtotime($buyer->create_time)) < ($reminderMsgRule['buyer_once_time'] * 60 * 60)) {
                                continue;
                            }
                        }

                        //如果订单的创建时间，与当前时间相差1个月，则不需要发送催付
                        if (!empty($order) && !empty($order->created_time)) {
                            if ((time() - strtotime($order->created_time)) > (30 * 86400)) {
                                continue;
                            }
                        }

                        //通过接口获取最新的订单信息
                        $orderInfo = AliexpressOrder::getOrderInfo($order->platform_order_id, $order->account_id);
                        if (!empty($orderInfo) && !empty($orderInfo['target']['order_status']) && $orderInfo['target']['order_status'] == 'FINISH') {
                            //如果该订单已完成，则不需要发送催付
                            continue;
                        }
                        if (!empty($orderInfo) && !empty($orderInfo['target']['gmt_trade_end'])) {
                            //订单结束时间不为空，说明订单已经结束，不需要发送催付
                            continue;
                        }

                        //将{$buyer_name}替换成买家全名
                        $msg = str_replace('{$buyer_name}', $order->buyer_id, $reminderMsgRule['content']);

                        //创建奇门请求api
                        $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
                        $taobaoQimenApi->setGatewayUrl($gatewayUrl);

                        //配置请求参数
                        $request = new \MinxinAliexpressNewstationletterRequest();
                        //设置账号id
                        $request->setAccountId($account['id']);
                        //设置卖家登录帐号
                        $request->setSellerId($seller_login_id);
                        //设置买家登录帐号
                        $request->setBuyerId($order->buyer_user_id);
                        //设置消息类型
                        $request->setMessageType('order');
                        //设置订单id
                        $request->setExternId($order->platform_order_id);
                        //设置催付消息
                        $request->setContent($msg);

                        $taobaoQimenApi->doRequest($request);
                        //如果催付成功，则记录该订单
                        if ($taobaoQimenApi->isSuccess()) {
                            //记录该订单已催付过
                            $taskRecord = new AliexpressTaskRecord();
                            $taskRecord->type = 2;
                            $taskRecord->platform_code = Platform::PLATFORM_CODE_ALI;
                            $taskRecord->platform_order_id = $order->platform_order_id;
                            $taskRecord->account_id = $account['id'];
                            $taskRecord->buyer_login_id = $order->buyer_user_id;
                            $taskRecord->seller_login_id = $seller_login_id;
                            $taskRecord->create_by = 'system';
                            $taskRecord->create_time = date('Y-m-d H:i:s');
                            $taskRecord->modify_by = 'system';
                            $taskRecord->modify_time = date('Y-m-d H:i:s');
                            $taskRecord->save();

                            echo "order id : {$order->platform_order_id} reminder msg success\n";
                        } else {
                            echo "order id : {$order->platform_order_id} reminder msg error\n";
                        }

                        unset($request);
                        unset($taobaoQimenApi);
                    }
                }

                unset($query);
                unset($accounts);
            }
        }
    }


    /**
     * 拉取待卖家评价的订单信息，如果没有纠纷自动留评价
     * 例子：
     * yii aliexpress/autofeedback
     * yii aliexpress/autofeedback 0 50
     *
     * @param int $accountId 账号ID，默认获取所有
     * @param int $pageSize 每次处理数据大小
     */
    public function actionAutofeedback($accountId = 0, $pageSize = 50)
    {
        //速卖通账号id数组
        $accountIds = [];
        if (!empty($accountId)) {
            $accountIds[] = $accountId;
        }

        //从{{%auto_feedback_account_rule}}表中获取需要自动留评的账号
        $accountRuleList = FeedbackAccountRule::find()->where(['platform_code' => Platform::PLATFORM_CODE_ALI, 'status' => 1])->asArray()->all();
        if (!empty($accountRuleList)) {
            foreach ($accountRuleList as $accountRule) {
                if ($accountRule['account_type'] == 'all') {
                    $accountIds = AliexpressAccount::find()
                        ->select('id')
                        ->andWhere(['and', ['<>', 'access_token', ''], ['<>', 'refresh_token', '']])
                        ->asArray()
                        ->column();
                    break;
                } else if ($accountRule['account_type'] == 'custom') {
                    $accountIds = array_merge($accountIds, explode(',', $accountRule['account_ids']));
                }
            }
            //去重
            $accountIds = array_unique($accountIds);
        }

        //获取账号信息
        $accounts = AliexpressAccount::getAccounts($accountIds);

        if (empty($accounts)) {
            return false;
        }

        //拉取数据每页个数，最大50
        if (($pageSize > self::PAGE_SIZE) || ($pageSize <= 0)) {
            $pageSize = self::PAGE_SIZE;
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['erp_gatewayUrl']) ? $qimenApiInfo['erp_gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return false;
        }

        foreach ($accounts as $account) {
            //创建奇门请求api
            $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
            $taobaoQimenApi->setGatewayUrl($gatewayUrl);

            $request = new \MinxinAliexpressOrderevaluatedRequest();
            //设置账号ID
            $request->setAccountId($account['id']);
            //设置当前页数
            $request->setCurrentPage(1);
            //设置分页大小
            $request->setPageSize($pageSize);
            $taobaoQimenApi->doRequest($request);
            if (!$taobaoQimenApi->isSuccess()) {
                continue;
            }

            //获取数据
            $data = $taobaoQimenApi->getResponse();
            //转换成数组
            $data = json_decode(json_encode($data), true);
            //如果为空，则跳过
            if (empty($data)) {
                continue;
            }

            //总数
            $total = $data['total_item'];
            //循环次数
            $step = ceil($total / $pageSize);

            for ($pageCur = 1; $pageCur <= $step; $pageCur++) {
                $request->setCurrentPage($pageCur);
                $taobaoQimenApi->setGatewayUrl($gatewayUrl);
                $taobaoQimenApi->doRequest($request);
                if (!$taobaoQimenApi->isSuccess()) {
                    continue;
                }

                //获取数据
                $list = $taobaoQimenApi->getResponse();
                //转换成数组
                $list = json_decode(json_encode($list), true, 512, JSON_BIGINT_AS_STRING);

                //如果为空，则跳过
                if (empty($list) || empty($list['result_list'])) {
                    continue;
                }
                //获取待评价订单id数组
                $orderIds = [];
                foreach ($list['result_list']['json'] as $item) {
                    $item = json_decode($item, true, 512, JSON_BIGINT_AS_STRING);
                    if (empty($item)) {
                        continue;
                    }
                    //注意这里订单ID需要转换一下，不然会出现精度溢出
                    $orderIds[] = $item['orderId'];
                }
                //获取订单纠纷
                $issueList = AliexpressDisputeList::find()
                    ->select('platform_order_id,platform_dispute_id')
                    ->where(['in', 'platform_order_id', $orderIds])
                    ->asArray()
                    ->all();
                $issueList = array_column($issueList, 'platform_dispute_id', 'platform_order_id');

                //循环处理要留评的订单
                foreach ($orderIds as $orderId) {
                    //如果该订单没有纠纷，则留评
                    if (!array_key_exists($orderId, $issueList)) {
                        //配置请求参数
                        $req = new \MinxinAliexpressAutomaticassessmentRequest();
                        //设置账号id
                        $req->setAccountId($account['id']);
                        //设置留评内容
                        $req->setFeedbackContent(self::AUTO_FEEDBACK_CONTENT);
                        //设置订单id
                        $req->setOrderId($orderId);
                        //设置留评星级
                        $req->setScore(self::AUTO_FEEDBACK_SCORE);

                        $taobaoQimenApi->setGatewayUrl($gatewayUrl);
                        $taobaoQimenApi->doRequest($req);

                        if ($taobaoQimenApi->isSuccess()) {
                            echo "order id : {$orderId} auto feedback success\n";
                        } else {
                            echo "order id : {$orderId} auto feedback error\n";
                        }

                        unset($req);
                    }
                }
            }

            unset($request);
            unset($taobaoQimenApi);
        }
    }

    /**
     * 拉取速卖通纠纷列表
     * 例子：
     * yii aliexpress/pullissuelist
     *
     * @param int $accountId 账号ID，默认获取所有
     * @param int $pageSize 每次处理数据大小
     */
    public function actionPullissuelist($accountId = 0, $pageSize = 50)
    {
        //获取速卖通账号信息
        $accounts = AliexpressAccount::getAccounts($accountId);

        //erp账号ID转客服账号ID
        $erpToKefuAccountIds = Account::erpToKefuAccountIds(Platform::PLATFORM_CODE_ALI);

        if (empty($accounts) || empty($erpToKefuAccountIds)) {
            return false;
        }

        //拉取数据每页个数，最大50
        if (($pageSize > self::PAGE_SIZE) || ($pageSize <= 0)) {
            $pageSize = self::PAGE_SIZE;
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['gatewayUrl']) ? $qimenApiInfo['gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return false;
        }

        //分别拉取三种状态的纠纷
        $issueStatus = ['processing', 'finish', 'canceled_issue'];

        //循环速卖通账号
        foreach ($accounts as $account) {
            foreach ($issueStatus as $status) {
                //创建奇门请求api
                $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
                $taobaoQimenApi->setGatewayUrl($gatewayUrl);

                //配置请求参数
                $request = new \MinxinAliexpressDisputelistinformationRequest();
                //设置账号ID
                $request->setAccountId($account['id']);
                //设置当前页数
                $request->setCurrentPage(1);
                //设置分页大小
                $request->setPageSize($pageSize);
                //设置纠纷状态
                $request->setIssueStatus($status);
                $taobaoQimenApi->doRequest($request);

                if (!$taobaoQimenApi->isSuccess()) {
                    continue;
                }
                //获取数据
                $data = $taobaoQimenApi->getResponse();
                //转换成数组
                $data = json_decode(json_encode($data), true);
                //如果为空，则跳过
                if (empty($data)) {
                    continue;
                }

                //总数
                $total = $data['total_item'];
                //循环次数
                $step = ceil($total / $pageSize);

                for ($pageCur = 1; $pageCur <= $step; $pageCur++) {
                    $request->setCurrentPage($pageCur);
                    $taobaoQimenApi->setGatewayUrl($gatewayUrl);
                    $taobaoQimenApi->doRequest($request);
                    if (!$taobaoQimenApi->isSuccess()) {
                        continue;
                    }

                    //获取数据
                    $list = $taobaoQimenApi->getResponse();
                    //转换成数组
                    $list = json_decode(json_encode($list), true, 512, JSON_BIGINT_AS_STRING);
                    //如果为空，则跳过
                    if (empty($list)) {
                        continue;
                    }

                    //构造SQL语句
                    $sql = 'INSERT INTO {{%aliexpress_dispute_list}} (
                        `platform_dispute_id`,
                        `account_id`, 
                        `buyer_login_id`,
                        `gmt_modified`, 
                        `issue_status`,
                        `gmt_create`,
                        `reason_chinese`,
                        `platform_order_id`, 
                        `platform_parent_order_id`,
                        `reason_english`,
                        `update_time`
                        ) VALUES';

                    foreach ($list['data_list']['issue_api_issue_dto'] as $issue) {
                        $kefuAccountId = array_key_exists($account['id'], $erpToKefuAccountIds) ? $erpToKefuAccountIds[$account['id']] : '';
                        $update_time = date('Y-m-d H:i:s', time());
                        $sql .= "(
                                '{$issue['issue_id']}',
                                {$kefuAccountId},
                                '{$issue['buyer_login_id']}',
                                '{$issue['gmt_modified']}',
                                '{$issue['issue_status']}',
                                '{$issue['gmt_create']}',
                                '{$issue['reason_chinese']}',
                                '{$issue['order_id']}',
                                '{$issue['parent_order_id']}',
                                '{$issue['reason_english']}',
                                '{$update_time}'
                             ),";
                    }

                    $sql = rtrim($sql, ',');
                    $sql .= 'ON DUPLICATE KEY UPDATE
                     `platform_dispute_id` = VALUES(`platform_dispute_id`),
                     `account_id` = VALUES(`account_id`),
                     `buyer_login_id` = VALUES(`buyer_login_id`),
                     `gmt_modified` = VALUES(`gmt_modified`),
                     `issue_status` = VALUES(`issue_status`),
                     `gmt_create` = VALUES(`gmt_create`),
                     `reason_chinese` = VALUES(`reason_chinese`),
                     `platform_order_id` = VALUES(`platform_order_id`),
                     `platform_parent_order_id` = VALUES(`platform_parent_order_id`),
                     `reason_english` = VALUES(`reason_english`),
                     `update_time` = VALUES(`update_time`);';

                    $result = Yii::$app->db->createCommand($sql)->execute();
                    if ($result !== false) {
                        echo "account id : {$account['id']} status : {$status} pull issue list success\n";
                    } else {
                        echo "account id : {$account['id']} status : {$status} pull issue list error\n";
                    }
                }

                unset($request);
                unset($taobaoQimenApi);
            }
        }
    }

    /**
     * 拉取速卖通订单评价
     * @param int $accountId
     * @param int $pageSize
     * @return bool
     * @throws \yii\db\Exception
     */
    public function actionAliexpressEvaluate($accountId = 0, $pageSize = 10)
    {
        //获取速卖通账号信息
        $accounts = AliexpressAccount::getAccounts($accountId);

        if (empty($accounts)) {
            return false;
        }

        //拉取数据每页个数，最大50
        if (($pageSize > self::PAGE_SIZE) || ($pageSize <= 0)) {
            $pageSize = self::PAGE_SIZE;
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['gatewayUrl']) ? $qimenApiInfo['gatewayUrl'] : '';
        $erp_gatewayUrl = !empty($qimenApiInfo['erp_gatewayUrl']) ? $qimenApiInfo['erp_gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return false;
        }
        //循环速卖通账号
        foreach ($accounts as $account) {
            /************** 获取$parent_order_ids start ***************/
            //
            $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
            $taobaoQimenApi->setGatewayUrl($erp_gatewayUrl);
            //配置请求参数
            $order_request = new \MinxinAliexpressOrderevaluatedRequest();
            //设置账号ID
            $order_request->setAccountId($account['id']);
            //设置当前页数
            $order_request->setCurrentPage(1);
            //设置分页大小
            $order_request->setPageSize($pageSize);
            $taobaoQimenApi->doRequest($order_request);
            if (!$taobaoQimenApi->isSuccess()) {
                echo $taobaoQimenApi->getErrorMessage();
                continue;
            }
            //获取数据
            $data = $taobaoQimenApi->getResponse();
            //转换成数组
            $data = json_decode(json_encode($data), true);
            /************** 获取$parent_order_ids end ***************/
            if (empty($data['result_list'])) {
                continue;
            }
            $parent_order_ids = $data['result_list']['json'];
            if (empty($parent_order_ids)) {
                continue;
            }
            //var_dump($parent_order_ids);die;
            foreach ($parent_order_ids as $parent_order_id) {
                $parent_order_id = json_decode($parent_order_id, true)['orderId'];
                //创建奇门请求api
                $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
                $taobaoQimenApi->setGatewayUrl($gatewayUrl);
                //配置请求参数
                $request = new \MinxinAliexpressEffectiveevaluationRequest();
                //设置账号ID
                $request->setAccountId($account['id']);
                $request->setParentOrderIds($parent_order_id);
                $taobaoQimenApi->doRequest($request);
                if (!$taobaoQimenApi->isSuccess()) {
                    echo $taobaoQimenApi->getErrorMessage();
                    continue;
                }
                //获取数据
                $data = $taobaoQimenApi->getResponse();
                if (empty($data)) {
                    continue;
                }
                //转换成数组
                $data = json_decode(json_encode($data), true);
                //如果为空，则跳过
                if (empty($data)) {
                    continue;
                }
                $target_list = $data['target_list'];
                if (empty($target_list)) {
                    continue;
                }
                $evaluteLists = $data['target_list']['trade_evaluation_open_dto'];

                if (empty($evaluteLists)) {
                    continue;
                }
                //构造SQL语句
                $sql = 'INSERT INTO {{%aliexpress_evaluate_list}} (
                        `buyer_evaluation`,
                        `buyer_fb_date`, 
                        `buyer_feedback`, 
                        `buyer_login_id`,
                        `buyer_reply`,
                        `gmt_create`,
                        `gmt_modified`, 
                        `gmt_order_complete`,
                        `order_id`,
                        `parent_order_id`,
                        `product_id`,
                        `seller_evaluation`,
                        `seller_fb_date`,
                        `seller_feedback`,
                        `seller_login_id`,
                        `seller_reply`,
                        `valid_date`,
                        `update_time`
                        ) VALUES';

                foreach ($evaluteLists as $evaluteList) {
                    if (empty($evaluteList)) {
                        continue;
                    }

                    //插入数据表
                    $buyer_evaluation = isset($evaluteList['buyer_evaluation']) ? trim($evaluteList['buyer_evaluation']) : 0;//
                    $buyer_fb_date = $evaluteList['buyer_fb_date'];//
                    $buyer_feedback = isset($evaluteList['buyer_feedback']) ? str_replace('"', "'", $evaluteList['buyer_feedback']) : "";
                    $buyer_login_id = isset($evaluteList['buyer_login_id']) ? $evaluteList['buyer_login_id'] : '';//
                    $buyer_reply = isset($evaluteList['buyer_reply']) ? $evaluteList['buyer_reply'] : '';
                    $gmt_create = $evaluteList['gmt_create'];//
                    $gmt_modified = isset($evaluteList['gmt_modified']) ? $evaluteList['gmt_modified'] : "";
                    $gmt_order_complete = $evaluteList['gmt_order_complete'];//
                    $order_id = isset($evaluteList['order_id']) ? $evaluteList['order_id'] : "";//
                    $parent_order_id = isset($evaluteList['parent_order_id']) ? $evaluteList['parent_order_id'] : '';//
                    $product_id = isset($evaluteList['product_id']) ? $evaluteList['product_id'] : '';//
                    $seller_evaluation = isset($evaluteList['seller_evaluation']) ? $evaluteList['seller_evaluation'] : '';
                    $seller_fb_date = isset($evaluteList['seller_fb_date']) ? $evaluteList['seller_fb_date'] : "1970-01-01 08:00:00";//
                    $seller_feedback = isset($evaluteList['seller_feedback']) ? $evaluteList['seller_feedback'] : '';
                    $seller_login_id = isset($evaluteList['seller_login_id']) ? $evaluteList['seller_login_id'] : '';//
                    $seller_reply = isset($evaluteList['seller_reply']) ? $evaluteList['seller_reply'] : '';
                    $valid_date = $evaluteList['valid_date'];//
                    $update_time = date('Y-m-d H:i:s', time());

                    $sql .= "(
                                '{$buyer_evaluation}',
                                '{$buyer_fb_date}'," .
                        '"' . $buyer_feedback . '"' . ",
                                '{$buyer_login_id}'," .
                        '"' . $buyer_reply . '"' . ",
                                '{$gmt_create}',
                                '{$gmt_modified}',
                                '{$gmt_order_complete}',
                                '{$order_id}',
                                '{$parent_order_id}',
                                '{$product_id}',
                                '{$seller_evaluation}',
                                '{$seller_fb_date}'," .
                        '"' . $seller_feedback . '"' . ",
                                '{$seller_login_id}'," .
                        '"' . $seller_reply . '"' . ",
                                '{$valid_date}',
                                '{$update_time}'
                             ),";
                }
                $sql = rtrim($sql, ',');

                $sql .= ' ON DUPLICATE KEY UPDATE
                     `buyer_evaluation` = VALUES(`buyer_evaluation`),
                     `buyer_fb_date` = VALUES(`buyer_fb_date`),
                     `buyer_feedback` = VALUES(`buyer_feedback`),
                     `buyer_login_id` = VALUES(`buyer_login_id`),
                     `buyer_reply` = VALUES(`buyer_reply`),
                     `gmt_create` = VALUES(`gmt_create`),
                     `gmt_modified` = VALUES(`gmt_modified`),
                     `gmt_order_complete` = VALUES(`gmt_order_complete`),
                     `order_id` = VALUES(`order_id`),
                     `parent_order_id` = VALUES(`parent_order_id`),
                     `product_id` = VALUES(`product_id`),
                     `seller_evaluation` = VALUES(`seller_evaluation`),
                     `seller_fb_date` = VALUES(`seller_fb_date`),
                     `seller_feedback` = VALUES(`seller_feedback`),
                     `seller_login_id` = VALUES(`seller_login_id`),
                     `seller_reply` = VALUES(`seller_reply`),
                     `valid_date` = VALUES(`valid_date`),
                     `update_time` = VALUES(`update_time`);';
                $result = Yii::$app->db->createCommand($sql)->execute();
                if ($result !== false) {
                    echo "account id : {$account['id']} evaluate :  pull evaluate list success\n";
                } else {
                    echo "account id : {$account['id']} evaluate :  pull evaluate list failed\n";
                }
                unset($request);
                unset($taobaoQimenApi);
            }
        }
    }


    /**
     * 拉取纠纷的详情
     * 例子：
     * yii aliexpress/pullissueinfo
     *
     * @param int $accountId 账号ID，默认获取所有
     * @param int $pageSize 每次处理数据大小
     */
    public function actionPullissueinfo(array $accountId = [], $pageSize = 100)
    {

        //获取速卖通账号信息
        $accounts = AliexpressAccount::getAccounts($accountId);

        if (empty($accounts)) {
            return false;
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['gatewayUrl']) ? $qimenApiInfo['gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return false;
        }

        $query = AliexpressDisputeList::find();
        //设置账号ID
        if (!empty($accountId)) {
            $query->andWhere(['account_id' => $accountId]);
        }
        //设置纠纷状态为处理中的，才拉取纠纷详情
        $query->andWhere(['issue_status' => 'processing']);

        //数据总数
        $count = $query->count();
        //执行次数
        $step = ceil($count / $pageSize);

        for ($pageCur = 1; $pageCur <= $step; $pageCur++) {
            //计算偏移量
            $offset = ($pageCur - 1) * $pageSize;
            //获取数据
            $issueList = $query->orderBy('gmt_create')->offset($offset)->limit($pageSize)->all();
            if (empty($issueList)) {
                continue;
            }

            foreach ($issueList as $issue) {
                //获取该订单的账号信息
                $account = array_key_exists($issue->account_id, $accounts) ? $accounts[$issue->account_id] : [];

                //如果为空，则跳过
                if (empty($account)) {
                    continue;
                }

                //创建奇门请求api
                $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
                $taobaoQimenApi->setGatewayUrl($gatewayUrl);

                $req = new \MinxinAliexpressObtainingconsultativedataRequest();
                $req->setAccountId($account['id']);
                $req->setIssueId($issue->platform_dispute_id);

                $taobaoQimenApi->doRequest($req);
                if (!$taobaoQimenApi->isSuccess()) {
                    continue;
                }
                $data = $taobaoQimenApi->getResponse();
                if (!empty($data->result_object)) {
                    $data = json_decode($data->result_object, true);
                } else {
                    $data = [];
                }
                //如果获取的纠纷详情数据为空，则跳过
                if (empty($data)) {
                    continue;
                }

                $info = AliexpressIssueDetail::findOne(['issue_id' => $issue->platform_dispute_id]);
                if (!empty($info)) {
                    $info->issue_id = $issue->platform_dispute_id;
                    $info->order_id = $issue->platform_order_id;
                    $info->gmt_create = date('Y-m-d H:i:s', substr($data['gmtCreate'], 0, 10));
                    $info->product_name = $data['productName'];
                    $info->product_amount = $data['productPrice']['amount'] ? $data['productPrice']['amount'] : ($data['productPrice']['cent'] / $data['productPrice']['centFactor']);
                    $info->product_currency_code = $data['productPrice']['currencyCode'];
                    $info->issue_reason_id = $data['issueReasonId'];
                    $info->issue_reason = $data['issueReason'];
                    $info->buyer_aliid = $data['buyerAliid'];
                    $info->issue_status = $data['issueStatus'];
                    $info->parent_order_id = str_replace(',', '', number_format($data['parentOrderId']));
                    $info->data = json_encode($data);

                    if ($info->save(false)) {
                        echo "issue id : {$issue->platform_dispute_id} update success\n";
                    } else {
                        echo "issue id : {$issue->platform_dispute_id} update error\n";
                    }
                } else {
                    $add = new AliexpressIssueDetail();
                    $add->issue_id = $issue->platform_dispute_id;
                    $add->order_id = $issue->platform_order_id;
                    $add->gmt_create = date('Y-m-d H:i:s', substr($data['gmtCreate'], 0, 10));
                    $add->product_name = $data['productName'];
                    $add->product_amount = $data['productPrice']['amount'] ? $data['productPrice']['amount'] : ($data['productPrice']['cent'] / $data['productPrice']['centFactor']);
                    $add->product_currency_code = $data['productPrice']['currencyCode'];
                    $add->issue_reason_id = $data['issueReasonId'];
                    $add->issue_reason = $data['issueReason'];
                    $add->buyer_aliid = $data['buyerAliid'];
                    $add->issue_status = $data['issueStatus'];
                    $add->parent_order_id = str_replace(',', '', number_format($data['parentOrderId']));
                    $add->data = json_encode($data);

                    if ($add->save(false)) {
                        echo "issue id : {$issue->platform_dispute_id} add success\n";
                    } else {
                        echo "issue id : {$issue->platform_dispute_id} add error\n";
                    }

                    unset($add);
                }

                unset($info);
                unset($data);
                unset($req);
                unset($taobaoQimenApi);
            }
        }
    }

    /**
     * 给速卖通消息打标签
     * 例子:
     * yii aliexpress/inboxmatchtags 1 10000
     * yii aliexpress/inboxmatchtags 2 10000
     *
     * @param int $accountOffset 账号的偏移量
     * @param int $accountPageSize 每次处理账号数据大小
     * @param int $inboxPageSize 每次处理速卖通消息大小
     */
    public function actionInboxmatchtags($inboxOffset = 1, $inboxPageSize = 5000)
    {
        if ($inboxOffset < 1) {
            return false;
        }

        $inboxOffset = ($inboxOffset - 1) * $inboxPageSize;
        $inboxList = AliexpressInbox::find()->orderBy('id DESC')->offset($inboxOffset)->limit($inboxPageSize)->all();

        if (empty($inboxList)) {
            echo "not data \n";
            return false;
        }

        $model = new AliexpressInbox();

        foreach ($inboxList as $inbox) {
            if ($model->matchTags($inbox)) {
                echo "inbox id : {$inbox->id} add tags success\n";
            } else {
                echo "inbox id : {$inbox->id} add tags error\n";
            }
        }
    }

    /**
     * 根据{{%auto_extend_time_rule}}表中设置的规则，自动延长收货时间
     * 例子：
     * yii aliexpress/autoextendtime
     *
     * @param int $pageSize 每次处理数据大小
     */
    public function actionAutoextendtime($pageSize = 50)
    {
        //取出速卖通自动延长收货时间规则
        $extendTimeRuleList = ExtendTimeRule::find()
            ->where(['platform_code' => Platform::PLATFORM_CODE_ALI, 'status' => 1])
            ->asArray()
            ->all();

        if (empty($extendTimeRuleList)) {
            return false;
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['erp_gatewayUrl']) ? $qimenApiInfo['erp_gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return false;
        }

        foreach ($extendTimeRuleList as $extendTimeRule) {
            $accounts = [];
            if ($extendTimeRule['account_type'] == 'all') {
                //取出所有的速卖通账号
                $accounts = AliexpressAccount::getAccounts();
            } else if ($extendTimeRule['account_type'] == 'custom') {
                //取出指定的速卖通账号
                $accounts = AliexpressAccount::getAccounts(explode(',', $extendTimeRule['account_ids']));
            }

            if (empty($accounts)) {
                continue;
            }

            //循环账号，处理自动延长收货时间
            foreach ($accounts as $account) {
                //创建奇门请求api
                $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
                $taobaoQimenApi->setGatewayUrl($gatewayUrl);

                $request = new \MinxinAliexpressOrdersimplificationqueryRequest();
                $request->setAccountId($account['id']);
                $request->setOrderStatus('WAIT_BUYER_ACCEPT_GOODS');
                $request->setPage(1);
                $request->setPageSize($pageSize);

                $taobaoQimenApi->doRequest($request);
                if (!$taobaoQimenApi->isSuccess()) {
                    continue;
                }
                //获取数据
                $data = $taobaoQimenApi->getResponse();
                //转换成数组
                $data = json_decode(json_encode($data), true);
                //如果为空，则跳过
                if (empty($data)) {
                    continue;
                }
                //总数
                $total = $data['total_item'];
                //循环次数
                $step = ceil($total / $pageSize);

                for ($pageCur = 1; $pageCur <= $step; $pageCur++) {
                    $request->setPage($pageCur);
                    $taobaoQimenApi->setGatewayUrl($gatewayUrl);
                    $taobaoQimenApi->doRequest($request);
                    if (!$taobaoQimenApi->isSuccess()) {
                        continue;
                    }

                    //获取数据
                    $list = $taobaoQimenApi->getResponse();
                    //转换成数组
                    $list = json_decode(json_encode($list), true);
                    $list = !empty($list['order_list']['simple_order_item_vo']) ? $list['order_list']['simple_order_item_vo'] : [];
                    //如果为空，则跳过
                    if (empty($list)) {
                        continue;
                    }

                    foreach ($list as $order) {
                        $order['order_id'] = str_replace(',', '', number_format($order['order_id']));

                        //如果该订单已经延长过时间，则跳过
                        $exists = AliexpressTaskRecord::find()
                            ->where([
                                'type' => 1,
                                'platform_code' => Platform::PLATFORM_CODE_ALI,
                                'platform_order_id' => $order['order_id'],
                                'account_id' => $account['id'],
                            ])
                            ->exists();
                        if ($exists) {
                            continue;
                        }

                        //确认收货时间
                        $acceptGoodsTime = self::NORMAL_ACCEPT_GOODS_TIME;

                        //第一个产品信息
                        $firstProduct = !empty($order['product_list']['simple_order_product_vo'][0]) ? $order['product_list']['simple_order_product_vo'][0] : [];
                        //如果第一个产品货币为户布，则认为该订单来自俄罗斯
                        if (!empty($firstProduct) && $firstProduct['product_unit_price_cur'] == 'RUB') {
                            $acceptGoodsTime = self::RUS__ACCEPT_GOODS_TIME;
                        }

                        $acceptGoodsTime += strtotime($order['gmt_create']);

                        //如果确认收货时间减去当前时间小于触发时间，则自动延长订单收货时间
                        if (($acceptGoodsTime - time()) < ($extendTimeRule['trigger_time'] * 60 * 60)) {
                            //配置请求参数
                            $req = new \MinxinAliexpressExtendbuyerreceipttimeRequest();
                            //设置账号ID
                            $req->setAccountId($account['id']);
                            //设置订单ID
                            $req->setOrderId($order['order_id']);
                            //设置延长时间
                            $req->setTimeExpand($extendTimeRule['extend_day']);
                            $taobaoQimenApi->setGatewayUrl($gatewayUrl);
                            $taobaoQimenApi->doRequest($req);

                            if ($taobaoQimenApi->isSuccess()) {
                                $taskRecord = new AliexpressTaskRecord();
                                $taskRecord->type = 1;
                                $taskRecord->platform_code = Platform::PLATFORM_CODE_ALI;
                                $taskRecord->platform_order_id = $order['order_id'];
                                $taskRecord->account_id = $account['id'];
                                $taskRecord->create_by = 'system';
                                $taskRecord->create_time = date('Y-m-d H:i:s');
                                $taskRecord->modify_by = 'system';
                                $taskRecord->modify_time = date('Y-m-d H:i:s');
                                $taskRecord->save();

                                echo "order id : {$order['order_id']} extend time success\n";
                            } else {
                                echo "order id : {$order['order_id']} extend time error\n";
                            }

                            unset($req);
                        }
                    }

                    unset($list);
                }

                unset($data);
                unset($request);
                unset($taobaoQimenApi);
            }
        }
    }

    /**
     * 将速卖通纠纷信息从mongodb中转移到mysql中
     * 例子：
     * yii aliexpress/processtmpissue
     */
    public function actionProcesstmpissue($limit = 500)
    {
        $issueTmp = new AliexpressIssueTmp();

        //获取待处理的纠纷信息
        $issueList = $issueTmp->getWaitingProcessList($limit);

        if (!empty($issueList)) {
            foreach ($issueList as $issue) {

                $flag = $issueTmp->processTmpIssue($issue);

                if ($flag) {
                    echo "issue id : {$issue->issue_id} process success\n";
                } else {
                    echo "issue id : {$issue->issue_id} process error {$issueTmp->getExceptionMessage()}\n";
                }
            }
        }

        die('ISSUE PROCESS END');
    }

    /**
     * 将速卖通订单评价从mongodb中转移到mysql中
     * 例子：
     * yii aliexpress/processtmpevaluate
     */
    public function actionProcesstmpevaluate($limit = 500)
    {
        $evaluateTmp = new AliexpressEvaluateTmp();

        do {
            $evaluateList = $evaluateTmp->getWaitingProcessList($limit);

            if (empty($evaluateList)) {
                break;
            }

            foreach ($evaluateList as $evaluate) {
                $flag = $evaluateTmp->processTmpEvaluate($evaluate);

                if ($flag) {
                    echo "evaluate process success\n";
                } else {
                    echo "evaluate process error\n";
                }
            }

        } while (!empty($evaluateList));

        die('EVALUATE PROCESS END');
    }
}
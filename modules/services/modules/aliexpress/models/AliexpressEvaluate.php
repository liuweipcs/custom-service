<?php

namespace app\modules\services\modules\aliexpress\models;

use app\modules\mails\models\AliexpressEvaluateTmp;
use app\modules\mails\models\AliexpressTask;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\services\modules\aliexpress\components\TaobaoQimenApi;

class AliexpressEvaluate
{
    public $errorMessage = '';

    /**
     * 获取账号的评价信息
     */
    public function getAccountEvaluate($accountId)
    {
        try {
            $aliexpressTaskModel = new AliexpressTask();
            //添加任务
            $taskId = $aliexpressTaskModel->getAdd($accountId, 'Aliexpressevaluate');
            //查询任务是否已经运行
            if ($aliexpressTaskModel->checkIsRunning($accountId, 'Aliexpressevaluate')) {
                $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
                $TaskModel->status = -1;
                $TaskModel->errors = 'Task Running';
                $TaskModel->save();
                return false;
            }
            //获取账号信息
            $accountInfo = Account::findById($accountId);
            if (empty($accountInfo)) {
                $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
                $TaskModel->status = -1;
                $TaskModel->errors = '账号不存在';
                $TaskModel->save();
                return false;
            }
            //获取erp账号信息
            $erpAccountInfo = AliexpressAccount::findById($accountInfo->old_account_id);
            if (empty($erpAccountInfo)) {
                $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
                $TaskModel->status = -1;
                $TaskModel->errors = 'ERP系统对应账号不存在';
                $TaskModel->save();
                return false;
            }
            //将当前任务状态设为运行中
            $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
            $TaskModel->status = 1;
            $TaskModel->save();

            //只拉取当天的评价
            $start_time = date('Y-m-d 00:00:00');

            $end_time = date('Y-m-d 23:59:59');
            $query = AliexpressOrder::find()->where([
                'and',
                ['between', 'created_time', $start_time, $end_time],
                ['account_id' => $erpAccountInfo->id],
            ]);
            //获取总数
            $count = $query->count();
            //接口每次最大处理数据量
            $pageSize = 50;
            //执行次数
            $step = ceil($count / $pageSize);

            for ($pageCur = 1; $pageCur <= $step; $pageCur++) {
                try {
                    $offset = ($pageCur - 1) * $pageSize;
                    $parentOrderIds = $query->select('platform_order_id')
                        ->orderBy('created_time DESC')
                        ->offset($offset)
                        ->limit($pageSize)
                        ->column();

                    if (empty($parentOrderIds)) {
                        continue;
                    }

                    //处理平台订单ID中间有-的情况
                    $tmp = [];
                    foreach ($parentOrderIds as $orderId) {
                        if (stripos($orderId, 'AL') !== false) {
                            continue;
                        }
                        //针对订单ID中有中划线的
                        if (stripos($orderId, '-') !== false) {
                            $orderIds = explode('-', $orderId);
                            if (!empty($orderIds)) {
                                foreach ($orderIds as $id) {
                                    if (is_numeric($id) && strlen($id) > 12) {
                                        $tmp[] = $id;
                                    }
                                }
                            }
                            continue;
                        }
                        //针对订单ID中有下划线的
                        if (stripos($orderId, '_') !== false) {
                            $orderIds = explode('_', $orderId);
                            if (!empty($orderIds)) {
                                foreach ($orderIds as $id) {
                                    if (is_numeric($id) && strlen($id) > 12) {
                                        $tmp[] = $id;
                                    }
                                }
                            }
                            continue;
                        }
                        $tmp[] = $orderId;
                    }
                    $parentOrderIds = $tmp;

                    $evaluateList = $this->getEvaluateList($erpAccountInfo, $parentOrderIds);

                    if (!empty($evaluateList)) {
                        $aliexpressEvaluate = new AliexpressEvaluateTmp();
                        $aliexpressEvaluate->account_id = $accountId;
                        $aliexpressEvaluate->info = json_encode($evaluateList);
                        $aliexpressEvaluate->create_time = date('Y-m-d H:i:s');
                        $aliexpressEvaluate->save(false);
                    }
                } catch (\Exception $e) {
                    //防止程序异常，中断运行
                }
            }

            //任务执行完成
            $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
            $TaskModel->status = 2;
            $TaskModel->save();
            return true;
        } catch (\Exception $e) {
            $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
            $TaskModel->status = -1;
            $TaskModel->errors = $e->getMessage();
            $TaskModel->save();
            return false;
        }
    }

    /**
     * 获取账号的中差评的评价信息
     */
    public function getAccountMidEvaluate($accountId)
    {
        try {
            $aliexpressTaskModel = new AliexpressTask();
            //添加任务
            $taskId = $aliexpressTaskModel->getAdd($accountId, 'Aliexpressemidvaluate');
            //查询任务是否已经运行
            if ($aliexpressTaskModel->checkIsRunning($accountId, 'Aliexpressemidvaluate')) {
                $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
                $TaskModel->status = -1;
                $TaskModel->errors = 'Task Running';
                $TaskModel->save();
                return false;
            }
            //获取账号信息
            $accountInfo = Account::findById($accountId);
            if (empty($accountInfo)) {
                $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
                $TaskModel->status = -1;
                $TaskModel->errors = '账号不存在';
                $TaskModel->save();
                return false;
            }
            //获取erp账号信息
            $erpAccountInfo = AliexpressAccount::findById($accountInfo->old_account_id);
            if (empty($erpAccountInfo)) {
                $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
                $TaskModel->status = -1;
                $TaskModel->errors = 'ERP系统对应账号不存在';
                $TaskModel->save();
                return false;
            }
            //将当前任务状态设为运行中
            $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
            $TaskModel->status = 1;
            $TaskModel->save();

            if (date('G') == 3) {
                //凌晨3点拉取三个月的
                $start_time = date('Y-m-d 00:00:00', strtotime('-3 month'));
            } else if (date('G') == 7) {
                //早上7点拉取二个月的
                $start_time = date('Y-m-d 00:00:00', strtotime('-2 month'));
            } else if (date('G') == 12) {
               //中午12点拉取一个月的
                $start_time = date('Y-m-d 00:00:00', strtotime('-1 month'));
            } else {
                //其他时间拉取7天的
                $start_time = date('Y-m-d 00:00:00', strtotime('-7 days'));
            }
            $end_time = date('Y-m-d 23:59:59');
            $query = AliexpressOrder::find()->where([
                'and',
                ['between', 'created_time', $start_time, $end_time],
                ['account_id' => $erpAccountInfo->id],
            ]);
            //获取总数
            $count = $query->count();
            //接口每次最大处理数据量
            $pageSize = 50;
            //执行次数
            $step = ceil($count / $pageSize);

            for ($pageCur = 1; $pageCur <= $step; $pageCur++) {
                $offset = ($pageCur - 1) * $pageSize;
                $parentOrderIds = $query->select('platform_order_id')
                    ->orderBy('created_time ASC')
                    ->offset($offset)
                    ->limit($pageSize)
                    ->column();

                if (empty($parentOrderIds)) {
                    continue;
                }

                //处理平台订单ID中间有-的情况
                $tmp = [];
                foreach ($parentOrderIds as $orderId) {
                    if (stripos($orderId, 'AL') !== false) {
                        continue;
                    }
                    //针对订单ID中有中划线的
                    if (stripos($orderId, '-') !== false) {
                        $orderIds = explode('-', $orderId);
                        if (!empty($orderIds)) {
                            foreach ($orderIds as $id) {
                                if (is_numeric($id) && strlen($id) > 12) {
                                    $tmp[] = $id;
                                }
                            }
                        }
                        continue;
                    }
                    //针对订单ID中有下划线的
                    if (stripos($orderId, '_') !== false) {
                        $orderIds = explode('_', $orderId);
                        if (!empty($orderIds)) {
                            foreach ($orderIds as $id) {
                                if (is_numeric($id) && strlen($id) > 12) {
                                    $tmp[] = $id;
                                }
                            }
                        }
                        continue;
                    }
                    $tmp[] = $orderId;
                }
                $parentOrderIds = $tmp;

                //买家评价星级
                $buyerProductRatingsArr = [1, 2, 3];
                foreach ($buyerProductRatingsArr as $buyerProductRatings) {
                    try {
                        //获取中差评的评价列表
                        $evaluateList = $this->getEvaluateList($erpAccountInfo, $parentOrderIds, $buyerProductRatings);

                        if (!empty($evaluateList)) {
                            $aliexpressEvaluate = new AliexpressEvaluateTmp();
                            $aliexpressEvaluate->account_id = $accountId;
                            $aliexpressEvaluate->info = json_encode($evaluateList);
                            $aliexpressEvaluate->create_time = date('Y-m-d H:i:s');
                            $aliexpressEvaluate->save(false);
                        }
                    } catch (\Exception $e) {
                        //防止程序异常，中断运行
                    }
                }
            }

            //任务执行完成
            $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
            $TaskModel->status = 2;
            $TaskModel->save();
            return true;
        } catch (\Exception $e) {
            $TaskModel = AliexpressTask::find()->where(['id' => $taskId])->one();
            $TaskModel->status = -1;
            $TaskModel->errors = $e->getMessage();
            $TaskModel->save();
            return false;
        }
    }

    /**
     * 获取评价列表
     * @param $erpAccountInfo erp账号信息
     * @param $parentOrderIds 父订单ID集合
     * @param $buyerProductRatings 买家评价星级
     */
    public function getEvaluateList($erpAccountInfo, $parentOrderIds = [], $buyerProductRatings = '')
    {
        if (empty($erpAccountInfo)) {
            $this->errorMessage = 'erp账号信息不能为空';
            return false;
        }
        if (empty($parentOrderIds)) {
            $this->errorMessage = '父订单ID不能为空';
            return false;
        }

        $taobaoQimenApi = new TaobaoQimenApi($erpAccountInfo->app_key, $erpAccountInfo->secret_key, $erpAccountInfo->access_token);
        $request = new \MinxinAliexpressEffectiveevaluationRequest();
        //设置账号ID
        $request->setAccountId($erpAccountInfo->id);
        //设置父订单ID
        $request->setParentOrderIds(implode(',', $parentOrderIds));

        //设置买家评价星级
        if (!empty($buyerProductRatings)) {
            $request->setBuyerProductRatings($buyerProductRatings);
        }

        //请求接口
        $taobaoQimenApi->doRequest($request);
        if (!$taobaoQimenApi->isSuccess()) {
            $this->errorMessage = $taobaoQimenApi->getErrorMessage();
            return false;
        }
        $data = $taobaoQimenApi->getResponse();
        $data = json_decode(json_encode($data), true, 512, JSON_BIGINT_AS_STRING);
        if (empty($data)) {
            $this->errorMessage = '解析json数据失败';
            return false;
        }
        return !empty($data['target_list']['trade_evaluation_open_dto']) ? $data['target_list']['trade_evaluation_open_dto'] : [];
    }

    /**
     * 查询卖家每日服务分信息
     * @param $erpAccountInfo erp账号信息
     */
    public function getServicescoreinfo($erpAccountInfo)
    {

        $taobaoQimenApi = new TaobaoQimenApi($erpAccountInfo->app_key, $erpAccountInfo->secret_key, $erpAccountInfo->access_token,'erp_gatewayUrl');
        $request = new \MinxinAliexpressQueryservicescoreinfoRequest();
        //设置账号ID
        $request->setAccountId($erpAccountInfo->id);
        $request->setParam1($erpAccountInfo->buyer_login_id);//'cn1519860127hjcv'//$erpAccountInfo->seller_id);

        //请求接口
        $taobaoQimenApi->doRequest($request);

        if (!$taobaoQimenApi->isSuccess()) {
            $this->errorMessage = $taobaoQimenApi->getErrorMessage();
            return false;
        }

        $data = $taobaoQimenApi->getResponse();

        $data = json_decode(json_encode($data), true, 512, JSON_BIGINT_AS_STRING);
        if (empty($data)) {
            $this->errorMessage = '解析json数据失败';
            return false;
        }

        return !empty($data['result']) ? $data['result'] : [];
    }

    /**
     * 抓取当月服务商等级
     */
    public function getQuerylevelinfo($erpAccountInfo){

        $taobaoQimenApi = new TaobaoQimenApi($erpAccountInfo->app_key, $erpAccountInfo->secret_key, $erpAccountInfo->access_token,'erp_gatewayUrl');
        $request = new \MinxinAliexpressQuerylevelinfoRequest();
        //设置账号ID
        $request->setAccountId($erpAccountInfo->id);

        $request->setParam1($erpAccountInfo->buyer_login_id);//'cn1519860127hjcv');//$erpAccountInfo->seller_id);

        //请求接口
        $taobaoQimenApi->doRequest($request);
        if (!$taobaoQimenApi->isSuccess()) {
            $this->errorMessage = $taobaoQimenApi->getErrorMessage();
            return false;
        }

        $data = $taobaoQimenApi->getResponse();

        $data = json_decode(json_encode($data), true, 512, JSON_BIGINT_AS_STRING);
        if (empty($data)) {
            $this->errorMessage = '解析json数据失败';
            return false;
        }

        return !empty($data['result']) ? $data['result'] : [];
    }
    /**
     * 抓取当月服务商等级
     */
    public function getDsrddisputeproductlist($erpAccountInfo,$page=1,$PageSize = 50){

        $taobaoQimenApi = new TaobaoQimenApi($erpAccountInfo->app_key, $erpAccountInfo->secret_key, $erpAccountInfo->access_token,'erp_gatewayUrl');
        $request = new \MinxinAliexpressQuerydsrddisputeproductlistRequest();
        //设置账号ID
        $request->setAccountId($erpAccountInfo->id);
        //设置当前页
        $request->setCurrentPage($page);
        $request->setLocaleStr('zh_CN');
        $request->setPageSize($PageSize);
        $request->setLoginId($erpAccountInfo->buyer_login_id);//'cn1519860127hjcv');//$erpAccountInfo->seller_id);

        //请求接口
        $taobaoQimenApi->doRequest($request);

        if (!$taobaoQimenApi->isSuccess()) {
            $this->errorMessage = $taobaoQimenApi->getErrorMessage();
            return false;
        }

        $data = $taobaoQimenApi->getResponse();

        $data = json_decode(json_encode($data), true, 512, JSON_BIGINT_AS_STRING);
        if (empty($data)) {
            $this->errorMessage = '解析json数据失败';
            return false;
        }

        return !empty($data) ? $data : [];
    }
}
<?php

namespace app\modules\services\modules\ebay\models;

use app\modules\accounts\models\Account;
use app\modules\mails\models\AccountTaskQueue;
use app\modules\mails\models\EbayNewApiToken;
use app\modules\mails\models\EbaySellerAccountOverview;
use app\modules\mails\models\EbaySellerEdsShippingPolicy;
use app\modules\mails\models\EbaySellerEpacketShippingPolicy;
use app\modules\mails\models\EbaySellerLtnp;
use app\modules\mails\models\EbaySellerPgcTracking;
use app\modules\mails\models\EbaySellerQclist;
use app\modules\mails\models\EbaySellerSdWarehouse;
use app\modules\mails\models\EbaySellerShip;
use app\modules\mails\models\EbaySellerShipOld;
use app\modules\mails\models\EbaySellerSpeedpakList;
use app\modules\mails\models\EbaySellerSpeedpakMisuse;
use app\modules\mails\models\EbaySellerStandardsProfile;
use app\modules\mails\models\EbaySellerTci;
use app\modules\systems\models\EbayApiTask;
use DTS\eBaySDK\Analytics\Services;
use app\modules\mails\models\EbayListDownload;
use app\modules\mails\models\EbayMisuseDownload;
/**
 * ebay账号表现
 */
class EbayAccountOverview {

    /**
     * 获取ebay账号表现数据
     */
    public function getAccountOverview($accountId) {
        //获取账号信息
        $accountInfo = Account::findOne($accountId);

        if (empty($accountInfo)) {
            return false;
        }

        $accessToken = $accountInfo->access_token;
        if (empty($accessToken)) {
            return false;
        }

        //获取卖家成绩表
        $this->getStandardsProfile($accountInfo, $accessToken);
        usleep(4000);

        //获取综合表现
        $this->getLtnp($accountInfo, $accessToken);
        usleep(4000);

        //获取货运表现(1-8周)
        $this->getShip1to8($accountInfo, $accessToken);
        usleep(4000);

        //获取货运表现(5-12周)
        $this->getShip5to12($accountInfo, $accessToken);
        usleep(4000);

        //非货运表现
        $this->getTci($accountInfo, $accessToken);
        usleep(4000);

        //物流标准(美国小于5美金)
        $this->getEdsShippingPolicy($accountInfo, $accessToken);
        usleep(4000);

        //物流标准(美国>$5交易)
        $this->getEpacketShippingPolicy($accountInfo, $accessToken);
        usleep(4000);

        //SpeedPAK 物流管理方案
        $this->getSpeedPakListData($accountInfo, $accessToken);
        usleep(mt_rand(100000, 1000000));

        //卖家设置SpeedPAK物流选项
        $this->getSpeedPakMisuseData($accountInfo, $accessToken);
        usleep(mt_rand(100000, 1000000));

        //海外仓标准
        $this->getSdWarehouse($accountInfo, $accessToken);
        usleep(4000);

        //商业计划追踪
        $this->getPgcTracking($accountInfo, $accessToken);
        usleep(4000);

        //待处理刊登
        $this->getQclist($accountInfo, $accessToken);
        usleep(4000);

        //政策状态
        $this->getOverview($accountInfo, $accessToken);
        usleep(4000);
 
    }
    /**
     * 卖家成绩表
     */
    public function getSellerOverview($accountId) {
        try {
            $task = EbayApiTask::createTask($accountId, AccountTaskQueue::EB_SELLER_ACCOUNT_OVERVIEW);
            if (empty($task)) {
                return false;
            }
            //检测是否有运行中的任务
            if (EbayApiTask::checkIsRunning(AccountTaskQueue::EB_SELLER_ACCOUNT_OVERVIEW, $accountId)) {
                $task->status = 1;
                $task->exec_status = 2;
                $task->error = '已经有该任务在运行中';
                $task->save();
                return false;
            }

            //获取账号信息
            $accountInfo = Account::findOne($accountId);
            if (empty($accountInfo)) {
                $task->status = 1;
                $task->exec_status = 2;
                $task->error = '账号信息为空';
                $task->save();
                return false;
            }

            $accessToken = $accountInfo->access_token;
            if (empty($accessToken)) {
                $task->status = 1;
                $task->exec_status = 2;
                $task->error = '账号access_token为空';
                $task->save();
                return false;
            }

            //设置任务状态为运行中
            $task->exec_status = 1;
            //设置调用次数
            $task->api_call_limits = $task->api_call_limits + 1;
            $task->save();

            //防止请求过于频繁
            usleep(mt_rand(100000, 1000000));
            //获取卖家成绩表
            $this->getStandardsProfile($accountInfo, $accessToken);
            usleep(mt_rand(100000, 1000000));

            //设置任务状态为完成
            $task->status = 3;
            $task->exec_status = 2;
            $task->end_time = date('Y-m-d H:i:s');
            $task->save();
        } catch (\Exception $e) {
            $task->status = 1;
            $task->exec_status = 2;
            $task->error = $e->getMessage();
            $task->end_time = date('Y-m-d H:i:s');
            $task->save();
        }
    }

    /**
     * 买家体验报告
     */
    public function getBuyerOverview($accountId) {
        try {
            $task = EbayApiTask::createTask($accountId, AccountTaskQueue::EB_BUYER_ACCOUNT_OVERVIEW);
            if (empty($task)) {
                return false;
            }
            //检测是否有运行中的任务
            if (EbayApiTask::checkIsRunning(AccountTaskQueue::EB_BUYER_ACCOUNT_OVERVIEW, $accountId)) {
                $task->status = 1;
                $task->exec_status = 2;
                $task->error = '已经有该任务在运行中';
                $task->save();
                return false;
            }
 
            //获取账号信息
            $accountInfo = Account::findOne($accountId);
            if (empty($accountInfo)) {
                $task->status = 1;
                $task->exec_status = 2;
                $task->error = '账号信息为空';
                $task->save();
                return false;
            }

            $accessToken = $accountInfo->access_token;
            if (empty($accessToken)) {
                $task->status = 1;
                $task->exec_status = 2;
                $task->error = '账号access_token为空';
                $task->save();
                return false;
            }

            //设置任务状态为运行中
            $task->exec_status = 1;
            $task->save();

            //获取综合表现
            $this->getLtnp($accountInfo, $accessToken);
            //防止请求过于频繁
            usleep(mt_rand(100000, 500000));

            //获取货运表现(1-8周)
            $this->getShip1to8($accountInfo, $accessToken);
            usleep(mt_rand(100000, 500000));

            //获取货运表现(5-12周)
            $this->getShip5to12($accountInfo, $accessToken);
            usleep(mt_rand(100000, 500000));

            //非货运表现
            $this->getTci($accountInfo, $accessToken);
            usleep(mt_rand(100000, 500000));

            //物流标准(美国小于5美金)
            $this->getEdsShippingPolicy($accountInfo, $accessToken);
            usleep(mt_rand(100000, 500000));

            //物流标准(美国>$5交易)
            $this->getEpacketShippingPolicy($accountInfo, $accessToken);
            usleep(mt_rand(100000, 500000));

            //SpeedPAK 物流管理方案
            $this->getSpeedPakListData($accountInfo, $accessToken);
            usleep(mt_rand(100000, 500000));

            //卖家设置SpeedPAK物流选项
            $this->getSpeedPakMisuseData($accountInfo, $accessToken);
            usleep(mt_rand(100000, 500000));

            //海外仓标准
            $this->getSdWarehouse($accountInfo, $accessToken);
            usleep(mt_rand(100000, 500000));

            //商业计划追踪
            $this->getPgcTracking($accountInfo, $accessToken);
            usleep(mt_rand(100000, 500000));

            //待处理刊登
            $this->getQclist($accountInfo, $accessToken);
            usleep(mt_rand(100000, 500000));

            //政策状态
            $this->getOverview($accountInfo, $accessToken);
            usleep(mt_rand(100000, 500000));
            //获取SpeedPAK 物流管理方案下载
            $this->getlistDownload($accountInfo, $accessToken);
            usleep(mt_rand(100000, 500000));
            //买家选择SpeedPAK物流选项时卖家正确使用SpeedPAK物流管理方案表现相关交易下载数据
            $this->getMisuseDownload($accountInfo, $accessToken);
            usleep(mt_rand(100000, 500000));
            //设置任务状态为完成
            $task->status = 3;
            $task->exec_status = 2;
            $task->end_time = date('Y-m-d H:i:s');
            $task->save();
        } catch (\Exception $e) {
            $task->status = 1;
            $task->exec_status = 2;
            $task->error = $e->getMessage();
            $task->end_time = date('Y-m-d H:i:s');
            $task->save();
        }
    }

    /**
     * 获取卖家成绩表
     */
    public function getStandardsProfile($accountInfo, $accessToken) {
        $service = new Services\AnalyticsService([
            'authorization' => $accessToken,
        ]);

        $service->setConfig([
            'httpOptions' => [
                'verify' => false,
            ],
        ]);

        $response = $service->getAllSellerProfiles();

        if (isset($response->errors) && !empty($response->errors)) {
            $errors = '';
            foreach ($response->errors as $error) {
                $errors .= $error->errorId . ':' . $error->message . ':' . $error->longMessage . "\n";
            }
            throw new \Exception($errors);
            return false;
        }

        if ($response->getStatusCode() === 200) {
            if (!empty($response->standardsProfiles)) {
                foreach ($response->standardsProfiles as $profile) {
                    $standardsLevel = !empty($profile->standardsLevel) ? $profile->standardsLevel : '';
                    $program = !empty($profile->program) ? $profile->program : '';
                    $defaultProgram = !empty($profile->defaultProgram) ? $profile->defaultProgram : 0;
                    $evaluationReason = !empty($profile->evaluationReason) ? $profile->evaluationReason : '';
                    $cycleType = !empty($profile->cycle->cycleType) ? $profile->cycle->cycleType : '';
                    //接口返回的是GMT时间，转换为当地时间
                    $evaluationDate = !empty($profile->cycle->evaluationDate) ? date('Y-m-d H:i:s', strtotime($profile->cycle->evaluationDate)) : '';
                    $evaluationMonth = !empty($profile->cycle->evaluationMonth) ? $profile->cycle->evaluationMonth : '';

                    if (!empty($profile->metrics)) {
                        foreach ($profile->metrics as $metric) {
                            $info = EbaySellerStandardsProfile::findOne([
                                        'account_id' => $accountInfo->id,
                                        'program' => $program,
                                        'evaluation_date' => $evaluationDate,
                                        'cycle_type' => $cycleType,
                                        'metric_key' => $metric->metricKey,
                            ]);

                            if (empty($info)) {
                                $info = new EbaySellerStandardsProfile();
                                $info->create_by = 'system';
                                $info->create_time = date('Y-m-d H:i:s');
                            }

                            $info->account_id = $accountInfo->id;
                            $info->program = $program;
                            $info->standards_level = $standardsLevel;
                            $info->default_program = $defaultProgram;
                            $info->evaluation_reason = $evaluationReason;
                            $info->cycle_type = $cycleType;
                            $info->evaluation_date = $evaluationDate;
                            $info->evaluation_month = $evaluationMonth;
                            $info->metric_name = !empty($metric->name) ? $metric->name : '';
                            $info->metric_key = !empty($metric->metricKey) ? $metric->metricKey : '';
                            $info->metric_level = !empty($metric->level) ? $metric->level : '';
                            $info->metric_type = !empty($metric->type) ? $metric->type : '';
                            $info->metric_value = !empty($metric->value) ? json_encode($metric->value) : '';
                            //处理度量值标量
                            if (isset($metric->value['value'])) {
                                if (empty($metric->value['value']) || $metric->value['value'] == '0.00') {
                                    $info->metric_value_scalar = isset($metric->value['numerator']) ? $metric->value['numerator'] : 0;
                                } else {
                                    $info->metric_value_scalar = floatval($metric->value['value']);
                                }
                            } else {
                                $info->metric_value_scalar = floatval($metric->value);
                            }
                            $info->metric_lookback_startdate = !empty($metric->lookbackStartDate) ? date('Y-m-d H:i:s', strtotime($metric->lookbackStartDate)) : '';
                            $info->metric_lookback_enddate = !empty($metric->lookbackEndDate) ? date('Y-m-d H:i:s', strtotime($metric->lookbackEndDate)) : '';
                            $info->metric_threshold_lower_bound = !empty($metric->thresholdLowerBound) ? json_encode($metric->thresholdLowerBound) : '';
                            $info->metric_threshold_upper_bound = !empty($metric->thresholdUpperBound) ? json_encode($metric->thresholdUpperBound) : '';
                            $info->metric_threshold_meta_data = !empty($metric->thresholdMetaData) ? $metric->thresholdMetaData : '';
                            $info->modify_by = 'system';
                            $info->modify_time = date('Y-m-d H:i:s');
                            $info->save(false);
                        }
                    }
                }
            }
        }
    }

    /**
     * 获取综合表现
     */
    public function getLtnp($accountInfo, $accessToken) {
        $data = EbayGccbtApi::ltnp($accessToken);
        if (empty($data)) {
            return false;
        }

        $info = EbaySellerLtnp::findOne(['account_id' => $accountInfo->id, 'refreshed_date' => $data['refreshedDate']]);
        if (empty($info)) {
            $info = new EbaySellerLtnp();
            $info->create_by = 'system';
            $info->create_time = date('Y-m-d H:i:s');
        }
        $info->account_id = $accountInfo->id;
        $info->program_status_lst_eval = isset($data['program_status_lst_eval']) ? $data['program_status_lst_eval'] : '-1';
        $info->refreshed_date = !empty($data['refreshedDate']) ? $data['refreshedDate'] : '';
        $info->dft_lst_eval_beg_dt = !empty($data['dft_lst_eval_beg_dt']) ? $data['dft_lst_eval_beg_dt'] : '';
        $info->dft_lst_eval_end_dt = !empty($data['dft_lst_eval_end_dt']) ? $data['dft_lst_eval_end_dt'] : '';
        $info->dft_wk_eval_beg_dt = !empty($data['dft_wk_eval_beg_dt']) ? $data['dft_wk_eval_beg_dt'] : '';
        $info->dft_wk_eval_end_dt = !empty($data['dft_wk_eval_end_dt']) ? $data['dft_wk_eval_end_dt'] : '';
        $info->next_review_dt = !empty($data['next_review_dt']) ? $data['next_review_dt'] : '';
        $info->status_lst_eval = !empty($data['status_lst_eval']) ? $data['status_lst_eval'] : '0';
        $info->dft_rt_lt10_12m_lst_eval = !empty($data['dft_rt_lt10_12m_lst_eval']) ? $data['dft_rt_lt10_12m_lst_eval'] : '0';
        $info->dft_rt_lt10_12m_th = !empty($data['dft_rt_lt10_12m_th']) ? $data['dft_rt_lt10_12m_th'] : '0';
        $info->status_lt10_lst_eval = !empty($data['status_lt10_lst_eval']) ? $data['status_lt10_lst_eval'] : '0';
        $info->dft_rt_gt10_12m_lst_eval = !empty($data['dft_rt_gt10_12m_lst_eval']) ? $data['dft_rt_gt10_12m_lst_eval'] : '0';
        $info->dft_rt_gt10_12m_th = !empty($data['dft_rt_gt10_12m_th']) ? $data['dft_rt_gt10_12m_th'] : '0';
        $info->status_gt10_lst_eval = !empty($data['status_gt10_lst_eval']) ? $data['status_gt10_lst_eval'] : '0';
        $info->adj_dft_rt_12m_lst_eval = !empty($data['adj_dft_rt_12m_lst_eval']) ? $data['adj_dft_rt_12m_lst_eval'] : '0';
        $info->adj_dft_rt_12m_th = !empty($data['adj_dft_rt_12m_th']) ? $data['adj_dft_rt_12m_th'] : '0';
        $info->status_adj_lst_eval = !empty($data['status_adj_lst_eval']) ? $data['status_adj_lst_eval'] : '0';
        $info->status_wk_eval = !empty($data['status_wk_eval']) ? $data['status_wk_eval'] : '0';
        $info->dft_rt_lt10_12m_wk_eval = !empty($data['dft_rt_lt10_12m_wk_eval']) ? $data['dft_rt_lt10_12m_wk_eval'] : '0';
        $info->status_lt10_wk_eval = !empty($data['status_lt10_wk_eval']) ? $data['status_lt10_wk_eval'] : '0';
        $info->dft_rt_gt10_12m_wk_eval = !empty($data['dft_rt_gt10_12m_wk_eval']) ? $data['dft_rt_gt10_12m_wk_eval'] : '0';
        $info->status_gt10_wk_eval = !empty($data['status_gt10_wk_eval']) ? $data['status_gt10_wk_eval'] : '0';
        $info->adj_dft_rt_12m_wk_eval = !empty($data['adj_dft_rt_12m_wk_eval']) ? $data['adj_dft_rt_12m_wk_eval'] : '0';
        $info->status_adj_wk_eval = !empty($data['status_adj_wk_eval']) ? $data['status_adj_wk_eval'] : '0';
        $info->snad_status_lst_eval = !empty($data['snad_status_lst_eval']) ? $data['snad_status_lst_eval'] : '0';
        $info->delta_snad_rt_12m_lst_eval = !empty($data['delta_snad_rt_12m_lst_eval']) ? $data['delta_snad_rt_12m_lst_eval'] : '0';
        $info->snad_status_wk_eval = !empty($data['snad_status_wk_eval']) ? $data['snad_status_wk_eval'] : '0';
        $info->adj_snad_rt_12m_na = !empty($data['adj_snad_rt_12m_na']) ? $data['adj_snad_rt_12m_na'] : '0';
        $info->adj_snad_rt_12m_uk = !empty($data['adj_snad_rt_12m_uk']) ? $data['adj_snad_rt_12m_uk'] : '0';
        $info->adj_snad_rt_12m_de = !empty($data['adj_snad_rt_12m_de']) ? $data['adj_snad_rt_12m_de'] : '0';
        $info->adj_snad_rt_12m_au = !empty($data['adj_snad_rt_12m_au']) ? $data['adj_snad_rt_12m_au'] : '0';
        $info->adj_snad_rt_12m_frites = !empty($data['adj_snad_rt_12m_frites']) ? $data['adj_snad_rt_12m_frites'] : '0';
        $info->adj_snad_rt_12m_gbh = !empty($data['adj_snad_rt_12m_gbh']) ? $data['adj_snad_rt_12m_gbh'] : '0';
        $info->adj_snad_rt_12m_other = !empty($data['adj_snad_rt_12m_other']) ? $data['adj_snad_rt_12m_other'] : '0';
        $info->modify_by = 'system';
        $info->modify_time = date('Y-m-d H:i:s');
        $info->save(false);
    }

    /**
     * 获取货运表现(1-8周)
     */
    public function getShip1to8($accountInfo, $accessToken) {
        $data = EbayGccbtApi::ship1to8($accessToken);
        if (empty($data)) {
            return false;
        }

        $info = EbaySellerShip::findOne(['account_id' => $accountInfo->id, 'refreshed_date' => $data['refreshedDate']]);
        if (empty($info)) {
            $info = new EbaySellerShip();
            $info->create_by = 'system';
            $info->create_time = date('Y-m-d H:i:s');
        }

        $info->account_id = $accountInfo->id;
        $info->result = isset($data['result']) ? $data['result'] : '-1';
        $info->refreshed_date = !empty($data['refreshedDate']) ? $data['refreshedDate'] : '';
        $info->review_start_date = !empty($data['reviewStartDate']) ? $data['reviewStartDate'] : '';
        $info->review_end_date = !empty($data['reviewEndDate']) ? $data['reviewEndDate'] : '';
        $info->glb_shtm_de_rate_pre = !empty($data['glbShtmDeRatePre']) ? $data['glbShtmDeRatePre'] : '0';
        $info->next_eval_date = !empty($data['nextEvalDate']) ? $data['nextEvalDate'] : '';
        $info->na_shtm_rate_pre = !empty($data['naShtmRatePre']) ? $data['naShtmRatePre'] : '0';
        $info->uk_shtm_rate_pre = !empty($data['ukShtmRatePre']) ? $data['ukShtmRatePre'] : '0';
        $info->de_shtm_rate_pre = !empty($data['deShtmRatePre']) ? $data['deShtmRatePre'] : '0';
        $info->au_shtm_rate_pre = !empty($data['auShtmRatePre']) ? $data['auShtmRatePre'] : '0';
        $info->oth_shtm_rate_pre = !empty($data['othShtmRatePre']) ? $data['othShtmRatePre'] : '0';
        $info->modify_by = 'system';
        $info->modify_time = date('Y-m-d H:i:s');
        $info->save(false);
    }

    /**
     * 获取货运表现(5-12周)
     */
    public function getShip5to12($accountInfo, $accessToken) {
        $data = EbayGccbtApi::ship5to12($accessToken);
        if (empty($data)) {
            return false;
        }

        $info = EbaySellerShipOld::findOne(['account_id' => $accountInfo->id, 'refreshed_date' => $data['refreshedDate']]);
        if (empty($info)) {
            $info = new EbaySellerShipOld();
            $info->create_by = 'system';
            $info->create_time = date('Y-m-d H:i:s');
        }

        $info->account_id = $accountInfo->id;
        $info->result = isset($data['result']) ? $data['result'] : '-1';
        $info->refreshed_date = !empty($data['refreshedDate']) ? $data['refreshedDate'] : '';
        $info->review_start_date = !empty($data['reviewStartDate']) ? $data['reviewStartDate'] : '';
        $info->review_end_date = !empty($data['reviewEndDate']) ? $data['reviewEndDate'] : '';
        $info->glb_shtm_de_rate_pre = !empty($data['glbShtmDeRatePre']) ? $data['glbShtmDeRatePre'] : '0';
        $info->next_eval_date = !empty($data['nextEvalDate']) ? $data['nextEvalDate'] : '';
        $info->na_shtm_rate_pre = !empty($data['naShtmRatePre']) ? $data['naShtmRatePre'] : '0';
        $info->uk_shtm_rate_pre = !empty($data['ukShtmRatePre']) ? $data['ukShtmRatePre'] : '0';
        $info->de_shtm_rate_pre = !empty($data['deShtmRatePre']) ? $data['deShtmRatePre'] : '0';
        $info->au_shtm_rate_pre = !empty($data['auShtmRatePre']) ? $data['auShtmRatePre'] : '0';
        $info->oth_shtm_rate_pre = !empty($data['othShtmRatePre']) ? $data['othShtmRatePre'] : '0';
        $info->modify_by = 'system';
        $info->modify_time = date('Y-m-d H:i:s');
        $info->save(false);
    }

    /**
     * 非货运表现
     */
    public function getTci($accountInfo, $accessToken) {
        $data = EbayGccbtApi::tci($accessToken);
        if (empty($data)) {
            return false;
        }

        $info = EbaySellerTci::findOne(['account_id' => $accountInfo->id, 'refreshed_date' => $data['refreshedDate']]);
        if (empty($info)) {
            $info = new EbaySellerTci();
            $info->create_by = 'system';
            $info->create_time = date('Y-m-d H:i:s');
        }

        $info->account_id = $accountInfo->id;
        $info->result = isset($data['result']) ? $data['result'] : '-1';
        $info->refreshed_date = !empty($data['refreshedDate']) ? $data['refreshedDate'] : '';
        $info->review_start_dt = !empty($data['reviewStartDt']) ? $data['reviewStartDt'] : '';
        $info->review_end_dt = !empty($data['reviewEndDt']) ? $data['reviewEndDt'] : '';
        $info->ns_defect_adj_rt8wk = !empty($data['nsDefectAdjRt8wk']) ? $data['nsDefectAdjRt8wk'] : '0';
        $info->next_eval_date = !empty($data['nextEvalDate']) ? $data['nextEvalDate'] : '';
        $info->na_ns_defect_adj_rt8wk = !empty($data['naNsDefectAdjRt8wk']) ? $data['naNsDefectAdjRt8wk'] : '0';
        $info->uk_ns_defect_adj_rt8wk = !empty($data['ukNsDefectAdjRt8wk']) ? $data['ukNsDefectAdjRt8wk'] : '0';
        $info->de_ns_defect_adj_rt8wk = !empty($data['deNsDefectAdjRt8wk']) ? $data['deNsDefectAdjRt8wk'] : '0';
        $info->au_ns_defect_adj_rt8wk = !empty($data['auNsDefectAdjRt8wk']) ? $data['auNsDefectAdjRt8wk'] : '0';
        $info->gl_ns_defect_adj_rt8wk = !empty($data['glNsDefectAdjRt8wk']) ? $data['glNsDefectAdjRt8wk'] : '0';
        $info->modify_by = 'system';
        $info->modify_time = date('Y-m-d H:i:s');
        $info->save(false);
    }

    /**
     * 物流标准(美国小于5美金)
     */
    public function getEdsShippingPolicy($accountInfo, $accessToken) {
        $data = EbayGccbtApi::edsShippingPolicy($accessToken);
        if (empty($data)) {
            return false;
        }

        $info = EbaySellerEdsShippingPolicy::findOne(['account_id' => $accountInfo->id, 'refreshed_date' => $data['refreshedDate']]);
        if (empty($info)) {
            $info = new EbaySellerEdsShippingPolicy();
            $info->create_by = 'system';
            $info->create_time = date('Y-m-d H:i:s');
        }

        $info->account_id = $accountInfo->id;
        $info->eds_status = isset($data['edsStatus']) ? $data['edsStatus'] : '-1';
        $info->refreshed_date = !empty($data['refreshedDate']) ? $data['refreshedDate'] : '';
        $info->review_start_date = !empty($data['reviewStartDate']) ? $data['reviewStartDate'] : '';
        $info->review_end_date = !empty($data['reviewEndDate']) ? $data['reviewEndDate'] : '';
        $info->next_review_date = !empty($data['nextReviewDate']) ? $data['nextReviewDate'] : '';
        $info->standard_value = !empty($data['standardValue']) ? $data['standardValue'] : '0';
        $info->add_trans_cnt = !empty($data['addTransCnt']) ? $data['addTransCnt'] : '0';
        $info->eds_comply_rate = !empty($data['edsComplyRate']) ? $data['edsComplyRate'] : '0';
        $info->add_buyer_std_trans_cnt = !empty($data['addBuyerStdTransCnt']) ? $data['addBuyerStdTransCnt'] : '0';
        $info->eds_std_comply_rate = !empty($data['edsStdComplyRate']) ? $data['edsStdComplyRate'] : '0';
        $info->add_buyer_econ_trans_cnt = !empty($data['addBuyerEconTransCnt']) ? $data['addBuyerEconTransCnt'] : '0';
        $info->eds_econ_comply_rate = !empty($data['edsEconComplyRate']) ? $data['edsEconComplyRate'] : '0';
        $info->modify_by = 'system';
        $info->modify_time = date('Y-m-d H:i:s');
        $info->save(false);
    }

    /**
     * 物流标准(美国>$5交易)
     */
    public function getEpacketShippingPolicy($accountInfo, $accessToken) {
        $data = EbayGccbtApi::epacketShippingPolicy($accessToken);
        if (empty($data)) {
            return false;
        }

        $info = EbaySellerEpacketShippingPolicy::findOne(['account_id' => $accountInfo->id, 'refreshed_date' => $data['refreshedDate']]);
        if (empty($info)) {
            $info = new EbaySellerEpacketShippingPolicy();
            $info->create_by = 'system';
            $info->create_time = date('Y-m-d H:i:s');
        }

        $info->account_id = $accountInfo->id;
        $info->e_packet_status = isset($data['ePacketStatus']) ? $data['ePacketStatus'] : '-1';
        $info->refreshed_date = !empty($data['refreshedDate']) ? $data['refreshedDate'] : '';
        $info->review_start_date = !empty($data['reviewStartDate']) ? $data['reviewStartDate'] : '';
        $info->review_end_date = !empty($data['reviewEndDate']) ? $data['reviewEndDate'] : '';
        $info->next_evaluation_date = !empty($data['nextEvaluationDate']) ? $data['nextEvaluationDate'] : '';
        $info->standard_value = !empty($data['standardValue']) ? $data['standardValue'] : '0';
        $info->evaluated_tnx_cnt = !empty($data['evaluatedTnxCnt']) ? $data['evaluatedTnxCnt'] : '0';
        $info->adoption = !empty($data['adoption']) ? $data['adoption'] : '0';
        $info->cbt_tnx_cnt = !empty($data['cbtTnxCnt']) ? $data['cbtTnxCnt'] : '0';
        $info->cbt_adoption = !empty($data['cbtAdoption']) ? $data['cbtAdoption'] : '0';
        $info->wh_tnx_cnt = !empty($data['whTnxCnt']) ? $data['whTnxCnt'] : '0';
        $info->wh_adoption = !empty($data['whAdoption']) ? $data['whAdoption'] : '0';
        $info->modify_by = 'system';
        $info->modify_time = date('Y-m-d H:i:s');
        $info->save(false);
    }

    /**
     * 获取SpeedPAK物流管理方案
     */
    public function getSpeedPakListData($accountInfo, $accessToken) {
        $data = EbayGccbtApi::speedPakListData($accessToken);
        if (empty($data)) {
            return false;
        }

        $info = EbaySellerSpeedpakList::findOne(['account_id' => $accountInfo->id, 'create_pst' => $data['createPst']]);
        if (empty($info)) {
            $info = new EbaySellerSpeedpakList();
            $info->create_by = 'system';
            $info->create_time = date('Y-m-d H:i:s');
        }

        $info->account_id = $accountInfo->id;
        $info->create_pst = !empty($data['createPst']) ? $data['createPst'] : '';
        $info->start_date = !empty($data['startDate']) ? $data['startDate'] : '';
        $info->end_date = !empty($data['endDate']) ? $data['endDate'] : '';
        $info->account_status = isset($data['accountStatus']) ? $data['accountStatus'] : '-1';
        $info->us_trans = isset($data['usTrans']) ? $data['usTrans'] : '0';
        $info->uk_trans = isset($data['ukTrans']) ? $data['ukTrans'] : '0';
        $info->de_trans = isset($data['deTrans']) ? $data['deTrans'] : '0';
        $info->us_adoption = isset($data['usAdoption']) ? $data['usAdoption'] : '0';
        $info->uk_adoption = isset($data['ukAdoption']) ? $data['ukAdoption'] : '0';
        $info->de_adoption = isset($data['deAdoption']) ? $data['deAdoption'] : '0';
        $info->us_color = isset($data['usColor']) ? $data['usColor'] : '0';
        $info->uk_color = isset($data['ukColor']) ? $data['ukColor'] : '0';
        $info->de_color = isset($data['deColor']) ? $data['deColor'] : '0';
        $info->us_requirement = isset($data['usRequirement']) ? $data['usRequirement'] : '0';
        $info->uk_requirement = isset($data['ukRequirement']) ? $data['ukRequirement'] : '0';
        $info->de_requirement = isset($data['deRequirement']) ? $data['deRequirement'] : '0';
        $info->next_evaluation_date = !empty($data['nextEvaluationDate']) ? $data['nextEvaluationDate'] : '';
        $info->modify_by = 'system';
        $info->modify_time = date('Y-m-d H:i:s');
        $info->save(false);
    }

    /**
     * 获取卖家设置SpeedPAK物流选项
     */
    public function getSpeedPakMisuseData($accountInfo, $accessToken) {
        $data = EbayGccbtApi::speedPakMisuseData($accessToken);
        if (empty($data)) {
            return false;
        }

        $info = EbaySellerSpeedpakMisuse::findOne(['account_id' => $accountInfo->id, 'create_pst' => $data['createPst']]);
        if (empty($info)) {
            $info = new EbaySellerSpeedpakMisuse();
            $info->create_by = 'system';
            $info->create_time = date('Y-m-d H:i:s');
        }

        $info->account_id = $accountInfo->id;
        $info->create_pst = !empty($data['createPst']) ? $data['createPst'] : '';
        $info->start_date = !empty($data['startDate']) ? $data['startDate'] : '';
        $info->end_date = !empty($data['endDate']) ? $data['endDate'] : '';
        $info->account_status = isset($data['accountStatus']) ? $data['accountStatus'] : '-1';
        $info->expedited_trans = isset($data['expeditedTrans']) ? $data['expeditedTrans'] : '0';
        $info->standard_trans = isset($data['standardTrans']) ? $data['standardTrans'] : '0';
        $info->economy_trans = isset($data['economyTrans']) ? $data['economyTrans'] : '0';
        $info->expedited_comply_rate = isset($data['expeditedComplyRate']) ? $data['expeditedComplyRate'] : '0';
        $info->standard_comply_rate = isset($data['standardComplyRate']) ? $data['standardComplyRate'] : '0';
        $info->economy_comply_rate = isset($data['economyComplyRate']) ? $data['economyComplyRate'] : '0';
        $info->expedited_required_rate = isset($data['expeditedRequiredRate']) ? $data['expeditedRequiredRate'] : '0';
        $info->standard_required_rate = isset($data['standardRequiredRate']) ? $data['standardRequiredRate'] : '0';
        $info->economy_required_rate = isset($data['economyRequiredRate']) ? $data['economyRequiredRate'] : '0';
        $info->expedited_color = isset($data['expeditedColor']) ? $data['expeditedColor'] : '0';
        $info->standard_color = isset($data['standardColor']) ? $data['standardColor'] : '0';
        $info->economy_color = isset($data['economyColor']) ? $data['economyColor'] : '0';
        $info->speedpak_trans = isset($data['speedpakTrans']) ? $data['speedpakTrans'] : '0';
        $info->next_evaluation_date = !empty($data['nextEvaluationDate']) ? $data['nextEvaluationDate'] : '';
        $info->modify_by = 'system';
        $info->modify_time = date('Y-m-d H:i:s');
        $info->save(false);
    }

    /**
     * 海外仓标准
     */
    public function getSdWarehouse($accountInfo, $accessToken) {
        $data = EbayGccbtApi::sdWarehouse($accessToken);
        if (empty($data)) {
            return false;
        }

        $info = EbaySellerSdWarehouse::findOne(['account_id' => $accountInfo->id, 'refreshed_date' => $data['refreshedDate']]);
        if (empty($info)) {
            $info = new EbaySellerSdWarehouse();
            $info->create_by = 'system';
            $info->create_time = date('Y-m-d H:i:s');
        }

        $info->account_id = $accountInfo->id;
        $info->warehouse_status = isset($data['warehouse_status']) ? $data['warehouse_status'] : '-1';
        $info->refreshed_date = !empty($data['refreshedDate']) ? $data['refreshedDate'] : '';
        $info->review_start_date = !empty($data['review_start_date']) ? $data['review_start_date'] : '';
        $info->review_end_date = !empty($data['review_end_date']) ? $data['review_end_date'] : '';
        $info->next_evaluation_date = !empty($data['next_evaluation_date']) ? $data['next_evaluation_date'] : '';
        $info->us_wh_cbt_trans_rate = !empty($data['us_wh_cbt_trans_rate']) ? $data['us_wh_cbt_trans_rate'] : '0';
        $info->us_cbt_sd = !empty($data['us_cbt_sd']) ? $data['us_cbt_sd'] : '0';
        $info->uk_wh_cbt_trans_rate = !empty($data['uk_wh_cbt_trans_rate']) ? $data['uk_wh_cbt_trans_rate'] : '0';
        $info->uk_cbt_sd = !empty($data['uk_cbt_sd']) ? $data['uk_cbt_sd'] : '0';
        $info->de_wh_cbt_trans_rate = !empty($data['de_wh_cbt_trans_rate']) ? $data['de_wh_cbt_trans_rate'] : '0';
        $info->de_cbt_sd = !empty($data['de_cbt_sd']) ? $data['de_cbt_sd'] : '0';
        $info->au_wh_cbt_trans_rate = !empty($data['au_wh_cbt_trans_rate']) ? $data['au_wh_cbt_trans_rate'] : '0';
        $info->au_cbt_sd = !empty($data['au_cbt_sd']) ? $data['au_cbt_sd'] : '0';
        $info->other_wh_cbt_trans_rate = !empty($data['other_wh_cbt_trans_rate']) ? $data['other_wh_cbt_trans_rate'] : '0';
        $info->other_cbt_sd = !empty($data['other_cbt_sd']) ? $data['other_cbt_sd'] : '0';
        $info->us_wh_shipping_defect_rate = !empty($data['us_wh_shipping_defect_rate']) ? $data['us_wh_shipping_defect_rate'] : '0';
        $info->us_ship_defect_sd = !empty($data['us_ship_defect_sd']) ? $data['us_ship_defect_sd'] : '0';
        $info->uk_wh_shipping_defect_rate = !empty($data['uk_wh_shipping_defect_rate']) ? $data['uk_wh_shipping_defect_rate'] : '0';
        $info->uk_ship_defect_sd = !empty($data['uk_ship_defect_sd']) ? $data['uk_ship_defect_sd'] : '0';
        $info->de_wh_shipping_defect_rate = !empty($data['de_wh_shipping_defect_rate']) ? $data['de_wh_shipping_defect_rate'] : '0';
        $info->de_ship_defect_sd = !empty($data['de_ship_defect_sd']) ? $data['de_ship_defect_sd'] : '0';
        $info->au_wh_shipping_defect_rate = !empty($data['au_wh_shipping_defect_rate']) ? $data['au_wh_shipping_defect_rate'] : '0';
        $info->au_ship_defect_sd = !empty($data['au_ship_defect_sd']) ? $data['au_ship_defect_sd'] : '0';
        $info->other_wh_shipping_defect_rate = !empty($data['other_wh_shipping_defect_rate']) ? $data['other_wh_shipping_defect_rate'] : '0';
        $info->other_ship_defect_sd = !empty($data['other_ship_defect_sd']) ? $data['other_ship_defect_sd'] : '0';
        $info->modify_by = 'system';
        $info->modify_time = date('Y-m-d H:i:s');
        $info->save(false);
    }

    /**
     * 商业计划追踪
     */
    public function getPgcTracking($accountInfo, $accessToken) {
        $data = EbayGccbtApi::pgcTracking($accessToken);
        if (empty($data)) {
            return false;
        }

        $info = EbaySellerPgcTracking::findOne(['account_id' => $accountInfo->id, 'refreshed_date' => $data['refreshedDate']]);
        if (empty($info)) {
            $info = new EbaySellerPgcTracking();
            $info->create_by = 'system';
            $info->create_time = date('Y-m-d H:i:s');
        }

        $info->account_id = $accountInfo->id;
        $info->pgc_status = isset($data['pgcStatus']) ? $data['pgcStatus'] : '-1';
        $info->refreshed_date = !empty($data['refreshedDate']) ? $data['refreshedDate'] : '';
        $info->account_cmltv = !empty($data['accountCmltv']) ? $data['accountCmltv'] : '0';
        $info->account_cmltv_std = !empty($data['accountCmltvStd']) ? $data['accountCmltvStd'] : '0';
        $info->suspension_sts = !empty($data['suspensionSts']) ? $data['suspensionSts'] : '0';
        $info->suspension_std = !empty($data['suspensionStd']) ? $data['suspensionStd'] : '0';
        $info->duplicate_sts = !empty($data['duplicateSts']) ? $data['duplicateSts'] : '0';
        $info->duplicate_std = !empty($data['duplicateStd']) ? $data['duplicateStd'] : '0';
        $info->pgc_performance = !empty($data['pgcPerformance']) ? $data['pgcPerformance'] : '0';
        $info->cridr_as_promised = !empty($data['cridr_as_promised']) ? $data['cridr_as_promised'] : '0';
        $info->cridr_as_std = !empty($data['cridr_as_std']) ? $data['cridr_as_std'] : '0';
        $info->cat_as_promised = !empty($data['cat_as_promised']) ? $data['cat_as_promised'] : '0';
        $info->cat_as_std = !empty($data['cat_as_std']) ? $data['cat_as_std'] : '0';
        $info->asp_as_promised = !empty($data['asp_as_promised']) ? $data['asp_as_promised'] : '0';
        $info->asp_as_std = !empty($data['asp_as_std']) ? $data['asp_as_std'] : '0';
        $info->dft_sts = !empty($data['dft_sts']) ? $data['dft_sts'] : '0';
        $info->dft_std = !empty($data['dft_std']) ? $data['dft_std'] : '0';
        $info->wh_sts = !empty($data['wh_sts']) ? $data['wh_sts'] : '0';
        $info->wh_std = !empty($data['wh_std']) ? $data['wh_std'] : '0';
        $info->avg_gmv_sts = !empty($data['avg_gmv_sts']) ? $data['avg_gmv_sts'] : '0';
        $info->avg_gmv_std = !empty($data['avg_gmv_std']) ? $data['avg_gmv_std'] : '0';
        $info->primary_corridor = !empty($data['primary_corridor']) ? $data['primary_corridor'] : '0';
        $info->secondary_corridor = !empty($data['secondary_corridor']) ? $data['secondary_corridor'] : '0';
        $info->primary_vertical = !empty($data['primary_vertical']) ? $data['primary_vertical'] : '0';
        $info->primary_category = !empty($data['primary_category']) ? $data['primary_category'] : '0';
        $info->secondary_vertical = !empty($data['secondary_vertical']) ? $data['secondary_vertical'] : '0';
        $info->secondary_category = !empty($data['secondary_category']) ? $data['secondary_category'] : '0';
        $info->estimated_item_asp_usd = !empty($data['estimated_item_asp_usd']) ? $data['estimated_item_asp_usd'] : '0';
        $info->location_of_warehouse = !empty($data['location_of_warehouse']) ? $data['location_of_warehouse'] : '0';
        $info->warehouse_adoption_rate = !empty($data['warehouse_adoption_rate']) ? $data['warehouse_adoption_rate'] : '0';
        $info->modify_by = 'system';
        $info->modify_time = date('Y-m-d H:i:s');
        $info->save(false);
    }

    /**
     * 待处理刊登
     */
    public function getQclist($accountInfo, $accessToken) {
        $data = EbayGccbtApi::qclist($accessToken);
        if (empty($data)) {
            return false;
        }

        if (!empty($data['qclist'])) {
            foreach ($data['qclist'] as $item) {
                $info = EbaySellerQclist::findOne(['account_id' => $accountInfo->id, 'refreshed_date' => $data['refreshedDate'], 'item_id' => $item['itemId']]);

                if (empty($info)) {
                    $info = new EbaySellerQclist();
                    $info->create_by = 'system';
                    $info->create_time = date('Y-m-d H:i:s');
                }

                $info->account_id = $accountInfo->id;
                $info->item_id = !empty($item['itemId']) ? $item['itemId'] : '';
                $info->refreshed_date = !empty($data['refreshedDate']) ? $data['refreshedDate'] : '';
                $info->rm_dead_dt = !empty($item['rmDeadDt']) ? $item['rmDeadDt'] : '';
                $info->auct_end_dt = !empty($item['auctEndDt']) ? $item['auctEndDt'] : '';
                $info->gmv_usd = !empty($item['gmvUsd']) ? $item['gmvUsd'] : '0';
                $info->total_trans = !empty($item['totalTrans']) ? $item['totalTrans'] : '0';
                $info->bbe_trans = !empty($item['bbeTrans']) ? $item['bbeTrans'] : '0';
                $info->modify_by = 'system';
                $info->modify_time = date('Y-m-d H:i:s');
                $info->save(false);
            }
        }
    }

    /**
     * 政策状态
     */
    public function getOverview($accountInfo, $accessToken) {
        $data = EbayGccbtApi::accountOverview($accessToken);
        if (empty($data)) {
            return false;
        }

        $info = new EbaySellerAccountOverview();

        $info->account_id = $accountInfo->id;
        $info->long_term_status = isset($data['longTermStatus']) ? $data['longTermStatus'] : '-1';
        $info->non_shipping_status = isset($data['nonShippingStatus']) ? $data['nonShippingStatus'] : '-1';
        $info->shipping_status = isset($data['shippingStatus']) ? $data['shippingStatus'] : '-1';
        $info->edshipping_status = isset($data['edshippingStatus']) ? $data['edshippingStatus'] : '-1';
        $info->pgc_tracking_status = isset($data['pgcTrackingStatus']) ? $data['pgcTrackingStatus'] : '-1';
        $info->ware_house_status = isset($data['wareHouseStatus']) ? $data['wareHouseStatus'] : '-1';
        $info->qc_listing_status = isset($data['qcListingStatus']) ? $data['qcListingStatus'] : '-1';
        $info->inr_status = isset($data['inrStatus']) ? $data['inrStatus'] : '-1';
        $info->create_by = 'system';
        $info->create_time = date('Y-m-d H:i:s');
        $info->modify_by = 'system';
        $info->modify_time = date('Y-m-d H:i:s');
        $info->save(false);
    }

    /*     * *
     * 获取SpeedPAK 物流管理方案及其他符合政策要求的物流服务使用状态相关交易下载数据
     * * */

    public function getlistDownload($accountInfo, $accessToken) {
        $data = EbayGccbtApi::listdownload($accessToken);
        if (empty($data)) {
            return false;
        }
        foreach ($data as $v) {
            $info = EbayListDownload::findOne(['account_id' => $accountInfo->id, 'createPst' => $v['createPst'],'itemId' => $v['itemId']]);
            if (empty($info)) {
                $info = new EbayListDownload();
                $info->create_by = 'system';
                $info->create_time = date('Y-m-d H:i:s');
            }
            $info->account_id = $accountInfo->id;
            $info->createPst =$v['createPst'];
            $info->transId = $v['transId'];
            $info->itemId = $v['itemId'];
            $info->transPaidDate = $v['transPaidDate'];
            $info->buyerAddressCntry = $v['buyerAddressCntry'];
            $info->asp = $v['asp'];
            $info->aspCurrency = $v['aspCurrency'];
            $info->itemLocation = $v['itemLocation'];
            $info->buyerSelShipOpt = $v['buyerSelShipOpt'];
            $info->buyerSelShipType = $v['buyerSelShipType'];
            $info->trackingNumber = $v['trackingNumber'];
            $info->carrierName = $v['carrierName'];
            $info->shippingService = $v['shippingService'];
            $info->aScanDate = $v['aScanDate'];
            $info->promisedHandlingTime = $v['promisedHandlingTime'];
            $info->useSpeedpakPlusFlag = $v['useSpeedpakPlusFlag'];
            $info->aScanOnTimeFlag = $v['aScanOnTimeFlag'];
            $info->serviceLevelMatchFlag = $v['serviceLevelMatchFlag'];
            $info->transComplyFlag = $v['transComplyFlag'];
            $info->save();
        }
    }

    
    /***
     * 政策细分：买家选择SpeedPAK物流选项时卖家正确使用SpeedPAK物流管理方案表现相关交易下载数据
     * **/
    public function getMisuseDownload($accountInfo, $accessToken){ 
        $data = EbayGccbtApi::misusedownload($accessToken);
        if (empty($data)) {
            return false;
        }
         foreach ($data as $v) {
            $info = EbayListDownload::findOne(['account_id' => $accountInfo->id, 'createPst' => $v['createPst'],'transId' => $v['transId']]);
            if(empty($info)){
               $info = new EbayMisuseDownload();
               $info->create_by = 'system';
               $info->create_time = date('Y-m-d H:i:s');
            }
            $info->account_id = $accountInfo->id;
            $info->createPst =$v['createPst'];
            $info->transId = $v['transId'];
            $info->itemId = $v['itemId'];
            $info->transPaidDate = $v['transPaidDate'];
            $info->trackingNumber = $v['trackingNumber'];
            $info->buyerSelShipOpt = $v['buyerSelShipOpt'];
            $info->speedpakServiceLevel = $v['speedpakServiceLevel'];
            $info->save();
        }

    }
}

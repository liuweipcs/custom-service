<?php

namespace app\modules\services\modules\amazon\models;

use app\modules\services\modules\amazon\components\GetReportRequest;
use app\modules\services\modules\amazon\components\GetReportListRequest;
use app\modules\services\modules\amazon\components\GetReportListRequestNT;
use app\modules\services\modules\amazon\components\RequestReportRequest;
use app\modules\services\modules\amazon\components\GetReportRequestListRequest;
use app\modules\mails\models\AmazonFeedBack;
use app\modules\mails\models\AmazonFBAReturn;
use app\modules\mails\models\AmazonResponseReportTask;
use app\modules\accounts\models\Account;
use app\common\VHelper;

class AmazonMWS
{
    /**
     * api wait second
     */
    const WAIT_SECOND = 5;

    /**
     * Get request_id list
     *
     * @param  string $accountName amazon account
     * @param  string $reportType report type
     * @param  string $fromDate from date to catch
     * @param  \Closure|null $func handle function
     *
     * @return array
     */
    public static function getRequestIds($oldAccountId, $reportType, $fromDate)
    {
        $requestReport = new RequestReportRequest();
        try {
	        $response = $requestReport->setAccountId($oldAccountId)
	            ->setServiceUrl()
	            ->setConfig()
	            ->setReportType($reportType)
	            ->setFromDate($fromDate)
	            ->setToDate('0 days')
	            ->setRequest()
	            ->setType('webservice')
	            ->setService()
	            ->sendHttpRequest()
	            ->getResponse();

	        $result = $requestReport->parseResponse($response);

	        $model = new AmazonResponseReportTask();
	        $model->report_type = $result['report_type']; 
	        $model->old_account_id = $oldAccountId; 
	        $model->start_date = $result['start_date']; 
	        $model->end_date = $result['end_date']; 
	        $model->submit_date = $result['submitted_date']; 
	        $model->request_id = $result['report_request_id']; 
	        $model->processing = $result['processing_status']; 
	        $model->create_date = date('Y-m-d H:i:s', time()); 

	        if(!$model->save()){
	            echo $oldAccountId.'--'.VHelper::errorToString($model->getErrors()).'<br/>';
	        }
	    } catch (\Exception $e) {
            echo $e->getMessage();
            echo $e->getFile();
            echo $e->getLine();
        }
    }

    /**
     * Get report list
     *
     * @param  string $accountName amazon account
     * @param  string $reportType report type
     * @param  string $fromDate from date to catch
     * @param  \Closure|null $func handle function
     *
     * @return array
     */
    public static function getReportList($accountName, $reportType, $fromDate, \Closure $func = null)
    {
        $reportList = new GetReportListRequest();

        $reponse = $reportList->setAccountName($accountName)
            ->setServiceUrl()
            ->setConfig()
            ->setReportType($reportType)
            ->setFromDate($fromDate)
            ->setToDate('0 days')
            ->setType('webservice')
            ->setRequest()
            ->setService()
            ->sendHttpRequest()
            ->getResponse();

        // parser first, then get the list
        $nextToken = $reportList->parseResponse(null);
        $reportIdList = $reportList->getReportIdList();

        while ($nextToken) {
            $reportListNT = new GetReportListRequestNT();

            $reportListNT->setAccountName($accountName)
                ->setServiceUrl()
                ->setConfig()
                ->setNextToken($nextToken)
                ->setType('webservice')
                ->setRequest()
                ->setService()
                ->sendHttpRequest()
                ->getResponse();

            //if has next token ?
            $nextToken = $reportListNT->parseResponse(null);
            $reportIdList = array_merge($reportIdList, $reportListNT->getReportIdList());
        }

        foreach ($reportIdList as $id) {
            $report = new GetReportRequest();

            $report->setAccountName($accountName)
                ->setServiceUrl()
                ->setConfig()
                ->setReportId($id)
                ->setRequest()
                ->setType('webservice')
                ->setService()
                ->sendHttpRequest()
                ->getResponse();

            $textReport = $report->parseResponse(null);

            if ($textReport && $func) {
                call_user_func($func, GetReportRequest::turnTxt2Array($textReport));
            }
        }

        return $reportIdList;
    }

    public static function handleReportUnitObjeccts($reportInfoList)
    {
        $rows = null;

        foreach ($reportInfoList as $it) {
            $object = array();

            $object['report_request_id'] = $it->getReportRequestId();
            $object['report_type'] = $it->getReportType();
            $object['report_processing_status'] = $it->getReportProcessingStatus();
            $object['generated_report_id'] = $it->getGeneratedReportId();
            $object['Scheduled'] = $it->getScheduled();
            $rows[] = $object;
        }

        return $rows;
    }

    /**
     * Get report list for feedback
     *
     * @param  string $accountName amazon account
     * @param  string $reportType report type
     * @param  string $fromDate from date to catch
     * @param  \Closure|null $func handle function
     *
     * @return array
     */
    public static function getReportLists($oldAccountId, $reportType, $idList, $fromDate, \Closure $func = null)
    {
        $reportList = new GetReportRequestListRequest();
        try {
	        $response = $reportList->setAccountId($oldAccountId)
	            ->setServiceUrl()
	            ->setConfig()
	            ->setReportType($reportType)
	            ->setFromDate($fromDate)
	            ->setToDate('0 days')
	            ->setReportRequestId($idList)
	            ->setRequest()
	            ->setType('webservice')
	            ->setService()
	            ->sendHttpRequest()
	            ->getResponse();

	        $getReportListResult = $response->getGetReportRequestListResult();

	        $reportInfoList = $getReportListResult->getReportRequestInfoList();
	        $rows['list'] = self::handleReportUnitObjeccts($reportInfoList);
	        $reportId = $rows['list'][0]['generated_report_id'];
	        //echo "<pre>";var_dump($rows['list']);exit;

	        if ($reportId) {
	        	$id = $reportId;
	            $report = new GetReportRequest();

	            $report->setAccountId($oldAccountId)
	                ->setServiceUrl()
	                ->setConfig()
	                ->setReportId($id)
	                ->setRequest()
	                ->setType('webservice')
	                ->setService()
	                ->sendHttpRequest()
	                ->getResponse();

	            $textReport = $report->parseResponse(null);

	            if ($textReport && $func) {
	            		call_user_func($func, GetReportRequest::turnTxt2Array($textReport));
	            }		
	        }
			$request_id = $idList[0];
	        $model = AmazonResponseReportTask::find()->where(['request_id'=>$request_id, 'old_account_id'=>$oldAccountId])->one();
			$model->report_id = $reportId ? $reportId:' ';
			$model->processing = $rows['list'][0]['report_processing_status'];
			$model->scheduled = $rows['list'][0]['Scheduled'] ? 2 : 1 ;
			$model->status = 2;

			if(!$model->save()){
			    echo $oldAccountId.'--'.VHelper::errorToString($model->getErrors()).'<br/>';
			}
	        return $reportId;
        } catch (Exception $e) {
        	echo $e->getMessage();
            echo $e->getFile();
            echo $e->getLine();
        }    
    }

    /**
     * get feedback from amazon report
     *
     * @param  string $accountId
     *
     * @return array
     */
    public static function getFeedBack($accountId)
    {
    	$accountId = $accountId ? (int)$accountId : 0;
        $account = Account::find()->where([
            'platform_code' => 'AMAZON',
            'id' => $accountId
        ])->one();
        $oldAccountId = $account->old_account_id;

        $idList = AmazonResponseReportTask::find()->select(['request_id'])->where(['old_account_id'=>$oldAccountId, 'status'=>1])->orderBy('submit_date DESC')->asArray()->one();
        if (!$idList) {
        	return true;
        }
        $idList = array_values($idList);
        self::getReportLists($oldAccountId, '_GET_SELLER_FEEDBACK_DATA_', $idList, '-15 days', function ($rs) use(&$accountId) {

            echo count($rs);
			echo '<br/>';
			if (isset($_GET['test']) && $_GET['test']=='list' ) {
				Vhelper::dump($rs);
			}

	        foreach ($rs as $key => $value) {
	            $hash = md5(implode(',', $value));
	            $exists = AmazonFeedBack::find()->where(['hash' => $hash])->exists();
	            $value = array_values($value);
	            $date = explode('/', $value[0]);
	            $email = strrev($value[8]);
	            $site_code = substr($email, 0, strpos($email, '.'));
	            if ($site_code == 'ed') {
	                $date = explode('.', $value[0]);
	            }
	            if ($site_code == 'moc') {
	                $dateData = date('Y-m-d', strtotime($date[0] . '/' . $date[1] . '/' . $date[2])); //美国时间格式
	            } else if ($site_code == 'pj') {
	                $dateData = date('Y-m-d', strtotime($date[1] . '/' . $date[2] . '/' . $date[0])); //日本时间格式
	            } else {
	                $dateData = date('Y-m-d', strtotime($date[1] . '/' . $date[0] . '/' . $date[2])); //欧洲时间格式
	            }
                    
	            if (!$exists) {
	                $model = new AmazonFeedBack();
	                $model->hash = $hash;

	                if ($site_code == 'pj') {
	                    $model->account_id = $accountId;
	                    $model->date = $dateData;
	                    $model->rating = $value[1];
	                    $model->comments = iconv("Shift_JIS", "UTF-8", $value[2]);
	                    $model->your_response = iconv("Shift_JIS", "UTF-8", $value[3]);
	                    $model->arrived_on_time = iconv("Shift_JIS", "UTF-8", $value[4]);
	                    $model->item_as_described = iconv("Shift_JIS", "UTF-8", $value[5]);
	                    $model->customer_service = iconv("Shift_JIS", "UTF-8", $value[6]);
	                    $model->order_id = $value[7];
	                    $model->rater_email = $value[8];
	                    $model->rater_role = '';
	                    $model->modified_time = date('Y-m-d H:i:s', time());
	                } else {
	                    $model->account_id = $accountId;
	                    $model->date = $dateData;
	                    $model->rating = $value[1];
	                    $model->comments = iconv("ISO-8859-1", "UTF-8", $value[2]);
	                    $model->your_response = iconv("ISO-8859-1", "UTF-8", $value[3]);
	                    $model->arrived_on_time = iconv("ISO-8859-1", "UTF-8", $value[4]);
	                    $model->item_as_described = iconv("ISO-8859-1", "UTF-8", $value[5]);
	                    $model->customer_service = iconv("ISO-8859-1", "UTF-8", $value[6]);
	                    $model->order_id = $value[7];
	                    $model->rater_email = $value[8];
	                    $model->rater_role = '';
	                    $model->modified_time = date('Y-m-d H:i:s', time());
	                }

                    if(!$model->save()){
                        echo $value[7].'--'.VHelper::errorToString($model->getErrors()).'<br/>';
                    }

	            }

        	}
        });
    }

    /**
     * get or updata feedback from amazon report
     *
     * @param  string $accountName
     *
     * @return array
     */
    public static function getFeedBackData($accountName)
    {
        $account = Account::find()->where([
            'platform_code' => 'AMAZON',
            'account_name' => $accountName
        ])->one();

        $accountId = $account ? (int)$account->id : 0;

        self::getReportList($accountName, '_GET_SELLER_FEEDBACK_DATA_', '-10 days', function ($rs) use (&$accountId) {
            echo count($rs);
            if (isset($_GET['test']) && $_GET['test'] == 'list') {
                Vhelper::dump($rs);
            }
            foreach ($rs as $key => $value) {
                $hash = md5(implode(',', $value));
                $exists = AmazonFeedBack::find()->where(['hash' => $hash])->one();
                $value = array_values($value);
                $date = explode('/', $value[0]);
                $email = strrev($value[8]);
                $site_code = substr($email, 0, strpos($email, '.'));
                if ($site_code == 'ed') {
                    $date = explode('.', $value[0]);
                }
                if ($site_code == 'moc') {
                    $dateData = date('Y-m-d', strtotime($date[0] . '/' . $date[1] . '/' . $date[2])); //美国时间格式
                } else if ($site_code == 'pj') {
                    $dateData = date('Y-m-d', strtotime($date[1] . '/' . $date[2] . '/' . $date[0])); //日本时间格式
                } else {
                    $dateData = date('Y-m-d', strtotime($date[1] . '/' . $date[0] . '/' . $date[2])); //欧洲时间格式
                }

                if (!$exists) {
                    try {
                        $model = new AmazonFeedBack();
                        $model->hash = $hash;

                        if ($site_code == 'pj') {
                            $model->account_id = $accountId;
                            $model->date = $dateData;
                            $model->rating = $value[1];
                            $model->comments = iconv("Shift_JIS", "UTF-8", $value[2]);
                            $model->your_response = iconv("Shift_JIS", "UTF-8", $value[3]);
                            $model->arrived_on_time = iconv("Shift_JIS", "UTF-8", $value[4]);
                            $model->item_as_described = iconv("Shift_JIS", "UTF-8", $value[5]);
                            $model->customer_service = iconv("Shift_JIS", "UTF-8", $value[6]);
                            $model->order_id = $value[7];
                            $model->rater_email = $value[8];
                            $model->rater_role = '';
                            $model->modified_time = date('Y-m-d H:i:s', time());
                        } else {
                            $model->account_id = $accountId;
                            $model->date = $dateData;
                            $model->rating = $value[1];
                            $model->comments = $value[2];
                            $model->your_response = $value[3];
                            $model->arrived_on_time = $value[4];
                            $model->item_as_described = $value[5];
                            $model->customer_service = $value[6];
                            $model->order_id = $value[7];
                            $model->rater_email = $value[8];
                            $model->rater_role = '';
                            $model->modified_time = date('Y-m-d H:i:s', time());
                        }
                        if (!$model->save()) var_dump($model->getErrors());
                    } catch (\Exception $e) {
                        echo $e->getMessage(), "<br/>\r\n";
                    }
                } else {
                    try {
                        $model = AmazonFeedBack::find()->where(['id' => $exists->id])->one();

                        if ($site_code == 'pj') {
                            $model->account_id = $accountId;

                            $model->rating = $value[1];
                            $model->comments = iconv("Shift_JIS", "UTF-8", $value[2]);
                            $model->your_response = iconv("Shift_JIS", "UTF-8", $value[3]);
                            $model->arrived_on_time = iconv("Shift_JIS", "UTF-8", $value[4]);
                            $model->item_as_described = iconv("Shift_JIS", "UTF-8", $value[5]);
                            $model->customer_service = iconv("Shift_JIS", "UTF-8", $value[6]);
                            $model->order_id = $value[7];
                            $model->rater_email = $value[8];
                            $model->rater_role = '';
                            $model->modified_time = date('Y-m-d H:i:s', time());
                        } else {
                            $model->account_id = $accountId;

                            $model->rating = $value[1];
                            $model->comments = $value[2];
                            $model->your_response = $value[3];
                            $model->arrived_on_time = $value[4];
                            $model->item_as_described = $value[5];
                            $model->customer_service = $value[6];
                            $model->order_id = $value[7];
                            $model->rater_email = $value[8];
                            $model->rater_role = '';
                            $model->modified_time = date('Y-m-d H:i:s', time());
                        }

                        if (!$model->save()) var_dump($model->getErrors());
                    } catch (\Exception $e) {
                        echo $e->getMessage(), "<br/>\r\n";
                    }
                }
            }
        });
    }

    /**
     * get fba return through report
     *
     * @param  string $accountName amazona account
     *
     * @return array
     */
    public static function getFBAReturn($accountName)
    {
        $account = Account::find()->where([
            'platform_code' => 'AMAZON',
            'account_name' => $accountName
        ])->one();

        $accountId = $account ? (int)$account->id : 0;

        self::getReportList($accountName, '_GET_FBA_FULFILLMENT_CUSTOMER_RETURNS_DATA_', '-3 days', function ($rs) use (&$accountId) {
            foreach ($rs as $key => $value) {
                $hash = md5(implode(',', $value));
                $exists = AmazonFBAReturn::find()->where(['hash' => $hash])->one();

                if (!$exists) {
                    try {
                        $model = new AmazonFBAReturn();

                        $model->hash = $hash;
                        $model->account_id = $accountId;
                        $model->return_date = date('Y-m-d H:i:s', strtotime($value['return-date']));
                        $model->order_id = $value['order-id'];
                        $model->sku = $value['sku'];
                        $model->asin = $value['asin'];
                        $model->fnsku = $value['fnsku'];
                        $model->product_name = $value['product-name'];
                        $model->quantity = $value['quantity'];
                        $model->fulfillment_center_id = $value['fulfillment-center-id'];
                        $model->detailed_disposition = $value['detailed-disposition'];
                        $model->reason = $value['reason'];
                        $model->status = $value['status'];
                        $model->update_date = time();

                        if (!$model->save()) var_dump($model->getErrors());
                    } catch (\Exception $e) {
                        echo $e->getMessage(), "<br/>\r\n";
                    }
                }
            }
        });
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/29 0029
 * Time: 上午 9:44
 */

namespace app\modules\mails\controllers;

use app\common\VHelper;
use app\components\Controller;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\EbayFeedback;
use app\modules\mails\models\EbayFeedbackResponse;
use app\modules\orders\models\Logistic;
use app\modules\orders\models\Order;
use app\modules\orders\models\Warehouse;
use yii\base\Exception;
use Yii;
use app\modules\services\modules\ebay\models\RespondToFeedback;
use app\modules\accounts\models\Account;
use yii\helpers\Json;
use yii\helpers\Url;
use app\modules\systems\models\BasicConfig;
use app\modules\reports\models\FeedbackStatistics;

class EbayfeedbackresponseController extends Controller
{
    public function actionAdd()
    {
        $this->isPopup = true;
        $id = $this->request->get('id');
        $responseType = $this->request->get('type');    //字符串Reply　OR　FOLLOWUP

        $account = Yii::$app->user->id;
        $ebayFeedbackModel = EbayFeedback::findOne((int)$id);
        $platform = Platform::PLATFORM_CODE_EB;
        $platform_order_id = $ebayFeedbackModel->order_line_item_id;

        $is_replied = EbayFeedbackResponse::find()->where('feedback_id=:feid and status=:status', [':feid' => $ebayFeedbackModel->feedback_id, ':status' => 1])->one();

        if (!$model = EbayFeedbackResponse::findOne(['feedback_id' => $ebayFeedbackModel->feedback_id, 'response_type' => $responseType]))
            $model = new EbayFeedbackResponse();
        if ($this->request->getIsAjax()) {

            if (isset($is_replied->status)) {
                $this->_showMessage('评论已回复', false);
            }
            if ($responseType === false) {
                $this->_showMessage('type值错误。', false);
            }
            $model->load($this->request->post());
            $model->feedback_id = $ebayFeedbackModel->feedback_id;
            $model->item_id = $ebayFeedbackModel->item_id;
            $model->order_line_item_id = $ebayFeedbackModel->order_line_item_id;
            $model->target_user_id = $ebayFeedbackModel->commenting_user;
            //$model->target_user_id = $ebayFeedbackModel->commenting_user;
            $model->transaction_id = $ebayFeedbackModel->transaction_id;

            $accountID = Account::findOne((int)$ebayFeedbackModel->account_id)->old_account_id;
            $model->account_id = $accountID;
            $model->siteid = $ebayFeedbackModel->siteid;
            $model->response_type = $responseType;

            $dbTransaction = $model->getDb()->beginTransaction();
            try {

                if (isset($accountID)) {
                    if (is_numeric($accountID) && $accountID > 0 && $accountID % 1 === 0) {
                        $flag = $model->save();
                        if (!$flag) {
                            $errors = $model->getErrors();
                            $error = array_pop($errors);
                            $this->_showMessage(\Yii::t('system', $error[0]), false);
                        }
                        ignore_user_abort(true);
                        set_time_limit(600);
                        $FeedbackModel = new RespondToFeedback($model);
                        $FeedbackModel->FeedbackID = $model->feedback_id;
                        $FeedbackModel->ResponseText = $model->response_text;
                        $FeedbackModel->ResponseType = $responseType;
                        $FeedbackModel->TargetUserID = $model->target_user_id;
                        $result = $FeedbackModel->handleResponse();

                        if (stripos($result, 'Success') !== false) {
                            $ebayFeedbackModel->status = 2;
                            if ($ebayFeedbackModel->save()) {
                                $dbTransaction->commit();
                                //评价回复处理
                                $feedbackStatistics = FeedbackStatistics::findOne(['feedback_id'=>$ebayFeedbackModel->feedback_id,'platform_code'=>Platform::PLATFORM_CODE_EB]);
                                if($feedbackStatistics && $feedbackStatistics->status == 0){
                                    $feedbackStatistics->status = 1;
                                    $feedbackStatistics->save(false);
                                }
                                $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebayfeedback/list') . '");';
                                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, $refreshUrl);
//                                $this->_showMessage('成功');

                            } else {
                                $dbTransaction->rollBack();
                                $this->_showMessage(\Yii::t('system', 'save feedback to is_reply false'), false);
                            }

                        } elseif (stripos($result, 'combination') !== false) {

                            throw new Exception("。Please make sure FeedbackID, TargetUserID, and ResponseType values combination is valid");

                        } elseif (stripos($result, 'Response Text too long') !== false) {

                            throw new Exception("。Response Text too long. Max length 80");
                        } elseif (stripos($result, 'Failure') !== false) {
                            $result = simplexml_load_string($result);
                            $dbTransaction->rollBack();
                            $this->_showMessage($result->Errors->LongMessage->__toString(), false);
                        }
                    }
                }


            } catch (Exception $e) {

                $dbTransaction->rollBack();

                $this->_showMessage(\Yii::t('system', 'Operate Failed') . $e->getMessage(), false);
            }
        }

        $orderinfo = Order::getOrderStack($platform, $platform_order_id);
        //如果找不到订单信息，通过transaction_id来查找
        if (empty($orderinfo)) {
            $orderinfo = Order::getOrderStackByTransactionId($platform, $ebayFeedbackModel->transaction_id);
        }
        if (!empty($orderinfo)) {
            $orderinfo = Json::decode(Json::encode($orderinfo), true);
        } else {
            $orderinfo = [];
        }
        $warehouseList = Warehouse::getWarehouseList();
        $logistics = Logistic::getAllLogistics();

        $departmentList = BasicConfig::getParentList(52);

        return $this->render('add', [
            'model' => $model,
            'platform' => $platform,
            'platform_order_id' => $platform_order_id,
            'ebayFeedbackModel' => $ebayFeedbackModel,
            'replyModel' => $is_replied,
            'info' => $orderinfo,
            'warehouseList' => $warehouseList,
            'logistics' => $logistics,
            'departmentList' => $departmentList,
        ]);
    }

    public function actionAddlist()
    {
        $this->isPopup = true;
        $responseType = 'FollowUp';
        $ids = $this->request->getQueryParam('ids');

        $account = Yii::$app->user->id;
        $platform = Platform::PLATFORM_CODE_EB;

        if ($this->request->getIsAjax()) {
            $ids = explode(',', $ids);

            foreach ($ids as $id) {

                $ebayFeedbackModel = EbayFeedback::findOne((int)$id);

                $platform_order_id = $ebayFeedbackModel->order_line_item_id;

                $is_replied = EbayFeedbackResponse::find()->where('feedback_id=:feid and status=:status', [':feid' => $ebayFeedbackModel->feedback_id, ':status' => 1])->one();
                if (isset($is_replied->status)) {
                    $this->_showMessage('评论已回复', false);
                }

                if (!$model = EbayFeedbackResponse::findOne(['feedback_id' => $ebayFeedbackModel->feedback_id, 'response_type' => $responseType]))
                    $model = new EbayFeedbackResponse();


                if ($responseType === false)
                    $this->_showMessage('type值错误。', false);
                $model->load($this->request->post());
                $model->feedback_id = $ebayFeedbackModel->feedback_id;
                $model->item_id = $ebayFeedbackModel->item_id;
                $model->order_line_item_id = $ebayFeedbackModel->order_line_item_id;
                $model->target_user_id = $ebayFeedbackModel->commenting_user;
                //$model->target_user_id = $ebayFeedbackModel->commenting_user;
                $model->transaction_id = $ebayFeedbackModel->transaction_id;

                $accountID = Account::findOne((int)$ebayFeedbackModel->account_id)->old_account_id;
                $model->account_id = $accountID;
                $model->siteid = $ebayFeedbackModel->siteid;
                $model->response_type = $responseType;

                $dbTransaction = $model->getDb()->beginTransaction();
                try {

                    if (isset($accountID)) {
                        if (is_numeric($accountID) && $accountID > 0 && $accountID % 1 === 0) {
                            $flag = $model->save();
                            if (!$flag) {
                                $errors = $model->getErrors();
                                $error = array_pop($errors);
                                $this->_showMessage(\Yii::t('system', $error[0]), false);
                            }
                            ignore_user_abort(true);
                            set_time_limit(600);
                            $FeedbackModel = new RespondToFeedback($model);
                            $FeedbackModel->FeedbackID = $model->feedback_id;
                            $FeedbackModel->ResponseText = $model->response_text;
                            $FeedbackModel->ResponseType = $responseType;
                            $FeedbackModel->TargetUserID = $model->target_user_id;
                            $result = $FeedbackModel->handleResponse();
                            if (stripos($result, 'Success') !== false) {
                                $dbTransaction->commit();
                                $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebayfeedback/list') . '");';
                                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, $refreshUrl);
//                                $this->_showMessage('成功');
                            } elseif (stripos($result, 'combination') !== false) {

                                throw new Exception("。Please make sure FeedbackID, TargetUserID, and ResponseType values combination is valid");

                            } elseif (stripos($result, 'Response Text too long') !== false) {

                                throw new Exception("。Response Text too long. Max length 80");
                            }
                        }
                    }


                } catch (Exception $e) {

                    $dbTransaction->rollBack();

                    $this->_showMessage(\Yii::t('system', 'Operate Failed') . $e->getMessage(), false);
                }

            }

            if ($flag) {
                $this->_showMessage(\Yii::t('system', 'Operate Success') . $e->getMessage(), true);
            }
        }


        return $this->render('addlist', ['ids' => $ids, 'platform' => $platform,]);
    }
}
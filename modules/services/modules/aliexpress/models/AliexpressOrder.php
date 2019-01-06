<?php

namespace app\modules\services\modules\aliexpress\models;

use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\AliexpressDisputeDetail;
use app\modules\mails\models\AliexpressDisputeList;
use Yii;
use app\components\Model;
use app\components\ConfigFactory;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\services\modules\aliexpress\components\TaobaoQimenApi;

class AliexpressOrder extends Model
{
    /**
     * 操作数据库名
     */
    public static function getDb()
    {
        return Yii::$app->db_order;
    }

    /**
     * 操作表名
     */
    public static function tableName()
    {
        return '{{%order_aliexpress}}';
    }

    /**
     * 返回带数据库前缀的表名
     */
    public static function dbTableName()
    {
        preg_match("/dbname=([^;]+)/i", static::getDb()->dsn, $matches);
        return $matches[1] . '.{{%order_aliexpress}}';
    }

    /**
     * 通过速卖通接口，获取订单的详情
     * @param $platformOrderId 平台订单ID
     * @param $accountId 账号ID(ERP系统的)
     */
    public static function getOrderInfo($platformOrderId, $accountId)
    {
        //获取速卖通账号
        $account = AliexpressAccount::find()->where(['id' => $accountId])->asArray()->one();

        if (empty($account)) {
            return false;
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['erp_gatewayUrl']) ? $qimenApiInfo['erp_gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return false;
        }

        //创建奇门请求api
        $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
        $taobaoQimenApi->setGatewayUrl($gatewayUrl);

        //配置请求参数
        $req = new \MinxinOrderFindorderqueryRequest();
        //设置账号ID
        $req->setAccountId($account['id']);
        //设置订单ID
        $req->setOrderId($platformOrderId);
        $taobaoQimenApi->doRequest($req);
        if (!$taobaoQimenApi->isSuccess()) {
            return false;
        }
        $data = $taobaoQimenApi->getResponse();
        $data = json_decode(json_encode($data), true, 512, JSON_BIGINT_AS_STRING);
        return $data;
    }

    /**
     * 新版获取订单的详情
     * @param $platformOrderId 平台订单ID
     * @param $accountId 账号ID(ERP系统的)
     */
    public static function getNewOrderInfo($platformOrderId, $accountId)
    {
        //获取速卖通账号
        $account = AliexpressAccount::find()->where(['id' => $accountId])->asArray()->one();

        if (empty($account)) {
            return false;
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['erp_gatewayUrl']) ? $qimenApiInfo['erp_gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return false;
        }

        //创建奇门请求api
        $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
        $taobaoQimenApi->setGatewayUrl($gatewayUrl);

        //配置请求参数
        $req = new \MinxinOrderFindorderqueryRequest();
        $req->setAccountId($account['id']);
        $req->setOrderId($platformOrderId);
        $taobaoQimenApi->doRequest($req);
        if (!$taobaoQimenApi->isSuccess()) {
            return false;
        }
        $data = $taobaoQimenApi->getResponse();
        $data = json_decode(json_encode($data), true, 512, JSON_BIGINT_AS_STRING);
        return $data;
    }

    /**
     * 通过速卖通接口，获取订单是否有纠纷
     *
     * @param $platformOrderId 平台订单ID
     * @param $accountId 账号ID(ERP系统的)
     *
     * END_ISSUE 纠纷结束
     * NO_ISSUE 没有纠纷
     * IN_ISSUE 纠纷处理中
     */
    public static function getOrderIssueStatus($platformOrderId, $accountId)
    {
        $data = self::getOrderInfo($platformOrderId, $accountId);
        if (empty($data['target']['issue_status'])) {
            return '';
        }
        return $data['target']['issue_status'];
    }

    /**
     * 通过速卖通接口，获取订单的纠纷详情
     * @param $issueId 纠纷ID
     */
    public static function getOrderIssueInfo($issueId)
    {
        if (empty($issueId)) {
            return false;
        }
        //获取纠纷信息
        $issueInfo = AliexpressDisputeList::find()->where(['platform_dispute_id' => $issueId])->asArray()->one();
        if (empty($issueInfo)) {
            return false;
        }

        //获取ERP账号ID
        $accountId = Account::find()
            ->select('old_account_id')
            ->where(['id' => $issueInfo['account_id'], 'platform_code' => Platform::PLATFORM_CODE_ALI])
            ->scalar();

        //获取速卖通账号
        $account = AliexpressAccount::find()->where(['id' => $accountId])->asArray()->one();
        if (empty($account)) {
            return false;
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['gatewayUrl']) ? $qimenApiInfo['gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return false;
        }

        //创建奇门请求api
        $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
        $taobaoQimenApi->setGatewayUrl($gatewayUrl);

        $req = new \MinxinAliexpressObtainingconsultativedataRequest();
        $req->setAccountId($account['id']);
        $req->setBuyerLoginId($issueInfo['buyer_login_id']);
        $req->setIssueId($issueId);

        $taobaoQimenApi->doRequest($req);
        if (!$taobaoQimenApi->isSuccess()) {
            return false;
        }

        $data = $taobaoQimenApi->getResponse();
        if (!empty($data->result_object)) {
            $data = json_decode(json_encode($data->result_object), true, 512, JSON_BIGINT_AS_STRING);
        } else {
            $data = [];
        }
        return $data;
    }

    /**
     * 获取卖家退货地址
     * @param $accountId 账号ID(ERP系统的)
     */
    public static function getSellerRefundAddress($accountId)
    {
        if (empty($accountId)) {
            return false;
        }

        //获取速卖通账号
        $account = AliexpressAccount::find()->where(['id' => $accountId])->asArray()->one();
        if (empty($account)) {
            return false;
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['erp_gatewayUrl']) ? $qimenApiInfo['erp_gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return false;
        }

        //创建奇门请求api
        $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
        $taobaoQimenApi->setGatewayUrl($gatewayUrl);

        $req = new \MinxinLogisticsGetselleraddressRequest();
        $req->setAccountId($accountId);
        $req->setSellerAddressQuery('refund');

        $taobaoQimenApi->doRequest($req);
        if (!$taobaoQimenApi->isSuccess()) {
            echo $taobaoQimenApi->getErrorMessage();
            return false;
        }
        $data = $taobaoQimenApi->getResponse();
        $data = json_decode(json_encode($data), true);
        return !empty($data['refund_seller_address_list']['refundselleraddresslist']) ? $data['refund_seller_address_list']['refundselleraddresslist'] : [];
    }

    /**
     * 卖家同意普通纠纷方案
     * @param $issueId 纠纷ID
     * @param $solutionId 协商ID
     */
    public static function agreeIssueSolution($issueId, $solutionId)
    {
        //获取纠纷列表
        $issueInfo = AliexpressDisputeList::find()->where(['platform_dispute_id' => $issueId])->asArray()->one();
        if (empty($issueInfo)) {
            return '没有找到纠纷信息';
        }
        //获取账号信息
        $accountInfo = Account::findOne($issueInfo['account_id']);
        if (empty($accountInfo)) {
            return '没有找到账号信息';
        }
        //获取速卖通账号
        $account = AliexpressAccount::find()->where(['id' => $accountInfo['old_account_id']])->asArray()->one();
        if (empty($account)) {
            return '没有找到账号信息';
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['gatewayUrl']) ? $qimenApiInfo['gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return '奇门网关地址为空';
        }

        //创建奇门请求api
        $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
        $taobaoQimenApi->setGatewayUrl($gatewayUrl);

        $req = new \MinxinAliexpressIssueSolutionagreeRequest();
        //纠纷id
        $req->setIssueId($issueId);
        //账号id
        $req->setAccountId($account['id']);
        //买家登录id
        $req->setBuyerLoginId($issueInfo['buyer_login_id']);
        //同意方案id
        $req->setSolutionId($solutionId);

        $taobaoQimenApi->doRequest($req);
        if (!$taobaoQimenApi->isSuccess()) {
            return $taobaoQimenApi->getErrorMessage();
        }

        return true;
    }

    /**
     * 卖家拒绝普通纠纷方案
     * @param $issueId 纠纷ID
     * @param $solutionId 协商ID
     */
    public static function refuseIssueSolution($issueId, $solutionId)
    {
        //获取纠纷列表
        $issueInfo = AliexpressDisputeList::find()->where(['platform_dispute_id' => $issueId])->asArray()->one();
        if (empty($issueInfo)) {
            return '没有找到纠纷信息';
        }
        //获取账号信息
        $accountInfo = Account::findOne($issueInfo['account_id']);
        if (empty($accountInfo)) {
            return '没有找到账号信息';
        }
        //获取速卖通账号
        $account = AliexpressAccount::find()->where(['id' => $accountInfo['old_account_id']])->asArray()->one();
        if (empty($account)) {
            return '没有找到账号信息';
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['gatewayUrl']) ? $qimenApiInfo['gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return '奇门网关地址为空';
        }

        //创建奇门请求api
        $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
        $taobaoQimenApi->setGatewayUrl($gatewayUrl);

        $req = new \MinxinAliexpressIssuesolutionsaveRequest();
        $req->setAccountId($account['id']);
        //纠纷id
        $req->setIssueId($issueId);
        //买家登录id
        $req->setBuyerLoginId($issueInfo['buyer_login_id']);
        //拒绝买家方案id
        $req->setBuyerSolutionId($solutionId);

        $taobaoQimenApi->doRequest($req);
        if (!$taobaoQimenApi->isSuccess()) {
            return $taobaoQimenApi->getErrorMessage();
        }

        return true;
    }

    /**
     * 新增或修改方案, 如果$solutionId为空，则为新增方案
     * @param $issueId 纠纷ID
     * @param $solutionId 协商ID
     * @param $solutionType 方案类型(退款refund,退货退款return_and_refund)
     * @param $refundAmount 退款金额
     * @param $solutionContext 理由说明
     * @param $buyerSolutionId 买家方案ID
     * @param int $returnGoodAddressId 退货地址id
     * @param string $refundAmountCurrency 退款金额币种
     */
    public static function saveIssueSolution($issueId, $solutionId, $solutionType, $refundAmount, $solutionContext, $buyerSolutionId = 0, $returnGoodAddressId = 0, $refundAmountCurrency = '')
    {
        //获取纠纷列表
        $issueInfo = AliexpressDisputeList::find()->where(['platform_dispute_id' => $issueId])->asArray()->one();
        if (empty($issueInfo)) {
            return '没有找到纠纷信息';
        }
        //获取账号信息
        $accountInfo = Account::findOne($issueInfo['account_id']);
        if (empty($accountInfo)) {
            return '没有找到账号信息';
        }
        //获取速卖通账号
        $account = AliexpressAccount::find()->where(['id' => $accountInfo['old_account_id']])->asArray()->one();
        if (empty($account)) {
            return '没有找到账号信息';
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['gatewayUrl']) ? $qimenApiInfo['gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return '奇门网关地址为空';
        }

        //创建奇门请求api
        $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
        $taobaoQimenApi->setGatewayUrl($gatewayUrl);

        $req = new \MinxinAliexpressIssuesolutionsaveRequest();
        $req->setAccountId($account['id']);
        //纠纷id
        $req->setIssueId($issueId);
        //买家登录id
        $req->setBuyerLoginId($issueInfo['buyer_login_id']);

        //退货的需要设置退货地址
        if ($solutionType == 'return_and_refund') {
            $req->setReturnGoodAddressId($returnGoodAddressId);
        }

        if (empty($solutionId)) {
            //设置是否新增方案
            $req->setAddSellerSolution('true');
            //方案类型
            $req->setAddSolutionType($solutionType);

            //买家方案ID
            if (!empty($buyerSolutionId)) {
                $req->setBuyerSolutionId($buyerSolutionId);
            }
        } else {
            //设置是否新增方案
            $req->setAddSellerSolution('false');
            //方案类型
            $req->setAddSolutionType($solutionType);
            //修改方案id
            $req->setModifySellerSolutionId($solutionId);
        }

        //退款金额
        if (empty($refundAmount)) {
            $req->setRefundAmount('0.00');
        } else {
            $req->setRefundAmount(strval($refundAmount));
        }

        //退款金额币种
        if (!empty($refundAmountCurrency)) {
            $req->setRefundAmountCurrency($refundAmountCurrency);
        } else {
            //如果退款币种为空，则获取纠纷详情中的退款当地货币币种
            $detail = AliexpressDisputeDetail::findOne(['platform_dispute_id' => $issueId]);
            if (!empty($detail) && !empty($detail->refund_money_max_local_currency)) {
                $req->setRefundAmountCurrency($detail->refund_money_max_local_currency);
            } else {
                $req->setRefundAmountCurrency('USD');
            }
        }

        //理由说明
        $req->setSolutionContext($solutionContext);

        //echo '<pre>';
        //echo 'account_id: '; var_dump($account['id']);
        //echo 'issue_id: '; var_dump($issueId);
        //echo 'buyer_login_id: '; var_dump($issueInfo['buyer_login_id']);
        //echo 'modify_seller_solution_id: '; var_dump($solutionId);
        //echo '<hr>';
        //var_dump($req);

        $taobaoQimenApi->doRequest($req);
        if (!$taobaoQimenApi->isSuccess()) {
            return $taobaoQimenApi->getErrorMessage();
        }

        return true;
    }

    /**
     * 卖家上传纠纷证据图片
     * @param $issueId 纠纷ID
     * @param $imgExt 图片扩展名
     * @param $imgData 图片二进制数据
     * @param string $imgFileName 图片名
     * @return string
     */
    public static function addIssueImage($issueId, $imgExt, $imgData, $imgFileName = '')
    {
        //获取纠纷列表
        $issueInfo = AliexpressDisputeList::find()->where(['platform_dispute_id' => $issueId])->asArray()->one();
        if (empty($issueInfo)) {
            return '没有找到纠纷信息';
        }
        //获取账号信息
        $accountInfo = Account::findOne($issueInfo['account_id']);
        if (empty($accountInfo)) {
            return '没有找到账号信息';
        }
        //获取速卖通账号
        $account = AliexpressAccount::find()->where(['id' => $accountInfo['old_account_id']])->asArray()->one();
        if (empty($account)) {
            return '没有找到账号信息';
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['gatewayUrl']) ? $qimenApiInfo['gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return '奇门网关地址为空';
        }

        //创建奇门请求api
        $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
        $taobaoQimenApi->setGatewayUrl($gatewayUrl);

        $req = new \MinxinAliexpressIssueimageUploadRequest();
        $req->setIssueId($issueId);
        $req->setBuyerLoginId($issueInfo['buyer_login_id']);
        $req->setAccountId($account['id']);
        $req->setExtension($imgExt);
        $req->setFileName($imgFileName);
        $req->setImageBytes(base64_encode($imgData));

        $taobaoQimenApi->doRequest($req);
        if (!$taobaoQimenApi->isSuccess()) {
            return $taobaoQimenApi->getErrorMessage();
        }

        return true;
    }

    /**
     * 卖家对未评价的订单进行评价
     * @param $platformOrderId 平台订单ID
     * @param $accountId 账号ID(ERP系统的)
     * @param $score 星级
     * @param $feedbackContent 评价内容
     * @param $imageUrls 图片地址数组
     */
    public static function addFeedback($platformOrderId, $accountId, $score = 0, $feedbackContent = '', $imageUrls = [])
    {
        //获取速卖通账号
        $account = AliexpressAccount::find()->where(['id' => $accountId])->asArray()->one();
        if (empty($account)) {
            return '没有找到账号信息';
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['erp_gatewayUrl']) ? $qimenApiInfo['erp_gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return '奇门网关地址为空';
        }

        //创建奇门请求api
        $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
        $taobaoQimenApi->setGatewayUrl($gatewayUrl);

        //配置请求参数
        $req = new \MinxinAliexpressAutomaticassessmentRequest();
        //设置账号id
        $req->setAccountId($accountId);
        //设置留评内容
        $req->setFeedbackContent($feedbackContent);
        //设置订单id
        $req->setOrderId($platformOrderId);
        //设置留评星级
        $req->setScore($score);

        $taobaoQimenApi->doRequest($req);
        if (!$taobaoQimenApi->isSuccess()) {
            return $taobaoQimenApi->getErrorMessage();
        }

        return true;
    }

    /**
     * 回复已生效的订单评价
     * @param $platformOrderId 平台订单ID
     * @param $platformParentOrderId 平台父订单ID
     * @param $accountId 账号ID(ERP系统的)
     * @param $text 回复内容
     */
    public static function replyFeedback($platformOrderId, $platformParentOrderId, $accountId, $text)
    {
        //获取速卖通账号
        $account = AliexpressAccount::find()->where(['id' => $accountId])->asArray()->one();
        if (empty($account)) {
            return '没有找到账号信息';
        }

        //获取奇门网关地址
        $qimenApiInfo = ConfigFactory::getConfig('qimen_api');
        $gatewayUrl = !empty($qimenApiInfo['gatewayUrl']) ? $qimenApiInfo['gatewayUrl'] : '';

        if (empty($gatewayUrl)) {
            return '奇门网关地址为空';
        }

        //创建奇门请求api
        $taobaoQimenApi = new TaobaoQimenApi($account['app_key'], $account['secret_key'], $account['access_token']);
        $taobaoQimenApi->setGatewayUrl($gatewayUrl);

        $req = new \MinxinAliexpressEvaluationReplyRequest();
        $req->setAccountId($accountId);
        $req->setChildOrderId($platformOrderId);
        $req->setParentOrderId($platformParentOrderId);
        $req->setText($text);

        $taobaoQimenApi->doRequest($req);
        if (!$taobaoQimenApi->isSuccess()) {
            return $taobaoQimenApi->getErrorMessage();
        }

        return true;
    }
}
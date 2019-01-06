<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/17
 * Time: 17:48
 */

namespace app\modules\mails\controllers;

use app\modules\mails\models\AliexpressDisputeAttachments;
use app\modules\mails\models\AliexpressDisputeProcess;
use app\modules\mails\models\AliexpressHolidayResponseTime;
use Yii;
use app\components\Controller;
use app\modules\mails\models\AliexpressDisputeList;
use app\modules\mails\models\AliexpressDisputeDetail;
use app\modules\mails\models\AliexpressDisputeSolution;
use app\common\VHelper;
use app\modules\orders\models\OrderKefu;
use app\modules\services\modules\aliexpress\models\OrderMessage;
use app\modules\services\modules\aliexpress\models\OrdeArbitration;
use app\modules\services\modules\aliexpress\models\GoodsReceipt;
use app\modules\services\modules\aliexpress\models\WaiverReturns;
use app\modules\accounts\models\Platform;
use yii\helpers\Json;
use app\modules\orders\models\Order;
use app\modules\mails\models\AliexpressExpression;
use app\modules\orders\models\Warehouse;
use app\modules\systems\models\Country;
use app\modules\orders\models\Transactionrecord;
use app\modules\accounts\models\Account;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\systems\models\RefundAccount;
use app\modules\services\modules\aliexpress\models\AliexpressOrder;
use yii\web\UploadedFile;
use yii\helpers\Url;

class AliexpressaccountperformanceController extends Controller
{
    /**
     * 速卖通每日服务分
     */
    public function actionsEverydaypoints(){

    }

    /**
     * 速卖通每月服务分
     */
    public function actionsMonthlypoints(){

    }
    /**
     * 低服务分商品
     */
    public function actionsLowscoreproduct(){

    }
}

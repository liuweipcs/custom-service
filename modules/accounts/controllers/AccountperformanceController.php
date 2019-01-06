<?php
/**
 * @desc 账号控制器
 * @author Fun
 */
namespace app\modules\accounts\controllers;
use Yii;
use app\components\Controller;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\UserAccount;
use app\modules\accounts\models\Platform;
use app\modules\orders\models\Logistic;
use app\modules\accounts\models\EbayCaseRefund;
use app\modules\systems\models\Tag;
use app\modules\mails\models\MailTemplate;
use app\modules\systems\models\Rule;
use app\modules\accounts\models\app\modules\accounts\models;
use app\modules\accounts\models\Aliexpressaccountservicescoreinfo;
use app\modules\accounts\models\Aliexpressaccountdisputeproductlist;
use app\modules\accounts\models\Aliexpressaccountlevelinfo;
class AccountperformanceController extends Controller
{
    /**
     * @desc 速卖通当月服务等级
     * @return \yii\base\string
     */
    public function actionLevelinfo()
    {
        $searchModel = new Aliexpressaccountlevelinfo();
        $dataProvider = $searchModel->searchs(Yii::$app->request->queryParams);
        $accountList =  ['' => '--请选择账号--'] + Aliexpressaccountdisputeproductlist::getAccountList(1);
        return $this->render('levelinfo', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'accountList' => $accountList,
        ]);


    }
    /**
     * 店铺考核期内DSR商品描述中低分商品分页列表
     */
    public function actionDsrddisputeproductlist(){
        $searchModel = new Aliexpressaccountdisputeproductlist();
        $dataProvider = $searchModel->searchs(Yii::$app->request->queryParams);
        $accountList =  ['' => '--请选择账号--'] + Aliexpressaccountdisputeproductlist::getAccountList(1);
        return $this->render('disputeproductlist', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'accountList' => $accountList,
        ]);
    }
    /**
     * 每日服务分
     */
    public function actionServicescoreinfo()
    {

        $searchModel = new Aliexpressaccountservicescoreinfo();
        $dataProvider = $searchModel->searchs(Yii::$app->request->queryParams);
        $accountList =  ['' => '--请选择账号--'] + Aliexpressaccountdisputeproductlist::getAccountList(1);
        return $this->render('servicescoreinfo', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'accountList' => $accountList,
        ]);
    }

    /**
     * 通过平台code获取该平台下的所有账号数据
     * @param string $platform_code 平台code
     * @param string $type 取数据的标示1标签数据2模板数据
     */
    public function actionGetaccount($platform_code,$type)
    {
        $result['account_info'] = Account::getAccountByPlatformCode($platform_code);
        $result['relation_data'] = array();
        $result['buyer_option_logistics'] = Logistic::getBuyerOptionLogistics($platform_code);

        //取标签数据
        if ($type == Rule::RULE_TYPE_TAG) {
            $result['relation_data'] = Tag::getTagAsArray($platform_code);
        }

        if ($type == Rule::RULE_TYPE_AUTO_ANSWER) {
            $result['relation_data'] = MailTemplate::getOrderTemplateDataAsArray($platform_code);
        }

        echo json_encode($result);
    }

    /**
     * 根据平台返回对应的账号或者站点信息
     * @param type $platform_code
     * @author allen <2018-06-14>
     */
    public function actionGetaccoutorsite(){
        $params = $this->request->post();
        $platform_code = isset($params['platform_code']) ? $params['platform_code'] : '';
        $type = isset($params['type']) ? $params['type'] : '';
        $data = UserAccount::getAccoutOrSite($platform_code,$type);
        echo json_encode($data);
    }
}
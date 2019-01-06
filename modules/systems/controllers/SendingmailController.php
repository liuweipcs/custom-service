<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/10
 * Time: 10:59
 */

namespace app\modules\systems\controllers;

use Yii;
use yii\data\Pagination;
use app\components\Controller;
use app\modules\accounts\models\UserAccount;
use app\modules\accounts\models\Account;
use app\modules\systems\models\MailAutoManage;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\Sendingmail;
use app\modules\systems\models\Systemssendingmaillist;

class SendingmailController extends Controller
{
    /**
     * 规则应用邮件列表
     * @return \yii\base\string
     * @throws \yii\base\InvalidConfigException
     */
    public function actionList()
    {
        $params = \Yii::$app->request->getBodyParams();

        $send_rule_id = Yii::$app->request->get('send_rule_id', false);
        $platform_code= Yii::$app->request->get('platform_code', false);
        if (!empty($send_rule_id)) {
            $params['send_rule_id'] = $send_rule_id;
        }
        if(!empty($platform_code)){
            $params['platform_code'] = $platform_code;
        }
        $params['send_rule_id'] = $send_rule_id;
        list($model, $dataProvider) = $this->dataList($params);

        return $this->renderList('list', [
            'model'         => $model,
            'dataProvider'  => $dataProvider,
            'platform_code' => Platform::PLATFORM_CODE_AMAZON,
        ]);
    }

    /**
     * @desc 邮件列表公共方法
     */
    protected function dataList($params)
    {
        $model           = new Sendingmail();
        $model->platform = $params['platform_code'];
        $dataProvider    = $model->searchList($params);
        return [$model, $dataProvider];
    }

    public function actionListingold()
    {
        /*$model = new Rule();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);
*/
        // platform_code] => [rule_name] => [account_id] => [buyer_id] => [mail_theme] => [begin_date] => [end_date]
        $params = Yii::$app->request->get();

        if (empty($params)) {
            $params['start_date'] = date('Y-m-d H:i:s', strtotime('-30 day'));
            $params['end_date']   = date('Y-m-d H:i:s');
        }
        //平台
        $platformList = UserAccount::getLoginUserPlatformAccounts();
        //查询账号
        $ImportPeople_list    = Account::getFullAccounts();//ebay账号
        $ImportPeople_list[0] = '全部';
        ksort($ImportPeople_list);
        $rules = MailAutoManage::getRule();

        $params['pageSize'] = isset($_REQUEST['pageSize']) ? trim($_REQUEST['pageSize']) : 10;//分页大小
        $params['pageCur']  = isset($_REQUEST['pageCur']) ? trim($_REQUEST['pageCur']) : null;//当前页
        $list               = Systemssendingmaillist::seachList($params);
        //创建分页组件
        $page = new Pagination([
            //总的记录条数
            'totalCount'    => $list['count'],
            //分页大小
            'pageSize'      => $params['pageSize'],
            //设置地址栏当前页数参数名
            'pageParam'     => 'pageCur',
            //设置地址栏分页大小参数名
            'pageSizeParam' => 'pageSize',
        ]);
        return $this->render('list', [
            'platformList'      => $platformList,
            'list'              => $list['data_list'],
            'count'             => $list['count'],
            'page'              => $page,
            'ImportPeople_list' => $ImportPeople_list,
            'rules'             => $rules,
            'params'            => $params,
        ]);
    }
}
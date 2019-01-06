<?php
/**
 * @desc 平台控制器
 * @author Fun
 */
namespace app\modules\accounts\controllers;
use app\components\Controller;
use app\modules\accounts\models\Platform;
use app\modules\users\models\User;
use app\modules\accounts\models\UserAccount;
class UseraccountController extends Controller
{
    /**
     * @desc 平台列表
     * @return \yii\base\string
     */
    public function actionList()
    {
        $model = new Platform();
        $params = \Yii::$app->request->getBodyParams();
/*         $platformList = Platform::getPlatformAsArray();
        $platformAccountList = Platform::getPlatformAccountList(); */
        $userList = User::getIdNamePairs();
        
        return $this->renderList('index', [
            'model' => $model,
/*             'platformList' => $platformList,
            'platformAccountList' => $platformAccountList, */
            'userList' => $userList,
        ]);
    }
    
    public function actionSearchaccount()
    {
        $userId = $this->request->getQueryParam('user_id');
        if (empty($userId))
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false);
        //查找用户已绑定账号列表
        $userAccountList = UserAccount::getUserAccountIds($userId);

        $platformAccountList = Platform::getPlatformAccountList();

        $selectedAccountList = [];
        $accountList = [];
        foreach ($platformAccountList as $platformCode => $rows)
        {
            $selectList = isset($userAccountList[$platformCode]) ? $userAccountList[$platformCode] : [];
            foreach ($rows as $row)
            {
                $accountId = $row['id'];
                $accountName = $row['account_name'];
                if (in_array($accountId, $selectList))
                {
                    $accountList[$platformCode][] = [
                        'id' => $accountId,
                        'account_name' => $accountName,
                        'check' => 1,
                    ];

                    $selectedAccountList[$platformCode][] = [
                        'id' => $accountId,
                    ];
                }
                else
                {
                    $accountList[$platformCode][] = [
                        'id' => $accountId,
                        'account_name' => $accountName,
                        'check' => 0,
                    ];

                }
            }
        }



        echo \yii\helpers\Json::encode([
            'accountList' => $accountList, 
            'selectedAccountList' => $selectedAccountList
        ]);
        \Yii::$app->end();
    }
    
    /**
     * @desc 保存
     */
    public function actionSave()
    {
        $accountIds = $this->request->getBodyParam('account_ids', []);
        $userId = $this->request->getBodyParam('user_id');

        if (empty($userId) || !($userModel = User::findById($userId)))
            $this->_showMessage(\Yii::t('user', 'Invalid User'), false);
        $accountIds = array_filter($accountIds);
//        if (empty($accountIds))
//            $this->_showMessage(\Yii::t('user', 'No Selected Data'), false);
        if (!UserAccount::bindUserAccount($userId, $accountIds))
            $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
        $this->_showMessage(\Yii::t('system', 'Operate Successful'), true);
    }
}
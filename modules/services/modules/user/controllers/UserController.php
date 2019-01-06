<?php

namespace app\modules\services\modules\user\controllers;

use app\common\CloudRequest;
use app\modules\users\models\User;
use yii\web\Controller;

class UserController extends Controller
{

    /**
     * 添加计划任务获取用户工号
     * @throws \yii\base\Exception
     */
    public function actionGetchangeaccount()
    {

        $cloudRequest = CloudRequest::getInstance();
        $arr          = ['appid' => $cloudRequest->appid];
        $account_info = $cloudRequest->cloud_get('/getchangeaccount', $arr);
        //oa账号信息
        if ($account_info['status'] == 1) {
            //获取账号成功
            $oa_account_arr = $account_info['list'];
            foreach ($oa_account_arr as $v) {

                $user_modal = User::findOne(['user_number' => $v['user_number']]);
                if (empty($user_modal)) {
                    $user_modal                 = new User();
                    $user_modal->user_name      = $v['user_name'];
                    $user_modal->user_number    = $v['user_number'];
                    $user_modal->login_name     = $v['login_name'];
                    $user_modal->login_password = \Yii::$app->getSecurity()->generatePasswordHash($user_modal->login_password);
                    $user_modal->create_by      = 'system';
                    $user_modal->create_time    = date('Y-m-d H:i:s');
                    $user_modal->status         = 1;
                }
                $user_modal->user_number = $v['user_number'];
                $user_modal->modify_by   = 'system';
                $user_modal->modify_time = date('Y-m-d H:i:s');
                if ($user_modal->save()) {
                    $success_data[] = $v['user_number'];
                }
                if (!empty($success_data)) {
                    //
                    $arr_update = ['appid' => $cloudRequest->appid,
                                   'token' => $cloudRequest->token,
                                   'id'    => json_encode($success_data)];
                    $result     = $cloudRequest->cloud_get('/updatestatus', $arr_update);
                    if ($result['status'] == 1) {
                        //状态修改成功
                        die('OA_ACCOUNT SYNC SUCCESS');
                    }
                }
            }
        }else{
            die('No OA_ACCOUNT INFO');
        }
    }
}
<?php

namespace app\modules\reports\controllers;

use function GuzzleHttp\Psr7\str;
use Yii;
use app\modules\reports\models\MailStatistics;
use app\modules\reports\models\DisputeStatistics;
use app\modules\reports\models\FeedbackStatistics;
use app\components\Controller;
use yii\db\Query;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\modules\users\models\Role;
use app\modules\accounts\models\UserAccount;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use yii\web\Response;
use app\modules\users\models\User;
use app\modules\users\models\UserRole;

class KefumailstatisticsController extends Controller
{


    const TASK_TYPE_RETURN = 'return'; //return纠纷
    const TASK_TYPE_RETURN_UPDATE = 'return_update'; //return纠纷update
    const TASK_TYPE_INQUIRY = 'inquiry';//inquiry纠纷
    const TASK_TYPE_INQUIRY_UPDATE = 'inquiry_update';//inquiry纠纷
    const TASK_TYPE_CANCELLATION = 'cancellation'; //Cancellation纠纷
    const TASK_TYPE_CANCELLATION_UPDATE = 'cancellation_update'; //Cancellation纠纷update
    const TASK_TYPE_FEEDBACK = 'feedback';
    const TASK_LOGISTICS = 'logistics'; //速卖通物流纠纷
    const TASK_BUYER = 'buyer'; //速卖通买家原因纠纷
    const TASK_QUALITY = 'quality'; //数买通质量原因纠纷

    /**
     * Lists all MailtatisticsController models.
     * @return mixed
     * 客服页面
     */
    public function actionIndex()
    {

        error_reporting(E_ALL ^ E_NOTICE);
        //$id = Yii::$app->user->identity->id;
        if ($this->request->get('user_name')) {
            $user_name = $this->request->get('user_name');
        } else {
            $user_name = Yii::$app->user->identity->login_name;
        }


        $user = User::findOne(['login_name' => $user_name]);
       /* $role_id = UserRole::findALL(['user_id' => $user->id])->role_id;*/

        $role_id = UserRole::find()->select('role_id')->where(['user_id' => $user->id])->column();



        //获取角色对应平台
        $platfrom = Role::find()->select('platform_code')->where(['in','id',$role_id])->column();
        $platform = [];
        foreach($platfrom as $item){
            $platform_array = explode(',', $item);
            $platform = array_merge($platform, $platform_array);
        }
        $platform = array_unique($platform);


        if(in_array('EB', $platform)){
            $platform_code = Platform::PLATFORM_CODE_EB;
        }else if(in_array('ALI',$platform)){
            $platform_code = Platform::PLATFORM_CODE_ALI;
        }else{
            $this->_showMessage('目前只针对ebay和速卖通有统计',false);
        }

        //ebay 平台统计
        /* $account_id = UserAccount::getUserPlatformAccountIds($user_name, $platform_code);*/
        $user_id = (new Query())->select('id')->from('{{%user}}')->where(['user_name' => $user_name])
            ->createCommand(Yii::$app->db_system)
            ->queryColumn();
        $account_old_id = (new Query())->select('account_ids')->from('{{%orderservice}}')->where(['user_id' => $user_id, 'platform_code' => $platform_code])
            ->createCommand(Yii::$app->db_system)
            ->queryOne();
        if ($account_old_id['account_ids']) {
            $account_old_id = explode(',', $account_old_id['account_ids']);
        }

        //ebay账号信息
        $account_id = Account::findAccountId($account_old_id, $platform_code);
        $account = Account::findAccountAll($account_old_id, $platform_code);
        $range = [];
        $send_time_range=[];
        $day = date('j');
        $monthnew = date('Y-m');
        $nowDate = date('Y-m-d');
        $upMonthDays = date('d');
        $upMonthFirsDay = date('Y-m-01 00:00:00');
        for ($i = 0; $i < $upMonthDays; $i++) {

            $start_time = date('Y-m-d 10:00:00', strtotime('-1 day', strtotime("+{$i} day", strtotime($upMonthFirsDay))));
            $range[$i + 1] = [
                'start_time' => $start_time,
                'end_time' => date('Y-m-d 10:00:00', strtotime('+1 day', strtotime($start_time))),
            ];

            $send_start_time = date('Y-m-d 18:00:00', strtotime('-1 day', strtotime("+{$i} day", strtotime($upMonthFirsDay))));
            $send_time_range[$i+1] = [
                'start_time' => $send_start_time,
                'end_time' => date('Y-m-d 18:00:00', strtotime('+1 day', strtotime($send_start_time))),
            ];

        }

        //待处理时间，
        $ranges_time = date('Y-m-01 00:00:00', strtotime('-2 month'));
        $ranges = [
            'start_time' => $ranges_time,
            'end_time' => date('Y-m-t 23:59:59'),
        ];

        //取出当月每天所有未回复邮件
        $mail_not_list = MailStatistics::mailNotList($platform_code, $account_id, $send_time_range);
        //所有邮件
        $range_reverse = array_reverse($send_time_range, true);
        $mail_list = MailStatistics::mailList($platform_code, $account_id, $range_reverse);
        //所有已回复
        $mail_end_list = MailStatistics::mailEndList($platform_code, $account_id, $send_time_range);
       //主动联系
        //$mail_list_touch = MailStatistics :: mailActionAll($platform_code, $account_id, $send_time_range);
        //待回复
        $mail_wait_list = MailStatistics::mailWaitList($platform_code, $account_id, $ranges);

        //未回复百分比
        $mail_not_percent = [];
        foreach ($mail_list as $k => $v) {
            if ($mail_list[$k] > 0 && $mail_not_list[$k] > 0) {
                $mail_not_percent[$k] = round($mail_not_list[$k] / $mail_list[$k], 4) * 100 . "%";
            } else {
                $mail_not_percent[$k] = 0;
            }
        }


        //当月所有未回复邮件
        /*  $data = '';
          foreach($range as $item){
              $data .= "(create_time between '{$item['start_time']}' and '{$item['end_time']}') or ";
          }
          $data = rtrim($data, 'or ');
          $mail_all = MailStatistics:: mailAll($platform_code,$account_id,$data);*/

        if($platform_code == "EB"){
            //当月所有未收到纠纷数量
            $inqurry_list = DisputeStatistics::disputeList($platform_code, $account_id, $range, self::TASK_TYPE_INQUIRY);
            //未收到纠纷未处理
            $inqurry_not_list = DisputeStatistics::disputeNotList($platform_code, $account_id, $range, self::TASK_TYPE_INQUIRY);
            //未收到纠纷已处理
            $inqurry_end_list = DisputeStatistics::disputeEndList($platform_code, $account_id, $range, self::TASK_TYPE_INQUIRY);
            //未收到纠纷待处理
            $inqurry_wait_list = DisputeStatistics::disputeWaitList($platform_code, $account_id, $ranges, self::TASK_TYPE_INQUIRY);
            //统计半分比
            $inqurry_not_percent = [];
            foreach ($inqurry_list as $k => $v) {
                if ($inqurry_list[$k] > 0 && $inqurry_not_list[$k] > 0) {
                    $inqurry_not_percent[$k] = round($inqurry_not_list[$k] / $inqurry_list[$k], 4) * 100 . "%";
                } else {
                    $inqurry_not_percent[$k] = 0;
                }
            }

            //当月退款退货纠纷
            $return_list = DisputeStatistics::disputeList($platform_code, $account_id, $range, self::TASK_TYPE_RETURN);
            //退款退货未处理
            $return_not_list = DisputeStatistics::disputeNotList($platform_code, $account_id, $range, self::TASK_TYPE_RETURN);
            //退款退货已处理
            $return_end_list = DisputeStatistics::disputeEndList($platform_code, $account_id, $range, self::TASK_TYPE_RETURN);
            //退款退货待处理
            $return_wait_list = DisputeStatistics::disputeWaitDay($platform_code, $account_id, $ranges, self::TASK_TYPE_RETURN);
            //统计半分比
            $return_not_percent = [];
            foreach ($return_list as $k => $v) {
                if ($return_list[$k] > 0 && $return_not_list[$k] > 0) {
                    $return_not_percent[$k] = round($return_not_list[$k] / $return_list[$k], 4) * 100 . "%";
                } else {
                    $return_not_percent[$k] = 0;
                }
            }

            //当月取消交易纠纷
            $cancellation_list = DisputeStatistics::cancellationList($platform_code, $account_id, $range, self::TASK_TYPE_CANCELLATION);
            //取消交易未处理
            $cancellation_not_list = DisputeStatistics::cancellationNotList($platform_code, $account_id, $range, self::TASK_TYPE_CANCELLATION);
            //取消交易已处理
            $cancellation_end_list = DisputeStatistics::cancellationEndList($platform_code, $account_id, $range, self::TASK_TYPE_CANCELLATION);
            //取消交易待处理
            $cancellation_wait_list = DisputeStatistics::cancellationWaitList($platform_code, $account_id, $ranges, self::TASK_TYPE_CANCELLATION);
            $cancellation_not_percent = [];
            foreach ($cancellation_list as $k => $v) {
                if ($cancellation_list[$k] > 0 && $cancellation_not_list[$k] > 0) {
                    $cancellation_not_percent[$k] = round($cancellation_not_list[$k] / $cancellation_list[$k], 4) * 100 . "%";
                } else {
                    $cancellation_not_percent[$k] = 0;
                }
            }
        }else{
            //当月所有未收到纠纷数量
            $inqurry_list = DisputeStatistics::disputeList($platform_code, $account_id, $send_time_range, self::TASK_LOGISTICS);
            //未收到纠纷未处理
            $inqurry_not_list = DisputeStatistics::disputeNotList($platform_code, $account_id, $send_time_range, self::TASK_LOGISTICS);
            //未收到纠纷已处理
            $inqurry_end_list = DisputeStatistics::disputeEndList($platform_code, $account_id, $send_time_range, self::TASK_LOGISTICS);
            //未收到纠纷待处理
            $inqurry_wait_list = DisputeStatistics::disputeWaitList($platform_code, $account_id, $ranges, self::TASK_LOGISTICS);
            //统计半分比
            $inqurry_not_percent = [];
            foreach ($inqurry_list as $k => $v) {
                if ($inqurry_list[$k] > 0 && $inqurry_not_list[$k] > 0) {
                    $inqurry_not_percent[$k] = round($inqurry_not_list[$k] / $inqurry_list[$k], 4) * 100 . "%";
                } else {
                    $inqurry_not_percent[$k] = 0;
                }
            }

            //当月退款退货纠纷
            $return_list = DisputeStatistics::disputeList($platform_code, $account_id, $send_time_range, self::TASK_BUYER);
            //退款退货未处理
            $return_not_list = DisputeStatistics::disputeNotList($platform_code, $account_id, $send_time_range, self::TASK_BUYER);
            //退款退货已处理
            $return_end_list = DisputeStatistics::disputeEndList($platform_code, $account_id, $send_time_range, self::TASK_BUYER);
            //退款退货待处理
            $return_wait_list = DisputeStatistics::disputeWaitList($platform_code, $account_id, $ranges, self::TASK_BUYER);
            //统计半分比
            $return_not_percent = [];
            foreach ($return_list as $k => $v) {
                if ($return_list[$k] > 0 && $return_not_list[$k] > 0) {
                    $return_not_percent[$k] = round($return_not_list[$k] / $return_list[$k], 4) * 100 . "%";
                } else {
                    $return_not_percent[$k] = 0;
                }
            }

            //当月取消交易纠纷
            $cancellation_list = DisputeStatistics::disputeList($platform_code, $account_id, $send_time_range, self::TASK_QUALITY);
            //取消交易未处理
            $cancellation_not_list = DisputeStatistics::disputeNotList($platform_code, $account_id, $send_time_range, self::TASK_QUALITY);
            //取消交易已处理
            $cancellation_end_list = DisputeStatistics::disputeEndList($platform_code, $account_id, $send_time_range, self::TASK_QUALITY);
            //取消交易待处理
            $cancellation_wait_list = DisputeStatistics::disputeWaitList($platform_code, $account_id, $ranges, self::TASK_QUALITY);
            $cancellation_not_percent = [];
            foreach ($cancellation_list as $k => $v) {
                if ($cancellation_list[$k] > 0 && $cancellation_not_list[$k] > 0) {
                    $cancellation_not_percent[$k] = round($cancellation_not_list[$k] / $cancellation_list[$k], 4) * 100 . "%";
                } else {
                    $cancellation_not_percent[$k] = 0;
                }
            }
        }

        //当月评价
        $feedback_list = FeedbackStatistics::feedbackList($platform_code, $account_id, $send_time_range);
        //评价未处理
        $feedback_not_list = FeedbackStatistics::feedbackNotList($platform_code, $account_id, $send_time_range);
        //评价已处理
        $feedback_end_list = FeedbackStatistics::feedbackEndList($platform_code, $account_id, $send_time_range);
        //评价待处理
        $feedback_wait_list = FeedbackStatistics::feedbackWaitList($platform_code, $account_id, $ranges);
        //统计半分比
        $feedback_not_percent = [];
        foreach ($feedback_list as $k => $v) {
            if ($feedback_list[$k] > 0 && $feedback_not_list[$k] > 0) {
                $feedback_not_percent[$k] = round($feedback_not_list[$k] / $feedback_list[$k], 4) * 100 . "%";
            } else {
                $feedback_not_percent[$k] = 0;
            }
        }
        $total = [];
        $completion = [];
        $completion_rate = [];
       foreach($mail_list as $k=>$v){
           //总计
           $total[$k] = $mail_list[$k] + $inqurry_list[$k] + $return_list[$k] + $cancellation_list[$k] + $feedback_list[$k];
           //完成率
           $completion[$k] = $mail_end_list[$k] + $inqurry_end_list[$k] + $return_end_list[$k] + $cancellation_end_list[$k] + $feedback_end_list[$k];
           if($total[$k] >0 && $completion[$k]){
               $completion_rate[$k] = round($completion[$k] / $total[$k], 4) * 100 . "%";
           }else{
               $completion_rate[$k] = 0;
           }
       }
        return $this->render('index', [
            'monthnew' => $monthnew,
            'nowDate' => $nowDate,
            'day' => $day,
            'user_name' => $user_name,
            'account' => $account,
            'mail_not_list' => $mail_not_list,
            'mail_list' => $mail_list,
            'mail_end_list' => $mail_end_list,
            'total' => $total,
            'completion_rate' => $completion_rate,
            'mail_wait_list' => $mail_wait_list,
            'mail_not_percent' => $mail_not_percent,
            'inqurry_list' => $inqurry_list,
            'inqurry_not_list' => $inqurry_not_list,
            'inqurry_end_list' => $inqurry_end_list,
            'inqurry_wait_list' => $inqurry_wait_list,
            'inqurry_not_percent' => $inqurry_not_percent,
            'return_list' => $return_list,
            'return_not_list' => $return_not_list,
            'return_end_list' => $return_end_list,
            'return_wait_list' => $return_wait_list,
            'return_not_percent' => $return_not_percent,
            'cancellation_list' => $cancellation_list,
            'cancellation_not_list' => $cancellation_not_list,
            'cancellation_end_list' => $cancellation_end_list,
            'cancellation_wait_list' => $cancellation_wait_list,
            'cancellation_not_percent' => $cancellation_not_percent,
            'feedback_list' => $feedback_list,
            'feedback_not_list' => $feedback_not_list,
            'feedback_end_list' => $feedback_end_list,
            'feedback_wait_list' => $feedback_wait_list,
            'feedback_not_percent' => $feedback_not_percent,
            'platform_code' => $platform_code,
        ]);
    }

    /**
     * 主管页面
     */
    public function actionCharge()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        /* $role_id = Yii::$app->user->identity->role_id;*/

        $user_id = Yii::$app->user->identity->id;
        $role_id = UserRole::find()->select('role_id')->where(['user_id' => $user_id])->column();

        //获取角色对应平台
        $platfrom_code = Role::find()->select('platform_code')->where(['in','id',$role_id])->column();
        $platfrom = [];
        foreach($platfrom_code as $item){
            $platform_array = explode(',', $item);
            $platfrom = array_merge($platfrom, $platform_array);
        }
        $platfrom = array_unique($platfrom);


        $platform_code = [];
        if(in_array('EB',$platfrom) && in_array('ALI',$platfrom)){

            foreach($platfrom as $k => $value){
                if($value == 'EB'){
                    $platform_code[0] = 'EB';
                }else if($value == 'ALI'){
                    $platform_code[1] = 'ALI';
                }else{
                    $platform_code[] = $value;
                }

            }
        }else{
            $platform_code = $platfrom;
        }

        $roleIds = [];
        //如果是admin 显示所有用户
        if (in_array(1,$role_id)) {
            $roleIds = $this->getAllRoleIds(Platform::PLATFORM_CODE_EB);
            $user_id = UserRole::find()->select('user_id')->where(['in','role_id',$roleIds])->asArray()->column();
            $userList = User::find()
                ->select(['id', 'login_name'])
                ->where(['in', 'id', $user_id])
                ->andWhere(['status' => 1])
                ->andWhere(['<>', 'role_id', 1])
                ->asArray()
                ->all();
        } else {
            if(in_array('EB', $platform_code)){
                $this->getChildRoleIds($role_id[0], $roleIds, Platform::PLATFORM_CODE_EB);
            }else {
                $this->getChildRoleIds($role_id[0], $roleIds, Platform::PLATFORM_CODE_ALI);
            }
            $user_id = UserRole::find()->select('user_id')->where(['in','role_id',$roleIds])->column();
            $userList = User::find()
                ->select(['id', 'login_name'])
                ->where(['in', 'id', $user_id])
                ->andWhere(['status' => 1])
                ->asArray()
                ->all();
        }

        $user_list = [];

       //只筛选有账号的客服

        if(in_array('EB', $platform_code)) {
            foreach ($userList as $k => $value) {
                $user_account = UserAccount::findOne(['user_id' => $value['id'], 'platform_code' => 'EB']);
                if (!empty($user_account)) {
                    $user_list[] = $value;
                }
            }
        }else{
            foreach ($userList as $k => $value) {
                $user_account = UserAccount::findOne(['user_id' => $value['id'], 'platform_code' => 'ALI']);
                if (!empty($user_account)) {
                    $user_list[] = $value;
                }
            }
        }



        $userList = array_column($user_list, 'login_name', 'login_name');



        $start_time = date('Y-m-d 10:00:00', strtotime('-1 day'));

        $range = [
            'start_time' => $start_time,
            'end_time' => date('Y-m-d 10:00:00', strtotime('+1 day', strtotime($start_time))),
        ];

        $ali_start_time = date('Y-m-d 18:00:00', strtotime('-1 day'));

        $ali_range = [
            'start_time' => $ali_start_time,
            'end_time' => date('Y-m-d 18:00:00', strtotime('+1 day', strtotime($ali_start_time))),
        ];

        $send_start_time = date('Y-m-d 18:00:00', strtotime('-1 day'));
        $send_range = [
            'start_time' => $send_start_time,
            'end_time' => date('Y-m-d 18:00:00', strtotime('+1 day', strtotime($send_start_time))),
        ];


        $feeddata = "(comment_time between '{$send_range['start_time']}' and '{$send_range['end_time']}')";
        $maildata = "(receive_date between '{$send_range['start_time']}' and '{$send_range['end_time']}') ";
        /*$send_time = "(send_time between '{$send_range['start_time']}' and '{$send_range['end_time']}') ";*/
        $cancel_request_date = "(cancel_request_date between '{$range['start_time']}' and '{$range['end_time']}')";
        $creation_date = "(creation_date between '{$range['start_time']}' and '{$range['end_time']}') ";
        $return_creation_date = "(return_creation_date between '{$range['start_time']}' and '{$range['end_time']}')";
        $gmt_create = "(gmt_create between '{$send_range['start_time']}' and '{$send_range['end_time']}') ";
        $buyer_fb_date = "(buyer_fb_date between '{$send_range['start_time']}' and '{$send_range['end_time']}') ";
        $andwhere['feeddata'] = $feeddata;
        $andwhere['maildata'] = $maildata;
        $andwhere['cancel_request_date'] = $cancel_request_date;
        $andwhere['creation_date'] = $creation_date;
        $andwhere['return_creation_date'] = $return_creation_date;
        $andwhere['gmt_create'] = $gmt_create;
        $andwhere['buyer_fb_date'] = $buyer_fb_date;
      /*  $andwhere['send_time'] = $send_time;*/
        //待处理时间，
        $ranges_time = date('Y-m-01 00:00:00', strtotime('-2 month'));
        $ranges = [
            'start_time' => $ranges_time,
            'end_time' => date('Y-m-t 23:59:59'),
        ];



        if(!in_array('EB', $platform_code) && !in_array('ALI', $platform_code)){
            $this->_showMessage('你还没有权限访问，请前往设置',false);
        }
        // 只能查询到客服绑定账号的回复
        if(in_array('EB', $platform_code)) {
            $plat_code = 'EB';
            $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);

            //取出今天所有未回复邮件
            $mail_not_list = MailStatistics::mailNotDay(Platform::PLATFORM_CODE_EB, $accountIds, $maildata);
            //所有邮件
            $mail_list = MailStatistics::mailDay(Platform::PLATFORM_CODE_EB, $accountIds, $maildata);
            //所有已回复
            $mail_end_list = MailStatistics::mailEndDay(Platform::PLATFORM_CODE_EB, $accountIds, $maildata);
            //主动联系
            /*  $mail_list_touch = MailStatistics :: mailAction(Platform::PLATFORM_CODE_EB, $accountIds, $send_time);
              $mail_list_active = $mail_list_touch - $mail_end_list;*/
            //待回复
            $mail_wait_list = MailStatistics::mailWaitList(Platform::PLATFORM_CODE_EB, $accountIds, $ranges);
            if ($mail_list > 0 && $mail_not_list) {
                $mail_not_percent = round($mail_not_list / $mail_list, 4) * 100 . "%";
            } else {
                $mail_not_percent = 0;
            }

            //当天所有未收到纠纷数量
            $inqurry_list = DisputeStatistics::disputeDay(Platform::PLATFORM_CODE_EB, $accountIds, $creation_date, self::TASK_TYPE_INQUIRY);
            //未收到纠纷未处理
            $inqurry_not_list = DisputeStatistics::disputeNotDay(Platform::PLATFORM_CODE_EB, $accountIds, $creation_date, self::TASK_TYPE_INQUIRY);
            //未收到纠纷已处理
            $inqurry_end_list = DisputeStatistics::disputeEndDay(Platform::PLATFORM_CODE_EB, $accountIds, $creation_date, self::TASK_TYPE_INQUIRY);
            //未收到纠纷待处理
            $inqurry_wait_list = DisputeStatistics::disputeWaitList(Platform::PLATFORM_CODE_EB, $accountIds, $ranges, self::TASK_TYPE_INQUIRY);
            if ($inqurry_list > 0 && $inqurry_not_list) {
                $inqurry_not_percent = round($inqurry_not_list / $inqurry_list, 4) * 100 . "%";
            } else {
                $inqurry_not_percent = 0;
            }
            //当天退款退货纠纷
            $return_list = DisputeStatistics::disputeDay(Platform::PLATFORM_CODE_EB, $accountIds, $return_creation_date, self::TASK_TYPE_RETURN);
            //退款退货未处理
            $return_not_list = DisputeStatistics::disputeNotDay(Platform::PLATFORM_CODE_EB, $accountIds, $return_creation_date, self::TASK_TYPE_RETURN);
            //退款退货已处理
            $return_end_list = DisputeStatistics::disputeEndDay(Platform::PLATFORM_CODE_EB, $accountIds, $return_creation_date, self::TASK_TYPE_RETURN);
            //退款退货待处理
            $return_wait_list = DisputeStatistics::disputeWaitDay(Platform::PLATFORM_CODE_EB, $accountIds, $ranges, self::TASK_TYPE_RETURN);
            if ($return_list > 0 && $return_not_list) {
                $return_not_percent = round($return_not_list / $return_list, 4) * 100 . "%";
            } else {
                $return_not_percent = 0;
            }
            //当天取消交易纠纷
            $cancellation_list = DisputeStatistics::cancellationDay(Platform::PLATFORM_CODE_EB, $accountIds, $cancel_request_date, self::TASK_TYPE_CANCELLATION);
            //取消交易未处理
            $cancellation_not_list = DisputeStatistics::cancellationNotDay(Platform::PLATFORM_CODE_EB, $accountIds, $cancel_request_date, self::TASK_TYPE_CANCELLATION);
            //取消交易已处理
            $cancellation_end_list = DisputeStatistics::cancellationEndDay(Platform::PLATFORM_CODE_EB, $accountIds, $cancel_request_date, self::TASK_TYPE_CANCELLATION);
            //取消交易待处理
            $cancellation_wait_list = DisputeStatistics::cancellationWaitList(Platform::PLATFORM_CODE_EB, $accountIds, $ranges, self::TASK_TYPE_CANCELLATION);

            if ($cancellation_list > 0 && $cancellation_not_list) {
                $cancellation_not_percent = round($cancellation_not_list / $cancellation_list, 4) * 100 . "%";
            } else {
                $cancellation_not_percent = 0;
            }
            //当天评价
            $feedback_list = FeedbackStatistics::feedbackDay(Platform::PLATFORM_CODE_EB, $accountIds, $feeddata);
            //评价未处理
            $feedback_not_list = FeedbackStatistics::feedbackNotDay(Platform::PLATFORM_CODE_EB, $accountIds, $feeddata);
            //评价已处理
            $feedback_end_list = FeedbackStatistics::feedbackEndDay(Platform::PLATFORM_CODE_EB, $accountIds, $feeddata);
            //评价待处理
            $feedback_wait_list = FeedbackStatistics::feedbackWaitList(Platform::PLATFORM_CODE_EB, $accountIds, $ranges);
            if ($feedback_list > 0 && $feedback_not_list) {
                $feedback_not_percent = round($feedback_not_list / $feedback_list, 4) * 100 . "%";
            } else {
                $feedback_not_percent = 0;
            }

            //每个客服对应邮件量
            $list = [];
            foreach ($userList as $k => $v) {
                $list[$v] = $this->getAccountId($v, Platform::PLATFORM_CODE_EB, $andwhere);

            }

            $date = [];
            foreach ($list as $k => $v) {
                if (empty($v['mail_list']) && empty($v['inqurry_list']) && empty($v['return_list']) && empty($v['cancellation_list']) && empty($v['feedback_list'])) {
                    unset($v);
                } else {
                    $date[$k] = $v;
                }
            }
        }else{
            $plat_code = 'ALI';
            $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_ALI);
            //取出今天所有未回复邮件
            $mail_not_list = MailStatistics::mailNotDay(Platform::PLATFORM_CODE_ALI, $accountIds, $maildata);
            //所有邮件
            $mail_list = MailStatistics::mailDay(Platform::PLATFORM_CODE_ALI, $accountIds, $maildata);
            //所有已回复
            $mail_end_list = MailStatistics::mailEndDay(Platform::PLATFORM_CODE_ALI, $accountIds, $maildata);
            //主动联系
            /*  $mail_list_touch = MailStatistics :: mailAction(Platform::PLATFORM_CODE_EB, $accountIds, $send_time);
              $mail_list_active = $mail_list_touch - $mail_end_list;*/
            //待回复
            $mail_wait_list = MailStatistics::mailWaitList(Platform::PLATFORM_CODE_ALI, $accountIds, $ranges);
            if ($mail_list > 0 && $mail_not_list) {
                $mail_not_percent = round($mail_not_list / $mail_list, 4) * 100 . "%";
            } else {
                $mail_not_percent = 0;
            }

            //当天所有物流纠纷
            $inqurry_list = DisputeStatistics::disputeDay(Platform::PLATFORM_CODE_ALI, $accountIds, $gmt_create, self::TASK_LOGISTICS);
            //物流纠纷未处理
            $inqurry_not_list = DisputeStatistics::disputeNotDay(Platform::PLATFORM_CODE_ALI, $accountIds, $gmt_create, self::TASK_LOGISTICS);
            //物流纠纷已处理
            $inqurry_end_list = DisputeStatistics::disputeEndDay(Platform::PLATFORM_CODE_ALI, $accountIds, $gmt_create, self::TASK_LOGISTICS);
            //物流纠纷待处理
            $inqurry_wait_list = DisputeStatistics::disputeWaitList(Platform::PLATFORM_CODE_ALI, $accountIds, $ranges, self::TASK_LOGISTICS);
            if ($inqurry_list > 0 && $inqurry_not_list) {
                $inqurry_not_percent = round($inqurry_not_list / $inqurry_list, 4) * 100 . "%";
            } else {
                $inqurry_not_percent = 0;
            }

            //当天所有买家原因纠纷
            $return_list = DisputeStatistics::disputeDay(Platform::PLATFORM_CODE_ALI, $accountIds, $gmt_create, self::TASK_BUYER);
            //买家原因纠纷未处理
            $return_not_list = DisputeStatistics::disputeNotDay(Platform::PLATFORM_CODE_ALI, $accountIds, $gmt_create, self::TASK_BUYER);
            //买家原因纠纷已处理
            $return_end_list = DisputeStatistics::disputeEndDay(Platform::PLATFORM_CODE_ALI, $accountIds, $gmt_create, self::TASK_BUYER);
            //买家原因纠纷待处理
            $return_wait_list = DisputeStatistics::disputeWaitList(Platform::PLATFORM_CODE_ALI, $accountIds, $ranges, self::TASK_BUYER);
            if ($return_list > 0 && $return_not_list) {
                $return_not_percent = round($return_not_list / $return_list, 4) * 100 . "%";
            } else {
                $return_not_percent = 0;
            }
            //当天取消交易纠纷
            $cancellation_list = DisputeStatistics::disputeDay(Platform::PLATFORM_CODE_ALI, $accountIds, $gmt_create, self::TASK_QUALITY);
            //取消交易未处理
            $cancellation_not_list = DisputeStatistics::disputeNotDay(Platform::PLATFORM_CODE_ALI, $accountIds, $gmt_create, self::TASK_QUALITY);
            //取消交易已处理
            $cancellation_end_list = DisputeStatistics::disputeEndDay(Platform::PLATFORM_CODE_ALI, $accountIds, $gmt_create, self::TASK_QUALITY);
            //取消交易待处理
            $cancellation_wait_list = DisputeStatistics::disputeWaitList(Platform::PLATFORM_CODE_ALI, $accountIds, $ranges, self::TASK_QUALITY);

            if ($cancellation_list > 0 && $cancellation_not_list) {
                $cancellation_not_percent = round($cancellation_not_list / $cancellation_list, 4) * 100 . "%";
            } else {
                $cancellation_not_percent = 0;
            }

            //速卖通当天评价
            $feedback_list = FeedbackStatistics::feedbackDay(Platform::PLATFORM_CODE_ALI, $accountIds, $buyer_fb_date);
            //速卖通评价未处理
            $feedback_not_list = FeedbackStatistics::feedbackNotDay(Platform::PLATFORM_CODE_ALI, $accountIds, $buyer_fb_date);
            //速卖通评价已处理
            $feedback_end_list = FeedbackStatistics::feedbackEndDay(Platform::PLATFORM_CODE_ALI, $accountIds, $buyer_fb_date);
            //速卖通评价待处理
            $feedback_wait_list = FeedbackStatistics::feedbackWaitList(Platform::PLATFORM_CODE_ALI, $accountIds, $ranges);
            if ($feedback_list > 0 && $feedback_not_list) {
                $feedback_not_percent = round($feedback_not_list / $feedback_list, 4) * 100 . "%";
            } else {
                $feedback_not_percent = 0;
            }

            //每个客服对应邮件量
            $list = [];
            foreach ($userList as $k => $v) {
                $list[$v] = $this->getAccountId($v, Platform::PLATFORM_CODE_ALI, $andwhere);

            }

            $date = [];
            foreach ($list as $k => $v) {
                if (empty($v['mail_list']) && empty($v['inqurry_list']) && empty($v['return_list']) && empty($v['cancellation_list']) && empty($v['feedback_list'])) {
                    unset($v);
                } else {
                    $date[$k] = $v;
                }
            }

        }

        return $this->render('charge', [
            'plat_code' => $plat_code,
            'platfrom' => $platform_code,
            'userList' => $userList,
            'list' => $date,
            'mail_not_list' => $mail_not_list,
            'mail_list' => $mail_list,
            'mail_end_list' => $mail_end_list,
            'mail_wait_list' => $mail_wait_list,
            'mail_not_percent' => $mail_not_percent,
            'inqurry_list' => $inqurry_list,
            'inqurry_not_list' => $inqurry_not_list,
            'inqurry_end_list' => $inqurry_end_list,
            'inqurry_wait_list' => $inqurry_wait_list,
            'inqurry_not_percent' => $inqurry_not_percent,
            'return_list' => $return_list,
            'return_not_list' => $return_not_list,
            'return_end_list' => $return_end_list,
            'return_wait_list' => $return_wait_list,
            'return_not_percent' => $return_not_percent,
            'cancellation_list' => $cancellation_list,
            'cancellation_not_list' => $cancellation_not_list,
            'cancellation_end_list' => $cancellation_end_list,
            'cancellation_wait_list' => $cancellation_wait_list,
            'cancellation_not_percent' => $cancellation_not_percent,
            'feedback_list' => $feedback_list,
            'feedback_not_list' => $feedback_not_list,
            'feedback_end_list' => $feedback_end_list,
            'feedback_wait_list' => $feedback_wait_list,
            'feedback_not_percent' => $feedback_not_percent,
        ]);

    }

    /**
     * @param $user_name
     * @param $platform_code
     */
    public function getAccountId($user_name, $platform_code, $andwhere = [])
    {

        $user_id = (new Query())->select('id')->from('{{%user}}')->where(['user_name' => $user_name])
            ->createCommand(Yii::$app->db_system)
            ->queryColumn();
        $account_old_id = (new Query())->select('account_ids')->from('{{%orderservice}}')->where(['user_id' => $user_id, 'platform_code' => $platform_code])
            ->createCommand(Yii::$app->db_system)
            ->queryOne();

        if ($account_old_id['account_ids']) {
            $account_old_id = explode(',', $account_old_id['account_ids']);
        }
        //账号信息
        $account_id = Account::findAccountId($account_old_id, $platform_code);

        //待处理时间，
        $ranges_time = date('Y-m-01 00:00:00', strtotime('-2 month'));
        $ranges = [
            'start_time' => $ranges_time,
            'end_time' => date('Y-m-t 23:59:59'),
        ];

        if (!empty($account_id)) {
            //取出今天所有未回复邮件
            $mail_not_list = MailStatistics::mailNotDay($platform_code, $account_id, $andwhere['maildata']);
            //所有邮件
            $mail_list = MailStatistics::mailDay($platform_code, $account_id, $andwhere['maildata']);
            //所有已回复
            $mail_end_list = MailStatistics::mailEndDay($platform_code, $account_id, $andwhere['maildata']);
            //主动联系
          /*  $mail_list_touch = MailStatistics :: mailAction(Platform::PLATFORM_CODE_EB, $account_id, $andwhere['send_time']);
            $mail_list_active = $mail_list_touch - $mail_end_list;*/

            //待回复
            $mail_wait_list = MailStatistics::mailWaitList($platform_code, $account_id, $ranges);
            //未处理比例
            if ($mail_list > 0 && $mail_not_list) {
                $mail_not_percent = round($mail_not_list / $mail_list, 4) * 100 . "%";
            } else {
                $mail_not_percent = 0;
            }
            if($platform_code == 'EB'){
                //当天所有未收到纠纷数量
                $inqurry_list = DisputeStatistics::disputeDay($platform_code, $account_id, $andwhere['creation_date'], self::TASK_TYPE_INQUIRY);
                //未收到纠纷未处理
                $inqurry_not_list = DisputeStatistics::disputeNotDay($platform_code, $account_id, $andwhere['creation_date'], self::TASK_TYPE_INQUIRY);
                //未收到纠纷已处理
                $inqurry_end_list = DisputeStatistics::disputeEndDay($platform_code, $account_id, $andwhere['creation_date'], self::TASK_TYPE_INQUIRY);
                //未收到纠纷待处理
                $inqurry_wait_list = DisputeStatistics::disputeWaitList($platform_code, $account_id, $ranges, self::TASK_TYPE_INQUIRY);

                if ($inqurry_list > 0 && $inqurry_not_list) {
                    $inqurry_not_percent = round($inqurry_not_list / $inqurry_list, 4) * 100 . "%";
                } else {
                    $inqurry_not_percent = 0;
                }

                //当月退款退货纠纷
                $return_list = DisputeStatistics::disputeDay($platform_code, $account_id, $andwhere['return_creation_date'], self::TASK_TYPE_RETURN);
                //退款退货未处理
                $return_not_list = DisputeStatistics::disputeNotDay($platform_code, $account_id, $andwhere['return_creation_date'], self::TASK_TYPE_RETURN);
                //退款退货已处理
                $return_end_list = DisputeStatistics::disputeEndDay($platform_code, $account_id, $andwhere['return_creation_date'], self::TASK_TYPE_RETURN);
                //退款退货待处理
                $return_wait_list = DisputeStatistics::disputeWaitDay($platform_code, $account_id, $ranges, self::TASK_TYPE_RETURN);

                if ($return_list > 0 && $return_not_list) {
                    $return_not_percent = round($return_not_list / $return_list, 4) * 100 . "%";
                } else {
                    $return_not_percent = 0;
                }

                //当月取消交易纠纷
                $cancellation_list = DisputeStatistics::cancellationDay($platform_code, $account_id, $andwhere['cancel_request_date'], self::TASK_TYPE_CANCELLATION);
                //取消交易未处理
                $cancellation_not_list = DisputeStatistics::cancellationNotDay($platform_code, $account_id, $andwhere['cancel_request_date'], self::TASK_TYPE_CANCELLATION);
                //取消交易已处理
                $cancellation_end_list = DisputeStatistics::cancellationEndDay($platform_code, $account_id, $andwhere['cancel_request_date'], self::TASK_TYPE_CANCELLATION);
                //取消交易待处理
                $cancellation_wait_list = DisputeStatistics::cancellationWaitList($platform_code, $account_id, $ranges, self::TASK_TYPE_CANCELLATION);

                if ($cancellation_list > 0 && $cancellation_not_list) {
                    $cancellation_not_percent = round($cancellation_not_list / $cancellation_list, 4) * 100 . "%";
                } else {
                    $cancellation_not_percent = 0;
                }

                //当月评价
                $feedback_list = FeedbackStatistics::feedbackDay($platform_code, $account_id, $andwhere['feeddata']);
                //评价未处理
                $feedback_not_list = FeedbackStatistics::feedbackNotDay($platform_code, $account_id, $andwhere['feeddata']);
                //评价已处理
                $feedback_end_list = FeedbackStatistics::feedbackEndDay($platform_code, $account_id, $andwhere['feeddata']);
                //评价待处理
                $feedback_wait_list = FeedbackStatistics::feedbackWaitList($platform_code, $account_id, $ranges);

                if ($feedback_list > 0 && $feedback_not_list) {
                    $feedback_not_percent = round($feedback_not_list / $feedback_list, 4) * 100 . "%";
                } else {
                    $feedback_not_percent = 0;
                }
            }else{
                //当天所有物流纠纷
                $inqurry_list = DisputeStatistics::disputeDay(Platform::PLATFORM_CODE_ALI, $account_id, $andwhere['gmt_create'], self::TASK_LOGISTICS);
                //物流纠纷未处理
                $inqurry_not_list = DisputeStatistics::disputeNotDay(Platform::PLATFORM_CODE_ALI, $account_id, $andwhere['gmt_create'], self::TASK_LOGISTICS);
                //物流纠纷已处理
                $inqurry_end_list = DisputeStatistics::disputeEndDay(Platform::PLATFORM_CODE_ALI, $account_id, $andwhere['gmt_create'], self::TASK_LOGISTICS);
                //物流纠纷待处理
                $inqurry_wait_list = DisputeStatistics::disputeWaitList(Platform::PLATFORM_CODE_ALI, $account_id, $ranges, self::TASK_LOGISTICS);
                if ($inqurry_list > 0 && $inqurry_not_list) {
                    $inqurry_not_percent = round($inqurry_not_list / $inqurry_list, 4) * 100 . "%";
                } else {
                    $inqurry_not_percent = 0;
                }
                //当天所有买家原因纠纷
                $return_list = DisputeStatistics::disputeDay(Platform::PLATFORM_CODE_ALI, $account_id, $andwhere['gmt_create'], self::TASK_BUYER);
                //买家原因纠纷未处理
                $return_not_list = DisputeStatistics::disputeNotDay(Platform::PLATFORM_CODE_ALI, $account_id, $andwhere['gmt_create'], self::TASK_BUYER);
                //买家原因纠纷已处理
                $return_end_list = DisputeStatistics::disputeEndDay(Platform::PLATFORM_CODE_ALI, $account_id, $andwhere['gmt_create'], self::TASK_BUYER);
                //买家原因纠纷待处理
                $return_wait_list = DisputeStatistics::disputeWaitList(Platform::PLATFORM_CODE_ALI, $account_id, $ranges, self::TASK_BUYER);
                if ($return_list > 0 && $return_not_list) {
                    $return_not_percent = round($return_not_list / $return_list, 4) * 100 . "%";
                } else {
                    $return_not_percent = 0;
                }

                //当天取消交易纠纷
                $cancellation_list = DisputeStatistics::disputeDay(Platform::PLATFORM_CODE_ALI, $account_id, $andwhere['gmt_create'], self::TASK_QUALITY);
                //取消交易未处理
                $cancellation_not_list = DisputeStatistics::disputeNotDay(Platform::PLATFORM_CODE_ALI, $account_id, $andwhere['gmt_create'], self::TASK_QUALITY);
                //取消交易已处理
                $cancellation_end_list = DisputeStatistics::disputeEndDay(Platform::PLATFORM_CODE_ALI, $account_id, $andwhere['gmt_create'], self::TASK_QUALITY);
                //取消交易待处理
                $cancellation_wait_list = DisputeStatistics::disputeWaitList(Platform::PLATFORM_CODE_ALI, $account_id, $ranges, self::TASK_QUALITY);

                if ($cancellation_list > 0 && $cancellation_not_list) {
                    $cancellation_not_percent = round($cancellation_not_list / $cancellation_list, 4) * 100 . "%";
                } else {
                    $cancellation_not_percent = 0;
                }

                //速卖通当天评价
                $feedback_list = FeedbackStatistics::feedbackDay(Platform::PLATFORM_CODE_ALI, $account_id, $andwhere['buyer_fb_date']);
                //速卖通评价未处理
                $feedback_not_list = FeedbackStatistics::feedbackNotDay(Platform::PLATFORM_CODE_ALI, $account_id, $andwhere['buyer_fb_date']);
                //速卖通评价已处理
                $feedback_end_list = FeedbackStatistics::feedbackEndDay(Platform::PLATFORM_CODE_ALI, $account_id, $andwhere['buyer_fb_date']);
                //速卖通评价待处理
                $feedback_wait_list = FeedbackStatistics::feedbackWaitList(Platform::PLATFORM_CODE_ALI, $account_id, $ranges);
                if ($feedback_list > 0 && $feedback_not_list) {
                    $feedback_not_percent = round($feedback_not_list / $feedback_list, 4) * 100 . "%";
                } else {
                    $feedback_not_percent = 0;
                }
            }


            //总计
            $total = $mail_list + $inqurry_list + $return_list + $cancellation_list + $feedback_list;

            //完成率
           $completion = $mail_end_list + $inqurry_end_list + $return_end_list + $cancellation_end_list + $feedback_end_list;

           if($total >0 && $completion){
               $completion_rate = round($completion / $total, 4) * 100 . "%";
           }else{
               $completion_rate = 0;
           }

        } else {
            $mail_not_list = 0;
            $mail_list = 0;
            $total = 0;
            $completion_rate = 0;
            $mail_end_list = 0;
            $mail_wait_list = 0;
            $mail_not_percent = 0;
            $inqurry_list = 0;
            $inqurry_not_list = 0;
            $inqurry_end_list = 0;
            $inqurry_wait_list = 0;
            $inqurry_not_percent = 0;
            $return_list = 0;
            $return_not_list = 0;
            $return_end_list = 0;
            $return_wait_list = 0;
            $return_not_percent = 0;
            $cancellation_list = 0;
            $cancellation_not_list = 0;
            $cancellation_end_list = 0;
            $cancellation_wait_list = 0;
            $cancellation_not_percent = 0;
            $feedback_list = 0;
            $feedback_not_list = 0;
            $feedback_end_list = 0;
            $feedback_wait_list = 0;
            $feedback_not_percent = 0;
        }


        $count = ['mail_list' => $mail_list, 'mail_not_list' => $mail_not_list, 'mail_end_list' => $mail_end_list, 'mail_wait_list' => $mail_wait_list,'mail_not_percent' => $mail_not_percent,'total' => $total,'completion_rate'=>$completion_rate,
            'inqurry_list' => $inqurry_list, 'inqurry_not_list' => $inqurry_not_list, 'inqurry_end_list' => $inqurry_end_list, 'inqurry_wait_list' => $inqurry_wait_list,'inqurry_not_percent' => $inqurry_not_percent,
            'return_list' => $return_list, 'return_not_list' => $return_not_list, 'return_end_list' => $return_end_list, 'return_wait_list' => $return_wait_list,'return_not_percent' => $return_not_percent,
            'cancellation_list' => $cancellation_list, 'cancellation_not_list' => $cancellation_not_list, 'cancellation_end_list' => $cancellation_end_list, 'cancellation_wait_list' => $cancellation_wait_list,'cancellation_not_percent' => $cancellation_not_percent,
            'feedback_list' => $feedback_list, 'feedback_not_list' => $feedback_not_list, 'feedback_end_list' => $feedback_end_list, 'feedback_wait_list' => $feedback_wait_list,'feedback_not_percent' => $feedback_not_percent];
        return $count;

    }

    /**
     * @param string $platformCode
     * @return array
     */
    public function getAllRoleIds($platformCode = '')
    {
        return Role::find()->select('id')
            ->andWhere(['like', 'platform_code', $platformCode])
            ->asArray()
            ->column();
    }

    /**
     * @param int $parentRoleId
     * @param array $roleIds
     * @param string $platformCode
     * @return array
     */
    public function getChildRoleIds($parentRoleId = 0, &$roleIds = [], $platformCode = '')
    {
        $ids = Role::find()->select('id')
            ->andWhere(['parent_id' => $parentRoleId])
            ->andWhere(['like', 'platform_code', $platformCode])
            ->asArray()
            ->column();

        if (empty($ids)) {
            return array_unique($roleIds);
        }
        $roleIds = array_merge($roleIds, $ids);
        foreach ($ids as $id) {
            $this->getChildRoleIds($id, $roleIds, $platformCode);
        }
    }

    /**
     * @return array
     */

    public function actionDates()
    {
        if (Yii::$app->request->isAjax) {
            $name = $this->request->post('name');
            $id = $this->request->post('account_id');
            $user_name = $this->request->post('user_name');
            $start_time = $this->request->post('start_time');
            $end_time = $this->request->post('end_time');
            $platform_code = $this->request->post('platform_code');

            //账号信息
            if ($id != 0) {
                $account_id[] = $id;
            } else {
                $user_id = (new Query())->select('id')->from('{{%user}}')->where(['user_name' => $user_name])
                    ->createCommand(Yii::$app->db_system)
                    ->queryColumn();
                $account_old_id = (new Query())->select('account_ids')->from('{{%orderservice}}')->where(['user_id' => $user_id, 'platform_code' => $platform_code])
                    ->createCommand(Yii::$app->db_system)
                    ->queryOne();
                if ($account_old_id['account_ids']) {
                    $account_old_id = explode(',', $account_old_id['account_ids']);
                }
                //账号信息
                $account_id = Account::findAccountId($account_old_id, $platform_code);
            }

            if($start_time && $end_time) {
                if ($start_time <= $end_time) {
                    $start = strtotime($start_time.' 10:00:00');
                    $end = strtotime($end_time.' 10:00:00');
                    $mail_start = strtotime($start_time.' 18:00:00');
                    $mail_end = strtotime($end_time.' 18:00:00');

                } else {
                    $start = strtotime($end_time.' 10:00:00');
                    $end = strtotime($start_time.' 10:00:00');
                    $mail_start = strtotime($end_time.' 18:00:00');
                    $mail_end = strtotime($start_time.' 18:00:00');
                }
                //计算天数
                $timediff = $end - $start;
                $days = intval($timediff/86400);
                $start_time = date('Y-m-d 10:00:00', strtotime("-1 day", strtotime($start_time)));
                $mail_start_time = date('Y-m-d 18:00:00', strtotime("-1 day", strtotime($start_time)));
                for ($i = 0; $i <= $days; $i++) {
                    $add = $i + 1;
                    $range[$i] = [
                        'start_time' => date('Y-m-d 10:00:00', strtotime("+{$i} day", strtotime($start_time))),
                        'end_time' => date('Y-m-d 10:00:00', strtotime("+{$add} day", strtotime($start_time))),
                    ];
                    $mail_range[$i] = [
                        'start_time' => date('Y-m-d 18:00:00', strtotime("+{$i} day", strtotime($mail_start_time))),
                        'end_time' => date('Y-m-d 18:00:00', strtotime("+{$add} day", strtotime($mail_start_time))),
                    ];
                }
            }

            if(isset($name)){
                if ($name == 1) {
                    $range[] = [
                        'start_time' => date('Y-m-d 10:00:00', strtotime("-{$name} day")),
                        'end_time' => date('Y-m-d 10:00:00'),
                    ];
                    $mail_range[] = [
                        'start_time' => date('Y-m-d 18:00:00', strtotime("-{$name} day")),
                        'end_time' => date('Y-m-d 18:00:00'),
                    ];
                } else {
                    $absName = abs($name);
                    $name = --$name;
                    $start_time = date('Y-m-d 10:00:00', strtotime("{$name} day"));
                    $mail_start_time = date('Y-m-d 18:00:00', strtotime("{$name} day"));
                    for ($i = 0; $i < $absName; $i++) {
                        $add = $i + 1;
                        $range[$i] = [
                            'start_time' => date('Y-m-d 10:00:00', strtotime("+{$i} day", strtotime($start_time))),
                            'end_time' => date('Y-m-d 10:00:00', strtotime("+{$add} day", strtotime($start_time))),
                        ];
                        $mail_range[$i] = [
                            'start_time' => date('Y-m-d 18:00:00', strtotime("+{$i} day", strtotime($mail_start_time))),
                            'end_time' => date('Y-m-d 18:00:00', strtotime("+{$add} day", strtotime($mail_start_time))),
                        ];
                    }
                }
            }

            $data = '';
            $feeddata = '';
            $maildata = '';
            $cancel_request_date = '';
            $creation_date = '';
            $return_creation_date = '';
            $gmt_create = '';
            $buyer_fb_date = '';
            foreach ($range as $item) {
                $data .= "(create_time between '{$item['start_time']}' and '{$item['end_time']}') or ";
                //$maildata .= "(receive_date between '{$item['start_time']}' and '{$item['end_time']}') or ";
                $cancel_request_date .= "(cancel_request_date between '{$item['start_time']}' and '{$item['end_time']}') or ";
                $creation_date .= "(creation_date between '{$item['start_time']}' and '{$item['end_time']}') or ";
                $return_creation_date .= "(return_creation_date between '{$item['start_time']}' and '{$item['end_time']}') or ";
            }
            foreach ($mail_range as $item){
                $maildata .= "(receive_date between '{$item['start_time']}' and '{$item['end_time']}') or ";
                $feeddata .= "(comment_time between '{$item['start_time']}' and '{$item['end_time']}') or ";
                $gmt_create .= "(gmt_create between '{$item['start_time']}' and '{$item['end_time']}') or ";
                $buyer_fb_date .= "(buyer_fb_date between '{$item['start_time']}' and '{$item['end_time']}') or ";
            }

            $data = rtrim($data, 'or ');
            $feeddata = rtrim($feeddata, 'or ');
            $maildata = rtrim($maildata, 'or ');
            $cancel_request_date = rtrim($cancel_request_date, 'or ');
            $creation_date = rtrim($creation_date, 'or ');
            $return_creation_date = rtrim($return_creation_date, 'or ');
            $gmt_create = rtrim($gmt_create, 'or ');
            $buyer_fb_date = rtrim($buyer_fb_date, 'or ');

            //待处理时间，
            $ranges_time = date('Y-m-01 00:00:00', strtotime('-2 month'));
            $ranges = [
                'start_time' => $ranges_time,
                'end_time' => date('Y-m-t 23:59:59'),
            ];

            //当前所有未回复邮件
            $mail_not_all = MailStatistics::mailNotAll($platform_code, $account_id, $maildata);
            //所有邮件
            $mail_all = MailStatistics::mailAll($platform_code, $account_id, $maildata);
            //所有已回复
            $mail_end_all = MailStatistics::mailEndAll($platform_code, $account_id, $maildata);
            //待回复
            $mail_wait_all = MailStatistics::mailWaitList($platform_code, $account_id, $ranges);
            if ($mail_all > 0 && $mail_not_all) {
                $mail_not_percent = round($mail_not_all / $mail_all, 4) * 100 . "%";
            } else {
                $mail_not_percent = 0;
            }
            if($platform_code == 'EB'){
                //当前所有未收到纠纷数量
                $inqurry_all = DisputeStatistics::disputeAll($platform_code, $account_id, $creation_date, self::TASK_TYPE_INQUIRY);
                //未收到纠纷未处理
                $inqurry_not_all = DisputeStatistics::disputeNotAll($platform_code, $account_id, $creation_date, self::TASK_TYPE_INQUIRY);
                //未收到纠纷已处理
                $inqurry_end_all = DisputeStatistics::disputeEndAll($platform_code, $account_id, $creation_date, self::TASK_TYPE_INQUIRY);
                //未收到纠纷待处理
                $inqurry_wait_all = DisputeStatistics::disputeWaitList($platform_code, $account_id, $ranges, self::TASK_TYPE_INQUIRY);

                if ($inqurry_all > 0 && $inqurry_not_all) {
                    $inqurry_not_percent = round($inqurry_not_all / $inqurry_all, 4) * 100 . "%";
                } else {
                    $inqurry_not_percent = 0;
                }

                //当前退款退货纠纷
                $return_all = DisputeStatistics::disputeAll($platform_code, $account_id, $return_creation_date, self::TASK_TYPE_RETURN);
                //退款退货未处理
                $return_not_all = DisputeStatistics::disputeNotAll($platform_code, $account_id, $return_creation_date, self::TASK_TYPE_RETURN);
                //退款退货已处理
                $return_end_all = DisputeStatistics::disputeEndAll($platform_code, $account_id, $return_creation_date, self::TASK_TYPE_RETURN);
                //退款退货待处理
                $return_wait_all = DisputeStatistics::disputeWaitDay($platform_code, $account_id, $ranges, self::TASK_TYPE_RETURN);
                if ($return_all > 0 && $return_not_all) {
                    $return_not_percent = round($return_not_all / $return_all, 4) * 100 . "%";
                } else {
                    $return_not_percent = 0;
                }

                //当前取消交易纠纷
                $cancellation_all = DisputeStatistics::cancellationAll($platform_code, $account_id, $cancel_request_date, self::TASK_TYPE_CANCELLATION);
                //取消交易未处理
                $cancellation_not_all = DisputeStatistics::cancellationNotAll($platform_code, $account_id, $cancel_request_date, self::TASK_TYPE_CANCELLATION);
                //取消交易已处理
                $cancellation_end_all = DisputeStatistics::cancellationEndAll($platform_code, $account_id, $cancel_request_date, self::TASK_TYPE_CANCELLATION);
                //取消交易待处理
                $cancellation_wait_all = DisputeStatistics::cancellationWaitList($platform_code, $account_id, $ranges, self::TASK_TYPE_CANCELLATION);

                if ($cancellation_all > 0 && $cancellation_not_all) {
                    $cancellation_not_percent = round($cancellation_not_all / $cancellation_all, 4) * 100 . "%";
                } else {
                    $cancellation_not_percent = 0;
                }

                //当前所有评价
                $feedback_all = FeedbackStatistics::feedbackAll($platform_code, $account_id, $feeddata);
                //评价未处理
                $feedback_not_all = FeedbackStatistics::feedbackNotAll($platform_code, $account_id, $feeddata);
                //评价已处理
                $feedback_end_all = FeedbackStatistics::feedbackEndAll($platform_code, $account_id, $feeddata);
                //评价待处理
                $feedback_wait_all = FeedbackStatistics::feedbackWaitList($platform_code, $account_id, $ranges);

                if ($feedback_all > 0 && $feedback_not_all) {
                    $feedback_not_percent = round($feedback_not_all / $feedback_all, 4) * 100 . "%";
                } else {
                    $feedback_not_percent = 0;
                }
            }else{
                //当前所有物流纠纷数量
                $inqurry_all = DisputeStatistics::disputeAll($platform_code, $account_id, $gmt_create, self::TASK_LOGISTICS);
                //物流纠纷未处理
                $inqurry_not_all = DisputeStatistics::disputeNotAll($platform_code, $account_id, $gmt_create, self::TASK_LOGISTICS);
                //物流到纠纷已处理
                $inqurry_end_all = DisputeStatistics::disputeEndAll($platform_code, $account_id, $gmt_create, self::TASK_LOGISTICS);
                //物流到纠纷待处理
                $inqurry_wait_all = DisputeStatistics::disputeWaitList($platform_code, $account_id, $ranges, self::TASK_LOGISTICS);

                if ($inqurry_all > 0 && $inqurry_not_all) {
                    $inqurry_not_percent = round($inqurry_not_all / $inqurry_all, 4) * 100 . "%";
                } else {
                    $inqurry_not_percent = 0;
                }

                //当前买家原因纠纷
                $return_all = DisputeStatistics::disputeAll($platform_code, $account_id, $gmt_create, self::TASK_BUYER);
                //买家原因未处理
                $return_not_all = DisputeStatistics::disputeNotAll($platform_code, $account_id, $gmt_create, self::TASK_BUYER);
                //买家原因已处理
                $return_end_all = DisputeStatistics::disputeEndAll($platform_code, $account_id, $gmt_create, self::TASK_BUYER);
                //买家原因待处理
                $return_wait_all = DisputeStatistics::disputeWaitList($platform_code, $account_id, $ranges, self::TASK_BUYER);
                if ($return_all > 0 && $return_not_all) {
                    $return_not_percent = round($return_not_all / $return_all, 4) * 100 . "%";
                } else {
                    $return_not_percent = 0;
                }

                //当前取消交易纠纷
                $cancellation_all = DisputeStatistics::disputeAll($platform_code, $account_id, $gmt_create, self::TASK_QUALITY);
                //取消交易未处理
                $cancellation_not_all = DisputeStatistics::disputeNotAll($platform_code, $account_id, $gmt_create, self::TASK_QUALITY);
                //取消交易已处理
                $cancellation_end_all = DisputeStatistics::disputeEndAll($platform_code, $account_id, $gmt_create, self::TASK_QUALITY);
                //取消交易待处理
                $cancellation_wait_all = DisputeStatistics::disputeWaitList($platform_code, $account_id, $ranges, self::TASK_QUALITY);

                if ($cancellation_all > 0 && $cancellation_not_all) {
                    $cancellation_not_percent = round($cancellation_not_all / $cancellation_all, 4) * 100 . "%";
                } else {
                    $cancellation_not_percent = 0;
                }

                //当前所有评价
                $feedback_all = FeedbackStatistics::feedbackAll($platform_code, $account_id, $buyer_fb_date);
                //评价未处理
                $feedback_not_all = FeedbackStatistics::feedbackNotAll($platform_code, $account_id, $buyer_fb_date);
                //评价已处理
                $feedback_end_all = FeedbackStatistics::feedbackEndAll($platform_code, $account_id, $buyer_fb_date);
                //评价待处理
                $feedback_wait_all = FeedbackStatistics::feedbackWaitList($platform_code, $account_id, $ranges);

                if ($feedback_all > 0 && $feedback_not_all) {
                    $feedback_not_percent = round($feedback_not_all / $feedback_all, 4) * 100 . "%";
                } else {
                    $feedback_not_percent = 0;
                }

            }


            $count = ['mail_not_all' => $mail_not_all, 'mail_all' => $mail_all, 'mail_end_all' => $mail_end_all, 'mail_wait_all' => $mail_wait_all, 'mail_not_percent' => $mail_not_percent,
                'inqurry_all' => $inqurry_all, 'inqurry_not_all' => $inqurry_not_all, 'inqurry_end_all' => $inqurry_end_all, 'inqurry_wait_all' => $inqurry_wait_all, 'inqurry_not_percent' => $inqurry_not_percent,
                'return_all' => $return_all, 'return_not_all' => $return_not_all, 'return_end_all' => $return_end_all, 'return_wait_all' => $return_wait_all, 'return_not_percent' => $return_not_percent,
                'cancellation_all' => $cancellation_all, 'cancellation_not_all' => $cancellation_not_all, 'cancellation_end_all' => $cancellation_end_all, 'cancellation_wait_all' => $cancellation_wait_all, 'cancellation_not_percent' => $cancellation_not_percent,
                'feedback_all' => $feedback_all, 'feedback_not_all' => $feedback_not_all, 'feedback_end_all' => $feedback_end_all, 'feedback_wait_all' => $feedback_wait_all, 'feedback_not_percent' => $feedback_not_percent];
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'count' => $count,
                'code' => 200,
                'message' => '请求成功',
            ];

        }

    }

    /**
     * 主管页ajax请求
     */
    public function actionData()
    {
        if (Yii::$app->request->isAjax) {
            $range = [];
            $name = $this->request->post('name');
            $platform_code = $this->request->post('platform_code');
            $start_time = $this->request->post('start_time');
            $end_time = $this->request->post('end_time');

            $user_id = Yii::$app->user->identity->id;
            $role_id = UserRole::find()->select('role_id')->where(['user_id' => $user_id])->column();

            $roleIds = [];
            if (in_array(1, $role_id)) {
                $roleIds = $this->getAllRoleIds($platform_code);
                $user_id = UserRole::find()->select('user_id')->where(['in','role_id',$roleIds])->asArray()->column();
                $userList = User::find()
                    ->select(['id', 'login_name'])
                    ->where(['in', 'id', $user_id])
                    ->andWhere(['status' => 1])
                    ->andWhere(['<>', 'role_id', 1])
                    ->asArray()
                    ->all();
            } else {
                $this->getChildRoleIds($role_id[0], $roleIds, $platform_code);
                $user_id = UserRole::find()->select('user_id')->where(['in','role_id',$roleIds])->column();
                $userList = User::find()
                    ->select(['id', 'login_name'])
                    ->where(['in', 'id', $user_id])
                    ->andWhere(['status' => 1])
                    ->asArray()
                    ->all();
            }

            $user_list = [];

            //只筛选有账号的客服
            foreach($userList as $k=>$value){
                $user_account = UserAccount::findOne(['user_id' => $value['id'],'platform_code' => $platform_code]);
                if(!empty($user_account)){
                    $user_list[] = $value;
                }
            }


            $userList = array_column($user_list, 'login_name', 'login_name');


            if($start_time && $end_time) {
                if ($start_time <= $end_time) {
                    $start = strtotime($start_time.' 10:00:00');
                    $end = strtotime($end_time.' 10:00:00');
                    $mail_start = strtotime($start_time.' 18:00:00');
                    $mail_end = strtotime($end_time.' 18:00:00');

                } else {
                    $start = strtotime($end_time.' 10:00:00');
                    $end = strtotime($start_time.' 10:00:00');
                    $mail_start = strtotime($end_time.' 18:00:00');
                    $mail_end = strtotime($start_time.' 18:00:00');
                }
                //计算天数
                $timediff = $end - $start;
                $days = intval($timediff/86400);
                $start_time = date('Y-m-d 10:00:00', strtotime("-1 day", strtotime($start_time)));
                $mail_start_time = date('Y-m-d 18:00:00', strtotime("-1 day", strtotime($start_time)));
                for ($i = 0; $i <= $days; $i++) {
                    $add = $i + 1;
                    $range[$i] = [
                        'start_time' => date('Y-m-d 10:00:00', strtotime("+{$i} day", strtotime($start_time))),
                        'end_time' => date('Y-m-d 10:00:00', strtotime("+{$add} day", strtotime($start_time))),
                    ];
                    $mail_range[$i] = [
                        'start_time' => date('Y-m-d 18:00:00', strtotime("+{$i} day", strtotime($mail_start_time))),
                        'end_time' => date('Y-m-d 18:00:00', strtotime("+{$add} day", strtotime($mail_start_time))),
                    ];
                }
            }
            if($name){
                if ($name == 1) {
                    $range[] = [
                        'start_time' => date('Y-m-d 10:00:00', strtotime("-{$name} day")),
                        'end_time' => date('Y-m-d 10:00:00'),
                    ];
                    $mail_range[] = [
                        'start_time' => date('Y-m-d 18:00:00', strtotime("-{$name} day")),
                        'end_time' => date('Y-m-d 18:00:00'),
                    ];
                } else {
                    $absName = abs($name);
                    $name = --$name;
                    $start_time = date('Y-m-d 10:00:00', strtotime("{$name} day"));
                    $mail_start_time = date('Y-m-d 18:00:00', strtotime("{$name} day"));
                    for ($i = 0; $i < $absName; $i++) {
                        $add = $i + 1;
                        $range[$i] = [
                            'start_time' => date('Y-m-d 10:00:00', strtotime("+{$i} day", strtotime($start_time))),
                            'end_time' => date('Y-m-d 10:00:00', strtotime("+{$add} day", strtotime($start_time))),
                        ];
                        $mail_range[$i] = [
                            'start_time' => date('Y-m-d 18:00:00', strtotime("+{$i} day", strtotime($mail_start_time))),
                            'end_time' => date('Y-m-d 18:00:00', strtotime("+{$add} day", strtotime($mail_start_time))),
                        ];
                    }
                }
            }
            $data = '';
            $feeddata = '';
            $maildata = '';
            $cancel_request_date = '';
            $creation_date = '';
            $return_creation_date = '';
            $gmt_create = '';
            $buyer_fb_date = '';
            foreach ($range as $item) {
                $data .= "(create_time between '{$item['start_time']}' and '{$item['end_time']}') or ";
               // $maildata .= "(receive_date between '{$item['start_time']}' and '{$item['end_time']}') or ";
                $cancel_request_date .= "(cancel_request_date between '{$item['start_time']}' and '{$item['end_time']}') or ";
                $creation_date .= "(creation_date between '{$item['start_time']}' and '{$item['end_time']}') or ";
                $return_creation_date .= "(return_creation_date between '{$item['start_time']}' and '{$item['end_time']}') or ";
            }

            foreach($mail_range as $item){
                $maildata .= "(receive_date between '{$item['start_time']}' and '{$item['end_time']}') or ";
                $feeddata .= "(comment_time between '{$item['start_time']}' and '{$item['end_time']}') or ";
                $gmt_create .= "(gmt_create between '{$item['start_time']}' and '{$item['end_time']}') or ";
                $buyer_fb_date .= "(buyer_fb_date between '{$item['start_time']}' and '{$item['end_time']}') or ";
            }

            $data = rtrim($data, 'or ');
            $feeddata = rtrim($feeddata, 'or ');
            $maildata = rtrim($maildata, 'or ');
            $cancel_request_date = rtrim($cancel_request_date, 'or ');
            $creation_date = rtrim($creation_date, 'or ');
            $return_creation_date = rtrim($return_creation_date, 'or ');
            $gmt_create = rtrim($gmt_create, 'or ');
            $buyer_fb_date = rtrim($buyer_fb_date, 'or ');
            $andwhere['maildata'] = $maildata;
            $andwhere['feeddata'] = $feeddata;
            $andwhere['cancel_request_date'] = $cancel_request_date;
            $andwhere['creation_date'] = $creation_date;
            $andwhere['gmt_create'] = $gmt_create;
            $andwhere['buyer_fb_date'] = $buyer_fb_date;
            $andwhere['return_creation_date'] = $return_creation_date;

            //待处理时间，
            $ranges_time = date('Y-m-01 00:00:00', strtotime('-2 month'));
            $ranges = [
                'start_time' => $ranges_time,
                'end_time' => date('Y-m-t 23:59:59'),
            ];

            if($platform_code == 'EB'){
                // 只能查询到客服绑定账号的回复
                $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);

                $mail_not_all = MailStatistics::mailNotAll($platform_code, $accountIds, $maildata);
                //所有邮件
                $mail_all = MailStatistics::mailAll($platform_code, $accountIds, $maildata);
                //所有已回复
                $mail_end_all = MailStatistics::mailEndAll($platform_code, $accountIds, $maildata);

                //待回复
                $mail_wait_all = MailStatistics::mailWaitList($platform_code, $accountIds, $ranges);
                if ($mail_all > 0 && $mail_not_all > 0) {
                    $mail_not_percent = round($mail_not_all / $mail_all, 4) * 100 . "%";
                } else {
                    $mail_not_percent = 0;
                }

                //当前所有未收到纠纷数量
                $inqurry_all = DisputeStatistics::disputeAll($platform_code, $accountIds, $creation_date, self::TASK_TYPE_INQUIRY);
                //未收到纠纷未处理
                $inqurry_not_all = DisputeStatistics::disputeNotAll($platform_code, $accountIds, $creation_date, self::TASK_TYPE_INQUIRY);
                //未收到纠纷已处理
                $inqurry_end_all = DisputeStatistics::disputeEndAll($platform_code, $accountIds, $creation_date, self::TASK_TYPE_INQUIRY);
                //未收到纠纷待处理
                $inqurry_wait_all = DisputeStatistics::disputeWaitList($platform_code, $accountIds, $ranges, self::TASK_TYPE_INQUIRY);
                if ($inqurry_all > 0 && $inqurry_not_all > 0) {
                    $inqurry_not_percent = round($inqurry_not_all / $inqurry_all, 4) * 100 . "%";
                } else {
                    $inqurry_not_percent = 0;
                }

                //当前退款退货纠纷
                $return_all = DisputeStatistics::disputeAll($platform_code, $accountIds, $return_creation_date, self::TASK_TYPE_RETURN);
                //退款退货未处理
                $return_not_all = DisputeStatistics::disputeNotAll($platform_code, $accountIds, $return_creation_date, self::TASK_TYPE_RETURN);
                //退款退货已处理
                $return_end_all = DisputeStatistics::disputeEndAll($platform_code, $accountIds, $return_creation_date, self::TASK_TYPE_RETURN);
                //退款退货待处理
                $return_wait_all = DisputeStatistics::disputeWaitDay($platform_code, $accountIds, $ranges, self::TASK_TYPE_RETURN);
                if ($return_all > 0 && $return_not_all > 0) {
                    $return_not_percent = round($return_not_all / $return_all, 4) * 100 . "%";
                } else {
                    $return_not_percent = 0;
                }

                //当前取消交易纠纷
                $cancellation_all = DisputeStatistics::cancellationAll($platform_code, $accountIds, $cancel_request_date, self::TASK_TYPE_CANCELLATION);
                //取消交易未处理
                $cancellation_not_all = DisputeStatistics::cancellationNotAll($platform_code, $accountIds, $cancel_request_date, self::TASK_TYPE_CANCELLATION);
                //取消交易已处理
                $cancellation_end_all = DisputeStatistics::cancellationEndAll($platform_code, $accountIds, $cancel_request_date, self::TASK_TYPE_CANCELLATION);
                //取消交易待处理
                $cancellation_wait_all = DisputeStatistics::cancellationWaitList($platform_code, $accountIds, $ranges, self::TASK_TYPE_CANCELLATION);
                if ($cancellation_all > 0 && $cancellation_not_all > 0) {
                    $cancellation_not_percent = round($cancellation_not_all / $cancellation_all, 4) * 100 . "%";
                } else {
                    $cancellation_not_percent = 0;
                }

                //当前所有评价
                $feedback_all = FeedbackStatistics::feedbackAll($platform_code, $accountIds, $feeddata);
                //评价未处理
                $feedback_not_all = FeedbackStatistics::feedbackNotAll($platform_code, $accountIds, $feeddata);
                //评价已处理
                $feedback_end_all = FeedbackStatistics::feedbackEndAll($platform_code, $accountIds, $feeddata);
                //评价待处理
                $feedback_wait_all = FeedbackStatistics::feedbackWaitList($platform_code, $accountIds, $ranges);
                if ($feedback_all > 0 && $feedback_not_all > 0) {
                    $feedback_not_percent = round($feedback_not_all / $feedback_all, 4) * 100 . "%";
                } else {
                    $feedback_not_percent = 0;
                }


                //每个客服对应邮件量
                $list = [];
                foreach ($userList as $k => $v) {
                    $list[$v] = $this->getAccountId($v, Platform::PLATFORM_CODE_EB, $andwhere);

                }

                $date = [];
                foreach ($list as $k => $v){
                    if(empty($v['mail_list']) && empty($v['inqurry_list']) && empty($v['return_list']) && empty($v['cancellation_list']) && empty($v['feedback_list'])){
                        unset($v);
                    }else{
                        $date[$k] = $v;
                    }
                }
            }else{
                // 只能查询到客服绑定账号的回复
                $accountIds = UserAccount::getCurrentUserPlatformAccountIds($platform_code);

                $mail_not_all = MailStatistics::mailNotAll($platform_code, $accountIds, $maildata);
                //所有邮件
                $mail_all = MailStatistics::mailAll($platform_code, $accountIds, $maildata);
                //所有已回复
                $mail_end_all = MailStatistics::mailEndAll($platform_code, $accountIds, $maildata);

                //待回复
                $mail_wait_all = MailStatistics::mailWaitList($platform_code, $accountIds, $ranges);
                if ($mail_all > 0 && $mail_not_all > 0) {
                    $mail_not_percent = round($mail_not_all / $mail_all, 4) * 100 . "%";
                } else {
                    $mail_not_percent = 0;
                }

                //速卖通当前所有物流纠纷数量
                $inqurry_all = DisputeStatistics::disputeAll($platform_code, $accountIds, $gmt_create, self::TASK_LOGISTICS);
                //速卖通未收到物流纠纷未处理
                $inqurry_not_all = DisputeStatistics::disputeNotAll($platform_code, $accountIds, $gmt_create, self::TASK_LOGISTICS);
                //速卖通未收到物流纠纷已处理
                $inqurry_end_all = DisputeStatistics::disputeEndAll($platform_code, $accountIds, $gmt_create, self::TASK_LOGISTICS);
                //速卖通未收到物流纠纷待处理
                $inqurry_wait_all = DisputeStatistics::disputeWaitList($platform_code, $accountIds, $ranges, self::TASK_LOGISTICS);
                if ($inqurry_all > 0 && $inqurry_not_all > 0) {
                    $inqurry_not_percent = round($inqurry_not_all / $inqurry_all, 4) * 100 . "%";
                } else {
                    $inqurry_not_percent = 0;
                }
                //速卖通当前退款退货纠纷
                $return_all = DisputeStatistics::disputeAll($platform_code, $accountIds, $gmt_create, self::TASK_BUYER);
                //速卖通退款退货未处理
                $return_not_all = DisputeStatistics::disputeNotAll($platform_code, $accountIds, $gmt_create, self::TASK_BUYER);
                //速卖通退款退货已处理
                $return_end_all = DisputeStatistics::disputeEndAll($platform_code, $accountIds, $gmt_create, self::TASK_BUYER);
                //速卖通退款退货待处理
                $return_wait_all = DisputeStatistics::disputeWaitList($platform_code, $accountIds, $ranges, self::TASK_BUYER);
                if ($return_all > 0 && $return_not_all > 0) {
                    $return_not_percent = round($return_not_all / $return_all, 4) * 100 . "%";
                } else {
                    $return_not_percent = 0;
                }
                //速卖通当前取消交易纠纷
                $cancellation_all = DisputeStatistics::disputeAll($platform_code, $accountIds, $gmt_create, self::TASK_QUALITY);
                //速卖通取消交易未处理
                $cancellation_not_all = DisputeStatistics::disputeNotAll($platform_code, $accountIds, $gmt_create, self::TASK_QUALITY);
                //速卖通取消交易已处理
                $cancellation_end_all = DisputeStatistics::disputeEndAll($platform_code, $accountIds, $gmt_create, self::TASK_QUALITY);
                //速卖通取消交易待处理
                $cancellation_wait_all = DisputeStatistics::disputeWaitList($platform_code, $accountIds, $ranges, self::TASK_QUALITY);
                if ($cancellation_all > 0 && $cancellation_not_all > 0) {
                    $cancellation_not_percent = round($cancellation_not_all / $cancellation_all, 4) * 100 . "%";
                } else {
                    $cancellation_not_percent = 0;
                }
                //速卖通当前所有评价
                $feedback_all = FeedbackStatistics::feedbackAll($platform_code, $accountIds, $buyer_fb_date);
                //速卖通评价未处理
                $feedback_not_all = FeedbackStatistics::feedbackNotAll($platform_code, $accountIds, $buyer_fb_date);
                //速卖通评价已处理
                $feedback_end_all = FeedbackStatistics::feedbackEndAll($platform_code, $accountIds, $buyer_fb_date);
                //速卖通评价待处理
                $feedback_wait_all = FeedbackStatistics::feedbackWaitList($platform_code, $accountIds, $ranges);
                if ($feedback_all > 0 && $feedback_not_all > 0) {
                    $feedback_not_percent = round($feedback_not_all / $feedback_all, 4) * 100 . "%";
                } else {
                    $feedback_not_percent = 0;
                }
                //速卖通每个客服对应邮件量
                $list = [];
                foreach ($userList as $k => $v) {
                    $list[$v] = $this->getAccountId($v, Platform::PLATFORM_CODE_ALI, $andwhere);

                }

                $date = [];
                foreach ($list as $k => $v){
                    if(empty($v['mail_list']) && empty($v['inqurry_list']) && empty($v['return_list']) && empty($v['cancellation_list']) && empty($v['feedback_list'])){
                        unset($v);
                    }else{
                        $date[$k] = $v;
                    }
                }
            }


            $count = ['mail_not_all' => $mail_not_all, 'mail_all' => $mail_all, 'mail_end_all' => $mail_end_all, 'mail_wait_all' => $mail_wait_all, 'mail_not_percent' => $mail_not_percent,
                'inqurry_all' => $inqurry_all, 'inqurry_not_all' => $inqurry_not_all, 'inqurry_end_all' => $inqurry_end_all, 'inqurry_wait_all' => $inqurry_wait_all, 'inqurry_not_percent' => $inqurry_not_percent,
                'return_all' => $return_all, 'return_not_all' => $return_not_all, 'return_end_all' => $return_end_all, 'return_wait_all' => $return_wait_all, 'return_not_percent' => $return_not_percent,
                'cancellation_all' => $cancellation_all, 'cancellation_not_all' => $cancellation_not_all, 'cancellation_end_all' => $cancellation_end_all, 'cancellation_wait_all' => $cancellation_wait_all, 'cancellation_not_percent' => $cancellation_not_percent,
                'feedback_all' => $feedback_all, 'feedback_not_all' => $feedback_not_all, 'feedback_end_all' => $feedback_end_all, 'feedback_wait_all' => $feedback_wait_all, 'feedback_not_percent' => $feedback_not_percent];
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'count' => $count,
                'user_list' => $userList,
                'platform_code' => $platform_code,
                'list' => $date,
                'code' => 200,
                'message' => '请求成功',
            ];

        }
    }

    /**
     * 返回统计趋势图明细数据
     */
    public function actionDatadetail()
    {
        $date = Yii::$app->request->post('date', '');
        $dataType = Yii::$app->request->post('dataType', 'inbox');
        $user_name = Yii::$app->request->post('user_name');
        $id = Yii::$app->request->post('account_id');
        $platform_code = Yii::$app->request->post('platform_code');

        if (empty($date)) {
            die(json_encode([
                'code' => 0,
                'message' => '日期不能为空',
            ]));
        }
        //ebay账号信息
        if ($id != 0) {
            $account_id[] = $id;
        } else {
            $user_id = (new Query())->select('id')->from('{{%user}}')->where(['user_name' => $user_name])
                ->createCommand(Yii::$app->db_system)
                ->queryColumn();
            $account_old_id = (new Query())->select('account_ids')->from('{{%orderservice}}')->where(['user_id' => $user_id, 'platform_code' => $platform_code])
                ->createCommand(Yii::$app->db_system)
                ->queryOne();

            if ($account_old_id['account_ids']) {
                $account_old_id = explode(',', $account_old_id['account_ids']);
                $account_id = Account::findAccountId($account_old_id, $platform_code);
            } else {
                $account_id = 0;
            }
        }
        $data = [];
        $range = [];
        if ($date == 1) {
            $range[] = [
                'start_time' => date('Y-m-d 10:00:00', strtotime("-{$date} day")),
                'end_time' => date('Y-m-d 10:00:00'),
            ];
            $mail_range[] = [
                'start_time' => date('Y-m-d 18:00:00', strtotime("-{$date} day")),
                'end_time' => date('Y-m-d 18:00:00'),
            ];
            $data['categories'][] = date('Y-m-d');
        } else {
            $absName = abs($date);
            $preDate = $date - 1;
            $start_time = date('Y-m-d 10:00:00', strtotime("{$preDate} day"));
            $mail_start_time = date('Y-m-d 18:00:00', strtotime("{$preDate} day"));
            for ($i = 0; $i < $absName; $i++) {
                $add = $i + 1;
                $range[$i] = [
                    'start_time' => date('Y-m-d 10:00:00', strtotime("+{$i} day", strtotime($start_time))),
                    'end_time' => date('Y-m-d 10:00:00', strtotime("+{$add} day", strtotime($start_time))),
                ];
                $mail_range[$i] = [
                    'start_time' => date('Y-m-d 18:00:00', strtotime("+{$i} day", strtotime($mail_start_time))),
                    'end_time' => date('Y-m-d 18:00:00', strtotime("+{$add} day", strtotime($mail_start_time))),
                ];
                $data['categories'][] = date('Y-m-d', strtotime("+{$add} day", strtotime($start_time)));
            }
        }
        //待处理时间，
        $ranges_time = date('Y-m-01 00:00:00', strtotime('-2 month'));
        $ranges = [
            'start_time' => $ranges_time,
            'end_time' => date('Y-m-t 23:59:59'),
        ];

        if ($dataType == 'inbox') {
            //所有邮件
            $list = MailStatistics::mailList($platform_code, $account_id, $mail_range);
            //所有未回复邮件
            $not_list = MailStatistics::mailNotList($platform_code, $account_id, $mail_range);
            //所有已回复
            $end_list = MailStatistics::mailEndList($platform_code, $account_id, $mail_range);
            //待回复
            $wait_list = MailStatistics::mailWaitList($platform_code, $account_id, $ranges);
        } else if ($dataType == 'return') {
            if($platform_code == 'EB'){
                //当月退款退货纠纷
                $list = DisputeStatistics::disputeList($platform_code, $account_id, $range, self::TASK_TYPE_RETURN);
                //退款退货未处理
                $not_list = DisputeStatistics::disputeNotList($platform_code, $account_id, $range, self::TASK_TYPE_RETURN);
                //退款退货已处理
                $end_list = DisputeStatistics::disputeEndList($platform_code, $account_id, $range, self::TASK_TYPE_RETURN);
                //退款退货待处理
                $wait_list = DisputeStatistics::disputeWaitDay($platform_code, $account_id, $ranges, self::TASK_TYPE_RETURN);
            }else{
                //当月物流纠纷
                $list = DisputeStatistics::disputeList($platform_code, $account_id, $mail_range, self::TASK_LOGISTICS);
                //物流纠纷未处理
                $not_list = DisputeStatistics::disputeNotList($platform_code, $account_id, $mail_range, self::TASK_LOGISTICS);
                //物流已处理
                $end_list = DisputeStatistics::disputeEndList($platform_code, $account_id, $mail_range, self::TASK_LOGISTICS);
                //物流纠纷待处理
                $wait_list = DisputeStatistics::disputeWaitList($platform_code, $account_id, $ranges, self::TASK_LOGISTICS);
            }

        } else if ($dataType == 'inquiry') {
            if($platform_code == 'EB'){
                //当月所有未收到纠纷数量
                $list = DisputeStatistics::disputeList($platform_code, $account_id, $range, self::TASK_TYPE_INQUIRY);
                //未收到纠纷未处理
                $not_list = DisputeStatistics::disputeNotList($platform_code, $account_id, $range, self::TASK_TYPE_INQUIRY);
                //未收到纠纷已处理
                $end_list = DisputeStatistics::disputeEndList($platform_code, $account_id, $range, self::TASK_TYPE_INQUIRY);
                //未收到纠纷待处理
                $wait_list = DisputeStatistics::disputeWaitList($platform_code, $account_id, $ranges, self::TASK_TYPE_INQUIRY);
            }else{
                //当月所有买家原因数量
                $list = DisputeStatistics::disputeList($platform_code, $account_id, $mail_range, self::TASK_BUYER);
                //买家原因未处理
                $not_list = DisputeStatistics::disputeNotList($platform_code, $account_id, $mail_range, self::TASK_BUYER);
                //买家原因已处理
                $end_list = DisputeStatistics::disputeEndList($platform_code, $account_id, $mail_range, self::TASK_BUYER);
                //买家原因待处理
                $wait_list = DisputeStatistics::disputeWaitList($platform_code, $account_id, $ranges, self::TASK_BUYER);
            }

        } else if ($dataType == 'cancellation') {
            if($platform_code == 'EB'){
                //当月取消交易纠纷
                $list = DisputeStatistics::cancellationList($platform_code, $account_id, $range, self::TASK_TYPE_CANCELLATION);
                //取消交易未处理
                $not_list = DisputeStatistics::cancellationNotList($platform_code, $account_id, $range, self::TASK_TYPE_CANCELLATION);
                //取消交易已处理
                $end_list = DisputeStatistics::cancellationEndList($platform_code, $account_id, $range, self::TASK_TYPE_CANCELLATION);
                //取消交易待处理
                $wait_list = DisputeStatistics::cancellationWaitList($platform_code, $account_id, $ranges, self::TASK_TYPE_CANCELLATION);
            }else{
                //当月质量纠纷
                $list = DisputeStatistics::disputeList($platform_code, $account_id, $mail_range, self::TASK_QUALITY);
                //质量未处理
                $not_list = DisputeStatistics::disputeNotList($platform_code, $account_id, $mail_range, self::TASK_QUALITY);
                //质量已处理
                $end_list = DisputeStatistics::disputeEndList($platform_code, $account_id, $mail_range, self::TASK_QUALITY);
                //质量待处理
                $wait_list = DisputeStatistics::disputeWaitList($platform_code, $account_id, $ranges, self::TASK_QUALITY);
            }

        } else if ($dataType == 'feedback') {
            //当月评价
            $list = FeedbackStatistics::feedbackList($platform_code, $account_id, $mail_range);
            //评价未处理
            $not_list = FeedbackStatistics::feedbackNotList($platform_code, $account_id, $mail_range);
            //评价已处理
            $end_list = FeedbackStatistics::feedbackEndList($platform_code, $account_id, $mail_range);
            //评价待处理
            $wait_list = FeedbackStatistics::feedbackWaitList($platform_code, $account_id, $ranges);
        }
        $data['series'] = [];
        if (!empty($not_list)) {
            $obj = new \stdClass();

            $obj->name = '未处理';
            $obj->data = $not_list;
            $data['series'][] = $obj;
        }
        if (!empty($list)) {
            $obj = new \stdClass();

            $obj->name = '所有';
            $obj->data = $list;
            $data['series'][] = $obj;
        }
        if (!empty($end_list)) {
            $obj = new \stdClass();

            $obj->name = '已处理';
            $obj->data = $end_list;
            $data['series'][] = $obj;
        }

        $obj = new \stdClass();
        $obj->name = '待处理';
        $obj->data = [];
        for ($ix = 0; $ix < abs($date); $ix++) {
            $obj->data[] = $wait_list;
        }
        $data['series'][] = $obj;
        die(json_encode([
            'code' => 1,
            'message' => '成功',
            'data' => $data,
        ]));
    }

    /**
     * 返回统计趋势图数据
     */
    public function actionChart()
    {
        $date = Yii::$app->request->post('date', '');
        $platform_code = Yii::$app->request->post('platform_code','');
        $dataType = Yii::$app->request->post('dataType', 'inbox');
        $countCostum = Yii::$app->request->post('countCostum'); //客服
        if (empty($date)) {
            die(json_encode([
                'code' => 0,
                'message' => '日期不能为空',
            ]));
        }
        $data = [];
        $accountIds = [];
        $range = [];
        if ($date == 1) {
            $range[] = [
                'start_time' => date('Y-m-d 10:00:00', strtotime("-{$date} day")),
                'end_time' => date('Y-m-d 10:00:00'),
            ];
            $mail_range[] = [
                'start_time' => date('Y-m-d 18:00:00', strtotime("-{$date} day")),
                'end_time' => date('Y-m-d 18:00:00'),
            ];
            $data['categories'][] = date('Y-m-d');
        } else {
            $absName = abs($date);
            $preDate = $date - 1;
            $start_time = date('Y-m-d 10:00:00', strtotime("{$preDate} day"));
            $mail_start_time = date('Y-m-d 18:00:00', strtotime("{$preDate} day"));
            for ($i = 0; $i < $absName; $i++) {
                $add = $i + 1;
                $range[$i] = [
                    'start_time' => date('Y-m-d 10:00:00', strtotime("+{$i} day", strtotime($start_time))),
                    'end_time' => date('Y-m-d 10:00:00', strtotime("+{$add} day", strtotime($start_time))),
                ];
                $mail_range[$i] = [
                    'start_time' => date('Y-m-d 18:00:00', strtotime("+{$i} day", strtotime($mail_start_time))),
                    'end_time' => date('Y-m-d 18:00:00', strtotime("+{$add} day", strtotime($mail_start_time))),
                ];
                $data['categories'][] = date('Y-m-d', strtotime("+{$add} day", strtotime($start_time)));
            }
        }

        $user_id = Yii::$app->user->identity->id;
        $role_id = UserRole::find()->select('role_id')->where(['user_id' => $user_id])->column();

        $roleIds = [];
        if (in_array(1, $role_id)) {
            $roleIds = $this->getAllRoleIds($platform_code);
            $user_id = UserRole::find()->select('user_id')->where(['in','role_id',$roleIds])->asArray()->column();
            $userList = User::find()
                ->select(['id', 'login_name'])
                ->where(['in', 'id', $user_id])
                ->andWhere(['status' => 1])
                ->andWhere(['<>', 'role_id', 1])
                ->asArray()
                ->all();
        } else {
            $this->getChildRoleIds($role_id[0], $roleIds, $platform_code);
            $user_id = UserRole::find()->select('user_id')->where(['in','role_id',$roleIds])->column();
            $userList = User::find()
                ->select(['id', 'login_name'])
                ->where(['in', 'id', $user_id])
                ->andWhere(['status' => 1])
                ->asArray()
                ->all();
        }
        $user_list = [];

        //只筛选有账号的客服
        foreach($userList as $k=>$value){
            $user_account = UserAccount::findOne(['user_id' => $value['id'],'platform_code' => $platform_code]);
            if(!empty($user_account)){
                $user_list[] = $value;
            }
        }

        $userList = array_column($user_list, 'login_name', 'login_name');

        //待处理时间，
        $ranges_time = date('Y-m-01 00:00:00', strtotime('-2 month'));
        $ranges = [
            'start_time' => $ranges_time,
            'end_time' => date('Y-m-t 23:59:59'),
        ];
        if (!empty($countCostum)) {
            foreach ($countCostum as $user_name) {
                $user_id = (new Query())->select('id')->from('{{%user}}')->where(['user_name' => $user_name])
                    ->createCommand(Yii::$app->db_system)
                    ->queryColumn();
                $account_old_id = (new Query())->select('account_ids')->from('{{%orderservice}}')->where(['user_id' => $user_id, 'platform_code' => $platform_code])
                    ->createCommand(Yii::$app->db_system)
                    ->queryOne();
                if ($account_old_id['account_ids']) {
                    $account_old_id = explode(',', $account_old_id['account_ids']);
                    $account_id = Account::findAccountId($account_old_id, $platform_code);
                    $accountIds[$user_name] = $account_id;
                } else {
                    $accountIds[$user_name] = 0;
                }
            }
        } else {
            //只能查询到客服绑定账号的回复
            $accountIds['all'] = UserAccount::getCurrentUserPlatformAccountIds($platform_code);
        }

        $list = [];
        $not_list = [];
        $end_list = [];
        $wait_list = [];
        foreach ($accountIds as $k => $account) {
            if ($dataType == 'all') {
                $mail_list[$k] = MailStatistics::mailList($platform_code, $account, $mail_range);
                //所有未回复邮件
                $mail_not_list[$k] = MailStatistics::mailNotList($platform_code, $account, $mail_range);
                //所有已回复
                $mail_end_list[$k] = MailStatistics::mailEndList($platform_code, $account, $mail_range);
                //待回复
                $mail_wait_list[$k] = MailStatistics::mailWaitList($platform_code, $account, $ranges);
                if($platform_code == 'EB'){
                    //当月退款退货纠纷
                    $return_list[$k] = DisputeStatistics::disputeList($platform_code, $account, $range, self::TASK_TYPE_RETURN);
                    //退款退货未处理
                    $return_not_list[$k] = DisputeStatistics::disputeNotList($platform_code, $account, $range, self::TASK_TYPE_RETURN);
                    //退款退货已处理
                    $return_end_list[$k] = DisputeStatistics::disputeEndList($platform_code, $account, $range, self::TASK_TYPE_RETURN);
                    //退款退货待处理
                    $return_wait_list[$k] = DisputeStatistics::disputeWaitDay($platform_code, $account, $ranges, self::TASK_TYPE_RETURN);
                    //当月退款退货纠纷
                    $inquiry_list[$k] = DisputeStatistics::disputeList($platform_code, $account, $range, self::TASK_TYPE_RETURN);
                    //退款退货未处理
                    $inquiry_not_list[$k] = DisputeStatistics::disputeNotList($platform_code, $account, $range, self::TASK_TYPE_RETURN);
                    //退款退货已处理
                    $inquiry_end_list[$k] = DisputeStatistics::disputeEndList($platform_code, $account, $range, self::TASK_TYPE_RETURN);
                    //退款退货待处理
                    $inquiry_wait_list[$k] = DisputeStatistics::disputeWaitList($platform_code, $account, $ranges, self::TASK_TYPE_RETURN);
                    //当月取消交易纠纷
                    $cancellation_list[$k] = DisputeStatistics::cancellationList($platform_code, $account, $range, self::TASK_TYPE_CANCELLATION);
                    //取消交易未处理
                    $cancellation_not_list[$k] = DisputeStatistics::cancellationNotList($platform_code, $account, $range, self::TASK_TYPE_CANCELLATION);
                    //取消交易已处理
                    $cancellation_end_list[$k] = DisputeStatistics::cancellationEndList($platform_code, $account, $range, self::TASK_TYPE_CANCELLATION);
                    //取消交易待处理
                    $cancellation_wait_list[$k] = DisputeStatistics::cancellationWaitList($platform_code, $account, $ranges, self::TASK_TYPE_CANCELLATION);
                }else{
                    //当月物流纠纷
                    $return_list[$k] = DisputeStatistics::disputeList($platform_code, $account, $mail_range, self::TASK_LOGISTICS);
                    //物流纠纷未处理
                    $return_not_list[$k] = DisputeStatistics::disputeNotList($platform_code, $account, $mail_range, self::TASK_LOGISTICS);
                    //物流纠纷已处理
                    $return_end_list[$k] = DisputeStatistics::disputeEndList($platform_code, $account, $mail_range, self::TASK_LOGISTICS);
                    //物流纠纷待处理
                    $return_wait_list[$k] = DisputeStatistics::disputeWaitList($platform_code, $account, $ranges, self::TASK_LOGISTICS);
                    //当月买家问题纠纷
                    $inquiry_list[$k] = DisputeStatistics::disputeList($platform_code, $account, $mail_range, self::TASK_BUYER);
                    //买家问题纠纷未处理
                    $inquiry_not_list[$k] = DisputeStatistics::disputeNotList($platform_code, $account, $mail_range, self::TASK_BUYER);
                    //买家问题纠纷已处理
                    $inquiry_end_list[$k] = DisputeStatistics::disputeEndList($platform_code, $account, $mail_range, self::TASK_BUYER);
                    //买家问题纠纷待处理
                    $inquiry_wait_list[$k] = DisputeStatistics::disputeWaitList($platform_code, $account, $ranges, self::TASK_BUYER);
                    //当月质量纠纷
                    $cancellation_list[$k] = DisputeStatistics::disputeList($platform_code, $account, $mail_range, self::TASK_QUALITY);
                    //质量纠纷未处理
                    $cancellation_not_list[$k] = DisputeStatistics::disputeNotList($platform_code, $account, $mail_range, self::TASK_QUALITY);
                    //质量纠纷已处理
                    $cancellation_end_list[$k] = DisputeStatistics::disputeEndList($platform_code, $account, $mail_range, self::TASK_QUALITY);
                    //质量纠纷待处理
                    $cancellation_wait_list[$k] = DisputeStatistics::disputeWaitList($platform_code, $account, $ranges, self::TASK_QUALITY);
                }
                //当月评价
                $feedback_list[$k] = FeedbackStatistics::feedbackList($platform_code, $account, $mail_range);
                //评价未处理
                $feedback_not_list[$k] = FeedbackStatistics::feedbackNotList($platform_code, $account, $mail_range);
                //评价已处理
                $feedback_end_list[$k] = FeedbackStatistics::feedbackEndList($platform_code, $account, $mail_range);
                //评价待处理
                $feedback_wait_list[$k] = FeedbackStatistics::feedbackWaitList($platform_code, $account, $ranges);
                $list[$k] = $mail_list[$k] + $return_list[$k] + $inquiry_list[$k] + $cancellation_list[$k] + $feedback_list[$k];
                $not_list[$k] = $mail_not_list[$k] + $return_not_list[$k] + $inquiry_not_list[$k] + $cancellation_not_list[$k] + $feedback_not_list[$k];
                $end_list[$k] = $mail_end_list[$k] + $return_end_list[$k] + $inquiry_end_list[$k] + $cancellation_end_list[$k] + $feedback_end_list[$k];
                $wait_list[$k] = $mail_wait_list[$k] + $return_wait_list[$k] + $inquiry_wait_list[$k] + $cancellation_wait_list[$k] + $feedback_wait_list[$k];
            } else if ($dataType == 'inbox') {
                //所有邮件
                $list[$k] = MailStatistics::mailList($platform_code, $account, $mail_range);
                //所有未回复邮件
                $not_list[$k] = MailStatistics::mailNotList($platform_code, $account, $mail_range);
                //所有已回复
                $end_list[$k] = MailStatistics::mailEndList($platform_code, $account, $mail_range);
                //待回复
                $wait_list[$k] = MailStatistics::mailWaitList($platform_code, $account, $ranges);
            } else if ($dataType == 'return') {
                if($platform_code == 'EB'){
                    //当月退款退货纠纷
                    $list[$k] = DisputeStatistics::disputeList($platform_code, $account, $range, self::TASK_TYPE_RETURN);
                    //退款退货未处理
                    $not_list[$k] = DisputeStatistics::disputeNotList($platform_code, $account, $range, self::TASK_TYPE_RETURN);
                    //退款退货已处理
                    $end_list[$k] = DisputeStatistics::disputeEndList($platform_code, $account, $range, self::TASK_TYPE_RETURN);
                    //退款退货待处理
                    $wait_list[$k] = DisputeStatistics::disputeWaitList($platform_code, $account, $ranges, self::TASK_TYPE_RETURN);
                }else{
                    //当月物流纠纷
                    $list[$k] = DisputeStatistics::disputeList($platform_code, $account, $mail_range, self::TASK_LOGISTICS);
                    //物流纠纷未处理
                    $not_list[$k] = DisputeStatistics::disputeNotList($platform_code, $account, $mail_range, self::TASK_LOGISTICS);
                    //物流纠纷已处理
                    $end_list[$k] = DisputeStatistics::disputeEndList($platform_code, $account, $mail_range, self::TASK_LOGISTICS);
                    //物流纠纷待处理
                    $wait_list[$k] = DisputeStatistics::disputeWaitList($platform_code, $account, $ranges, self::TASK_LOGISTICS);
                }

            } else if ($dataType == 'inquiry') {
                if($platform_code == 'EB'){
                    //当月所有未收到纠纷数量
                    $list[$k] = DisputeStatistics::disputeList($platform_code, $account, $range, self::TASK_TYPE_INQUIRY);
                    //未收到纠纷未处理
                    $not_list[$k] = DisputeStatistics::disputeNotList($platform_code, $account, $range, self::TASK_TYPE_INQUIRY);
                    //未收到纠纷已处理
                    $end_list[$k] = DisputeStatistics::disputeEndList($platform_code, $account, $range, self::TASK_TYPE_INQUIRY);
                    //未收到纠纷待处理
                    $wait_list[$k] = DisputeStatistics::disputeWaitList($platform_code, $account, $ranges, self::TASK_TYPE_INQUIRY);
                }else{
                    //当月所有买家原因纠纷数量
                    $list[$k] = DisputeStatistics::disputeList($platform_code, $account, $mail_range, self::TASK_BUYER);
                    //买家原因纠纷未处理
                    $not_list[$k] = DisputeStatistics::disputeNotList($platform_code, $account, $mail_range, self::TASK_BUYER);
                    //买家原因纠纷已处理
                    $end_list[$k] = DisputeStatistics::disputeEndList($platform_code, $account, $mail_range, self::TASK_BUYER);
                    //买家原因纠纷待处理
                    $wait_list[$k] = DisputeStatistics::disputeWaitList($platform_code, $account, $ranges, self::TASK_BUYER);
                }

            } else if ($dataType == 'cancellation') {
                if($platform_code == 'EB'){
                    //当月取消交易纠纷
                    $list[$k] = DisputeStatistics::cancellationList($platform_code, $account, $range, self::TASK_TYPE_CANCELLATION);
                    //取消交易未处理
                    $not_list[$k] = DisputeStatistics::cancellationNotList($platform_code, $account, $range, self::TASK_TYPE_CANCELLATION);
                    //取消交易已处理
                    $end_list[$k] = DisputeStatistics::cancellationEndList($platform_code, $account, $range, self::TASK_TYPE_CANCELLATION);
                    //取消交易待处理
                    $wait_list[$k] = DisputeStatistics::cancellationWaitList($platform_code, $account, $ranges, self::TASK_TYPE_CANCELLATION);
                }else{
                    //当月所有质量纠纷数量
                    $list[$k] = DisputeStatistics::disputeList($platform_code, $account, $mail_range, self::TASK_QUALITY);
                    //质量纠纷未处理
                    $not_list[$k] = DisputeStatistics::disputeNotList($platform_code, $account, $mail_range, self::TASK_QUALITY);
                    //质量纠纷已处理
                    $end_list[$k] = DisputeStatistics::disputeEndList($platform_code, $account, $mail_range, self::TASK_QUALITY);
                    //质量纠纷待处理
                    $wait_list[$k] = DisputeStatistics::disputeWaitList($platform_code, $account, $ranges, self::TASK_QUALITY);
                }

            }
            if ($dataType == 'feedback') {
                //当月评价
                $list[$k] = FeedbackStatistics::feedbackList($platform_code, $account, $mail_range);
                //评价未处理
                $not_list[$k] = FeedbackStatistics::feedbackNotList($platform_code, $account, $mail_range);
                //评价已处理
                $end_list[$k] = FeedbackStatistics::feedbackEndList($platform_code, $account, $mail_range);
                //评价待处理
                $wait_list[$k] = FeedbackStatistics::feedbackWaitList($platform_code, $account, $ranges);
            }
        }

        $data['series'] = [];
        if (!empty($countCostum)) {
            if (count($countCostum) > 1) {
                foreach ($countCostum as $user_name) {
                    if (!empty($list)) {
                        $obj = new \stdClass();
                        $obj->name = $user_name . '所有';
                        $obj->data = $list[$user_name];
                        $data['series'][] = $obj;
                    }
                }
            } else {
                foreach ($countCostum as $user_name) {
                    if (!empty($not_list)) {
                        $obj = new \stdClass();

                        $obj->name = $user_name . '未处理';
                        $obj->data = $not_list[$user_name];
                        $data['series'][] = $obj;
                    }
                    if (!empty($list)) {
                        $obj = new \stdClass();

                        $obj->name = $user_name . '所有';
                        $obj->data = $list[$user_name];
                        $data['series'][] = $obj;
                    }
                    if (!empty($end_list)) {
                        $obj = new \stdClass();

                        $obj->name = $user_name . '已处理';
                        $obj->data = $end_list[$user_name];
                        $data['series'][] = $obj;
                    }
                    if (!empty($wait_list)) {
                        $obj = new \stdClass();

                        $obj->name = $user_name . '待处理';
                        $obj->data = [];
                        for ($ix = 0; $ix < abs($date); $ix++) {
                            $obj->data[] = $wait_list[$user_name];
                        }
                        $data['series'][] = $obj;
                    }
                }
            }
        } else {
            if (!empty($not_list)) {
                $obj = new \stdClass();

                $obj->name = '未处理';
                $obj->data = $not_list['all'];
                $data['series'][] = $obj;
            }
            if (!empty($list)) {
                $obj = new \stdClass();

                $obj->name = '所有';
                $obj->data = $list['all'];
                $data['series'][] = $obj;
            }
            if (!empty($end_list)) {
                $obj = new \stdClass();

                $obj->name = '已处理';
                $obj->data = $end_list['all'];
                $data['series'][] = $obj;
            }
            if (!empty($wait_list)) {
                $obj = new \stdClass();

                $obj->name = '待处理';
                $obj->data = [];
                for ($ix = 0; $ix < abs($date); $ix++) {
                    $obj->data[] = $wait_list['all'];
                }
                $data['series'][] = $obj;
            }
        }

        die(json_encode([
            'code' => 1,
            'message' => '成功',
            'data' => $data,
            'userList' => $userList,
        ]));
    }

    /**
     * @action
     */
    public function actionMonths()
    {
        if (Yii::$app->request->isAjax) {
            $action = $this->request->post('action');
            $months = $this->request->post('months');
            $id = $this->request->post('id');
            $user_name = $this->request->post('user_name');
            $platform_code = $this->request->post('platform_code');

            //ebay账号信息
            if ($id != 0) {
                $account_id[] = $id;
            } else {
                $user_id = (new Query())->select('id')->from('{{%user}}')->where(['user_name' => $user_name])
                    ->createCommand(Yii::$app->db_system)
                    ->queryColumn();
                $account_old_id = (new Query())->select('account_ids')->from('{{%orderservice}}')->where(['user_id' => $user_id, 'platform_code' => $platform_code])
                    ->createCommand(Yii::$app->db_system)
                    ->queryOne();

                if ($account_old_id['account_ids']) {
                    $account_old_id = explode(',', $account_old_id['account_ids']);
                }
                $account_id = Account::findAccountId($account_old_id, $platform_code);
            }

            if ($action == 'up') {
                $upMonthSec = strtotime('-1 month', strtotime($months));
                $date = date('Y-m-d', $upMonthSec);
                $datenow = date('Y-m', $upMonthSec);
                $upMonthDays = date('t', $upMonthSec);
                $upMonthFirsDay = date('Y-m-01 00:00:00', $upMonthSec);

                for ($i = 0; $i < $upMonthDays; $i++) {

                    $start_time = date('Y-m-d 10:00:00', strtotime('-1 day', strtotime("+{$i} day", strtotime($upMonthFirsDay))));
                    $mail_start_time = date('Y-m-d 18:00:00', strtotime('-1 day', strtotime("+{$i} day", strtotime($upMonthFirsDay))));
                    $range[$i + 1] = [
                        'start_time' => $start_time,
                        'end_time' => date('Y-m-d 18:00:00', strtotime('+1 day', strtotime($start_time))),
                    ];
                    $mail_range[$i + 1] = [
                        'start_time' => $mail_start_time,
                        'end_time' => date('Y-m-d 18:00:00', strtotime('+1 day', strtotime($mail_start_time))),
                    ];
                }

            } else {
                $upMonthSec = strtotime('+1 month', strtotime($months));
                $date = date('Y-m-d', $upMonthSec);
                $datenow = date('Y-m', $upMonthSec);
                $upMonthDays = date('t', $upMonthSec);
                $upMonthFirsDay = date('Y-m-01 00:00:00', $upMonthSec);

                for ($i = 0; $i < $upMonthDays; $i++) {

                    $start_time = date('Y-m-d 10:00:00', strtotime('-1 day', strtotime("+{$i} day", strtotime($upMonthFirsDay))));
                    $mail_start_time = date('Y-m-d 18:00:00', strtotime('-1 day', strtotime("+{$i} day", strtotime($upMonthFirsDay))));
                    $range[$i + 1] = [
                        'start_time' => $start_time,
                        'end_time' => date('Y-m-d 10:00:00', strtotime('+1 day', strtotime($start_time))),
                    ];
                    $mail_range[$i + 1] = [
                        'start_time' => $mail_start_time,
                        'end_time' => date('Y-m-d 18:00:00', strtotime('+1 day', strtotime($mail_start_time))),
                    ];

                }

            }


            //取出当月每天所有未回复邮件
            $mail_not_list = MailStatistics::mailNotList($platform_code, $account_id, $mail_range);
            //所有邮件
            $range_reverse = array_reverse($mail_range, true);
            $range_reve = [];
            foreach ($range_reverse as $k => $value) {
                $range_reve["{$k}"] = $value;
            }
            $mail_list = MailStatistics::mailList($platform_code, $account_id, $range_reve);

            //所有已回复
            $mail_end_list = MailStatistics::mailEndList($platform_code, $account_id, $mail_range);

            $mail_not_percent = [];
            foreach ($mail_list as $k => $v) {
                if ($mail_list[$k] > 0 && $mail_not_list[$k] > 0) {
                    $mail_not_percent[$k] = round($mail_not_list[$k] / $mail_list[$k], 4) * 100 . "%";
                } else {
                    $mail_not_percent[$k] = 0;
                }
            }
            //当月所有未回复邮件
            /*  $data = '';
              foreach($range as $item){
                  $data .= "(create_time between '{$item['start_time']}' and '{$item['end_time']}') or ";
              }
              $data = rtrim($data, 'or ');
              $mail_all = MailStatistics:: mailAll($platform_code,$account_id,$data);*/

            if($platform_code == 'EB'){
                //当月所有未收到纠纷数量
                $inqurry_list = DisputeStatistics::disputeList($platform_code, $account_id, $range, self::TASK_TYPE_INQUIRY);
                //未收到纠纷未处理
                $inqurry_not_list = DisputeStatistics::disputeNotList($platform_code, $account_id, $range, self::TASK_TYPE_INQUIRY);
                //未收到纠纷已处理
                $inqurry_end_list = DisputeStatistics::disputeEndList($platform_code, $account_id, $range, self::TASK_TYPE_INQUIRY);

                $inqurry_not_percent = [];
                foreach ($inqurry_list as $k => $v) {
                    if ($inqurry_list[$k] > 0 && $inqurry_not_list[$k] > 0) {
                        $inqurry_not_percent[$k] = round($inqurry_not_list[$k] / $inqurry_list[$k], 4) * 100 . "%";
                    } else {
                        $inqurry_not_percent[$k] = 0;
                    }
                }
                //当月退款退货纠纷
                $return_list = DisputeStatistics::disputeList($platform_code, $account_id, $range, self::TASK_TYPE_RETURN);
                //退款退货未处理
                $return_not_list = DisputeStatistics::disputeNotList($platform_code, $account_id, $range, self::TASK_TYPE_RETURN);
                //退款退货已处理
                $return_end_list = DisputeStatistics::disputeEndList($platform_code, $account_id, $range, self::TASK_TYPE_RETURN);

                $return_not_percent = [];
                foreach ($return_list as $k => $v) {
                    if ($return_list[$k] > 0 && $return_not_list[$k] > 0) {
                        $return_not_percent[$k] = round($return_not_list[$k] / $return_list[$k], 4) * 100 . "%";
                    } else {
                        $return_not_percent[$k] = 0;
                    }
                }
                //当月取消交易纠纷
                $cancellation_list = DisputeStatistics::cancellationList($platform_code, $account_id, $range, self::TASK_TYPE_CANCELLATION);
                //取消交易未处理
                $cancellation_not_list = DisputeStatistics::cancellationNotList($platform_code, $account_id, $range, self::TASK_TYPE_CANCELLATION);
                //取消交易已处理
                $cancellation_end_list = DisputeStatistics::cancellationEndList($platform_code, $account_id, $range, self::TASK_TYPE_CANCELLATION);

                $cancellation_not_percent = [];
                foreach ($cancellation_list as $k => $v) {
                    if ($cancellation_list[$k] > 0 && $cancellation_not_list[$k] > 0) {
                        $cancellation_not_percent[$k] = round($cancellation_not_list[$k] / $cancellation_list[$k], 4) * 100 . "%";
                    } else {
                        $cancellation_not_percent[$k] = 0;
                    }
                }
            }else{
                //当月所有物流纠纷数量
                $inqurry_list = DisputeStatistics::disputeList($platform_code, $account_id, $mail_range, self::TASK_LOGISTICS);
                //物流纠纷未处理
                $inqurry_not_list = DisputeStatistics::disputeNotList($platform_code, $account_id, $mail_range, self::TASK_LOGISTICS);
                //物流纠纷已处理
                $inqurry_end_list = DisputeStatistics::disputeEndList($platform_code, $account_id, $mail_range, self::TASK_LOGISTICS);

                $inqurry_not_percent = [];
                foreach ($inqurry_list as $k => $v) {
                    if ($inqurry_list[$k] > 0 && $inqurry_not_list[$k] > 0) {
                        $inqurry_not_percent[$k] = round($inqurry_not_list[$k] / $inqurry_list[$k], 4) * 100 . "%";
                    } else {
                        $inqurry_not_percent[$k] = 0;
                    }
                }
                //当月买家原因纠纷
                $return_list = DisputeStatistics::disputeList($platform_code, $account_id, $mail_range, self::TASK_BUYER);
                //买家原因未处理
                $return_not_list = DisputeStatistics::disputeNotList($platform_code, $account_id, $mail_range, self::TASK_BUYER);
                //买家原因已处理
                $return_end_list = DisputeStatistics::disputeEndList($platform_code, $account_id, $mail_range, self::TASK_BUYER);

                $return_not_percent = [];
                foreach ($return_list as $k => $v) {
                    if ($return_list[$k] > 0 && $return_not_list[$k] > 0) {
                        $return_not_percent[$k] = round($return_not_list[$k] / $return_list[$k], 4) * 100 . "%";
                    } else {
                        $return_not_percent[$k] = 0;
                    }
                }
                //当月质量纠纷
                $cancellation_list = DisputeStatistics::disputeList($platform_code, $account_id, $mail_range, self::TASK_QUALITY);
                //质量纠纷未处理
                $cancellation_not_list = DisputeStatistics::disputeNotList($platform_code, $account_id, $mail_range, self::TASK_QUALITY);
                //质量纠纷已处理
                $cancellation_end_list = DisputeStatistics::disputeEndList($platform_code, $account_id, $mail_range, self::TASK_QUALITY);

                $cancellation_not_percent = [];
                foreach ($cancellation_list as $k => $v) {
                    if ($cancellation_list[$k] > 0 && $cancellation_not_list[$k] > 0) {
                        $cancellation_not_percent[$k] = round($cancellation_not_list[$k] / $cancellation_list[$k], 4) * 100 . "%";
                    } else {
                        $cancellation_not_percent[$k] = 0;
                    }
                }
            }

            //当月评价
            $feedback_list = FeedbackStatistics::feedbackList($platform_code, $account_id, $mail_range);
            //评价未处理
            $feedback_not_list = FeedbackStatistics::feedbackNotList($platform_code, $account_id, $mail_range);
            //评价已处理
            $feedback_end_list = FeedbackStatistics::feedbackEndList($platform_code, $account_id, $mail_range);

            $feedback_not_percent = [];
            foreach ($feedback_list as $k => $v) {
                if ($feedback_list[$k] > 0 && $feedback_not_list[$k] > 0) {
                    $feedback_not_percent[$k] = round($feedback_not_list[$k] / $feedback_list[$k], 4) * 100 . "%";
                } else {
                    $feedback_not_percent[$k] = 0;
                }
            }
            $total = [];
            $completion = [];
            $completion_rate = [];
            foreach($mail_list as $k=>$v){
                //总计
                $total[$k] = $mail_list[$k] + $inqurry_list[$k] + $return_list[$k] + $cancellation_list[$k] + $feedback_list[$k];
                //完成率
                $completion[$k] = $mail_end_list[$k] + $inqurry_end_list[$k] + $return_end_list[$k] + $cancellation_end_list[$k] + $feedback_end_list[$k];
                if($total[$k] >0 && $completion[$k]){
                    $completion_rate[$k] = round($completion[$k] / $total[$k], 4) * 100 . "%";
                }else{
                    $completion_rate[$k] = 0;
                }
            }
            $data = ['mail_not_list' => $mail_not_list, 'mail_list' => $mail_list, 'mail_end_list' => $mail_end_list, 'mail_not_percent' => $mail_not_percent,'total'=>$total,'completion_rate'=>$completion_rate,
                'inqurry_list' => $inqurry_list, 'inqurry_not_list' => $inqurry_not_list, 'inqurry_end_list' => $inqurry_end_list, 'inqurry_not_percent' => $inqurry_not_percent,
                'return_list' => $return_list, 'return_not_list' => $return_not_list, 'return_end_list' => $return_end_list, 'return_not_percent' => $return_not_percent,
                'cancellation_list' => $cancellation_list, 'cancellation_not_list' => $cancellation_not_list, 'cancellation_end_list' => $cancellation_end_list, 'cancellation_not_percent' => $cancellation_not_percent,
                'feedback_list' => $feedback_list, 'feedback_not_list' => $feedback_not_list, 'feedback_end_list' => $feedback_end_list, 'feedback_not_percent' => $feedback_not_percent];
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'form_data' => $data,
                'code' => 200,
                'datenow' => $datenow,
                'dateday' => $date,
            ];

        }
    }

}

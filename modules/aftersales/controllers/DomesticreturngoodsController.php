<?php

namespace app\modules\aftersales\controllers;

use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use Yii;
use yii\helpers\Json;
use yii\data\Pagination;
use app\components\Controller;
use app\modules\aftersales\models\Domesticreturngoods;
use app\modules\orders\models\OrderDetail;
use app\modules\orders\models\OrderKefu;
use app\modules\aftersales\models\OrdersAliexpress;
use app\modules\systems\models\ErpOrderApi;
use app\modules\systems\models\RefundAccount;
use app\modules\systems\models\BasicConfig;
use app\modules\orders\models\Warehouse;
use app\modules\accounts\models\UserAccount;
use app\common\VHelper;

/**
 * RefundreturnreasonController implements the CRUD actions for RefundReturnReason model.
 */
class DomesticreturngoodsController extends Controller {

    public static $returntype = [
        '1' => '安检',
        '2' => '尺寸超长、体积超重',
        '3' => '偏远',
        '4' => '地址问题',
        '5' => '跟踪号失败',
        '6' => '退货重号',
        '7' => '派送不成功'
    ];
    public static $statestring = [
        '1' => '未处理',
        '2' => '无需处理',
        '3' => '已处理',
        '4' => '驳回EPR',
        '5' => '暂不处理'
    ];
    public static $handle = [
        '1' => '修改信息',
        '2' => '拆单',
        '3' => '退款',
        '4' => '重寄',
        '5' => '补款'
    ];

    /**
     * 国内退件列表
     * @return string
     */
    public function actionOrderslist() {
        $platformcode = isset($_REQUEST['platform_code']) ? $_REQUEST['platform_code'] : ''; //平台
        $trackno = isset($_REQUEST['trackno']) ? $_REQUEST['trackno'] : null; //跟踪号
        $order_id = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : null; //对应订单号
        $buyer_id = isset($_REQUEST['buyer_id']) ? trim($_REQUEST['buyer_id']) : null; //买家账号
        $account_id = isset($_REQUEST['account_id']) ? trim($_REQUEST['account_id']) : null; //卖家账号
        $return_type_post = isset($_REQUEST['return_type']) ? trim($_REQUEST['return_type']) : null; //退货类型大类
        $source = isset($_REQUEST['source']) ? trim($_REQUEST['source']) : null; //退货来源
        if ($return_type_post != null && $return_type_post < 7) {
            $return_typesmall = $return_type_post;
            $return_type = 1;
        } else if ($return_type_post == 7) {
            $return_type = 2;
            $return_typesmall = 1;
        }
        $state = isset($_REQUEST['state']) ? trim($_REQUEST['state']) : 1; //处理状态,默认查询未处理
        $start_date = isset($_REQUEST['start_date']) ? trim($_REQUEST['start_date']) : null; //处理时间
        $end_date = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : null; //处理时间
        $handle_type = isset($_REQUEST['handle_type']) ? trim($_REQUEST['handle_type']) : null; //处理类型
        $return_number = isset($_REQUEST['return_number']) ? trim($_REQUEST['return_number']) : null; //退件单号

        $pageSize = isset($_REQUEST['pageSize']) ? trim($_REQUEST['pageSize']) : 10; //分页大小
        $pageCur = isset($_REQUEST['pageCur']) ? trim($_REQUEST['pageCur']) : null; //当前页
        //获取平台列表
        $platformList = UserAccount::getLoginUserPlatformAccounts();

        if (!empty($platformcode)) {
            //如果用户选择平台，则使用用户选择的平台
            $platform_code = $platformcode;
        } else {
            //否则默认，给用户选择一个平台显示
            if (!empty($platformList)) {
                //如果平台列表中包含EB平台，则默认选择ebay平台
                if (isset($platformList[Platform::PLATFORM_CODE_EB])) {
                    $platform_code = Platform::PLATFORM_CODE_EB;
                } else {
                    //否则，默认选取第一个平台
                    foreach ($platformList as $key => $platform) {
                        if (!empty($key) && $key != ' ') {
                            $platform_code = $key;
                            break;
                        }
                    }
                }
            }
        }

        $parameter = ['platform_code' => $platform_code,
            'trackno' => $trackno,
            'order_id' => $order_id,
            'buyer_id' => $buyer_id,
            'account_id' => $account_id,
            'return_type' => $return_type,
            'return_typesmall' => $return_typesmall,
            'state' => $state,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'handle_type' => $handle_type,
            'return_number' => $return_number,
            'source' => $source,
            'pageCur' => $pageCur,
            'pageSize' => $pageSize];

        $result = Domesticreturngoods::getReceiptList($parameter);
        //创建分页组件
        $page = new Pagination([
            //总的记录条数
            'totalCount' => $result['count'],
            //分页大小
            'pageSize' => $pageSize,
            //设置地址栏当前页数参数名
            'pageParam' => 'pageCur',
            //设置地址栏分页大小参数名
            'pageSizeParam' => 'pageSize',
        ]);

        $ImportPeople_list = Account::getIdNameKVList($platform_code);
        ksort($ImportPeople_list);
        //查询所有的账号简称对
        $store_name = Account::getOldIdShortNamePairs($platform_code);
        //查询所有的仓库
        $warehouseList = Warehouse::getAllWarehouseList(true);

        return $this->render('receiptlist', [
                    'receipts' => $result['data_list'],
                    'page' => $page,
                    'count' => $result['count'],
                    'trackno' => $trackno,
                    'buyer_id' => $buyer_id,
                    'return_type' => $return_type_post,
                    'state' => $state,
                    'return_number' => $return_number,
                    'account_id' => $account_id,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'warehouseList' => $warehouseList,
                    'handle_type' => $handle_type,
                    'returntype' => self::$returntype,
                    'statestring' => self::$statestring,
                    'handle' => self::$handle,
                    'order_id' => $order_id,
                    'ImportPeople_list' => $ImportPeople_list,
                    'platform_code' => $platform_code,
                    'platformList' => $platformList,
                    'source' => $source,
                    'store_name' => $store_name,
        ]);
    }

    /**
     * 退货单下载
     */
    public function actionDownloadreceipt() {
        $this->isPopup = true;
        $platform_code = isset($_REQUEST['platform_code']) ? $_REQUEST['platform_code'] : null; //平台
        $trackno = isset($_REQUEST['trackno']) ? $_REQUEST['trackno'] : null; //跟踪号
        $order_id = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : null; //对应订单号
        $buyer_id = isset($_REQUEST['buyer_id']) ? trim($_REQUEST['buyer_id']) : null; //买家账号
        $return_type_post = isset($_REQUEST['return_type']) ? trim($_REQUEST['return_type']) : null; //退货类型大类
        $source = isset($_REQUEST['source']) ? trim($_REQUEST['source']) : null; //退货来源
        $account_id = isset($_REQUEST['account_id']) ? trim($_REQUEST['account_id']) : null; //账号id
        if ($return_type_post != null && $return_type_post < 7) {
            $return_typesmall = $return_type_post;
            $return_type = 1;
        } else if ($return_type_post == 7) {
            $return_type = 2;
            $return_typesmall = 1;
        }
        $state = isset($_REQUEST['state']) ? trim($_REQUEST['state']) : null; //处理状态
        $start_date = isset($_REQUEST['start_date']) ? trim($_REQUEST['start_date']) : null; //处理时间
        $end_date = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : null; //处理时间
        $handle_type = isset($_REQUEST['handle_type']) ? trim($_REQUEST['handle_type']) : null; //处理类型
        $return_number = isset($_REQUEST['return_number']) ? trim($_REQUEST['return_number']) : null; //退件单号

        $pageSize = isset($_REQUEST['pageSize']) ? trim($_REQUEST['pageSize']) : 10; //分页大小
        $pageCur = isset($_REQUEST['pageCur']) ? trim($_REQUEST['pageCur']) : null; //当前页
        $excel = isset($_REQUEST['excel']) ? trim($_REQUEST['excel']) : null; //下载excel

        $data = Domesticreturngoods::getDownloadList($platform_code, $account_id, $trackno, $order_id, $buyer_id, $return_type, $return_typesmall, $state, $start_date, $end_date, $handle_type, $return_number, $source, $pageCur, 4000);

        if ($data['count'] > 4000) {
            //暂时不限制
        }
        //标题数组
        $fieldArr = [
            '对应订单号',
            '退件单号',
            '跟踪号',
            '平台',
            '买家ID',
            '退货类型',
            '退货来源',
            'ERP备注人/内容',
            '状态',
            '处理类型/明细',
        ];

        //导出数据数组
        $dataArr = [];
        if (is_array($data['data_list']) && !empty($data['data_list'])) {
            foreach ($data['data_list'] as $item) {
                if ($item['source'] == 1) {
                    $erp_type = json_decode($item['erp_type']);
                    $type = $erp_type->$item['return_type']->$item['return_typesmall'];
                } else {
                    $type = '';
                }

                $source = array('1' => '国内退件', '2' => '海外退件');
                $state = array('1' => '未处理', '2' => '无需处理', '3' => '已处理', '4' => '驳回EPR');
                $state_remark = $state[$item['state']] . chr(13);
                if ($item['state'] == 4)
                    $state_remark .= '驳回原因：' . $item['reason'] . chr(13);
                $state_remark .= $item['handle_user'] . chr(13);
                $state_remark .= $item['handle_time'];
                //导出数据数组
                $dataArr[] = [
                    (string) $item['order_id'], //
                    (string) $item['return_number'], //
                    (string) $item['trackno'],
                    (string) $item['platform_code'],
                    (string) $item['buyer_id'],
                    (string) $type,
                    (string) $source[$item['source']],
                    (string) $item['remark'],
                    $state_remark,
                    (string) $item['record'],
                ];
            }
            VHelper::exportExcel($fieldArr, $dataArr, 'returngoods_' . date('Y-m-d'));
        }else {
            echo '未找到数据';
        }
    }
    /**
     * 驳回ERP
     */
    public function actionReject() {
        $id = Yii::$app->request->post('id');
        $reason = Yii::$app->request->post('reason');
        $article = Domesticreturngoods::findOne(['id' => $id]);
        if ($article !== null) {
            $article->state = 4;
            $article->reason = $reason;
            $article->handle_user = Yii::$app->user->identity->login_name;
            $article->handle_time = date('Y-m-d H:i:s');
        }
        $data = array(
            "order_id" => $article->order_id,
            "platform_code" => $article->platform_code,
            "track_number" => $article->trackno, //2发货,3不发货
            'create_user' => Yii::$app->user->identity->login_name, //修改人
            'create_time' => date('Y-m-d H:i:s'), //修改时间
        );
        $orderModel = new ErpOrderApi();
        $res = $orderModel->Refuseorder($data);
        $response = new \stdClass();
        if ($res->statusCode == 200) {
            if ($article->save()) {
                $response->ack = true;
                $response->message = '驳回成功';
            } else {
                $response->ack = false;
                $response->message = '驳回失败，请联系管理员';
            }
        } else {
            $response->ack = false;
            $response->message = $res->message;
        }
        header('HTTP/1.0 200 OK');
        header('Content-type:  application/json');
        echo json_encode($response);
    }

    /**
     * 补款前端页面
     */
    public function actionSupplement() {

        $this->isPopup = true;
        $id = $this->request->getQueryParam('id');
        $platform = $this->request->getQueryParam('platform');
        $currencys = array('USD', 'AUD', 'CAD', 'EUR', 'GBP');
        $receipt_reason_types = [
            '1' => '收到退回',
            '2' => '加钱重寄',
            '3' => '假重寄',
            '4' => '其他'
        ];
        $palPalList = ['' => '--请选择--'];
        $palPalLists = RefundAccount::getList();
        if ($palPalLists) {
            foreach ($palPalLists as $value) {
                $palPalList[$value['id']] = $value['email'];
            }
        }
        $receipt_bank = BasicConfig::getParentList(118);
        return $this->render('receipt', [
                    'id' => $id,
                    'platform' => $platform,
                    'currencys' => $currencys,
                    'receipt_reason_types' => $receipt_reason_types,
                    'paypallist' => $palPalList,
                    'receipt_banks' => $receipt_bank
        ]);
    }

    /**
     * 补款保存页面
     */
    public function actionRelation() {
        $data = Yii::$app->request->post();
        $id = $data['id'];
        $receipt_bank = BasicConfig::getParentList(118);
        $Makeupdata = array();

        $article = Domesticreturngoods::findOne(['id' => $id]);
        $datetime = date('Y-m-d H:i:s');
        if ($article !== null) {

            switch ($data['receipt_type']) {
                case 1:
                    $record = '补款' . '<br>';
                    $record .= '补款方式：paypal收款' . '<br>';
                    $record .= '交易号：' . $data['record']['transaction_id'] . '<br>';
                    $record .= '金额：' . $data['record']['amt'] . $data['record']['currency'] . '<br>';
                    $Makeupdata = array(
                        'order_id' => $article->order_id, //原订单号
                        'return_order' => $article->return_number, //退款单号
                        'type' => 1, //1、paypal。2、线下收款。3关联补款单
                        'receivablespaypal' => $data['paypal_account_id'], // 收款paypal账号
                        'paymentpaypal' => '', // 付款paypal账号
                        'price' => $data['record']['receive_type'], //金额
                        'commission' => $data['record']['fee_amt'], //佣金
                        'currency' => $data['record']['currency'],
                        'transaction_id' => $data['record']['transaction_id'], //paypal交易号
                        'platform_code' => $data['platform'], //平台code
                        'state' => $data['record']['payer_status'], //  收款状态
                        'user' => Yii::$app->user->identity->login_name, //  处理人
                        'handle_time' => date('Y-m-d H:i:s'), //  处理时间
                    );
                    break;
                case 2:
                    $record = '补款' . '<br>';
                    $record .= '补款方式：线下收款' . '<br>';
                    $record .= '交易流水号：' . $data['transaction_id'] . '<br>';
                    $record .= '金额：' . $data['receipt_money'] . $data['receipt_currency'] . '<br>';
                    $record .= '收款银行：' . $receipt_bank[$data['receipt_bank']] . '<br>';
                    $Makeupdata = array(
                        'order_id' => $article->order_id, //原订单号
                        'return_order' => $article->return_number, //退款单号
                        'type' => 2, //1、paypal。2、线下收款。3关联补款单
                        'price' => $data['receipt_money'], //金额
                        'receipt_bank' => $receipt_bank[$data['receipt_bank']], //银行 id
                        'currency' => $data['receipt_currency'],
                        'transaction_id' => $data['transaction_id'], //交易流水号
                        'platform_code' => $data['platform'], //平台code
                        'state' => '', //  收款状态
                        'user' => Yii::$app->user->identity->login_name, //  处理人
                        'handle_time' => date('Y-m-d H:i:s'), //  处理时间
                    );
                    if ($data['receipt_bank'] == ' ') {
                        $this->_showMessage('没有选择收款银行', false);
                        exit;
                    }

                    break;
                case 3:
                    $record = '补款' . '<br>';
                    $record .= '补款方式：关联补款订单' . '<br>';
                    $record .= '交易单号：' . $data['vc_order_id'] . '<br>';
                    $record .= '金额：' . $data['vc_totprice'] . $data['total_price'] . '<br>';
                    $Makeupdata = array(
                        'order_id' => $article->order_id, //原订单号
                        'return_order' => $article->return_number, //退款单号
                        'type' => 3, //1、paypal。2、线下收款。3关联补款单
                        'price' => $data['vc_totprice'], //金额
                        'receipt_bank' => '', //银行 id
                        'currency' => $data['vc_currency'],
                        'transaction_id' => $data['vc_order_id'], //交易流水号 'supplement_order_id'=>'AL170913002626-RE99',//补款订单id
                        'platform_code' => $data['platform'], //平台code
                        'state' => '', //	收款状态
                        'user' => Yii::$app->user->identity->login_name, //  处理人
                        'handle_time' => date('Y-m-d H:i:s'), //  处理时间
                    );
                    break;
                default:
                    return;
            }


            $article->state = 3;
            $article->handle_type = 5;
            $article->record = $record;
            $article->handle_user = Yii::$app->user->identity->login_name;
            $article->handle_time = $datetime;

            $datas = array(
                "order_id" => $article->order_id,
                "platform_code" => $article->platform_code,
                "track_number" => $article->trackno,
                "type" => 2, //2发货,3不发货
                'create_user' => Yii::$app->user->identity->login_name, //修改人
                'create_time' => $datetime, //修改时间
            );

            $orderModel = new ErpOrderApi();
            $resdiu = $orderModel->Whethership($datas);
            if ($resdiu->statusCode == 200) {


                $orderModel = new ErpOrderApi();
                $res = $orderModel->Makeup($Makeupdata);

                if ($res->statusCode == 200) {
                    if ($article->save()) {
                        $this->_showMessage('保存成功', true);
                    } else {
                        $this->_showMessage('保存失败，请联系管理员', false);
                    }
                } else {
                    $this->_showMessage($res->message, false);
                }
            } else {
                $this->_showMessage($resdiu->message, false);
            }
        } else {
            $this->_showMessage('退款单号不存在', false);
        }
    }

    /**
     * hangup暂不处理退款单
     */
    public function actionHangup() {
        $id = $this->request->getQueryParam('id');
        $article = Domesticreturngoods::findOne(['id' => $id]);
        $datetime = date('Y-m-d H:i:s');
        $response = new \stdClass();
        if ($article !== null) {
            $record = '暂不处理';
            $article->state = 5;
            $article->record = $record;
            $article->handle_user = Yii::$app->user->identity->login_name;
            $article->handle_time = $datetime;

            if ($article->save()) {
                $response->ack = true;
                $response->message = '保存成功';
            } else {
                $response->ack = false;
                $response->message = '保存失败，请联系管理员';
            }
        } else {
            $response->ack = false;
            $response->message = '保存失败，请联系管理员';
        }
        header('HTTP/1.0 200 OK');
        header('Content-type:  application/json');
        echo json_encode($response);
    }

    /**
     * hangup批量暂不处理退款单
     * @author harvin 
     */
    public function actionHangupall() {
        $ids = Yii::$app->request->post('selectIds');
        if (empty($ids)) {
            return json_encode(['state' => 0, 'msg' => "请选择数据"]);
        }
        //转化数组
        $orderIds = explode(',', $ids);
        $article = Domesticreturngoods::find()->where(['in', 'id', $orderIds])->all();
        $res = [];
        foreach ($article as $v) {
            if ($v->state == 1) {
                $res[] = $v->id;
            }
        }
        if (empty($res)) {
            return json_encode(['state' => 0, 'msg' => "只有未处理才能操作"]);
        }
        $datetime = date('Y-m-d H:i:s'); 
        //批量修改
        //state 1、未处理，2、无需处理，3、已处理，4、驳回EPR，5暂不处理',
        $re = Domesticreturngoods::updateAll(['state' => 5,'record'=>'暂不处理','handle_user'=>Yii::$app->user->identity->login_name,'handle_time'=>$datetime], ['in', 'id', $res]);
        if ($re) {
            return json_encode(['state' => 1, 'msg' => "保存成功"]);
        } else {
            return json_encode(['state' => 0, 'msg' => "保存失败"]);
        }
    }

    /**
     *  暂扣订单 、
     */
    public function actionWithhold() {
        $id = $this->request->getQueryParam('id');
        $article = Domesticreturngoods::findOne(['id' => $id]);
        $datetime = date('Y-m-d H:i:s');
        $response = new \stdClass();

        if ($article !== null) {
            $data = array(
                "order_id" => $article->order_id,
                "platform_code" => $article->platform_code,
                "track_number" => $article->trackno,
                "create_user" => Yii::$app->user->identity->login_name,
                "create_time" => $datetime,
            );
            $orderModel = new ErpOrderApi();
            $res = $orderModel->holdShippedOrder($data);

            if ($res->statusCode == 200) {
                $response->ack = true;
                $response->message = '保存成功';
            } else {
                $response->ack = false;
                $response->message = $res->message;
            }
        } else {
            $response->ack = false;
            $response->message = '保存失败，请联系管理员';
        }
        header('HTTP/1.0 200 OK');
        header('Content-type:  application/json');
        echo json_encode($response);
    }

    /**
     * 批量操作暂扣订单
     * @author harvin 
     * * */
    public function actionWithholdall() {
        $ids = Yii::$app->request->post('selectIds'); //勾选数据id
        if (empty($ids)) {
            return json_encode(['state' => 0, 'msg' => '请勾选数据']);
        }
        //转化成数组
        $orderIds = explode(',', $ids);
        $datetime = date('Y-m-d H:i:s');
        foreach ($orderIds as $v) {
            $article = Domesticreturngoods::findOne(['id' => $v]);
            if (!empty($article)) {
                $data = array(
                    "order_id" => $article->order_id,
                    "platform_code" => $article->platform_code,
                    "track_number" => $article->trackno,
                    "create_user" => Yii::$app->user->identity->login_name,
                    "create_time" => $datetime,
                );
                $orderModel = new ErpOrderApi();
                $res = $orderModel->holdShippedOrder($data);
                if ($res->statusCode == 200) {
                    $failg = true;
                    $msg = '保存成功';
                } else {
                    $failg = true;
                    $msg = $res->message;
                }
            }
        }
        if ($failg) {
            return json_encode(['state' => 1, 'msg' => $msg]);
        } else {
            return json_encode(['state' => 1, 'msg' => '保存失败，请联系管理员']);
        }
    }

    /**
     *  永久作废
     */
    public function actionPermanentcancel() {
        $id = $this->request->getQueryParam('id');
        $article = Domesticreturngoods::findOne(['id' => $id]);
        $datetime = date('Y-m-d H:i:s');
        $response = new \stdClass();

        if ($article !== null) {
            $data = array(
                "order_id" => $article->order_id,
                "platform_code" => $article->platform_code,
                "track_number" => $article->trackno,
                "create_user" => Yii::$app->user->identity->login_name,
                "create_time" => $datetime,
            );
            $orderModel = new ErpOrderApi();
            $res = $orderModel->cancelShippedOrder($data);

            if ($res->statusCode == 200) {
                $response->ack = true;
                $response->message = '保存成功';
            } else {
                $response->ack = false;
                $response->message = $res->message;
            }
        } else {
            $response->ack = false;
            $response->message = '保存失败，请联系管理员';
        }
        header('HTTP/1.0 200 OK');
        header('Content-type:  application/json');
        echo json_encode($response);
    }

    /**
     *  批量永久作废
     * @author harvin 
     */
    public function actionPermanentcancelall() {
        $ids = Yii::$app->request->post('selectIds'); //获取勾选数据
        if (empty($ids)) {
            return json_encode(['state' => 0, 'msg' => '请勾选数据']);
        }
        //转化成数组
        $orderIds = explode(',', $ids);
        $datetime = date('Y-m-d H:i:s');
        foreach ($orderIds as $v) {
            $article = Domesticreturngoods::findOne(['id' => $v]);
            if (!empty($article)) {
                $data = array(
                    "order_id" => $article->order_id,
                    "platform_code" => $article->platform_code,
                    "track_number" => $article->trackno,
                    "create_user" => Yii::$app->user->identity->login_name,
                    "create_time" => $datetime,
                );
                $orderModel = new ErpOrderApi();
                $res = $orderModel->cancelShippedOrder($data);
                if ($res->statusCode == 200) {
                    $failg = true;
                    $msg = '保存成功';
                } else {
                    $failg = true;
                    $msg = $res->message;
                }
            }
        }
        if ($failg) {
            return json_encode(['state' => 1, 'msg' => $msg]);
        } else {
            return json_encode(['state' => 1, 'msg' => '保存失败，请联系管理员']);
        }
    }

    /**
     * @desc 速卖通补款订单列表
     */
    public function actionAliexpresslist() {
        $bool = FALSE;
        $order_id = $this->request->getQueryParam('order_id');
        // 交易id
        $platform_order_id = $this->request->getQueryParam('platform_order_id');
        if (empty($order_id) && empty($platform_order_id)) {
            $this->_showMessage('平台订单号/订单号 为必填数据', false);
        }
        $orders = OrdersAliexpress::getOrders(trim($order_id), trim($platform_order_id));

        if (empty($orders[0])) {
            $this->_showMessage('查询不到补款单', false);
        }

        $return_data = json_decode(Json::encode($orders[0]), true);
        $this->_showMessage('', true, null, false, $return_data, null, false);
    }

    /**
     * 拆单
     */
    public function actionSplitorder() {
        $postdata = Yii::$app->request->post();
        $data = array(
            "order_id" => $postdata['order_id_v'], //订单号(必填)
            "platform_code" => $postdata['platform_code_v'], //平台code(必填)
            'Order' => $postdata['Order'],
            'OrderDetail' => $postdata['OrderDetail'],
        );
        $article = Domesticreturngoods::findOne(['id' => $postdata['separate_id']]);
        $datetime = date('Y-m-d H:i:s');
        if ($article !== null) {
            $orderModel = new ErpOrderApi();
            $res = $orderModel->separateordermessage($data);
            if ($res->statusCode == 200) {
                $record = '拆分后订单：' . '<br>';

                foreach ($postdata['OrderDetail'] as $key => $details) {
                    foreach ($details as $detail) {
                        if ($detail['quantity'] > 0) {
                            $record .= $key . '<br>';
                            $datas = array(
                                "order_id" => $article->order_id,
                                "platform_code" => $article->platform_code,
                                "track_number" => $article->trackno,
                                "type" => 2, //2发货,3不发货
                                'create_user' => Yii::$app->user->identity->login_name, //修改人
                                'create_time' => $datetime, //修改时间
                            );
                            $orderModel = new ErpOrderApi();
                            $resgoods = $orderModel->Whethership($datas);
                        }
                    }
                }

                $article->state = 3;
                $article->handle_type = 2;
                $article->record = $record;
                $article->handle_user = Yii::$app->user->identity->login_name;
                $article->handle_time = $datetime;
                if ($resgoods->statusCode == 200) {
                    if ($article->save()) {
                        $this->_showMessage('保存成功', true);
                    } else {
                        $this->_showMessage('保存失败，请联系管理员', false);
                    }
                } else {
                    $this->_showMessage($res->message, false);
                }
            } else {
                $this->_showMessage($res->message, false);
            }
        } else {
            $this->_showMessage('退款单号不存在', false);
        }
    }

    public function actionChangesnum() {

        $orderIdM = Yii::$app->request->post('id');
        $platform = Yii::$app->request->post('code');
        $model = OrderDetail::getOrderdetail($platform, $orderIdM);
        foreach ($model as $v) {
            $arr[] = $v->quantity;
        }

        $num = count($arr);

        exit(json_encode(['num' => $num, 'msg' => $arr]));
    }

    /**
     * 拆单页面显示
     */
    public function actionSeparate() {
        $this->isPopup = true;
        $warehouseModel = Warehouse::getWarehouseList();
        $mode = new \Stdclass();
        $mode->separate_id = $this->request->getQueryParam('id');
        $mode->order_id = $this->request->getQueryParam('order_id');
        $mode->platform_code = $this->request->getQueryParam('platform_code');
        $model = OrderKefu::getOrders($mode->platform_code, $mode->order_id);
        $modelDetails = OrderDetail::getOrderdetail($mode->platform_code, $mode->order_id);
        //print_r($modelDetails);
        return $this->render('separate', [
                    'model' => $model,
                    'mode' => $mode,
                    'warehouseModel' => $warehouseModel,
                    'modelDetails' => $modelDetails,
        ]);
    }

    public function actionShip() {
        $data = array(
            "order_id" => 'AL180417005605',
            "platform_code" => 'ALI',
            "track_number" => 'LY029398938CN', //2发货,3不发货
            'create_user' => '张三', //修改人
            'create_time' => '2018-11-11 00:00:47', //修改时间
        );
        $orderModel = new ErpOrderApi();
        $res = $orderModel->Refuseorder($data);
        print_r($res);
    }

    //查询该订单 并且是未处理状态、类型为国内港前退件（1.安检不过关2.尺寸超长、体积超重3.偏远4.地址问题5.跟踪号失败6.退货重号）
    public function actionGetreturngoods() {
        $order_id = $this->request->getQueryParam('order_id');
        $orders = Domesticreturngoods::findOne(['order_id' => $order_id]);
        $messege = new \Stdclass();
        if ($orders !== null) {
            $messege->ack = true;
        } else {
            $messege->ack = false;
        }
        echo json_encode($messege);
    }

}

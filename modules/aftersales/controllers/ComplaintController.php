<?php

namespace app\modules\aftersales\controllers;

use app\modules\accounts\models\Account;
use app\modules\orders\models\Logistic;
use app\modules\orders\models\OrderKefu;
use app\modules\orders\models\Warehouse;
use app\modules\systems\models\Country;
use Yii;
use yii\helpers\Json;
use app\components\Controller;
use app\modules\accounts\models\Platform;
use app\modules\aftersales\models\AfterSalesRefund;
use app\modules\aftersales\models\RefundReturnReason;
use app\modules\systems\models\BasicConfig;
use app\modules\users\models\UserRole;
use app\modules\aftersales\models\BasicConfigModel;
use app\modules\aftersales\models\ComplaintModel;
use app\modules\aftersales\models\ComplaintdetailModel;
use app\modules\aftersales\models\WarehouseprocessingModel;
use app\modules\aftersales\models\ComplaintlogModel;
use app\modules\aftersales\models\Upload;
use yii\data\Pagination;

/**
 * RefundreturnreasonController implements the CRUD actions for RefundReturnReason model.
 */
class ComplaintController extends Controller {

    //列表
    public function actionIndex() {

        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null; //客诉类型
        $platformCode = isset($_REQUEST['platform_code']) ? $_REQUEST['platform_code'] : null; //客诉类型
        $key = isset($_REQUEST['key']) ? trim($_REQUEST['key']) : null; //关键字
        $is_expedited = isset($_REQUEST['is_expedited']) ? $_REQUEST['is_expedited'] : null; //是否加急
        $is_overtime = isset($_REQUEST['is_overtime']) ? $_REQUEST['is_overtime'] : null; //是否超时
        $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : null; //状态
        $get_date = isset($_REQUEST['get_date']) ? $_REQUEST['get_date'] : null; //下单时间 发货时间 付款时间
        $begin_date = isset($_REQUEST['begin_date']) ? $_REQUEST['begin_date'] : null; //开始时间
        $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : null; //结束时间
        $account_id = isset($_REQUEST['account_id']) ? $_REQUEST['account_id'] : null; //账号
        if (empty($begin_date) && $end_date != null) {
            $this->_showMessage('请选择开始时间', true, null, true, '');
        }
        if (empty($end_date) && $begin_date != null) {
            $this->_showMessage('请选择结束时间', true, null, true, '');
        }

        $platformList = Platform::getPlatformAsArray();
        if ($platformCode == null) {
            $ImportPeople_list = Account::getIdNameKefuList('EB');
        } else {
            $ImportPeople_list = Account::getIdNameKefuList($platformCode);
        }
        //编辑客诉类型
        $basic = BasicConfigModel::find()->select('name')->where(['parent_id' => 131])->all();
        $result = ComplaintModel::getcomplianorder($type, $platformCode, $key, $is_expedited, $is_overtime, $status, $get_date, $begin_date, $end_date, $account_id);

        //创建分页组件
        //创建分页组件
        $page = new Pagination([
            //总的记录条数
            'totalCount' => count($result),
            //分页大小
            'pageSize' => $pageSize,
            //设置地址栏当前页数参数名
            'pageParam' => 'pageCur',
            //设置地址栏分页大小参数名
            'pageSizeParam' => 'pageSize',
        ]);

        return $this->render('index', [
                    'platformList' => $platformList,
                    'ImportPeople_list' => $ImportPeople_list,
                    'basic' => $basic,
                    'page' => $page,
                    'result' => $result,
                    'count' => count($result),
                    'type' => $type,
                    'platformCode' => $platformCode,
                    'is_expedited' => $is_expedited,
                    'is_overtime' => $is_overtime,
                    'status' => $status,
                    'type' => $type,
                    'get_date' => $get_date,
                    'begin_date' => $begin_date,
                    'end_date' => $end_date,
                    'keys' => $key,
                    'account_id' => $account_id
        ]);
    }

    /**
     * @desc 登记客诉单
     * @return \yii\base\string
     */
    public function actionRegister() {
        set_time_limit(0);
        $this->isPopup = true;
        $orderId = $this->request->getQueryParam('order_id');
        $platform = $this->request->getQueryParam('platform');
        $returngoodsid = $this->request->getBodyParam('id');

        $model = new ComplaintdetailModel();

        //订单信息
        $orderinfo = [];
        if (empty($platform)) {
            $this->_showMessage('平台CODE无效', false, null, false, null, 'layer.closeAll()');
        }
        if (empty($orderId)) {
            $this->_showMessage('订单号无效', false, null, false, null, 'layer.closeAll()');
        }
        $orderinfo = OrderKefu::getOrderStackByOrderId($platform, '', $orderId);
        if (empty($orderinfo)) {
            $this->_showMessage('找不到对应订单', false, null, false, null, 'layer.closeAll()');
        }
        //查找订单对应的买家账号
        $accountname = Account::find()->select('account_name')->where(['platform_code' => $platform])->andWhere(['old_account_id' => $orderinfo->info->account_id])->asArray()->one();
        $order_amount = isset($orderinfo->info) && isset($orderinfo->info->total_price) ? $orderinfo->info->total_price : 0.00;
        $allow_refund_amount = AfterSalesRefund::getAllowRefundAmount($orderId, $order_amount, $platform);
        $datas = ['orderId' => $orderId, 'platformCode' => $platform];
        $orderinfo = Json::decode(Json::encode($orderinfo), true);
        $countires = Country::getCodeNamePairs();
        $warehouseList = Warehouse::getWarehouseList();
        $warehouseList_new = [];
        $warehouseList_new[' '] = '请选择发货仓库';
        foreach ($warehouseList as $key => $value) {
            $warehouseList_new[$key] = $value;
        }

        $logistics = Logistic::getWarehouseLogistics($orderinfo['info']['warehouse_id']);
        $reasonList = RefundReturnReason::getList();

        $reasonCodeList = ComplaintModel::getreason($platform);

        //加黑名单临时解决方案  黄玲，汪成荣，方超和胡丽玲
        $isAuthority = false;
        if (UserRole::checkManage(Yii::$app->user->identity->id)) {
            $isAuthority = true;
        }

        $departmentList = BasicConfig::getParentList(52);
        $departmentList_new = [];
        foreach ($departmentList as $k => &$v) {
            $departmentList_new[$k]['depart_id'] = $k;
            $departmentList_new[$k]['depart_name'] = $v;
        }
        //编辑客诉类型
        $basic = BasicConfigModel::find()->select('name')->where(['parent_id' => 131])->all();

        return $this->render('register', [
                    'info' => $orderinfo,
                    'countries' => $countires,
                    'platform' => $platform,
                    'warehouseList' => $warehouseList_new,
                    'logistics' => $logistics,
                    'departmentList' => json_encode($departmentList_new),
                    'reasonList' => $reasonList,
                    'reasonCodeList' => $reasonCodeList,
                    'allow_refund_amount' => $allow_refund_amount,
                    'currencyCode' => isset($orderinfo['info']['currency']) ? $orderinfo['info']['currency'] : '',
                    'isAuthority' => $isAuthority,
                    'accountName' => $accountname['account_name'],
                    'basic' => $basic,
                    'model' => $model,
        ]);
    }

    /**
     * 保存客诉单数据
     * * */
    public function actionGetsave() {

        $data = $this->request->post('data'); //勾选数据     
        $is_expedited = $this->request->post('is_expedited'); //加急 0 1 
        $description = $this->request->post('description'); //描述
        $type = $this->request->post('type'); //客诉类型
        $buyer_id = $this->request->post('buyer_id'); //买家ID
        $orderId = $this->request->post('order_id'); //系统订单号
        $platform = $this->request->post('platform_code'); //平台
        $platform_order_id = $this->request->post('platform_order_id');
        $shipped_date = $this->request->post('shipped_date');
        $warehouse_id = $this->request->post('warehouse_id');
        $account_id = $this->request->post('account_id');
        //判断可建客诉单
        $rule = ComplaintModel::getcomplainwns($warehouse_id);
        if (!$rule) {
            return json_encode(['state' => 0, 'msg' => "只能海外虚拟仓、易佰东莞仓库、易佰美国仓才能登记客诉单"]);
        }
        //事物开始
        $connection = Yii::$app->db->beginTransaction();
        try {
            //插入仓库客诉主表
            $complain = new ComplaintModel();
            $complain->complaint_order = ComplaintModel::getComplaintorder(); //客诉单号
            $complain->buyer_id = $buyer_id; //买家ID
            $complain->order_id = $orderId; //系统订单号
            $complain->platform_code = $platform; //平台
            $complain->warehouse_id = $warehouse_id; //仓库id
            $complain->platform_order_id = $platform_order_id; //系统订单号
            $complain->shipping_date = $shipped_date; //发货时间
            $complain->status = 0; //-2:删除;-1:审核不通过;0:待审核;1:推送成功待仓库处理;2:推送失败;3:仓库处理完成待确认;4:重新推送失败待重新推送;5:重新推送成功待仓库处理;6:已完成
            $complain->type = $type; //客诉类型
            $complain->account_id = $account_id;
            $complain->description = $description; //详细描述
            $complain->create_user = Yii::$app->user->identity->user_name; //创建人
            $complain->create_time = date('Y-m-d H:s:i'); //创建时间
            $complain->is_expedited = $is_expedited; //是否加急(0:否；1:是)

            $complain->save();
            //获取仓库客诉表
            $complaint_order_id = $complain->attributes['id'];
            //客诉产品详情表
            $time = date('Y-m-d H:i:s');
            foreach ($data as $key => $v) {
                $complaintdetail = new ComplaintdetailModel();
                $complaintdetail->complaint_order_id = $complaint_order_id; //客诉表主键id
                $complaintdetail->sku = $v[1][1]; //公司sku
                $complaintdetail->title = $v[1][0]; //产品名称
                $complaintdetail->product_line = $v[1][2]; //产品线
                $complaintdetail->qty = $v[2]; //产品数量
                $complaintdetail->img_url = implode(',', $v[3]); //相关图片(json类型)
                $complaintdetail->create_time = $time;
                $complaintdetail->save();
            }
            $connection->commit();
            return json_encode(['state' => 1, 'msg' => "添加成功"]);
        } catch (\Exception $e) {
            $connection->rollBack();
            return json_encode(['state' => 0, 'msg' => "添加失败"]);
        }
    }

    /*     * *
     * 保存修改数据
     * * */

    public function actionGeteditsave() {
        $data = $this->request->post('data'); //勾选数据     
        $is_expedited = $this->request->post('is_expedited'); //加急 0 1 
        $description = $this->request->post('description'); //描述
        $type = $this->request->post('type'); //客诉类型
        $complaint_order = $this->request->post('complaint_order'); //客诉单号
        $connection = Yii::$app->db->beginTransaction();
        try {
            $re['type'] = $type; //客诉类型
            $re['description'] = $description; //详细描述
            $re['is_expedited'] = $is_expedited; //是否加急(0:否；1:是)   
            $res = ComplaintModel::updateAll($re, ['complaint_order' => $complaint_order]);
            foreach ($data as $key => $v) {
                $complaintdetail = ComplaintdetailModel::find()->where(['sku' => $v[1][1]])->one();
                $complaintdetail->img_url = implode(',', $v[3]); //相关图片
                $complaintdetail->qty = $v[2]; //数量
                $complaintdetail->save();
            }
            $connection->commit();
            return json_encode(['state' => 1, 'msg' => "修改成功"]);
        } catch (\Exception $e) {
            $connection->rollBack();
            return json_encode(['state' => 0, 'msg' => "修改失败"]);
        }
    }

    /**
     * @desc 处理登记页面
     * @return \yii\base\string
     */
    public function actionGetcompain() {
        set_time_limit(0);
        $this->isPopup = true;
        $complaint_order = $this->request->getQueryParam('complaint_order'); //获取客诉单号
        $complain = ComplaintModel::find()->where(['complaint_order' => $complaint_order])->asArray()->one();
        $complaindetail = ComplaintdetailModel::getcomplaindetail($complain['id']);
        $processing = WarehouseprocessingModel::getWarehouseprocessing($complain['id']);
        $orderId = $complain['order_id'];
        $platform = $complain['platform_code'];
        //订单信息
        $orderinfo = [];
        if (empty($platform)) {
            $this->_showMessage('平台CODE无效', false, null, false, null, 'layer.closeAll()');
        }
        if (empty($orderId)) {
            $this->_showMessage('订单号无效', false, null, false, null, 'layer.closeAll()');
        }
        $orderinfo = OrderKefu::getOrderStackByOrderId($platform, '', $orderId);
        if (empty($orderinfo)) {
            $this->_showMessage('找不到对应订单', false, null, false, null, 'layer.closeAll()');
        }
        //查找订单对应的买家账号
        $accountname = Account::find()->select('account_name')->where(['platform_code' => $platform])->andWhere(['old_account_id' => $orderinfo->info->account_id])->asArray()->one();
        $order_amount = isset($orderinfo->info) && isset($orderinfo->info->total_price) ? $orderinfo->info->total_price : 0.00;
        $allow_refund_amount = AfterSalesRefund::getAllowRefundAmount($orderId, $order_amount, $platform);
        $datas = ['orderId' => $orderId, 'platformCode' => $platform];
        $orderinfo = Json::decode(Json::encode($orderinfo), true);
        $countires = Country::getCodeNamePairs();
        $warehouseList = Warehouse::getWarehouseList();
        $warehouseList_new = [];
        $warehouseList_new[' '] = '请选择发货仓库';
        foreach ($warehouseList as $key => $value) {
            $warehouseList_new[$key] = $value;
        }
        $logistics = Logistic::getWarehouseLogistics($orderinfo['info']['warehouse_id']);
        $reasonList = RefundReturnReason::getList();

        $reasonCodeList = ComplaintModel::getreason($platform);

        //加黑名单临时解决方案  黄玲，汪成荣，方超和胡丽玲
        $isAuthority = false;
        if (UserRole::checkManage(Yii::$app->user->identity->id)) {
            $isAuthority = true;
        }
        $departmentList = BasicConfig::getParentList(52);
        $departmentList_new = [];
        foreach ($departmentList as $k => &$v) {
            $departmentList_new[$k]['depart_id'] = $k;
            $departmentList_new[$k]['depart_name'] = $v;
        }

        return $this->render('handle', [
                    'info' => $orderinfo,
                    'countries' => $countires,
                    'platform' => $platform,
                    'warehouseList' => $warehouseList_new,
                    'logistics' => $logistics,
                    'departmentList' => json_encode($departmentList_new),
                    'reasonList' => $reasonList,
                    'reasonCodeList' => $reasonCodeList,
                    'allow_refund_amount' => $allow_refund_amount,
                    'currencyCode' => isset($orderinfo['info']['currency']) ? $orderinfo['info']['currency'] : '',
                    'isAuthority' => $isAuthority,
                    'accountName' => $accountname['account_name'],
                    'complain' => $complain,
                    'complaindetail' => $complaindetail,
                    'processing' => $processing
        ]);
    }

    /**
     * @desc 处理页面审核
     * @return \yii\base\string
     */
    public function actionGetexamine() {
        $this->isPopup = true;
        $complaint_order = $this->request->getQueryParam('complaint_order');
        if (Yii::$app->request->isPost) {
            $status = $this->request->post('status');
            $remark = $this->request->post('remark');
            $complaint_order = $this->request->post('complaint_order');
            $complaint_order_id = ComplaintModel::getComplaindata($complaint_order);
            foreach ($complaint_order_id['complian'] as $v) {
                $sku[] = $v['sku'];
                $product_line[] = $v['product_line'];
                $imgpath = explode(',', $v['img_url']);
                foreach ($imgpath as $vo) {
                    $images[] = '/' . $vo;
                }
                $img_url[] = implode(',', $images);
            }
            if (empty($status)) {
                $this->_showMessage('请选择审核状态', false, null, false, null, 'layer.closeAll()');
            }
            if ($status == "-1") {
                if (empty($remark)) {
                    $this->_showMessage('审核不通过，请说明原因', false, null, false, null, 'layer.closeAll()');
                }
            }
            //记录操作日志
            $complaintlog = new ComplaintlogModel();
            $complaintlog->complaint_order_id = $complaint_order_id['id'];
            $complaintlog->create_user = Yii::$app->user->identity->user_name;
            $complaintlog->operational_time = date('Y-m-d H:i:s');

            if ($status == "1") {
                $complaintlog->operational_action = "推送给仓库信息";
                //获取接口地址
                $url = ComplaintModel::getcomplainWarehouse($complaint_order_id['warehouse_id'], 1);
                if (empty($url)) {
                    $this->_showMessage('接口地址不存在', true, null, false, 'layer.closeAll()');
                }
                //插入客诉仓库处理表
                $res = new WarehouseprocessingModel();
                $res->complaint_order_id = $complaint_order_id['id'];
                $res->create_user = Yii::$app->user->identity->user_name;
                $res->add_time = date('Y-m-d H:i:s');
                $res->save();
                $id = $res->attributes['id']; //自增长ID
                //调用仓库系统接口
                $data = [
                    [
                        'complaint_order' => $complaint_order, //客诉单号
                        'type' => $complaint_order_id['type'], //客诉类型
                        'description' => $complaint_order_id['description'], //描述
                        'platform_order_id' => $complaint_order_id['platform_order_id'], //ERP订单号
                        'order_id' => $complaint_order_id['order_id'], //平台订单号
                        'SKU' => $sku, //SKU
                        'product_line' => $product_line, //SKU中文名称
                        'SKU_IMG' => $img_url, //图片地址
                        'last_processing_time' => $complaint_order_id['last_processing_time'], //最晚处理时间
                        'is_overtime' => $complaint_order_id['is_overtime'], //是否加急(0:否；1:是)
                        'id' => $id, //客诉仓库处理表id     
                        'create_username' => Yii::$app->user->identity->user_name,
                    ],
                ];
                $post_data = json_encode($data);

                $ru = ComplaintModel::curlPost($url, $post_data);
                $res = json_decode($ru, true);

                if ($res['code'] == 1) {
                    $compl = ComplaintModel::find()->where(['complaint_order' => $complaint_order])->one();
                    if ($compl->is_expedited == 1) {
                        $compl->last_processing_time = ComplaintModel::getProcessingTime(1); //最晚处理时间
                        $compl->expedited_time = date("Y-m-d H:s:i"); //加急时间
                        $compl->expedited_user = Yii::$app->user->identity->user_name; //加急操作人   
                    } else {
                        $compl->last_processing_time = ComplaintModel::getProcessingTime(2); //最晚处理时间
                    }
                    $compl->status = 1;
                    $compl->audit_time = date('Y-m-d H:i:s');
                    $compl->auditer = Yii::$app->user->identity->user_name;
                    $compl->save();
                    $complaintlog->operational_action = "推送成功";
                    $complaintlog->save();
                    $this->_showMessage('操作成功！', true, null, true, 'window.location.reload()');
                } else {
                    ComplaintModel::updateAll(['status' => 2, 'audit_time' => date('Y-m-d H:i:s'), 'auditer' => Yii::$app->user->identity->user_name], ['complaint_order' => $complaint_order]);
                    $complaintlog->operational_action = "推送失败";
                    $complaintlog->save();
                    WarehouseprocessingModel::updateAll(['status' => '-1'], ['id' => $id]);
                    $this->_showMessage('操作失败！', true, null, true, 'window.location.reload()');
                }
            } else {
                $complaintlog->operational_action = "审核不通过";
                $complaintlog->remark = $remark;
                $complaintlog->save();
                ComplaintModel::updateAll(['status' => '-1', 'audit_time' => date('Y-m-d H:i:s'), 'auditer' => Yii::$app->user->identity->user_name], ['complaint_order' => $complaint_order]);
                $this->_showMessage('操作成功！', true, null, true, 'top.window.location.reload()');
            }
        }
        return $this->render('examine', ['complaint_order' => $complaint_order]);
    }

    /**
     * 批量审核
     * * */
    public function actionGetexamineall() {
        $this->isPopup = true;
        $data = $usd = [];
        $id = $this->request->post('id');
        $status = $this->request->post('status');
        $remark = $this->request->post('remark');
        $host_url = include Yii::getAlias('@app') . '/config/vms_api.php';
        //转化数组
        $ids = explode(',', $id);
        foreach ($ids as $key => $v) {
            $bool = true;
            $complaint_order_id = ComplaintModel::find()->where(['id' => $v])->asArray()->one();
            $complaintorder = ComplaintModel::getComplaindata($complaint_order_id['complaint_order']);
            foreach ($complaintorder['complian'] as $v) {
                $sku[] = $v['sku'];
                $product_line[] = $v['product_line'];
                $imgpath = explode(',', $v['img_url']);
                foreach ($imgpath as $vo) {
                    $images[] = '/' . $vo;
                }
                $img_url[] = implode(',', $images);
            }
            //记录操作日志
            $complaintlog = new ComplaintlogModel();
            $complaintlog->complaint_order_id = $complaint_order_id['id'];
            $complaintlog->create_user = Yii::$app->user->identity->user_name;
            $complaintlog->operational_time = date('Y-m-d H:i:s');
            $url = ComplaintModel::getcomplainWarehouse($complaintorder['warehouse_id'], 1);
            if (empty($url)) {
                $bool = false;
            }
            if ($bool) {
                if ($status == 1) {
                    $complaintlog->operational_action = "推送给仓库信息";
                    $complaintlog->save();
                    //插入客诉仓库处理表
                    $res = new WarehouseprocessingModel();
                    $res->complaint_order_id = $complaint_order_id['id'];
                    $res->create_user = Yii::$app->user->identity->user_name;
                    $res->add_time = date('Y-m-d H:i:s');
                    $res->save();
                    $id = $res->attributes['id']; //自增长ID

                    if ($url == $host_url['dongguan'] . "/Api/Order/CustomerComplain/add") {
                        $state = 1;
                        //东莞仓及海外虚拟仓
                        $data[] = [
                            'complaint_order' => $complaint_order_id['complaint_order'], //客诉单号
                            'type' => $complaint_order_id['type'], //客诉类型
                            'description' => $complaint_order_id['description'], //描述
                            'platform_order_id' => $complaint_order_id['platform_order_id'], //ERP订单号
                            'order_id' => $complaint_order_id['order_id'], //平台订单号
                            'SKU' => $sku, //SKU
                            'product_line' => $product_line, //SKU中文名称
                            'SKU_IMG' => $img_url, //图片地址
                            'last_processing_time' => $complaint_order_id['last_processing_time'], //最晚处理时间
                            'is_overtime' => $complaint_order_id['is_overtime'], //是否加急(0:否；1:是)
                            'id' => $id, //客诉仓库处理表id     
                            'create_username' => Yii::$app->user->identity->user_name,
                        ];
                    }
                    if ($url == $host_url['us'] . "/Api/Order/CustomerComplain/add") {
                        $state = 2;
                        //美国仓
                        $usd[] = [
                            'complaint_order' => $complaint_order_id['complaint_order'], //客诉单号
                            'type' => $complaint_order_id['type'], //客诉类型 
                            'description' => $complaint_order_id['description'], //描述
                            'platform_order_id' => $complaint_order_id['platform_order_id'], //ERP订单号
                            'order_id' => $complaint_order_id['order_id'], //平台订单号
                            'SKU' => $sku, //SKU
                            'product_line' => $product_line, //SKU中文名称
                            'SKU_IMG' => $img_url, //图片地址
                            'last_processing_time' => $complaint_order_id['last_processing_time'], //最晚处理时间
                            'is_overtime' => $complaint_order_id['is_overtime'], //是否加急(0:否；1:是)
                            'id' => $id, //客诉仓库处理表id     
                            'create_username' => Yii::$app->user->identity->user_name,
                        ];
                    }
                }
                if ($status == "-1") {
                    $complaintlog->operational_action = "审核不通过";
                    $complaintlog->remark = $remark;
                    $complaintlog->save();
                    ComplaintModel::updateAll(['status' => '-1', 'audit_time' => date('Y-m-d H:i:s'), 'auditer' => Yii::$app->user->identity->user_name], ['complaint_order' => $complaint_order_id['complaint_order']]);
                }
            }
        }
        if ($status == "-1") {
            return json_encode(['state' => 1, 'msg' => '操作成功']);
        }
        if (empty($data) && empty($usd)) {
            return json_encode(['state' => 0, 'msg' => '请求错误']);
        }
        if ($status == "1") {
            if ($state == 1 && !empty($data)) {
                $urls = $host_url['dongguan'] . "/Api/Order/CustomerComplain/add";
                $post_data = json_encode($data);
                $ru = ComplaintModel::curlPost($urls, $post_data);
                $re = json_decode($ru, true);
                if ($re['code'] == 1) {
                    foreach ($data as $key => $value) {
                        $compl = ComplaintModel::find()->where(['complaint_order' => $value['complaint_order']])->one();
                        if ($compl->is_expedited == 1) {
                            $compl->last_processing_time = ComplaintModel::getProcessingTime(1); //最晚处理时间
                            $compl->expedited_time = date("Y-m-d H:s:i"); //加急时间
                            $compl->expedited_user = Yii::$app->user->identity->user_name; //加急操作人   
                        } else {
                            $compl->last_processing_time = ComplaintModel::getProcessingTime(2); //最晚处理时间
                        }
                        $compl->status = 1;
                        $compl->audit_time = date('Y-m-d H:i:s');
                        $compl->auditer = Yii::$app->user->identity->user_name;
                        $compl->save();
                    }
                    $recder = true;
                } else {
                    foreach ($data as $vv) {
                        WarehouseprocessingModel::updateAll(['status' => '-1'], ['id' => $vv['id']]);
                        ComplaintModel::updateAll(['status'=>2],['complaint_order'=>$vv['complaint_order']]);
                    }
                    $recder = false;
                }
            }
            if ($state == 2 && !empty($res)) {
                $urls = $host_url['us'] . "/Api/Order/CustomerComplain/add";
                $post_data = json_encode($res);
                $ru = ComplaintModel::curlPost($urls, $post_data);
                $re = json_decode($ru, true);
                if ($re['code'] == 1) {
                    foreach ($res as $key => $value) {
                       $compl = ComplaintModel::find()->where(['complaint_order' => $value['complaint_order']])->one();
                        if ($compl->is_expedited == 1) {
                            $compl->last_processing_time = ComplaintModel::getProcessingTime(1); //最晚处理时间
                            $compl->expedited_time = date("Y-m-d H:s:i"); //加急时间
                            $compl->expedited_user = Yii::$app->user->identity->user_name; //加急操作人   
                        } else {
                            $compl->last_processing_time = ComplaintModel::getProcessingTime(2); //最晚处理时间
                        }
                        $compl->status = 1;
                        $compl->audit_time = date('Y-m-d H:i:s');
                        $compl->auditer = Yii::$app->user->identity->user_name;
                        $compl->save();
                    }
                    $usdcder = true;
                } else {
                    foreach ($res as $vv) {
                        WarehouseprocessingModel::updateAll(['status' => '-1'], ['id' => $vv['id']]);
                        ComplaintModel::updateAll(['status'=>2],['complaint_order'=>$vv['complaint_order']]);
                    }
                }
                $usdcder = false;
            }

            if ($recder || $usdcder) {
                return json_encode(['state' => 1, 'msg' => '推送成功']);
            }
            if (!$recder || !$usdcder) {
                return json_encode(['state' => 1, 'msg' => '推送失败']);
            }
        }
    }

    /*     * *
     * 确认
     * * */

    public function actionGetconfirm() {
        $this->isPopup = true;
        $complaint_order = $this->request->getQueryParam('complaint_order');
        $complaint_order_id = ComplaintModel::find()->where(['complaint_order' => $complaint_order])->asArray()->one();
        $complaint_processing = WarehouseprocessingModel::find()->where(['complaint_order_id' => $complaint_order_id['id']])
                        ->orderBy('add_time desc')->asArray()->one();
        if (Yii::$app->request->isPost) {
            $status = $this->request->post('status'); //6完结 5重新推仓库处理
            $remark = $this->request->post('remark'); //原因
            if (empty($status)) {
                $this->_showMessage('请选择结果', true, null, false, null);
            }
            if (empty($remark)) {
                $this->_showMessage('请输入原因', true, null, false, null);
            }

            //记录操作日志
            $complaintlog = new ComplaintlogModel();
            $complaintlog->complaint_order_id = $complaint_order_id['id'];
            $complaintlog->create_user = Yii::$app->user->identity->user_name;
            $complaintlog->operational_time = $time;
            $complaintlog->remark = $remark;
            if ($status == 6) {
                $complaintlog->operational_action = "完结";
                $complaintlog->operational_time = date('Y-m-d H:i:s');
                $data = [
                    [
                        'result' => 1,
                        'result_description' => $remark,
                        'operator' => Yii::$app->user->identity->user_name,
                        'complaint_order' => $complaint_order,
                        'id' => $complaint_processing['id'],
                        'new_id' => 0
                    ],
                ];

                $post_data = json_encode($data);
                //获取接口地址
                $url = ComplaintModel::getcomplainWarehouse($complaint_order_id['warehouse_id'], 3);
                if (empty($url)) {
                    $this->_showMessage('接口地址不存在', true, null, false, 'layer.closeAll()');
                }

                //调用仓库系统api
                $res = ComplaintModel::curlPost($url, $post_data);
                $res = json_decode($res, true);
                if ($res['code'] == 1) {
                    $total_time = time() - strtotime($complaint_order_id['audit_time']);
                    ComplaintModel::updateAll(['status' => 6, 'confirm_time' => date("Y-m-d H:i:s"), 'total_time' => $total_time], ['complaint_order' => $complaint_order]);
                    WarehouseprocessingModel::updateAll(['status' => 3, 'confirm_time' => date("Y-m-d H:i:s"), 'confirm_user' => Yii::$app->user->identity->user_name], ['id' => $res['data']['success_list'][0]]);
                    $complaintlog->save();
                    $this->_showMessage('处理成功', true, null, true, 'top.window.location.reload()');
                } else {
                    $this->_showMessage('失败成功', true, null, false, null);
                }
            }
            //重新推送给仓库
            if ($status == 5) {
                $res = new WarehouseprocessingModel();
                $res->complaint_order_id = $complaint_order_id['id'];
                $res->create_user = Yii::$app->user->identity->user_name;
                $res->add_time = date('Y-m-d H:i:s');
                $res->save();
                $id = $res->attributes['id']; //自增长ID
                $data = [
                    [
                        'result' => 2,
                        'result_description' => $remark,
                        'operator' => Yii::$app->user->identity->user_name,
                        'complaint_order' => $complaint_order,
                        'id' => $complaint_processing['id'],
                        'new_id' => $id,
                    ],
                ];
                $post_data = json_encode($data);
                //获取接口地址
                $url = ComplaintModel::getcomplainWarehouse($complaint_order_id['warehouse_id'], 3);
                if (empty($url)) {
                    $this->_showMessage('接口地址不存在', true, null, false, 'layer.closeAll()');
                }
                //调用仓库系统api
                $res = ComplaintModel::curlPost($url, $post_data);

                $res = json_decode($res, true);
                if ($res['code'] == 1) {
                    WarehouseprocessingModel::updateAll(['confirm_time' => date("Y-m-d H:i:s")], ['id' => $complaint_processing['id']]);
                    $complaintss = WarehouseprocessingModel::find()->where(['id' => $res['data']['success_list'][0]])->one();
                    ComplaintModel::updateAll(['status' => 5], ['id' => $complaintss->complaint_order_id]);
                    $complaintss->status = 2;
                    $complaintss->confirm_time = date("Y-m-d H:i:s");
                    $complaintss->confirm_user = Yii::$app->user->identity->user_name;
                    $complaintss->save();
                    $complaintlog->operational_action = "推送给仓库";
                    $complaintlog->operational_time = date('Y-m-d H:i:s');
                    $complaintlog->save();
                    $this->_showMessage('重新推送成功', true, null, true, 'top.window.location.reload()');
                } else {
                    $complaintss = WarehouseprocessingModel::find()->where(['id' => $res['data']['fail_list'][0]])->asArray()->one();
                    ComplaintModel::updateAll(['status' => 4], ['id' => $complaintss['complaint_order_id']]);
                    $complaintlog->operational_action = "重新推送给仓库失败";
                    $complaintlog->save();
                    $this->_showMessage('重新推送失败', true, null, false, null);
                }
            }
        }


        return $this->render('confirm', ['complaint_order' => $complaint_order]);
    }

    /**
     * 批量确认
     * * */
    public function actionGetconfirmall() {
        $id = $this->request->post('ids');
        $status = $this->request->post('status');
        $remark = $this->request->post('remark');
    }

    /*     * *
     * 加急
     * * */

    public function actionGeturgent() {
        $complaint_order = $this->request->post('id');
        $complaint_order_id = ComplaintModel::find()->where(['complaint_order' => $complaint_order])->asArray()->one();
        $complaint_processing = WarehouseprocessingModel::find()->where(['complaint_order_id' => $complaint_order_id['id']])
                        ->orderBy('add_time desc')->asArray()->one();
        $time = date('Y-m-d H:s:i');
        //记录操作日志
        $complaintlog = new ComplaintlogModel();
        $complaintlog->complaint_order_id = $complaint_order_id['id'];
        $complaintlog->create_user = Yii::$app->user->identity->user_name;
        $complaintlog->operational_time = $time;
        $complaintlog->operational_action = "加急推送";
        $complaintlog->remark = '';
        $data = [
            [
                'complaint_order' => $complaint_order,
                'operator' => Yii::$app->user->identity->user_name,
                'expedite_time' => $time,
                'id' => $complaint_processing['id']
            ],
        ];
        $post_data = json_encode($data);
        //获取接口地址
        $url = ComplaintModel::getcomplainWarehouse($complaint_order_id['warehouse_id'], 2);

        if (empty($url)) {
            $this->_showMessage('接口地址不存在', true, null, false, 'layer.closeAll()');
        }

        //调用仓库系统api
        $res = ComplaintModel::curlPost($url, $post_data);
        $res = json_decode($res, true);
        if ($res['code'] == 1) {
            ComplaintModel::updateAll(['is_expedited' => 1, 'expedited_time' => $time, 'expedited_user' => Yii::$app->user->identity->user_name], ['complaint_order' => $complaint_order]);
            $complaintlog->save();
            return json_encode(['state' => 1, 'msg' => '加急成功']);
        } else {
            return json_encode(['state' => 0, 'msg' => '加急失败']);
        }
    }

    /*     * *
     * 删除
     * * */

    public function actionGetdelete() {
        $complaint_order = $this->request->post('id');
        $res = ComplaintModel::getdelete($complaint_order);
        return $res;
    }

    /**
     * 批量删除
     * * */
    public function actionDelall() {

        $id = $this->request->post('id');
        $ids = explode(',', $id);
        $connection = Yii::$app->db->beginTransaction();
        try {
            ComplaintModel::deleteAll(['in', 'id', $id]);
            ComplaintdetailModel::deleteAll(['in', 'complaint_order_id', $id]);
            $connection->commit();
            return json_encode(['state' => 1, 'msg' => '删除成功']);
        } catch (Exception $exc) {
            $connection->rollBack();
            return json_encode(['state' => 0, 'msg' => '删除失败']);
        }
    }

    /*
     * 修改
     * */

    public function actionGetedit() {
        set_time_limit(0);
        $this->isPopup = true;
        $orderId = $this->request->getQueryParam('order_id');
        $platform = $this->request->getQueryParam('platform');
        $complaint_order = $this->request->getQueryParam('complaint_order');

        //订单信息
        $orderinfo = [];
        if (empty($platform)) {
            $this->_showMessage('平台CODE无效', false, null, false, null, 'layer.closeAll()');
        }
        if (empty($orderId)) {
            $this->_showMessage('订单号无效', false, null, false, null, 'layer.closeAll()');
        }
        $orderinfo = OrderKefu::getOrderStackByOrderId($platform, '', $orderId);
        if (empty($orderinfo)) {
            $this->_showMessage('找不到对应订单', false, null, false, null, 'layer.closeAll()');
        }
        //查找订单对应的买家账号
        $accountname = Account::find()->select('account_name')->where(['platform_code' => $platform])->andWhere(['old_account_id' => $orderinfo->info->account_id])->asArray()->one();
        $order_amount = isset($orderinfo->info) && isset($orderinfo->info->total_price) ? $orderinfo->info->total_price : 0.00;
        $allow_refund_amount = AfterSalesRefund::getAllowRefundAmount($orderId, $order_amount, $platform);
        $datas = ['orderId' => $orderId, 'platformCode' => $platform];
        $orderinfo = Json::decode(Json::encode($orderinfo), true);
        $countires = Country::getCodeNamePairs();
        $warehouseList = Warehouse::getWarehouseList();
        $warehouseList_new = [];
        $warehouseList_new[' '] = '请选择发货仓库';
        foreach ($warehouseList as $key => $value) {
            $warehouseList_new[$key] = $value;
        }
        $logistics = Logistic::getWarehouseLogistics($orderinfo['info']['warehouse_id']);
        $reasonList = RefundReturnReason::getList();

        $reasonCodeList = ComplaintModel::getreason($platform);

        //加黑名单临时解决方案  黄玲，汪成荣，方超和胡丽玲
        $isAuthority = false;
        if (UserRole::checkManage(Yii::$app->user->identity->id)) {
            $isAuthority = true;
        }

        $departmentList = BasicConfig::getParentList(52);
        $departmentList_new = [];
        foreach ($departmentList as $k => &$v) {
            $departmentList_new[$k]['depart_id'] = $k;
            $departmentList_new[$k]['depart_name'] = $v;
        }
        //编辑客诉类型
        $basic = BasicConfigModel::find()->select('name')->where(['parent_id' => 131])->all();
        $data = ComplaintModel::getComplaindatas($complaint_order);

        return $this->render('edit', [
                    'info' => $orderinfo,
                    'countries' => $countires,
                    'platform' => $platform,
                    'warehouseList' => $warehouseList_new,
                    'logistics' => $logistics,
                    'departmentList' => json_encode($departmentList_new),
                    'reasonList' => $reasonList,
                    'reasonCodeList' => $reasonCodeList,
                    'allow_refund_amount' => $allow_refund_amount,
                    'currencyCode' => isset($orderinfo['info']['currency']) ? $orderinfo['info']['currency'] : '',
                    'isAuthority' => $isAuthority,
                    'accountName' => $accountname['account_name'],
                    'basic' => $basic,
                    'data' => $data,
        ]);
    }

    public function actionGetupload() {
        try {
            $model = new Upload();
            $info = $model->upImage();


            $info && is_array($info) ?
                            exit(Json::htmlEncode($info)) :
                            exit(Json::htmlEncode([
                                        'code' => 1,
                                        'msg' => 'error'
            ]));
        } catch (\Exception $e) {
            exit(Json::htmlEncode([
                        'code' => 1,
                        'msg' => $e->getMessage()
            ]));
        }
    }

}

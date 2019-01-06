<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/27 0027
 * Time: 下午 6:23
 */

namespace app\modules\mails\controllers;

use app\common\VHelper;
use app\components\Controller;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\EbayFeedback;
use app\modules\mails\models\EbayFeedbackResponse;
use app\modules\orders\models\Logistic;
use app\modules\orders\models\Order;
use app\modules\orders\models\OrderEbay;
use app\modules\orders\models\OrderRemarkKefu;
use app\modules\orders\models\Warehouse;
use app\modules\services\modules\ebay\models\LeaveFeedback;
use app\modules\systems\models\BasicConfig;
use app\modules\mails\models\EbayInboxSubject;
use app\modules\accounts\models\UserAccount;
use app\modules\systems\models\Country;
use yii\helpers\Json;
use Yii;
use app\modules\reports\models\FeedbackStatistics;

class EbayfeedbackController extends Controller
{
    /**
     * @desc ebay评价列表
     * @return \yii\base\string
     * @throws \yii\base\InvalidConfigException
     */
    public function actionList()
    {
        $model = new EbayFeedback();
        $params = \Yii::$app->request->getBodyParams();

        $dataProvider = $model->searchList($params);

        $departmentList = BasicConfig::getParentList(52);

        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'departmentList' => $departmentList,
        ]);
    }


    public function actionGetrepliedmsg()
    {
        $feedbackId = \Yii::$app->request->get('feedback_id');


        $data = EbayFeedbackResponse::find()->select('response_text')->where('feedback_id = :feid and status = :status', [':feid' => $feedbackId, ':status' => 1])->one();

        echo Json::encode($data->response_text);

    }

    public function actionMark()
    {
        if ($this->request->getIsAjax()) {
            $id = (int)\Yii::$app->request->getQueryParam('id');
            $ids = $this->request->getBodyParam('ids');
            if (empty($ids)) {
                $ids[] = $id;
            }
        }
        if ($ids) {
            foreach ($ids as $id) {
                $model = EbayFeedback::findById($id);
                if (empty($model))
                    $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
                $flag = true;
                //评价回复处理
                $feedbackStatistics = FeedbackStatistics::findOne(['feedback_id'=>$model->feedback_id,'platform_code'=>Platform::PLATFORM_CODE_EB]);
                if($feedbackStatistics && $feedbackStatistics->status == 0){
                    $feedbackStatistics->status = 1;
                    $feedbackStatistics->save(false);
                }
                if ($model->status == 0) {
                    $model->status = 1;
                    $flag = $model->save();
                }
                continue;
            }
        }
        if ($flag == true) {
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/ebayfeedback/list') . '");');
        } else {
            $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
        }
    }

    public function actionReplyback()
    {
        $this->isPopup = true;

        // 订单id
        $order_id = trim($this->request->get('order_id'));

        $order = new Order();
        $platform = Platform::PLATFORM_CODE_EB;
        $info = $order->getOrderByPlatformOrderID($platform, $order_id);

        $model = new EbayFeedback();

        if (!isset($info->ack) || $info->ack != true)
            $this->_showMessage('未获取到订单信息', false);

        $platform_order_id = $info->order->platform_order_id;

        $itemIds = array();
        $itemInfos = array();
        foreach ($info->order->items as $item) {
            $itemIds[$item->item_id] = $item->sku;
            $itemInfos[$item->item_id]['sku'] = $item->sku;
            $itemInfos[$item->item_id]['item_id'] = $item->item_id;
            $itemInfos[$item->item_id]['sale_price'] = $item->sale_price;
            $itemInfos[$item->item_id]['currency'] = $item->currency;
            $itemInfos[$item->item_id]['transaction_id'] = $item->transaction_id;
        }

        if ($this->request->getIsAjax()) {
            // 获取erp 帐号数据
            $account_info = Account::getHistoryAccountInfo($info->order->account_id, $platform);
            if ($account_info == false)
                $this->_showMessage('找不到帐号信息', false);

            $account_name = $account_info->account_name;
            $account_id = $account_info->id;
            $post_data = $this->request->post();
            // 填充feedback数据
            $model->item_id = $post_data['EbayFeedback']['item_id'];
            $model->comment_text = $post_data['EbayFeedback']['comment_text'];
            $model->commenting_user = $account_name;
            $model->comment_time = date('Y-m-d H:i:s');
            $model->comment_type = 4;
            $model->item_price = $itemInfos[$model->item_id]['sale_price'];
            $model->currency = $itemInfos[$model->item_id]['currency'];
            $model->role = 2;
            $model->transaction_id = $itemInfos[$model->item_id]['transaction_id'];
            $model->order_line_item_id = $info->order->platform_order_id;
            $model->account_id = $account_id;

            $leaveFeedbackModel = new LeaveFeedback($model, $info->order->buyer_id);
            $result = $leaveFeedbackModel->handleResponse();
            $result = simplexml_load_string($result);
            if ($result->Ack == 'Success') {
                $model->comment_time = date('Y-m-d H:i:s', strtotime($result->Timestamp));
                $model->feedback_id = $result->FeedbackID;
                $model->save();
                $this->_showMessage('操作成功！', true, null, false, null);
            } else {
                $this->_showMessage(\Yii::t('system', 'Operate Failed') . $result->Errors->ShortMessage, false);
            }

        }
        return $this->render('add', ['model' => $model, 'itemIds' => $itemIds, 'platform' => $platform, 'platform_order_id' => $platform_order_id]);

    }

    /**
     * 跟进状态  纠纷原因设置
     * @author allen <2018-02-13>
     */
    public function actionSetreason()
    {
        $request = Yii::$app->request->post();
        $id = $request['id'];
        $typeId = $request['type_id'];
        $return_arr = ['status' => 1, 'info' => '操作成功!'];
        
        switch ($typeId) {
            //设置纠纷原因
            case 1:
                $departmentId = $request['department_id'][0];
                $reasonId = $request['reason_id'][0];
                //判断原因必选
                if (empty($departmentId) || empty($reasonId)) {
                    echo json_encode(['status' => 0, 'info' => '请选择责任所属部门和原因类型']);
                    die;
                }
                $res = EbayFeedback::updateAll(['department_id' => $departmentId, 'reason_id' => $reasonId], 'id = :id', [':id' => $id]);
                if ($res === false) {
                    $return_arr = ['status' => 0, 'info' => '设置纠纷原因失败!'];
                }
                break;

            //跟进状态
            case 2:
                $stepId = $request['step_id'];
                $text = $request['text'];
                $model = EbayFeedback::find()->where(['id' => $id])->one();
                $send_link_time = $model->send_link_time;
                //判断跟进状态必选
                if (empty($stepId)) {
                    echo json_encode(['status' => 0, 'info' => '请选择跟进状态']);
                    die;
                }
                //更新操作
                $updateData = ['step_id' => $stepId, 'remark' => $text];
                //如果是已发送链接操作 则记录发送链接时间(只记录第一次发送链接时间)
                if (in_array($stepId, array(23, 24)) && empty($send_link_time)) {
                    $updateData['send_link_time'] = date("Y-m-d H:i:s");
                }

                //如果是已修改操作 则清楚发送链接时间
                if ($stepId == 25) {
                    $updateData['send_link_time'] = "";
                }

                //如果是已移除操作 则评价等级改为5：Withdrawn(已撤销)
                if ($stepId == 31) {
                    $updateData['comment_type'] = 5;
                }

                $res = EbayFeedback::updateAll($updateData, 'id=:id', [':id' => $id]);
                if ($res === false) {
                    $return_arr = ['status' => 0, 'info' => '更新跟进状态失败!'];
                }
                break;
        }

        echo json_encode($return_arr);
        die;
    }

    /**
     * 导出ebay评价数据
     */
    public function actionExport()
    {
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        //获取get参数
        $get = YII::$app->request->get();
        $ids = !empty($get['ids']) ? $get['ids'] : [];
        $data = [];

        //只能查询到客服绑定账号的回复
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);

        if (is_array($ids) && !empty($ids)) {
            //取出选中的评价数据
            $data = EbayFeedback::find()
                ->select('f.*, a.account_name')
                ->alias('f')
                ->leftJoin('{{%account}} as a', 'a.id = f.account_id')
                ->andWhere(['in', 'f.id', $ids])
                ->andWhere(['in', 'f.account_id', $accountIds])
                ->asArray()
                ->all();
        } else {
            //取出筛选的评价数据
            $query = EbayFeedback::find()
                ->select('f.*, a.account_name')
                ->alias('f')
                ->leftJoin('{{%account}} as a', 'a.id = f.account_id')
                ->andWhere(['in', 'f.account_id', $accountIds]);

            //添加表单的筛选条件
            if (!empty($get['feedback_id'])) {
                $query->andWhere(['f.feedback_id' => $get['feedback_id']]);
            }
            if (!empty($get['commenting_user'])) {
                $query->andWhere(['like', 'f.commenting_user', "%{$get['commenting_user']}%"]);
            }
            if (!empty($get['comment_type'])) {
                $query->andWhere(['f.comment_type' => $get['comment_type']]);
            }
            if (!empty($get['item_id'])) {
                $query->andWhere(['f.item_id' => $get['item_id']]);
            }
            if (isset($get['status']) && $get['status'] != '') {
                $query->andWhere(['f.status' => $get['status']]);
            }
            if (!empty($get['department_id'])) {
                $query->andWhere(['f.department_id' => $get['department_id']]);
            }
            if (!empty($get['reason_id'])) {
                $query->andWhere(['f.reason_id' => $get['reason_id']]);
            }
            if (!empty($get['step_id'])) {
                $query->andWhere(['f.step_id' => $get['step_id']]);
            }
            if (!empty($get['account_id'])) {
                $query->andWhere(['f.account_id' => $get['account_id']]);
            }
            if (!empty($get['order_line_item_id'])) {
                $query->andWhere(['f.order_line_item_id' => $get['order_line_item_id']]);
            }
            if (!empty($get['sku'])) {
                //通过sku查询item_id
                $itemIds = Order::getEbayFeedBackItemIdBySku([
                    'sku' => $get['sku'],
                ]);

                if (!empty($itemIds)) {
                    $query->andWhere(['in', 'f.item_id', $itemIds]);
                }
            }
            if (!empty($get['start_time']) && !empty($get['end_time'])) {
                $query->andWhere(['between', 'f.comment_time', $get['start_time'], $get['end_time']]);
            } else if (!empty($get['start_time'])) {
                $query->andWhere(['>=', 'f.comment_time', $get['start_time']]);
            } else if (!empty($get['end_time'])) {
                $query->andWhere(['<=', 'f.comment_time', $get['end_time']]);
            }
            //添加隐藏查询条件
            if (!empty($get['hidden_val'])) {
                switch ($get['hidden_val']) {
                    case 1://纠纷超过30天条件
                        $query->andWhere("DATE_FORMAT(now(),'%Y-%m-%d %H:%i:%s') > DATE_FORMAT(date_add(DATE_FORMAT(f.comment_time,'%Y-%m-%d %H:%i:%s'), INTERVAL 1 MONTH),'%Y-%m-%d %H:%i:%s')");
                        break;
                    case 2://改评链接发送5天未修改
                        $query->andWhere("DATE_FORMAT(now(),'%Y-%m-%d %H:%i:%s') > DATE_FORMAT(date_add(DATE_FORMAT(f.send_link_time,'%Y-%m-%d %H:%i:%s'), INTERVAL 5 day),'%Y-%m-%d %H:%i:%s')");
                        $query->andWhere("DATE_FORMAT(now(),'%Y-%m-%d %H:%i:%s') <= DATE_FORMAT(date_add(DATE_FORMAT(f.send_link_time,'%Y-%m-%d %H:%i:%s'), INTERVAL 8 day),'%Y-%m-%d %H:%i:%s')");
                        break;
                    case 3://改评链接发送8天未修改
                        $query->andWhere("DATE_FORMAT(now(),'%Y-%m-%d %H:%i:%s') > DATE_FORMAT(date_add(DATE_FORMAT(f.send_link_time,'%Y-%m-%d %H:%i:%s'), INTERVAL 8 day),'%Y-%m-%d %H:%i:%s')");
                        $query->andWhere("DATE_FORMAT(now(),'%Y-%m-%d %H:%i:%s') <= DATE_FORMAT(date_add(DATE_FORMAT(f.send_link_time,'%Y-%m-%d %H:%i:%s'), INTERVAL 10 day),'%Y-%m-%d %H:%i:%s')");
                        break;
                    case 4://超时未修改(超过10天)
                        $query->andWhere("DATE_FORMAT(now(),'%Y-%m-%d %H:%i:%s') > DATE_FORMAT(date_add(DATE_FORMAT(f.send_link_time,'%Y-%m-%d %H:%i:%s'), INTERVAL 10 day),'%Y-%m-%d %H:%i:%s')");
                        break;
                }
            }

            $data = $query->asArray()->all();


        }

        $platformOrderIds = [];
        $transactionIds = [];
        $itemIds = [];
        if (!empty($data)) {
            foreach ($data as $item) {
                $platformOrderIds[] = $item['order_line_item_id'];
                $itemIds[] = $item['item_id'];
                $transactionIds[]  = $item['transaction_id'];
            }

            $platformOrderIds = array_unique($platformOrderIds);
            $itemIds = array_unique($itemIds);
            $transactionIds = array_unique($transactionIds);
        }

        //获取订单信息
        $result = Order::getEbayFeedBackOrderInfos([
            'platformCode' => Platform::PLATFORM_CODE_EB,
            'platformOrderIds' => implode(',', $platformOrderIds),
            'transactionIds' => implode(',', $transactionIds),
            'itemIds' => implode(',', $itemIds),
        ]);

        if (empty($result)) {
            $this->_showMessage('数据为空', false);
        }

        $orders = $result['order'];
        $trans = $result['trans'];

        //获取所有配置数据
        $allConfig = BasicConfig::getAllConfigData();

        //标题数组
        $fieldArr = [
            '帐号',
            '付款时间',
            '系统订单状态',
            '发货时间',
            '发货仓库',
            '邮寄方式',
            '责任部门',
            '差评原因',
            '评价ID',
            '评论用户',
            '系统订单号',
            '产品中文名称',
            'SKU',
            '跟进状态',
            '用户评分',
            '评论内容',
            '评价级别',
            '改评链接发送时间',
            '产品价格',
            '是否回复',
            'ItemID',
            '评论时间',
            '是否有站内消息',
            '备注',
            '发货仓库',
            '发货方式',
            '目的国',
            'Item Location',
            '付款时间',
            '订单备注'

        ];

        //导出数据数组
        $dataArr = [];
        $order_id_arr=[];
        $platform_order_id_arr=[];
        foreach ($data as &$v){
            $v['order_id']=OrderEbay::getOrderId($v['order_line_item_id']);
            $order_id_arr[]=$v['order_id'];
            $platform_order_id_arr[]=$v['order_line_item_id'];
        }
        $extra_info    = OrderEbay::getExtraInfo($platform_order_id_arr);
//        var_dump($extra_info);die;
        $warehouseList = Warehouse::getAllWarehouseList(true);
        $countryList   = Country::getCodeNamePairs('cn_name');
        $remarks='';
        $order_remark_arr = OrderRemarkKefu::getOrderRemarksByArray($order_id_arr);
        foreach ($data as &$v) {
            foreach ($order_remark_arr as $v2){
                if($v2['order_id']==$v['order_id']){
                    $remarks .=$v2['remark']."-";
                }
            }
            $v['remarks']=rtrim($remarks,'-');

            foreach ($extra_info as $value) {
                if ($value['platform_order_id'] == $v['order_line_item_id']) {
                    $v['warehouse_id'] = $value['warehouse_id'];
                    $v['ship_code']    = $value['ship_code'];
                    $v['ship_country'] = $value['ship_country'];
                    $v['shipped_date'] = $value['shipped_date'];
                    $v['location']     = $value['location'];
                    $v['warehouse']    = isset($v['warehouse_id']) && (int)$v['warehouse_id'] > 0 ? $warehouseList[$v['warehouse_id']] : null;  //发货仓库
                    $v['logistics']    = isset($v['ship_code']) ? Logistic::getSendGoodsWay($v['ship_code']) : null; //发货方式
                    $v['ship_country'] = $v['ship_country'] . (array_key_exists($v['ship_country'], $countryList)
                            ? '(' . $countryList[$v['ship_country']] . ')' : '');
                    $v['pay_time']     = $value['paytime'];

                }
            }
            if (empty($v['warehouse'])) {
                $v['warehouse'] = '';
            }
            if (empty($v['logistics'])) {
                $v['logistics'] = '';
            }
            if (empty($v['ship_country'])) {
                $v['ship_country'] = '';
            }
            if (empty($v['location'])) {
                $v['location'] = '';
            }
            if (empty($v['pay_time'])) {
                $v['pay_time'] = '';
            }
        }
        foreach ($data as $item) {
            //回复状态
            $item['status_name'] = '';
            switch ($item['status']) {
                case 0:
                    $item['status_name'] = '未回复';
                    break;
                case 1:
                    $item['status_name'] = '标记回复';
                    break;
                case 2:
                    $item['status_name'] = '已回复';
                    break;
            }

            //是否有站内信
            $inboxSubject = EbayInboxSubject::find()->where([
                'buyer_id' => $item['commenting_user'],
                'item_id' => $item['item_id'],
                'account_id' => $item['account_id'],
            ])->exists();

            //产品价格
            $price = '';
            if (!empty($item['item_price']) && !empty($item['currency'])) {
                $price = $item['item_price'] . $item['currency'];
            } else {
                if (array_key_exists($item['order_line_item_id'], $orders)) {
                    $price = $orders[$item['order_line_item_id']]['sale_price'] . $orders[$item['order_line_item_id']]['currency'];
                }
            }

            //导出数据数组
            $dataArr[] = [
                $item['account_name'],
                array_key_exists($item['order_line_item_id'], $orders) ? $orders[$item['order_line_item_id']]['paytime'] : (array_key_exists($item['transaction_id'], $trans) ? $trans[$item['transaction_id']]['paytime'] : ''),
                array_key_exists($item['order_line_item_id'], $orders) ? $orders[$item['order_line_item_id']]['complete_status_text'] : (array_key_exists($item['transaction_id'], $trans) ? $trans[$item['transaction_id']]['complete_status_text'] : ''),
                array_key_exists($item['order_line_item_id'], $orders) ? $orders[$item['order_line_item_id']]['shipped_date'] : (array_key_exists($item['transaction_id'], $trans) ? $trans[$item['transaction_id']]['shipped_date'] : ''),
                array_key_exists($item['order_line_item_id'], $orders) ? $orders[$item['order_line_item_id']]['warehouse_name'] : (array_key_exists($item['transaction_id'], $trans) ? $trans[$item['transaction_id']]['warehouse_name'] : ''),
                array_key_exists($item['order_line_item_id'], $orders) ? $orders[$item['order_line_item_id']]['ship_code_name'] : (array_key_exists($item['transaction_id'], $trans) ? $trans[$item['transaction_id']]['ship_code_name'] : ''),
                array_key_exists($item['department_id'], $allConfig) ? $allConfig[$item['department_id']] : '未设置',
                array_key_exists($item['reason_id'], $allConfig) ? $allConfig[$item['reason_id']] : '未设置',
                $item['feedback_id'],
                $item['commenting_user'],
                array_key_exists($item['order_line_item_id'], $orders) ? $orders[$item['order_line_item_id']]['order_id'] : (array_key_exists($item['transaction_id'], $trans) ? $trans[$item['transaction_id']]['order_id'] : ''),
                array_key_exists($item['order_line_item_id'], $orders) ? $orders[$item['order_line_item_id']]['picking_name'] : (array_key_exists($item['transaction_id'], $trans) ? $trans[$item['transaction_id']]['picking_name'] : ''),
                array_key_exists($item['order_line_item_id'], $orders) ? $orders[$item['order_line_item_id']]['sku'] : (array_key_exists($item['transaction_id'], $trans) ? $trans[$item['transaction_id']]['sku'] : ''),
                array_key_exists($item['step_id'], $allConfig) ? $allConfig[$item['step_id']] : '未跟进',
                $item['commenting_user_score'],
                VHelper::removeEmoji($item['comment_text']),
                array_key_exists($item['comment_type'], EbayFeedback::$commentTypeMap) ? EbayFeedback::$commentTypeMap[$item['comment_type']] : '',
                $item['send_link_time'],
                $price,
                $item['status_name'],
                $item['item_id'],
                $item['comment_time'],
                $inboxSubject ? '有' : '暂无',
                $item['remark'],
                $item['warehouse'],
                $item['logistics'],
                $item['ship_country'],
                $item['location'],
                $item['pay_time'],
                $item['remarks'],

            ];
        }

        VHelper::exportExcel($fieldArr, $dataArr, 'ebayfeedback_' . date('Y-m-d'));
    }
}

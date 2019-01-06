<?php

namespace app\modules\orders\models;
use app\modules\mails\models\AliexpressEvaluateList;
use app\modules\mails\models\AmazonFeedBack;
use app\modules\mails\models\AmazonReviewData;
use app\modules\products\models\WalmartSellerSkuCost;
use Yii;
use app\modules\logistics\models\LogisticsKefu;
use app\modules\warehouses\models\WarehouseKefu;
use app\modules\users\models\OrderserviceKefu;
use app\modules\aftersales\models\AfterSalesProduct;
use app\components\Model;
use app\modules\accounts\models\Platform;
use app\common\MHelper;
use yii\db\Query;
use yii\db\ActiveQuery;
use app\common\VHelper;
use yii\helpers\Json;
use app\modules\aftersales\models\AfterSaleTotalStatistics;

/**
 * 订单的所有信息从客服系统从库获取，优化访问速度
 */
class OrderKefu extends Model {

    //普通订单
    const ORDER_TYPE_NORMAL = 1;
    //合并后的订单
    const ORDER_TYPE_MERGE_MAIN = 2;
    //被合并的订单
    const ORDER_TYPE_MERGE_RES = 3;
    //拆分的主订单
    const ORDER_TYPE_SPLIT_MAIN = 4;
    //拆分后的子订单
    const ORDER_TYPE_SPLIT_CHILD = 5;
    //被重寄的订单
    const ORDER_TYPE_REDIRECT_MAIN = 6;
    //重寄后的订单
    const ORDER_TYPE_REDIRECT_ORDER = 7;
    //客户补款的订单
    const ORDER_TYPE_REPAIR_ORDER = 8;
    //初始化状态
    const COMPLETE_STATUS_INIT = 0;
    //正常订单
    const COMPLETE_STATUS_NORMAL = 1;
    //异常订单
    const COMPLETE_STATUS_ABNORMAL = 5;
    //缺货订单
    const COMPLETE_STATUS_STOCKOUT = 10;
    //已备货订单
    const COMPLETE_STATUS_GOODS_PREPARE = 13;
    //待发货订单
    const COMPLETE_STATUS_WAITTING_SHIP = 15;
    //超期订单
    const COMPLETE_STATUS_EXPIRED = 17;
    //部分发货订单
    const COMPLETE_STATUS_PARTIAL_SHIP = 19;
    //已发货订单
    const COMPLETE_STATUS_SHIPPED = 20;
    //暂扣订单
    const COMPLETE_STATUS_HOLD = 25;
    //部分退款订单
    //const COMPLETE_STATUS_PARTIAL_REFUND = 30;
    //全部退款订单
    //const COMPLETE_STATUS_ALL_REFUND = 35;
    //已取消订单
    const COMPLETE_STATUS_CANCELED = 40;
    //已完成订单
    const COMPLETE_STATUS_COMPLETED = 45;
    //通途处理订单
    const COMPLETE_STATUS_TONGTU = 99;
    //借用领用单确认收货状态
    const COMPLETE_STATUS_CONFIRM = 90;
    //借用领用单已经部分归还状态
    const COMPLETE_STATUS_DEPART_RETURN = 91;
    //借用领用单已经全部归还状态
    const COMPLETE_STATUS_ALL_RETURN = 92;

    //操作表名
    public static $table = '';
    //操作数据库
    public static $db = '';

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb() {
        return Yii::$app->{self::$db};
    }

    public static function tableName() {
        return self::$table;
    }
    
    /**
     * 产品状态列表
     * @return type
     * @author allen <2018-11-21>
     */
    public static function skuStatusList(){
        return [
            1 => '刚开发',
            8 => '待买样',
            9 => '待品检',
            10 => '拍摄中',
            12 => '修图中',
            2 => '编辑中',
            3 => '预上线',
            4 => '在售中',
            5 => '已滞销',
            6 => '待清仓',
            7 => '已停售',
            0 => '审核不通过',
            11 => '产品信息确认',
            14 => '设计审核中',
            15 => '文案审核中',
            16 => '文案主管终审中',
            17 => '试卖编辑中',
            18 => '试卖在售中',
            19 => '试卖文案终审中',
            20 => '预上线拍摄中',
            21 => '物流审核中',
            27 => '作图审核中',
            28 => '关务审核中',
            29 => '开发检查中'
        ];
    }

    /**
     * 通过交易ID获取订单信息
     */
    public static function getOrderStackByTransactionId($platformCode, $transactionId, $type = 1) {
        return self::_getOrderInfo([
                    'platformCode' => $platformCode,
                    'transactionId' => $transactionId,
                    'type' => $type,
        ]);
    }

    /**
     * 通过平台订单id和系统订单id获取订单信息
     */
    public static function getOrderStack($platformCode, $platformOrderId, $systemsOrderId = '', $type = 1, $accountId = '') {
        return self::_getOrderInfo([
                    'platformCode' => $platformCode,
                    'orderId' => $platformOrderId,
                    'systemOrderId' => $systemsOrderId,
                    'type' => $type,
                    'accountId' => $accountId,
        ]);
    }

    /**
     * 获取历史订单信息
     */
    public static function getHistoryOrders($platformCode, $buyerId, $email = '', $accountId = '', $platformOrderId = "") {
        return self::_getHistoryOrders([
                    'platformCode' => $platformCode,
                    'buyerId' => $buyerId,
                    'email' => $email,
                    'accountId' => $accountId,
                    'platformOrderId' => $platformOrderId,
        ]);
    }

    /**
     * 根据sku获取订单ids
     */
    public static function getOrderIdsBySku($platformCode, $sku) {
        if (empty($platformCode) || empty($sku)) {
            return [];
        }

        $platform = self::getOrderModel($platformCode);
        if (empty($platform)) {
            return [];
        }

        $result = self::model($platform->orderdetail)
                ->select('order_id')
                ->where(['sku' => $sku])
                ->asArray()
                ->all();

        if (empty($result)) {
            return [];
        }

        $orderIds = [];
        foreach ($result as $item) {
            $orderIds[] = $item['order_id'];
        }
        return $orderIds;
    }

    /**
     * 根据平台选择对于的订单模型
     */
    public static function getOrderModel($platformCode) {
        $model = null;
        switch ($platformCode) {
            case Platform::PLATFORM_CODE_ALI:
                $model = new OrderAliexpressKefu();
                break;
            case Platform::PLATFORM_CODE_AMAZON:
                $model = new OrderAmazonKefu();
                break;
            case Platform::PLATFORM_CODE_EB:
                $model = new OrderEbayKefu();
                break;
            case Platform::PLATFORM_CODE_WISH:
                $model = new OrderWishKefu();
                break;
            case Platform::PLATFORM_CODE_CDISCOUNT:
            case Platform::PLATFORM_CODE_SHOPEE:
            case Platform::PLATFORM_CODE_LAZADA:
            case Platform::PLATFORM_CODE_WALMART:
            case Platform::PLATFORM_CODE_OFFLINE:
            case Platform::PLATFORM_CODE_MALL:
            case Platform::PLATFORM_CODE_JOOM:
            case Platform::PLATFORM_CODE_PF:
            case Platform::PLATFORM_CODE_BB:
            case Platform::PLATFORM_CODE_DDP:
            case Platform::PLATFORM_CODE_STR:
            case Platform::PLATFORM_CODE_JUM:
            case Platform::PLATFORM_CODE_JET:
            case Platform::PLATFORM_CODE_GRO:
            case Platform::PLATFORM_CODE_DIS:
            case Platform::PLATFORM_CODE_SPH:
            case Platform::PLATFORM_CODE_INW:
            case Platform::PLATFORM_CODE_JOL:
            case Platform::PLATFORM_CODE_SOU:
            case Platform::PLATFORM_CODE_PM:
            case Platform::PLATFORM_CODE_WADI:
            case Platform::PLATFORM_CODE_OBERLO:
            case Platform::PLATFORM_CODE_WJFX:
            case Platform::PLATFORM_CODE_ALIXX:
            case Platform::PLATFORM_CODE_TOP:
            case Platform::PLATFORM_CODE_VOVA:
                $model = new OrderOtherKefu();
                break;
            default:
                break;
        }
        return $model;
    }

    /**
     * 根据表名返回模型
     */
    public static function model($tableName = '', $db = 'db_order') {
        if (empty($tableName)) {
            return false;
        }
        $tableName = trim($tableName, '{{%}}');
        self::$table = '{{%' . $tableName . '}}';
        self::$db = $db;

        return Yii::createObject(ActiveQuery::className(), [get_called_class(), ['from' => [static::tableName()]], ['getDb' => static::getDb()]]);
    }

    /**
     * @param $key 返回订单完成状态
     * @return array|string
     */
    public static function getOrderCompleteStatus($key) {
        $complete_status = array(
            self::COMPLETE_STATUS_INIT => '初始化',
            self::COMPLETE_STATUS_NORMAL => '正常',
            self::COMPLETE_STATUS_ABNORMAL => '异常',
            self::COMPLETE_STATUS_STOCKOUT => '缺货',
            self::COMPLETE_STATUS_GOODS_PREPARE => '已备货',
            self::COMPLETE_STATUS_EXPIRED => '超期',
            self::COMPLETE_STATUS_WAITTING_SHIP => '待发货',
            self::COMPLETE_STATUS_PARTIAL_SHIP => '部分发货',
            self::COMPLETE_STATUS_SHIPPED => '已发货',
            self::COMPLETE_STATUS_HOLD => '暂扣',
            //self::COMPLETE_STATUS_PARTIAL_REFUND => '部分退款',
            //self::COMPLETE_STATUS_ALL_REFUND => '全部退款',
            self::COMPLETE_STATUS_CANCELED => '已取消',
            self::COMPLETE_STATUS_COMPLETED => '已完成',
            self::COMPLETE_STATUS_TONGTU => '通途系统处理订单',
            self::COMPLETE_STATUS_CONFIRM => '借用领用单确认收货',
            self::COMPLETE_STATUS_DEPART_RETURN => '借用领用单已经部分归还',
            self::COMPLETE_STATUS_ALL_RETURN => '借用领用单已经全部归还',
        );
        if (is_numeric($key)) {
            return array_key_exists($key, $complete_status) ? $complete_status[$key] : '';
        } else {
            return $complete_status;
        }
    }

    /**
     *
     * @param $k
     * @return array|mixed|string
     */
    public static function getOrderStatus($k) {
        $order_status = array(
            'All' => '全部',
            'PLACE_ORDER_SUCCESS' => '等待买家付款',
            'IN_CANCEL' => '买家申请取消',
            'WAIT_SELLER_SEND_GOODS' => '等待卖家发货',
            'SELLER_PART_SEND_GOODS' => '部分发货',
            'WAIT_BUYER_ACCEPT_GOODS' => '等待买家收货',
            'FUND_PROCESSING' => '买家确认收货后，等待退放款处理',
            'FINISH' => '已结束的订单',
            'IN_ISSUE' => '含纠纷的订单',
            'IN_FROZEN' => '冻结中的订单',
            'WAIT_SELLER_EXAMINE_MONEY' => '等待卖家确认金额',
            'RISK_CONTROL' => '订单处于风控24小时中',
        );
        if ($k == null) {
            return $order_status;
        }
        return array_key_exists($k, $order_status) ? $order_status[$k] : $k;
    }

    /**
     * 共用的获取订单信息方法
     */
    private static function _getOrderInfo($params = []) {
        //平台code
        $platformCode = !empty($params['platformCode']) ? trim($params['platformCode']) : '';
        //平台订单id
        $platformOrderId = !empty($params['orderId']) ? trim($params['orderId']) : '';
        //系统订单id
        $systemOrderId = !empty($params['systemOrderId']) ? trim($params['systemOrderId']) : '';
        //交易id
        $transactionId = !empty($params['transactionId']) ? trim($params['transactionId']) : '';
        //类型 type  1:查订单   2：查订单详情
        $searchType = trim($params['type']);
        //账号id
        $accountId = !empty($params['accountId']) ? trim($params['accountId']) : "";
        if (empty($platformCode)) {
            return false;
        }

        if (empty($platformOrderId) && empty($transactionId) && empty($systemOrderId)) {
            return false;
        }

        //根据平台code获取对应的订单模型
        $platform = self::getOrderModel($platformCode);

        if (empty($platform)) {
            return false;
        }

        $data = array();
        //获取订单基本信息
        if (!empty($systemOrderId)) {
            $model = self::model($platform->ordermain)->where(['order_id' => $systemOrderId])->one();
            if (empty($model)) {
                $model = self::model($platform->ordermaincopy)->where(['order_id' => $systemOrderId])->one();
            }
        } else {
            if (empty($platformOrderId)) {
                $orderDetail = self::model($platform->orderdetail)->where(['transaction_id' => $transactionId])->one();
                $orderId = '';
                if (!empty($orderDetail)) {
                    $orderId = $orderDetail->order_id;
                }
                if (empty($orderId)) {
                    $orderDetail = self::model($platform->orderdetailcopy)->where(['transaction_id' => $transactionId])->one();
                    if (!empty($orderDetail)) {
                        $orderId = $orderDetail->order_id;
                    }
                }
                $model = self::model($platform->ordermain)->where(['order_id' => $orderId])->one();
                if (empty($model)) {
                    $model = self::model($platform->ordermaincopy)->where(['order_id' => $orderId])->one();
                }
            } else {
                //walmart
                if ($platformCode == Platform::PLATFORM_CODE_WALMART) {
                    //WALMART
                    $model = self::model($platform->ordermain)->where(['platform_code' => $platformCode, 'order_number' => $platformOrderId])->one();
                    if (empty($model)) {
                        $model = self::model($platform->ordermaincopy)->where(['platform_code' => $platformCode, 'order_number' => $platformOrderId])->one();
                    }
                } else {
                    if (!empty($accountId)) {
                        $model = self::model($platform->ordermain)->where(['platform_code' => $platformCode, 'platform_order_id' => $platformOrderId,'account_id' => $accountId])->one();
                        if (empty($model)) {
                            $model = self::model($platform->ordermaincopy)->where(['platform_code' => $platformCode, 'platform_order_id' => $platformOrderId,'account_id' => $accountId])->one();
                        }        
                    } else {
                        $model = self::model($platform->ordermain)->where(['platform_code' => $platformCode, 'platform_order_id' => $platformOrderId])->one();
                        if (empty($model)) {
                            $model = self::model($platform->ordermaincopy)->where(['platform_code' => $platformCode, 'platform_order_id' => $platformOrderId])->one();
                        } 
                    }
                    
                }
            }
        }

        //判断当前订单号是否是客户合并付款前的子订单,如果是需要绑定到对应的合并付款后的主订单上 add by allen str <2018-06-01>
        if ($model) {
            $models = '';
            $connection = Yii::$app->db_system;
            $mergeModel = $connection->createCommand("select * from {{%mall_api_log}} where order_id ='" . $model->order_id . "'")->queryOne();
            if ($mergeModel) {
                $order_id = $mergeModel['parent_order_id']; //获取合并付款后的主订单号
                $models = self::model($platform->ordermain)->where(['order_id' => $order_id])->one();
                if (empty($models)) {
                    $models = self::model($platform->ordermaincopy)->where(['order_id' => $order_id])->one();
                }
            }

            if (!empty($models)) {
                $model = $models;
            }
        }
        if (!empty($model)) {
            $data['info'] = $model->attributes;
            $data['info']['complete_status_text'] = self::getOrderCompleteStatus($model->attributes['complete_status']);
            $data['info']['order_status_text'] = self::getOrderStatus($model->attributes['order_status']);
        } else {
            return false;
        }

        $son_order_id_arr = array();
        //根据订单类型获取关联订单单号
        switch ($model->order_type) {
            //合并后的订单、被拆分的订单查询子订单
            case Order::ORDER_TYPE_MERGE_MAIN:
            case Order::ORDER_TYPE_SPLIT_MAIN:
                $son_order_ids = self::model($platform->ordermain)->where(['parent_order_id' => $model->order_id])->all();
                if (empty($son_order_ids)) {
                    $son_order_ids = self::model($platform->ordermaincopy)->where(['parent_order_id' => $model->order_id])->all();
                }
                if (!empty($son_order_ids)) {
                    foreach ($son_order_ids as $son_order_id) {
                        $son_order_id_arr[] = $son_order_id->order_id;
                    }
                }
                if ($son_order_id_arr) {
                    $data['info']['son_order_id'] = $son_order_id_arr;
                }
                break;
        }

        //获取订单备注
        $note = self::model($platform->ordernote)->where(['order_id' => $model->order_id])->one();
        if (!empty($note)) {
            $data['note'] = $note->attributes;
        }

        //订单备注
        $remark = OrderRemarkKefu::getOrderRemarks($model->order_id);
        if (!empty($remark)) {
            $orderRemarkInfos = [];
            foreach ($remark as $key => $orderRemarkInfo) {
                $orderRemarkInfo['create_user'] = MHelper::getUsername($orderRemarkInfo['create_user_id']);
                $orderRemarkInfos[$key] = $orderRemarkInfo;
            }
            $data['remark'] = $orderRemarkInfos;
        }

        //订单产品信息
        $detail = self::model($platform->orderdetail)->where(['order_id' => $model->order_id])->asArray()->all();
        if (empty($detail)) {
            $detail = self::model($platform->orderdetailcopy)->where(['order_id' => $model->order_id])->asArray()->all();
        }

        //获取客服中文名称
        $accountId = $model->account_id;
        $serviceer = '无客服';
        if (!empty($platformCode) && !empty($accountId)) {
            $userid = OrderserviceKefu::getAllCheckUserId($platformCode, $accountId);
            if (!empty($userid)) {
                $userobj = self::model('user', 'db_system')->where(['and', ['in', 'id', $userid], ['department_id' => 11]])->one();
                $serviceer = !empty($userobj->user_full_name) ? "客服:" . $userobj->user_full_name : "无客服";
            }
        }

        if (!empty($detail)) {
            foreach ($detail as $key => $row) {
                $seller_user = '无销售员';
                if ($platformCode == Platform::PLATFORM_CODE_EB) {
                    //eBay销售员数据获取方式优化 update by allen <2018-11-22> str
                    $searchErp = FALSE;//默认不从erp获取
                    //先从刊登系统查询销售账号 如果不能查到就从erp查
                    $api_config = include \Yii::getAlias('@app') . '/config/kandeng_api.php';
                    $url = $api_config['baseUrl'].'/services/api/ebay/seller/itemid/'.$row['item_id'];
                    $responseData = VHelper::curl_post($url, '', 'GET');
                    if($responseData != 'Not Found'){
                        $responseData = json_decode($responseData,true);
                        if($responseData['seller'] == '无销售员'){
                            $searchErp = TRUE;
                        }else{
                            $seller_user = "销售:".$responseData['seller'];
                        }
                    }else{
                        $searchErp = TRUE;
                    }
                    
                    //刊登系统未获取到销售员 改从erp获取
                    if($searchErp){
                        $sellerUser = self::model('ebay_online_listing', 'db_product')->where(['itemid' => $row['item_id']])->one();
                        $seller_user = !empty($sellerUser->seller_user) ? "销售:" . $sellerUser->seller_user : "无销售员";
                    }
                    //eBay销售员数据获取方式优化 update by allen <2018-11-22> end
                } else if ($platformCode == Platform::PLATFORM_CODE_AMAZON) {
                    $sellerUser = self::model('amazon_sku_map', 'db_product')->where(['seller_sku' => $row['sku_old'], 'account_id' => $model->account_id])->one();
                    $seller_user = !empty($sellerUser->owner_name) ? "销售:" . $sellerUser->owner_name : "无销售员";
                } else if ($platformCode == Platform::PLATFORM_CODE_ALI) {
                    if (!empty($accountId)) {
                        $seller_users = (new Query())
                                ->from('{{%aliexpress_account_config}} as t')
                                ->leftJoin('{{%user}} as a', "t.user_id = a.id")
                                ->select("a.user_full_name")
                                ->where("t.account_id='" . $accountId . "' AND a.department_id=4 AND a.user_status=1 ORDER BY t.add_time DESC LIMIT 1")
                                ->createCommand(Yii::$app->db_system)
                                ->queryScalar();
                    }
                    $seller_user = !empty($seller_users) ? "销售:" . $seller_users : "无销售员";
                } else if ($platformCode == Platform::PLATFORM_CODE_LAZADA) {
                    $sellerUser = self::model('lazada_account', 'db_system')->where(['id' => $accountId])->one();
                    $user_id_mine = $sellerUser->user_id;
                    $seller_userobj = self::model('user', 'db_system')->where(['id' => $user_id_mine])->one();
                    $seller_user = !empty($seller_userobj->user_full_name) ? "销售:" . $seller_userobj->user_full_name : "无销售员";
                } else if ($platformCode == Platform::PLATFORM_CODE_SHOPEE) {
                    $sellerUser = self::model('shopee_account', 'db_system')->where(['id' => $accountId])->one();
                    $user_id_mine = $sellerUser->user_id;
                    $seller_userobj = self::model('user', 'db_system')->where(['id' => $user_id_mine])->one();
                    $seller_user = !empty($seller_userobj->user_full_name) ? "销售:" . $seller_userobj->user_full_name : "无销售员";
                } else if ($platformCode == Platform::PLATFORM_CODE_WALMART) {
                    if (!empty($userid)) {
                        if (in_array($accountId, [8, 9, 10, 11, 12, 13, 26])) {
                            if (!empty($row['sku'])) {
                                $info = WalmartSellerSkuCost::findOne(['seller_sku' => $row['sku']]);
                                if (!empty($info->create_user_id)) {
                                    $sellerUser = self::model('walmart_account', 'db_system')->where(['id' => $accountId])->one();
                                    $user_id_mine = $sellerUser->user_id;
                                    $seller_userobj = self::model('user', 'db_system')->where(['id' => $user_id_mine])->one();
                                }
                                $seller_user = !empty($seller_userobj->user_full_name) ? "销售员(DSV):" . $seller_userobj->user_full_name : "无销售员(DSV)";
                            }
                        } else {
                            $useridnew = array();
                            for ($ks = 0; $ks < count($userid); $ks++) {
                                if (strstr($userid[$ks], "9000")) {
                                    $useridnew[] = $userid[$ks];
                                }
                            }
                            $cutzero = implode(",", $useridnew);
                            $cutzero = str_replace("9000", "", $cutzero);
                            $cutzero = explode(',', $cutzero);
                            if (!empty($cutzero)) {
                                $seller_userobj = self::model('user', 'db_system')->where(['and', ['in', 'id', $cutzero], ['department_id' => 11]])->one();
                                $seller_user = !empty($seller_userobj->user_full_name) ? "销售:" . $seller_userobj->user_full_name : "无销售员";
                            }
                        }
                    }
                } else if ($platformCode == Platform::PLATFORM_CODE_CDISCOUNT || $platformCode == Platform::PLATFORM_CODE_PM || $platformCode == Platform::PLATFORM_CODE_WISH) {
                    if (!empty($userid)) {
                        $seller_userobj = self::model('user', 'db_system')->where(['and', ['in', 'id', $userid], ['<>', 'department_id', 11]])->one();
                        $seller_user = !empty($seller_userobj->user_full_name) ? "销售:" . $seller_userobj->user_full_name : "无销售员";
                    }
                }

                $data['product'][] = $row;
                $data['product'][$key]['seller_user'] = $seller_user;
                $data['product'][$key]['serviceer'] = $serviceer;
                $sku = $row['sku'];

                //查询产品中文名以及产品线名
                $data_product = (new Query())
                        ->select('t.id as product_id,t.picking_name,t1.linelist_cn_name,t.gross_product_weight as product_weight')
                        ->from('{{%product}} as t')
                        ->leftjoin('{{%product_linelist}} as t1', 't1.id = t.product_linelist_id')
                        ->where('t.sku=:sku', array(':sku' => $sku))
                        ->createCommand(Yii::$app->db_product)
                        ->queryOne();

                //获取产品详情
                $productDetail = self::model('product_description', 'db_product')->where(['sku' => $sku, 'language_code' => 'Chinese'])->one();

                if (!empty($data_product)) {
                    $data['product'][$key]['picking_name'] = !empty($productDetail->title) ? $productDetail->title : $data_product['picking_name'];
                    $data['product'][$key]['linelist_cn_name'] = $data_product['linelist_cn_name'];
                    $data['product'][$key]['product_weight'] = $data_product['product_weight'];
                    $data['product'][$key]['product_id'] = $data_product['product_id'];
                } else {
                    if (!empty($productDetail)) {
                        $data['product'][$key]['picking_name'] = $productDetail->title;
                        $data['product'][$key]['linelist_cn_name'] = '';
                        $data['product'][$key]['product_weight'] = 0;
                        $data['product'][$key]['product_id'] = 0;
                    } else {
                        $data['product'][$key]['picking_name'] = '无中文名称';
                        $data['product'][$key]['linelist_cn_name'] = '';
                        $data['product'][$key]['product_weight'] = 0;
                        $data['product'][$key]['product_id'] = 0;
                    }
                }
            }
        }

        //订单交易信息
        $trade = OrderTransactionKefu::getOrderTransactionDetailByOrderId($model->order_id);
        if (empty($trade)) {
            $xx = 1;
            $trade = self::model($platform->ordertransaction)->where(['order_id' => $model->order_id])->asArray()->all();
            if (empty($trade)) {
                $xx = 2;
                $trade = self::model($platform->ordertransactioncopy)->where(['order_id' => $model->order_id])->asArray()->all();
            }
        }

        if (!empty($trade)) {
            foreach ($trade as &$trvalue) {
                $receiverEmail = '';
                $payerEmail = '';
                $trvalue['receiver_email'] = '';
                $trvalue['payer_email'] = '';
                $receive_type = OrderTransactionKefu::getOrderTransactionType($trvalue['receive_type']);
                $trvalue['receive_type'] = $receive_type;
                if ($platformCode == Platform::PLATFORM_CODE_EB) {
                    if (empty($receiverEmail) || empty($payerEmail)) {
                        $transactionInfo = self::model('ebay_paypal_transaction')->where(['l_transaction_id' => $trvalue['transaction_id']])->one();
                        if (!empty($transactionInfo)) {
                            $paypalId = $transactionInfo->paypal;
                            $paypalEmail = '';
                            $paypalInfo = self::model('paypal_account', 'db_system')->where(['id' => $paypalId])->asArray()->one();
                            if (!empty($paypalInfo)) {
                                $paypalEmail = $paypalInfo['email'];
                            }
                            $amt = $transactionInfo->l_amt;
                            if ($amt < 0) {
                                $payerEmail = $paypalEmail;
                                $receiverEmail = $transactionInfo->l_email;
                            } else {
                                $payerEmail = $transactionInfo->l_email;
                                $receiverEmail = $paypalEmail;
                            }
                            $trvalue['receiver_email'] = $receiverEmail;
                            $trvalue['payer_email'] = $payerEmail;
                        }
                    }
                }
            }
        } else {
            $trade = [];
        }
        $data['trade'] = $trade;

        //订单包裹信息
        $orderPackageInfos = OrderPackageKefu::getOrderPackages($model->order_id);
        if (!empty($orderPackageInfos)) {
            foreach ($orderPackageInfos as $key => $row) {
                $packageId = $row['package_id'];
                $orderPackageInfos[$key]['warehouse_name'] = WarehouseKefu::getWarehouseNameById($row['warehouse_id']);
                $orderPackageInfos[$key]['ship_name'] = LogisticsKefu::getLogisticsName($model->ship_code);
                $orderPackageDetailInfos = OrderPackageDetailKefu::getPackageDetails($packageId);
                if (!empty($orderPackageDetailInfos)) {
                    $orderPackageInfos[$key]['items'] = $orderPackageDetailInfos;
                } else {
                    $orderPackageInfos[$key]['items'] = array();
                }
            }
        }
        $data['orderPackage'] = $orderPackageInfos;
        if ($platformCode == Platform::PLATFORM_CODE_ALI) {
            //订单评价消息
            $order_evaluate = AliexpressEvaluateList::getFindOne($model->platform_order_id);
            if (!empty($order_evaluate)) {
                $data['evaluate'] = $order_evaluate;
            } else {
                $data['evaluate'] = array();
            }
        }


        //订单利润详细
        $profit = self::model('order_profit')->where(['order_id' => $model->order_id])->asArray()->one();
        if (!empty($profit)) {
            $data['profit'] = $profit;
        } else {
            $data['profit'] = [];
        }

        //订单仓储物流
        if ($model->ship_code) {
            $Logistics = LogisticsKefu::getViewNameIdByShipCode($model->ship_code);
            if (!empty($Logistics)) {
                foreach ($Logistics as $value) {
                    $data['wareh_logistics']['logistics'] = $value->attributes;
                }
            }
        }
        if ($model->warehouse_id) {
            $warehouse = WarehouseKefu::getWarehouseById($model->warehouse_id);
            if (!empty($warehouse->attributes)) {
                $data['wareh_logistics']['warehouse'] = $warehouse->attributes;
            }
        }
        //amazon平台的单，把amazon item 明细查询出来
        if ($platformCode == Platform::PLATFORM_CODE_AMAZON) {
            $amazonItems = self::model('order_amazon_item')->where(['order_id' => $model->order_id])->all();
            if (empty($amazonItems)) {
                $amazonItems = [];
            }
            foreach ($amazonItems as $itemRow) {
                $data['items'][] = $itemRow->attributes;
            }
        }
        if (!isset($data['items'])) {
            $data['items'] = [];
        }

        //订单操作日志
        $data['logs'] = OrderUpdateLogKefu::getOrderUpdateLog($model->order_id);
        $ondeList = OrderNodeKefu::getOrderNode($model->order_id);
        $orderNodelist = OrderNodeKefu::getNnodeName();
        $LogisticsInfo = WarehouseKefu::getWarehouseById($model->warehouse_id);

        if (empty($LogisticsInfo) || $LogisticsInfo->warehouse_type != 1) {
            unset($orderNodelist[OrderNodeKefu::WAREHOUSE_PULL_ORDER]);
            unset($orderNodelist[OrderNodeKefu::WAREHOUSE_SCANNING_PACKAGE]);
            unset($orderNodelist[OrderNodeKefu::WAREHOUSE_SCANNING]);
        }
        if (!empty($ondeList[OrderNodeKefu::ORDER_PICKING])) {
            unset($orderNodelist[OrderNodeKefu::ORDER_SHORTAGE]);
        }
        if (!empty($ondeList[OrderNodeKefu::ORDER_SHORTAGE])) {
            unset($orderNodelist[OrderNodeKefu::ORDER_PICKING]);
        }

        $data['ondeList'] = isset($ondeList) && !empty($ondeList) ? $ondeList : array();
        $data['orderNodelist'] = isset($orderNodelist) && !empty($orderNodelist) ? $orderNodelist : array();

        //订单异常信息
        $abnormals = OrderAbnormityKefu::getOrderAbnormals($model->order_id);
        $data['abnormals'] = isset($abnormals) && !empty($abnormals) ? $abnormals : array();

        return $data;
    }

    /**
     * 获取walmart 历史订单
     * @param $platformCode
     * @param $platformOrderId
     * @return array
     */
    public static function walmartHistoryOrders($platformCode, $platformOrderId, $account_id) {

        //获取对应的订单模型
        $platform = self::getOrderModel($platformCode);

        if (empty($platform)) {
            return [];
        }

        $query = self::model($platform->ordermain);
        $query1 = self::model($platform->ordermain . '_copy');
        $query->where(['account_id' => $account_id]);
        $query->andWhere(['or', ['platform_order_id' => $platformOrderId], ['order_number' => $platformOrderId]]);
        $query->andWhere(['platform_code' => $platformCode]);
        $query->andWhere(['<>', 'is_lock', 2]);
        $query->orderBy('paytime DESC');
        $arr = $query->asArray()->one();
        if (empty($arr)) {
            $query1->where(['account_id' => $account_id]);
            $query1->andWhere(['platform_code' => $platformCode]);
            $query1->andWhere(['or', ['platform_order_id' => $platformOrderId], ['order_number' => $platformOrderId]]);
            $query1->andWhere(['<>', 'is_lock', 2]);
            $query1->orderBy('paytime DESC');
            $arr = $query1->asArray()->one();
        }

        if (empty($arr)) {
            return [];
        }
        $arr = [$arr];
        $orderId_arr = []; //
        if (!empty($arr)) {
            $warehouseTypeList = Warehouse::getWarehousetype();
            foreach ($arr as &$avalue) {
                $orderId_arr[] = $avalue['order_id'];
                //获取完成状态
                $avalue['complete_status_text'] = self::getOrderCompleteStatus($avalue['complete_status']);
                $avalue['order_status_text'] = self::getOrderStatus($avalue['order_status']);
                //添加仓库类型
                $avalue['warehouse_type'] = array_key_exists($avalue['warehouse_id'], $warehouseTypeList) ?
                        $warehouseTypeList[$avalue['warehouse_id']] : '';

                //获取订单详情
                $pDetails = self::model($platform->orderdetail)->where(['order_id' => $avalue['order_id']])->asArray()->all();
                if (empty($pDetails)) {
                    $pDetails = self::model($platform->orderdetailcopy)->where(['order_id' => $avalue['order_id']])->asArray()->all();
                }

                if (!empty($pDetails)) {
                    foreach ($pDetails as $key => $value) {
                        $productDetail = self::model('product_description', 'db_product')->where(['sku' => $value['sku'], 'language_code' => 'Chinese'])->one();
                        if ($productDetail) {
                            $pDetails[$key]['titleCn'] = $productDetail->title;
                        } else {
                            $pDetails[$key]['titleCn'] = "";
                        }
                    }
                }

                $avalue['detail'] = $pDetails;
                //获取订单交易信息
                $trade = OrderTransactionKefu::getOrderTransactionDetailByOrderId($avalue['order_id']);
                if (empty($trade)) {
                    $trade = self::model($platform->ordertransaction)->where(['order_id' => $avalue['order_id']])->asArray()->all();
                    if (empty($trade)) {
                        $trade = self::model($platform->ordertransactioncopy)->where(['order_id' => $avalue['order_id']])->asArray()->all();
                    }
                }
                if (!empty($trade)) {
                    foreach ($trade as $key => &$trvalue) {
                        $trvalue['receive_type'] = OrderTransactionKefu::getOrderTransactionType($trvalue['receive_type']);
                    }
                    $avalue['trade'] = $trade;
                }

                //获取订单利润详细
                $profit = self::model('order_profit')->where(['order_id' => $avalue['order_id']])->asArray()->one();
                if (!empty($profit)) {
                    $avalue['profit'] = $profit;
                } else {
                    $avalue['profit'] = array();
                }
                //订单包裹信息
                $orderPackageInfos = OrderPackageKefu::getOrderPackages($avalue['order_id']);
                if (!empty($orderPackageInfos)) {
                    foreach ($orderPackageInfos as $key => $row) {
                        $packageId = $row['package_id'];
                        $orderPackageInfos[$key]['warehouse_name'] = WarehouseKefu::getWarehouseNameById($row['warehouse_id']);
                        $orderPackageInfos[$key]['ship_name'] = LogisticsKefu::getLogisticsName($avalue['ship_code']);
                        $orderPackageDetailInfos = OrderPackageDetailKefu::getPackageDetails($packageId);
                        if (!empty($orderPackageDetailInfos)) {
                            $orderPackageInfos[$key]['items'] = $orderPackageDetailInfos;
                        } else {
                            $orderPackageInfos[$key]['items'] = array();
                        }
                    }
                    $avalue['orderPackage'] = $orderPackageInfos;
                } else {
                    $avalue['orderPackage'] = array();
                }

                //子订单id数组
                $son_order_id_arr = array();

                //根据订单类型获取关联订单单号
                switch ($avalue['order_type']) {
                    // 合并后的订单、被拆分的订单查询子订单
                    case Order::ORDER_TYPE_MERGE_MAIN:
                    case Order::ORDER_TYPE_SPLIT_MAIN:
                        $son_order_ids = self::model($platform->ordermain)->where(['parent_order_id' => $avalue['order_id']])->all();
                        if (empty($son_order_ids)) {
                            $son_order_ids = self::model($platform->ordermaincopy)->where(['parent_order_id' => $avalue['order_id']])->all();
                        }
                        if (!empty($son_order_ids)) {
                            foreach ($son_order_ids as $son_order_id) {
                                $son_order_id_arr[] = $son_order_id->order_id;
                            }
                        }
                        if ($son_order_id_arr) {
                            $avalue['son_order_id'] = $son_order_id_arr;
                        } else {
                            $avalue['son_order_id'] = array();
                        }
                        break;
                }
            }
            //获取订单备注
            $order_remark_arr = OrderRemarkKefu::getOrderRemarksByArray($orderId_arr);
            foreach ($arr as &$v1) {
                foreach ($order_remark_arr as $value) {
                    if ($v1['order_id'] == $value['order_id']) {
                        $v1['remark'][] = $value['remark'];
                    }
                }
            }
        }
        return $arr;
    }

    /**
     * 共用的获取历史订单
     */
    private static function _getHistoryOrders($params = []) {
        //平台code
        $platformCode = !empty($params['platformCode']) ? trim($params['platformCode']) : '';
        //购买人id
        $buyerId = !empty($params['buyerId']) ? trim($params['buyerId']) : '';
        //邮箱
        $email = !empty($params['email']) ? trim($params['email']) : '';
        //账号id
        $accountId = !empty($params['accountId']) ? trim($params['accountId']) : "";
        //平台订单id
        $platformOrderId = !empty($params['platformOrderId']) ? trim($params['platformOrderId']) : '';

        if (empty($platformCode)) {
            return [];
        }

//        if ($platformCode == Platform::PLATFORM_CODE_EB) {
//            $order_id_arr = (new Query())
//                ->select('order_id')
//                ->from("{{%mall_api_log}}")
//                ->where(['task_name' => 'ebaynotfind'])
//                ->createCommand(Yii::$app->db_system)
//                ->queryColumn();
//        }
        //获取对应的订单模型
        $platform = self::getOrderModel($platformCode);

        if (empty($platform)) {
            return [];
        }

        $query = self::model($platform->ordermain);
        $queryCopy = self::model($platform->ordermaincopy);

        $query->andWhere(['platform_code' => $platformCode]);
        $queryCopy->andWhere(['platform_code' => $platformCode]);

        if (!empty($buyerId)) {
            $query->andWhere(['buyer_id' => $buyerId]);
            $queryCopy->andWhere(['buyer_id' => $buyerId]);
        }

        if ($platformCode == Platform::PLATFORM_CODE_EB) {
            $query->andWhere(['<>', 'is_lock', 2]);
            $queryCopy->andWhere(['<>', 'is_lock', 2]);
        }

        if (!empty($email)) {
            $query->andWhere(['email' => $email]);
            $queryCopy->andWhere(['email' => $email]);
        }

        if (!empty($accountId)) {
            $query->andWhere(['account_id' => $accountId]);
            $queryCopy->andWhere(['account_id' => $accountId]);
        }

        //查询主表
        if($platformCode == Platform::PLATFORM_CODE_WALMART && !empty($buyerId)){
            $arr = $query->orderBy('paytime DESC')->asArray()->All();
        }else{
            $arr = $query->orderBy('paytime DESC')->limit(10)->asArray()->All();
        }

        //查询copy表
        if (!empty($arr)) {
            $orderIds = array_column($arr, 'order_id');
            if (!empty($orderIds)) {
                $queryCopy->andWhere(['not in', 'order_id', $orderIds]);
            }
        }

        if($platformCode == Platform::PLATFORM_CODE_WALMART && !empty($buyerId)){
            $arr1 = $queryCopy->orderBy('paytime DESC')->asArray()->All();
        }else{
            $arr1 = $queryCopy->orderBy('paytime DESC')->limit(10)->asArray()->All();
        }

        //主表与copy表合并
        $arr2 = array_merge($arr, $arr1);

        //如果平台订单ID不为空，则把该订单加入到历史订单中
        if (!empty($platformOrderId)) {
            $isExist = false;
            if (!empty($arr2)) {
                foreach ($arr2 as $item) {
                    if ($item['platform_order_id'] == $platformOrderId) {
                        $isExist = true;
                        break;
                    }
                }
            }
            if (!$isExist) {
                $query = self::model($platform->ordermain);
                $order = $query->andWhere(['platform_code' => $platformCode])
                        ->andWhere(['platform_order_id' => $platformOrderId])
                        ->andWhere(['account_id' => $accountId])
                        ->asArray()
                        ->one();
                if (!empty($order)) {
                    $arr2[] = $order;
                } else {
                    $queryCopy = self::model($platform->ordermaincopy);
                    $order = $queryCopy->andWhere(['platform_code' => $platformCode])
                            ->andWhere(['platform_order_id' => $platformOrderId])
                            ->andWhere(['account_id' => $accountId])
                            ->asArray()
                            ->one();
                    if (!empty($order)) {
                        $arr2[] = $order;
                    }
                }
            }
        }

        if (empty($arr2)) {
            return [];
        }

        $orderId_arr = [];
        if (!empty($arr2)) {
            $warehouseTypeList = Warehouse::getWarehousetype();
            foreach ($arr2 as &$avalue) {
                $orderId_arr[] = $avalue['order_id'];
                //获取完成状态
                $avalue['complete_status_text'] = self::getOrderCompleteStatus($avalue['complete_status']);
                $avalue['order_status_text'] = self::getOrderStatus($avalue['order_status']);
                //添加仓库类型
                $avalue['warehouse_type'] = array_key_exists($avalue['warehouse_id'], $warehouseTypeList) ?
                        $warehouseTypeList[$avalue['warehouse_id']] : '';

                //获取订单详情
                $pDetails = self::model($platform->orderdetail)->where(['order_id' => $avalue['order_id']])->asArray()->all();
                if (empty($pDetails)) {
                    $pDetails = self::model($platform->orderdetailcopy)->where(['order_id' => $avalue['order_id']])->asArray()->all();
                }

                if (!empty($pDetails)) {
                    foreach ($pDetails as $key => $value) {
                        $productDetail = self::model('product_description', 'db_product')->where(['sku' => $value['sku'], 'language_code' => 'Chinese'])->one();
                        if ($productDetail) {
                            $pDetails[$key]['titleCn'] = $productDetail->title;
                        } else {
                            $pDetails[$key]['titleCn'] = "";
                        }
                    }
                }

                $avalue['detail'] = $pDetails;
                //获取订单交易信息
                $trade = OrderTransactionKefu::getOrderTransactionDetailByOrderId($avalue['order_id']);
                if (empty($trade)) {
                    $trade = self::model($platform->ordertransaction)->where(['order_id' => $avalue['order_id']])->asArray()->all();
                    if (empty($trade)) {
                        $trade = self::model($platform->ordertransactioncopy)->where(['order_id' => $avalue['order_id']])->asArray()->all();
                    }
                }
                if (!empty($trade)) {
                    foreach ($trade as $key => &$trvalue) {
                        $trvalue['receive_type'] = OrderTransactionKefu::getOrderTransactionType($trvalue['receive_type']);
                    }
                    $avalue['trade'] = $trade;
                }

                //获取订单利润详细
                $profit = self::model('order_profit')->where(['order_id' => $avalue['order_id']])->asArray()->one();
                if (!empty($profit)) {
                    $avalue['profit'] = $profit;
                } else {
                    $avalue['profit'] = array();
                }
                //订单包裹信息
                $orderPackageInfos = OrderPackageKefu::getOrderPackages($avalue['order_id']);
                if (!empty($orderPackageInfos)) {
                    foreach ($orderPackageInfos as $key => $row) {
                        $packageId = $row['package_id'];
                        $orderPackageInfos[$key]['warehouse_name'] = WarehouseKefu::getWarehouseNameById($row['warehouse_id']);
                        $orderPackageInfos[$key]['ship_name'] = LogisticsKefu::getLogisticsName($avalue['ship_code']);
                        $orderPackageDetailInfos = OrderPackageDetailKefu::getPackageDetails($packageId);
                        if (!empty($orderPackageDetailInfos)) {
                            $orderPackageInfos[$key]['items'] = $orderPackageDetailInfos;
                        } else {
                            $orderPackageInfos[$key]['items'] = array();
                        }
                    }
                    $avalue['orderPackage'] = $orderPackageInfos;
                } else {
                    $avalue['orderPackage'] = array();
                }

                if ($platformCode == Platform::PLATFORM_CODE_ALI) {
                    //订单评价消息
                    $order_evaluate = AliexpressEvaluateList::getFindOne($avalue['platform_order_id']); //平台订单id
                    if (!empty($order_evaluate)) {
                        $avalue['evaluate'] = $order_evaluate;
                    } else {
                        $avalue['evaluate'] = array();
                    }
                }

                //亚马逊
                if ($platformCode == Platform::PLATFORM_CODE_AMAZON) {
                    //亚马逊feedback
                    $order_feedback = AmazonFeedBack::getFindOne($avalue['platform_order_id']);
                    if (!empty($order_feedback)) {
                        $avalue['order_feedback'] = $order_feedback;
                    } else {
                        $avalue['order_feedback'] = array();
                    }
                    //亚马逊review
                    $order_review = AmazonReviewData::find()
                            ->select('t.*,m.orderId,m.site,m.custEmail')
                            ->from('{{%amazon_review_data}} t')
                            ->join('LEFT JOIN', '{{%amazon_review_message_data}} m', 't.customerId = m.custId and t.sellerAcct = m.sellerAcct')
                            ->where(['m.orderId' => $avalue['platform_order_id']])
                            ->one();
                    if (!empty($order_review)) {
                        $avalue['order_review'] = $order_review;
                    } else {
                        $avalue['order_review'] = array();
                    }
                }

                //子订单id数组
                $son_order_id_arr = array();

                //根据订单类型获取关联订单单号
                switch ($avalue['order_type']) {
                    // 合并后的订单、被拆分的订单查询子订单
                    case Order::ORDER_TYPE_MERGE_MAIN:
                    case Order::ORDER_TYPE_SPLIT_MAIN:
                        $son_order_ids = self::model($platform->ordermain)->where(['parent_order_id' => $avalue['order_id']])->all();
                        if (empty($son_order_ids)) {
                            $son_order_ids = self::model($platform->ordermaincopy)->where(['parent_order_id' => $avalue['order_id']])->all();
                        }
                        if (!empty($son_order_ids)) {
                            foreach ($son_order_ids as $son_order_id) {
                                $son_order_id_arr[] = $son_order_id->order_id;
                            }
                        }
                        if ($son_order_id_arr) {
                            $avalue['son_order_id'] = $son_order_id_arr;
                        } else {
                            $avalue['son_order_id'] = array();
                        }
                        break;
                }
            }
            //获取订单备注
            $order_remark_arr = OrderRemarkKefu::getOrderRemarksByArray($orderId_arr);
            foreach ($arr2 as &$v1) {
                foreach ($order_remark_arr as $value) {
                    if ($v1['order_id'] == $value['order_id']) {
                        $v1['remark'][] = $value['remark'];
                    }
                }
            }
        }
        return $arr2;
    }

    /**
     * @desc 根据订单ID获取订单数据
     * @param unknown $platformCode
     * @param unknown $platformOrderId
     * @return multitype:|Ambigous <multitype:, NULL, mixed>
     */
    public static function getOrderStackByOrderId($platformCode, $platformOrderId = null, $systemsOrderId = null) {
        $orderInfo = [];
        if (empty($platformCode) || (empty($platformOrderId) && empty($systemsOrderId)))
            return $orderInfo;
        $cacheKey = md5('cache_erp_order_' . $platformCode . '_' . $platformOrderId . '_all');
        $cacheNamespace = 'namespace_erp_order_' . $platformCode . '_' . $platformOrderId;
        //从缓存获取订单数据
        /*          if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($cacheKey, $cacheNamespace) &&
          !empty(\Yii::$app->memcache->get($cacheKey, $cacheNamespace)))
          {
          return \Yii::$app->memcache->get($cacheKey, $cacheNamespace);
          } */
        //从库获取订单数据
        $params = ['platformCode' => $platformCode, 'orderId' => $platformOrderId, 'systemOrderId' => $systemsOrderId];
        $result = self::Mailrelatedorder($params);
        if (empty($result))
            return $orderInfo;
        $orderInfo = $result;
        if (!empty($orderInfo) && isset(\Yii::$app->memcache)) {
            \Yii::$app->memcache->set($cacheKey, $orderInfo, $cacheNamespace);
        }
        return $orderInfo;
    }

    //获取订单
    private static function Mailrelatedorder($params = []) {
        error_reporting(E_ERROR);
        //平台code
        $platformCode = !empty($params['platformCode']) ? trim($params['platformCode']) : '';
        //平台订单id
        $platformOrderId = !empty($params['orderId']) ? trim($params['orderId']) : '';
        //系统订单id
        $systemOrderId = !empty($params['systemOrderId']) ? trim($params['systemOrderId']) : '';
        //交易id
        $transactionId = !empty($params['transactionId']) ? trim($params['transactionId']) : '';
        if (empty($platformCode)) {
            return false;
        }

        if (empty($platformOrderId) && empty($transactionId) && empty($systemOrderId)) {
            return false;
        }
//        if ($platformCode == Platform::PLATFORM_CODE_EB) {
//            $order_id_arr = (new Query())
//                ->select('order_id')
//                ->from('{{%mall_api_log}}')
//                ->where(['task_name' => 'ebaynotfind'])
//                ->createCommand(Yii::$app->db_system)
//                ->queryColumn();
//
//            if (!empty($systemOrderId)) {
//                if (in_array($systemOrderId, $order_id_arr)) {
//                    $systemOrderId = '';
//                }
//            }
//
//            if (!empty($platformOrderId)) {
//                $platformOrderId_arr = (new Query())
//                    ->select('platform_order_id')
//                    ->from('{{%order_ebay}}')
//                    ->where(['in', 'order_id', $order_id_arr])
//                    ->createCommand(Yii::$app->db_order)
//                    ->queryColumn();
//
//                if (in_array($platformOrderId, $platformOrderId_arr)) {
//                    $platformOrderId = '';
//                }
//            }
//        }
        //根据平台code获取对应的订单模型
        $platform = self::getOrderModel($platformCode);

        if (empty($platform)) {
            return false;
        }

        $data = array();
        //获取订单基本信息
        if (!empty($systemOrderId)) {
            $model = self::model($platform->ordermain)
                    ->where(['order_id' => $systemOrderId])
                    ->andWhere(['<>', 'is_lock', 2])
                    ->one();
            if (empty($model)) {
                $model = self::model($platform->ordermaincopy)
                        ->where(['order_id' => $systemOrderId])
                        ->andWhere(['<>', 'is_lock', 2])
                        ->one();
            }
        } else {
            if (empty($platformOrderId)) {
                $orderDetail = self::model($platform->orderdetail)->where(['transaction_id' => $transactionId])->one();
                $orderId = '';
                if (!empty($orderDetail)) {
                    $orderId = $orderDetail->order_id;
                }
                if (empty($orderId)) {
                    $orderDetail = self::model($platform->orderdetailcopy)->where(['transaction_id' => $transactionId])->one();
                    if (!empty($orderDetail)) {
                        $orderId = $orderDetail->order_id;
                    }
                }
                $model = self::model($platform->ordermain)->where(['order_id' => $orderId])->one();
                if (empty($model)) {
                    $model = self::model($platform->ordermaincopy)->where(['order_id' => $orderId])->one();
                }
            } else {
                $model = self::model($platform->ordermain)->where(['platform_code' => $platformCode, 'platform_order_id' => $platformOrderId])->one();
                if (empty($model)) {
                    $model = self::model($platform->ordermaincopy)->where(['platform_code' => $platformCode, 'platform_order_id' => $platformOrderId])->one();
                }
            }
        }
        if (!empty($model)) {
            $data['info'] = $model->attributes;
            $data['info']['complete_status_text'] = self::getOrderCompleteStatus($model->attributes['complete_status']);
            $data['info']['order_status_text'] = self::getOrderStatus($model->attributes['order_status']);
        } else {
            return false;
        }

        $son_order_id_arr = array();
        //根据订单类型获取关联订单单号
        switch ($model->order_type) {
            //合并后的订单、被拆分的订单查询子订单
            case Order::ORDER_TYPE_MERGE_MAIN:
            case Order::ORDER_TYPE_SPLIT_MAIN:
                $son_order_ids = self::model($platform->ordermain)->where(['parent_order_id' => $model->order_id])->all();
                if (empty($son_order_ids)) {
                    $son_order_ids = self::model($platform->ordermaincopy)->where(['parent_order_id' => $model->order_id])->all();
                }
                if (!empty($son_order_ids)) {
                    foreach ($son_order_ids as $son_order_id) {
                        $son_order_id_arr[] = $son_order_id->order_id;
                    }
                }
                if ($son_order_id_arr) {
                    $data['info']['son_order_id'] = $son_order_id_arr;
                }
                break;
        }
        //获取订单备注
        $note = self::model($platform->ordernote)->where(['order_id' => $model->order_id])->one();
        if (!empty($note)) {
            $data['note'] = $note->attributes;
        }

        //订单备注
        $remark = OrderRemarkKefu::getOrderRemarks($model->order_id);
        if (!empty($remark)) {
            $orderRemarkInfos = [];
            foreach ($remark as $key => $orderRemarkInfo) {
                $orderRemarkInfo['create_user'] = MHelper::getUsername($orderRemarkInfo['create_user_id']);
                $orderRemarkInfos[$key] = $orderRemarkInfo;
            }
            $data['remark'] = $orderRemarkInfos;
        }

        //订单产品信息
        $detail = self::model($platform->orderdetail)->where(['order_id' => $model->order_id])->asArray()->all();
        if (empty($detail)) {
            $detail = self::model($platform->orderdetailcopy)->where(['order_id' => $model->order_id])->asArray()->all();
        }

        //获取客服中文名称
        $accountId = $model->account_id;
        $serviceer = '无客服';
        if (!empty($platformCode) && !empty($accountId)) {
            $userid = OrderserviceKefu::getAllCheckUserId($platformCode, $accountId);
            if (!empty($userid)) {
                $userobj = self::model('user', 'db_system')->where(['and', ['in', 'id', $userid], ['department_id' => 11]])->one();
                $serviceer = !empty($userobj->user_full_name) ? "客服:" . $userobj->user_full_name : "无客服";
            }
        }

        if (!empty($detail)) {
            foreach ($detail as $key => $row) {

                if ($platformCode == Platform::PLATFORM_CODE_EB) {
                    $sellerUser = self::model('ebay_online_listing', 'db_product')->where(['itemid' => $row['item_id']])->one();
                    $seller_user = !empty($sellerUser->seller_user) ? "销售:" . $sellerUser->seller_user : "无销售员";
                } else if ($platformCode == Platform::PLATFORM_CODE_AMAZON) {
                    $sellerUser = self::model('amazon_sku_map', 'db_product')->where(['seller_sku' => $row['sku_old'], 'account_id' => $model->account_id])->one();
                    $seller_user = !empty($sellerUser->owner_name) ? "销售:" . $sellerUser->owner_name : "无销售员";
                } else if ($platformCode == Platform::PLATFORM_CODE_ALI) {
                    if (!empty($accountId)) {
                        $seller_users = (new Query())
                                ->from('{{%aliexpress_account_config}} as t')
                                ->leftJoin('{{%user}} as a', "t.user_id = a.id")
                                ->select("a.user_full_name")
                                ->where("t.account_id='" . $accountId . "' AND a.department_id=4 AND a.user_status=1 ORDER BY t.add_time DESC LIMIT 1")
                                ->createCommand(Yii::$app->db_system)
                                ->queryScalar();
                    }
                    $seller_user = !empty($seller_users) ? "销售:" . $seller_users : "无销售员";
                } else if ($platformCode == Platform::PLATFORM_CODE_LAZADA) {
                    $sellerUser = self::model('lazada_account', 'db_system')->where(['id' => $accountId])->one();
                    $user_id_mine = $sellerUser->user_id;
                    $seller_userobj = self::model('user', 'db_system')->where(['id' => $user_id_mine])->one();
                    $seller_user = !empty($seller_userobj->user_full_name) ? "销售:" . $seller_userobj->user_full_name : "无销售员";
                } else if ($platformCode == Platform::PLATFORM_CODE_SHOPEE) {
                    $sellerUser = self::model('shopee_account', 'db_system')->where(['id' => $accountId])->one();
                    $user_id_mine = $sellerUser->user_id;
                    $seller_userobj = self::model('user', 'db_system')->where(['id' => $user_id_mine])->one();
                    $seller_user = !empty($seller_userobj->user_full_name) ? "销售:" . $seller_userobj->user_full_name : "无销售员";
                } else if ($platformCode == Platform::PLATFORM_CODE_WALMART) {
                    if (!empty($userid)) {
                        $useridnew = array();
                        for ($ks = 0; $ks < count($userid); $ks++) {
                            if (strstr($userid[$ks], "9000")) {
                                $useridnew[] = $userid[$ks];
                            }
                        }
                        $cutzero = implode(",", $useridnew);
                        $cutzero = str_replace("9000", "", $cutzero);
                        $cutzero = explode(',', $cutzero);
                        if (!empty($cutzero)) {
                            $seller_userobj = self::model('user', 'db_system')->where(['and', ['in', 'id', $cutzero], ['department_id' => 11]])->one();
                            $seller_user = !empty($seller_userobj->user_full_name) ? "销售:" . $seller_userobj->user_full_name : "无销售员";
                        }
                    }
                } else if ($platformCode == Platform::PLATFORM_CODE_CDISCOUNT || $platformCode == Platform::PLATFORM_CODE_PM || $platformCode == Platform::PLATFORM_CODE_WISH) {
                    if (!empty($userid)) {
                        $seller_userobj = self::model('user', 'db_system')->where(['and', ['in', 'id', $userid], ['<>', 'department_id', 11]])->one();
                        $seller_user = !empty($seller_userobj->user_full_name) ? "销售:" . $seller_userobj->user_full_name : "无销售员";
                    }
                }

                $data['product'][] = $row;
                $data['product'][$key]['seller_user'] = $seller_user;
                $data['product'][$key]['serviceer'] = $serviceer;
                $sku = $row['sku'];

                //查询产品中文名以及产品线名
                $data_product = (new Query())
                        ->select('t.picking_name,t1.linelist_cn_name,t.gross_product_weight as product_weight')
                        ->from('{{%product}} as t')
                        ->leftjoin('{{%product_linelist}} as t1', 't1.id = t.product_linelist_id')
                        ->where('t.sku=:sku', array(':sku' => $sku))
                        ->createCommand(Yii::$app->db_product)
                        ->queryOne();

                //获取产品详情
                $productDetail = self::model('product_description', 'db_product')->where(['sku' => $sku, 'language_code' => 'Chinese'])->one();

                if (!empty($data_product)) {
                    $data['product'][$key]['picking_name'] = !empty($productDetail->title) ? $productDetail->title : $data_product['picking_name'];
                    $data['product'][$key]['linelist_cn_name'] = $data_product['linelist_cn_name'];
                    $data['product'][$key]['product_weight'] = $data_product['product_weight'];
                } else {
                    if (!empty($productDetail)) {
                        $data['product'][$key]['picking_name'] = $productDetail->title;
                        $data['product'][$key]['linelist_cn_name'] = '';
                        $data['product'][$key]['product_weight'] = 0;
                    } else {
                        $data['product'][$key]['picking_name'] = '无中文名称';
                        $data['product'][$key]['linelist_cn_name'] = '';
                        $data['product'][$key]['product_weight'] = 0;
                    }
                }
            }
        }

        //订单交易信息
        $trade = OrderTransactionKefu::getOrderTransactionDetailByOrderId($model->order_id);
        if (empty($trade)) {
            $xx = 1;
            $trade = self::model($platform->ordertransaction)->where(['order_id' => $model->order_id])->asArray()->all();
            if (empty($trade)) {
                $xx = 2;
                $trade = self::model($platform->ordertransactioncopy)->where(['order_id' => $model->order_id])->asArray()->all();
            }
        }

        if (!empty($trade)) {
            foreach ($trade as &$trvalue) {
                $receiverEmail = '';
                $payerEmail = '';
                $trvalue['receiver_email'] = '';
                $trvalue['payer_email'] = '';
                $receive_type = OrderTransactionKefu::getOrderTransactionType($trvalue['receive_type']);
                $trvalue['receive_type'] = $receive_type;
                if ($platformCode == Platform::PLATFORM_CODE_EB) {
                    if (empty($receiverEmail) || empty($payerEmail)) {
                        $transactionInfo = self::model('ebay_paypal_transaction')->where(['l_transaction_id' => $trvalue['transaction_id']])->one();
                        if (!empty($transactionInfo)) {
                            $paypalId = $transactionInfo->paypal;
                            $paypalEmail = '';
                            $paypalInfo = self::model('paypal_account', 'db_system')->where(['id' => $paypalId])->asArray()->one();
                            if (!empty($paypalInfo)) {
                                $paypalEmail = $paypalInfo['email'];
                            }
                            $amt = $transactionInfo->l_amt;
                            if ($amt < 0) {
                                $payerEmail = $paypalEmail;
                                $receiverEmail = $transactionInfo->l_email;
                            } else {
                                $payerEmail = $transactionInfo->l_email;
                                $receiverEmail = $paypalEmail;
                            }
                            $trvalue['receiver_email'] = $receiverEmail;
                            $trvalue['payer_email'] = $payerEmail;
                        }
                    }
                }
            }
        } else {
            $trade = [];
        }
        $data['trade'] = $trade;

        //订单包裹信息
        $orderPackageInfos = OrderPackageKefu::getOrderPackages($model->order_id);
        if (!empty($orderPackageInfos)) {
            foreach ($orderPackageInfos as $key => $row) {
                $packageId = $row['package_id'];
                $orderPackageInfos[$key]['warehouse_name'] = WarehouseKefu::getWarehouseNameById($row['warehouse_id']);
                $orderPackageInfos[$key]['ship_name'] = LogisticsKefu::getLogisticsName($model->ship_code);
                $orderPackageDetailInfos = OrderPackageDetailKefu::getPackageDetails($packageId);
                if (!empty($orderPackageDetailInfos)) {
                    $orderPackageInfos[$key]['items'] = $orderPackageDetailInfos;
                } else {
                    $orderPackageInfos[$key]['items'] = array();
                }
            }
        }
        $data['orderPackage'] = $orderPackageInfos;

        //订单利润详细
        $profit = self::model('order_profit')->where(['order_id' => $model->order_id])->asArray()->one();
        if (!empty($profit)) {
            $data['profit'] = $profit;
        } else {
            $data['profit'] = [];
        }

        //订单仓储物流
        if ($model->ship_code) {
            $Logistics = LogisticsKefu::getViewNameIdByShipCode($model->ship_code);
            if (!empty($Logistics)) {
                foreach ($Logistics as $value) {
                    $data['wareh_logistics']['logistics'] = $value->attributes;
                }
            }
        }
        if ($model->warehouse_id) {
            $warehouse = WarehouseKefu::getWarehouseById($model->warehouse_id);
            if (!empty($warehouse->attributes)) {
                $data['wareh_logistics']['warehouse'] = $warehouse->attributes;
            }
        }
        //amazon平台的单，把amazon item 明细查询出来
        if ($platformCode == Platform::PLATFORM_CODE_AMAZON) {
            $amazonItems = self::model('order_amazon_item')->where(['order_id' => $model->order_id])->all();
            if (empty($amazonItems)) {
                $amazonItems = [];
            }
            foreach ($amazonItems as $itemRow) {
                $data['items'][] = $itemRow->attributes;
            }
        }
        if (!isset($data['items'])) {
            $data['items'] = [];
        }

        //订单操作日志
        $data['logs'] = OrderUpdateLogKefu::getOrderUpdateLog($model->order_id);
        $ondeList = OrderNodeKefu::getOrderNode($model->order_id);
        $orderNodelist = OrderNodeKefu::getNnodeName();
        $LogisticsInfo = WarehouseKefu::getWarehouseById($model->warehouse_id);

        if (empty($LogisticsInfo) || $LogisticsInfo->warehouse_type != 1) {
            unset($orderNodelist[OrderNodeKefu::WAREHOUSE_PULL_ORDER]);
            unset($orderNodelist[OrderNodeKefu::WAREHOUSE_SCANNING_PACKAGE]);
            unset($orderNodelist[OrderNodeKefu::WAREHOUSE_SCANNING]);
        }
        if (!empty($ondeList[OrderNodeKefu::ORDER_PICKING])) {
            unset($orderNodelist[OrderNodeKefu::ORDER_SHORTAGE]);
        }
        if (!empty($ondeList[OrderNodeKefu::ORDER_SHORTAGE])) {
            unset($orderNodelist[OrderNodeKefu::ORDER_PICKING]);
        }

        $data['ondeList'] = isset($ondeList) && !empty($ondeList) ? $ondeList : array();
        $data['orderNodelist'] = isset($orderNodelist) && !empty($orderNodelist) ? $orderNodelist : array();

        //订单异常信息
        $abnormals = OrderAbnormityKefu::getOrderAbnormals($model->order_id);
        $data['abnormals'] = isset($abnormals) && !empty($abnormals) ? $abnormals : array();

        foreach ($data as $k => $v) {
            if ($k == 'product') {
                foreach ($data['product'] as $kk => $vv) {
                    $data['product'][$kk] = (object) $vv;
                }
            } elseif ($k == 'trade') {
                foreach ($data['trade'] as $kk => $vv) {
                    $data['trade'][$kk] = (object) $vv;
                }
            } elseif ($k == 'orderPackage') {
                foreach ($data['orderPackage'] as $kk => $vv) {
                    $data['orderPackage'][$kk] = (object) $vv;
                }
            } elseif ($k == 'profit' || $k == 'orderNodelist' || $k == 'info') {
                $data[$k] = (object) $v;
            }
        }
        $data = (object) $data;

        return $data;
    }

    /**
     * 根据售后单类型 和售后计算公式等信息计算售后单退款额/重寄额数据
     * @param int $type 售后单类型 1:退款单  3:重寄单
     * @param int $formula_id 计算方式
     * @param string $proCost 问题产品成本RMB(产品平均产品之和)
     * @param string $orderId 原订单ID
     * @param string $redirOrderId 重寄订单ID
     * @param string $registerAmount 登记的退款金额(RMB)
     * @param string $rate 汇率
     * @return string $amount            返回的退款额/重寄额
     * @author allen <2018-05-25>
     */
    public static function refundAmount($type, $formula_id, $proCost, $orderId, $redirOrderId = "", $registerAmount = "", $rate) {
        $amount = 0;
        $amount_rmb = 0;
        //退款
        if ($type == 1) {
            $model = self::model('order_profit', 'db_order')->where(['order_id' => $orderId])->one(); //原订单对象
            switch ($formula_id) {
                //不计退款额
                case 109:
                    $amount = 0;
                    $amount_rmb = 0;
                    break;
                //退款额 = 原订单利润
                case 110:
                case 111:
                    if (empty($model)) {
                        echo $orderId;
                        die;
                    } else {
                        $amount = $model->profit / $rate;
                        $amount_rmb = $model->profit;
                    }
                    break;
                //退款额=登记退款的金额
                case 112:
                case 114:
                    $amount = $registerAmount;
                    $amount_rmb = $registerAmount * $rate;
                    break;
                //退款额=原订单的运费
                case 113:
                    if (empty($model)) {
                        echo $orderId;
                        die;
                    } else {
                        $amount = $model->shipping_price / $rate;
                        $amount_rmb = $model->shipping_price;
                    }
                    break;
                //退款额=退款金额-问题产品成本
                case 115:
                case 116:
                    $amount = $registerAmount - $proCost / $rate;
                    $amount_rmb = ($registerAmount * $rate) - $proCost;
                    break;
            }
        }

        //重寄
        if ($type == 3) {
            $model = self::model('order_profit', 'db_order')->where(['order_id' => $redirOrderId])->one(); //重寄订单
            switch ($formula_id) {
                //109,112不会创建重寄单
                //重寄额=（重寄单利润+成本）的相反数
                case 110:
                case 111:
                case 113:
                case 115:
                case 116:
                    if (empty($model)) {
                        echo $orderId;
                        die;
                    } else {
                        $amount = -(($model->profit + $proCost) / $rate);
                        $amount_rmb = -($model->profit + $proCost);
                    }
                    break;
                //重寄额=重寄单利润的相反数
                case 114:
                    if (empty($model)) {
                        echo $orderId;
                        die;
                    } else {
                        $amount = -($model->profit);
                        if ($rate != 0) {
                            $amount_rmb = -(($model->profit) / $rate);
                        } else {
                            $amount_rmb = 0;
                        }
                    }
                    break;
            }
        }

        return ['refund_amount' => $amount, 'refund_amount_rmb' => $amount_rmb];
    }

    /**
     * 根据订单号获取对应的合并付款的子订单号【客户主动合并付款】
     * @param type $orderId
     * @author allen <2018-05-18>
     */
    public static function getPlatformOrderId($orderId) {
        $data = [];
        $model = self::model('mall_api_log', 'db_system')->where(['parent_order_id' => $orderId])->all();
        if (!empty($model)) {
            foreach ($model as $value) {
                if (!empty($value->platform_order_id)) {
                    $data[] = $value->platform_order_id;
                }
            }
        }
        return $data;
    }

    /**
     * 根据产品sku获取产品成本价
     * @param type $sku
     * @return type
     * @author allen <2018-05-26>
     */
    public static function getProAvgPrice($sku) {
        $cost = 0;
        $model = self::model('product', 'db_product')->where(['sku' => $sku])->one();
        if (!empty($model)) {
            $cost = $model->avg_price;
        }
        return $cost;
    }

    /**
     * 根据订单号获取订单售后统计所需数据
     * @param type $platformCode
     * @param type $orderId
     * @author allen <2018-05-26>
     */
    public static function getOrderInfo($platformCode, $orderId) {
        $platform = self::getOrderModel($platformCode);
        $model = self::model($platform->ordermain)->where(['order_id' => $orderId])->one();
        if (empty($model)) {
            $model = self::model($platform->ordermain . '_copy')->where(['order_id' => $orderId])->one();
        }

        if (!empty($model)) {
            $totalPrice = $model->total_price;
            $currency = $model->currency;
            $rateMonth = date('Ym', strtotime($model->created_time));
            //从汇率表获取订单汇率
            $rateModel = self::model('currency_rate', 'db_system')->where(['from_currency_code' => $currency, 'to_currency_code' => 'CNY', 'rate_month' => $rateMonth])->one();
            if (empty($rateModel)) {
                //汇率表获取不到数据到利润表中获取
                $rateModel = self::model('order_profit', 'db_order')->where(['order_id' => $orderId])->one();
                if ($rateModel) {
                    $rate = $rateModel->currency_rate;
                } else {
                    //还获取不到默认为0
                    $rate = 0;
                }
            } else {
                $rate = $rateModel->rate;
            }

            $totalPriceRmb = $totalPrice * $rate;

            return [
                'totalPrice' => $totalPrice,
                'currency' => $currency,
                'rate' => $rate,
                'totalPriceRmb' => $totalPriceRmb
            ];
        }
    }

    /**
     * 根据售后单获取对应的问题产品成本
     * @param type $afterSaleId
     * @author allen <2018-05-26>
     */
    public static function getProCostPrice($afterSaleId) {
        $costs = 0;
        $model = AfterSalesProduct::find()->where(['after_sale_id' => $afterSaleId])->all();
        if (!empty($model)) {
            foreach ($model as $value) {
                $sku = $value->sku;
                $cost = self::getProAvgPrice($sku);
                $qty = $value->issue_quantity;
                $costs += $cost * $qty;
            }
        }
        return $costs;
    }

    /**
     * 获取按月统计的已发货订单总销售额数据
     * @param type $platformCode 平台Code
     * @author allen <2018-06-12>
     */
    public static function getRunSaleDatas($platformCode, $date = '') {
//        ini_set('memory_limit', '4096M');
        set_time_limit(0);
        if (empty($date)) {
            $date = (Ym);
        }
        $platform = self::getOrderModel($platformCode);
        $rateDate = date("Ym", strtotime($date));
        $rateList = VHelper::getTargetCurrencyAmtAll($rateDate);
        $query = self::model($platform->ordermain);
        $query->select(['account_id', 'order_id', 'total_price', 'currency']);
        $query->where(['in', 'complete_status', [19, 20, 99]]);
        if ($platform == 'AMAZON') {
            $query->andWhere(['amazon_fulfill_channel' => 'MFN']);
        }
        $model = $query->andwhere(['like', 'shipped_date', $date . '%', FALSE])->asArray()->all();
        $price = 0;
        $i = 0;
        $arr = [];
        if (!empty($model)) {
            foreach ($model as $value) {
                if (strpos($value['order_id'], '-RE') === FALSE) {
                    $i++;
//                    if(isset($arr[$value['account_id']])){
//                        $arr[$value['account_id']]['price'] += $value['total_price'] * $rateList[$value['currency']];
//                    }else{
//                        $arr[$value['account_id']]['price'] = $value['total_price'] * $rateList[$value['currency']];
//                    }
                    $price += $value['total_price'] * $rateList[$value['currency']];
                }
            }

            if ($price) {
                $totalStatistics = AfterSaleTotalStatistics::find()->where(['platform_code' => $platformCode, 's_data' => $date])->one();
                if (empty($totalStatistics)) {
                    $totalStatistics = new AfterSaleTotalStatistics();
                    $totalStatistics->platform_code = $platformCode;
                    $totalStatistics->s_data = $date;
                    $totalStatistics->s_year = date('Y', strtotime($date));
                }
                $totalStatistics->price = round($price, 2);
                $totalStatistics->save();
            }

            echo '总订单量: ' . $i . '<br/>总销售额(RMB):' . $price;
        }
    }

    /**
     * 获取交易信息
     */
    public static function getTrade($order_id, $platformCode) {

        $platform = self::getOrderModel($platformCode);
        $trade = OrderTransactionKefu::getOrderTransactionDetailByOrderId($order_id);
        if (empty($trade)) {
            $trade = self::model($platform->ordertransaction)->where(['order_id' => $order_id])->asArray()->all();
            if (empty($trade)) {
                $trade = self::model($platform->ordertransactioncopy)->where(['order_id' => $order_id])->asArray()->all();
            }
        }
        return $trade;
    }

    /**
     * 获取利润信息
     */
    public static function getProfit($order_id) {
        //获取订单利润详细
        $info = self::model('order_profit')->where(['order_id' => $order_id])->asArray()->one();
        if (!empty($info)) {
            $profit = $info;
        } else {
            $profit = array();
        }
        return $profit;
    }

    /**
     * @author alpha
     * @desc
     * @param $platformCode
     * @param $order_id
     * @return bool
     */
    public static function getProductDetail($platformCode, $order_id) {
        //根据平台code获取对应的订单模型
        $platform = self::getOrderModel($platformCode);

        if (empty($platform)) {
            return false;
        }
        /*   switch ($platform) {
          case 'EB':
          $mallLink = 'http://www.ebay.com/itm/' . $value['item_id'];
          $endTag = '';
          break;
          case 'AMAZON':
          $mallLink = $value['detail_link_href'];
          $endTag = '';
          break;
          default :
          $mallLink = 'https://www.aliexpress.com/item//' . $value['item_id'];
          $endTag = '.html';
          } */


        if ($platformCode == 'EB') {
            $select = ['title', 'item_id'];
        } elseif ($platformCode == 'AMAZON') {
            $select = ['title', 'detail_link_href'];
        } else {
            $select = $select = ['title', 'item_id'];
        }

        //订单产品信息
        $detail = self::model($platform->orderdetail)->select($select)->where(['order_id' => $order_id])->asArray()->all();
        if (empty($detail)) {
            $detail = self::model($platform->orderdetailcopy)->select($select)->where(['order_id' => $order_id])->asArray()->all();
        }

        if(!empty($detail)){
            return self::unique_arr($detail);
        }
    }

    static function unique_arr($array2D, $stkeep = false, $ndformat = true) {
        $joinstr = '+++++';
        // 判断是否保留一级数组键 (一级数组键可以为非数字)
        if ($stkeep)
            $stArr = array_keys($array2D);
        // 判断是否保留二级数组键 (所有二级数组键必须相同)
        if ($ndformat)
            $ndArr = array_keys(end($array2D));
        //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
        foreach ($array2D as $v) {
            $v = join($joinstr, $v);
            $temp[] = $v;
        }
        //去掉重复的字符串,也就是重复的一维数组
        $temp = array_unique($temp);
        //再将拆开的数组重新组装
        foreach ($temp as $k => $v) {
            if ($stkeep)
                $k = $stArr[$k];
            if ($ndformat) {
                $tempArr = explode($joinstr, $v);
                foreach ($tempArr as $ndkey => $ndval)
                    $output[$k][$ndArr[$ndkey]] = $ndval;
            } else
                $output[$k] = explode($joinstr, $v);
        }
        return $output;
    }

    /**
     * 通过订单ID获取订单信息
     */
    public static function getOrders($platformCode, $order_id) {
        $platform = self::getOrderModel($platformCode);
        return $platform::findOne($order_id);
    }

    /**
     * 通过订单ID获取订单信息多个
     */
    public static function getplatformcodeOrders($platformCode) {
        $platform = self::getOrderModel($platformCode);
        return $platform::tableName();
    }

    /**
     * 获取指定账号对应sku最近3,7,15,30,60,90天亚马逊fba销量数据
     * @param type $accountId erp订单号
     * @param type $sku  公司sku
     * @author allen <2018-11-17>
     * 
     * SELECT t.account_id,d.sku,d.quantity,t.created_time FROM yibai_order_amazon t
      LEFT JOIN yibai_order_amazon_detail d ON t.order_id = d.order_id
      WHERE t.account_id = 230 AND d.sku = 'GYBJ3903'
      AND DATE_SUB(CURDATE(), INTERVAL 90 DAY) <= date(t.created_time)
      ORDER BY t.created_time
     */
    public static function getFbaReturnSales($sku,$accountId = null) {
        $salesQty3 = $salesQty7 = $salesQty15 = $salesQty30 = $salesQty60 = $salesQty90 = 0; //初始化
        $platform = self::getOrderModel("AMAZON");
        $query = self::model($platform->ordermain);
        $query->select('t.account_id,d.sku,d.quantity,t.created_time');
        $query ->from('{{%order_amazon}} AS t');
        $query ->leftjoin('{{%order_amazon_detail}} AS d', 't.order_id = d.order_id');
        $query ->where(['d.sku' => $sku]);
        if($accountId){
            $query -> andWhere(['t.account_id' => $accountId]);
        }
        $query->andWhere('DATE_SUB(CURDATE(), INTERVAL 90 DAY) <= date(t.created_time)');
        $res = $query ->orderBy('t.created_time')->asArray()->all();
        if (!empty($res)) {
            foreach ($res as $value) {
                $returnTime = strtotime(date('Y-m-d', strtotime($value['created_time']))); //退款时间
                $nowTime = strtotime(date('Y-m-d')); //当前时间
                $diff = $nowTime - $returnTime; //相差时间差
                $diffDay = $diff / 86400; //相差天数
                //只统计最近90天销量
                if ($diffDay <= 90) {
                    if ($diffDay > 60) {
                        $salesQty90 += $value['quantity'];
                    } elseif ($diffDay > 30) {
                        $salesQty60 += $value['quantity'];
                        $salesQty90 += $value['quantity'];
                    } elseif ($diffDay > 15) {
                        $salesQty30 += $value['quantity'];
                        $salesQty60 += $value['quantity'];
                        $salesQty90 += $value['quantity'];
                    } elseif ($diffDay > 7) {
                        $salesQty15 += $value['quantity'];
                        $salesQty30 += $value['quantity'];
                        $salesQty60 += $value['quantity'];
                        $salesQty90 += $value['quantity'];
                    } elseif ($diffDay > 3) {
                        $salesQty7 += $value['quantity'];
                        $salesQty15 += $value['quantity'];
                        $salesQty30 += $value['quantity'];
                        $salesQty60 += $value['quantity'];
                        $salesQty90 += $value['quantity'];
                    } else {
                        $salesQty3 += $value['quantity'];
                        $salesQty7 += $value['quantity'];
                        $salesQty15 += $value['quantity'];
                        $salesQty30 += $value['quantity'];
                        $salesQty60 += $value['quantity'];
                        $salesQty90 += $value['quantity'];
                    }
                }
                //echo '退货日期: ' . date('Y-m-d', strtotime($value['created_time'])) . '距离当前' . $diffDay . '天<br/>';
                //echo '3天销量: ' . $salesQty3 . '<br/>7天销量: ' . $salesQty7 . '<br/>15天销量: ' . $salesQty15 . '<br/>30天销量: ' . $salesQty30 . '<br/>60天销量: ' . $salesQty60 . '<br/>90天销量: ' . $salesQty90;
            }
        }

        return [
            'salesQty3' => $salesQty3,
            'salesQty7' => $salesQty7,
            'salesQty15' => $salesQty15,
            'salesQty30' => $salesQty30,
            'salesQty60' => $salesQty60,
            'salesQty90' => $salesQty90,
        ];
    }
    
    /**
     * 获取产品的状态
     * @param type $sku
     * @return type
     * @author allen <2018-11-21>
     */
    public static function getSkuStatus($sku){
        $model = self::model('{{%product}}', 'db_product')->select('product_status')->where(['sku' => $sku])->one();
        $proStatusList = self::skuStatusList();
        return $proStatusList[$model->product_status];
    }

}

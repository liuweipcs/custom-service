<?php

namespace app\modules\orders\models;

use Yii;
use app\components\Model;
use app\modules\mails\models\AliexpressInbox;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;

class OrderAliexpressKefu extends Model
{
    public $orderdetail = 'order_aliexpress_detail';
    public $orderdetailcopy = 'order_aliexpress_detail_copy';
    public $ordermain = 'order_aliexpress';
    public $ordermaincopy = 'order_aliexpress_copy';
    public $ordernote = 'order_aliexpress_note';
    public $ordertransaction = 'order_aliexpress_transaction';
    public $ordertransactioncopy = 'order_aliexpress_transaction_copy';
    public $product_detail;

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_order;
    }

    public static function tableName()
    {
        return '{{%order_aliexpress}}';
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\ActiveRecord::attributes()
     */
    public function attributes()
    {
        $attributes        = parent::attributes();
        $extraAttributes[] = 'issue_status';                //纠纷状态
        $extraAttributes[] = 'refund_status_text';          //退款状态
        $extraAttributes[] = 'after_sale_text';             //是否有售后问题
        $extraAttributes[] = 'evaluate';                    //订单评价
        $extraAttributes[] = 'warehouse_name';              //发货仓库名称
        $extraAttributes[] = 'warehouse_type';              //发货仓库类型
        $extraAttributes[] = 'ship_name';                   //发货方式
        $extraAttributes[] = 'order_link';                  //订单号链接
        $extraAttributes[] = 'complete_status_text';        //
        $extraAttributes[] = 'package_info';                //包裹信息
        $extraAttributes[] = 'account_country_buyer';       //账号,国家,买家ID
        $extraAttributes[] = 'pay_time_status';             //付款时间,订单状态
        $extraAttributes[] = 'order_refund_monery';          //订单,退款,利润信息
        $extraAttributes[] = 'issue_feedback_sale';          //纠纷,评价，售后
        $extraAttributes[] = 'order_status_time';            //订单状态,收货时间，延迟收货时间
        $extraAttributes[] = 'product_detail';//产品详情
        return array_merge($attributes, $extraAttributes);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'account_id' => '账号名',
            'order_id'   => '订单号',
            'platform_order_id' => '平台订单号'
        ];
    }
    
    /**
     * @desc 搜索过滤项
     * @return multitype:multitype:string multitype:  multitype:string multitype:string
     */
    public function filterOptions()
    {
        $accountInfos = Account::getCurrentUserPlatformAccountList(Platform::PLATFORM_CODE_ALI, 
            Account::STATUS_VALID);
        $accountList = [];
        if (!empty($accountInfos))
        {
            foreach ($accountInfos as $accountInfo)
                $accountList[$accountInfo->old_account_id] = $accountInfo->account_name;
        }
        return [
            [
                'name' => 'order_id',
                'type'   => 'text',
                'search' => '=',
            ],
            [
                'name' => 'platform_order_id',
                'type'   => 'text',
                'search' => '=',
            ],
            [
                'name' => 'account_id',
                'type'   => 'search',
                'data'   => $accountList,
                'search' => '=',
            ]
        ];
    }

    /**
     * 重写父类的查询列表方法
     */
    public function searchList($params = [], $sort = null)
    {
        $query = self::find()->alias('t')->distinct();
        if (empty($sort)) {
            $sort               = new \yii\data\Sort([
                'attributes' => [
                    'paytime'
                ],
            ]);
            $sort->defaultOrder = array(
                'paytime' => SORT_DESC,
            );
        }
        $dataProvider = parent::search($query, $sort, $params);
        $models       = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * 重写父类的查询列表方法
     */
    public function searchMessageOrderList($params = [], $sort = null)
    {
        /**
         * @var $query \yii\db\ActiveQuery
         */
        $query = self::find()->alias('t')->distinct();
        $query->select('order_id,platform_code,platform_order_id,account_id,order_status,email,buyer_id,timestamp,
            created_time,last_update_time,paytime,ship_name,ship_street1,ship_street2,ship_zip,ship_city_name,
            ship_stateorprovince,ship_country,ship_country_name,ship_phone,ship_cost,subtotal_price,total_price,
            currency,payment_status,refund_status,ship_code,complete_status,amazon_fulfill_channel,warehouse_id,
            order_profit_rate,calculate_profit_flag,parent_order_id,order_type,is_upload,buyer_option_logistics,
            upload_time,company_ship_code,real_ship_code,track_number,shipped_date,priority_satus,is_manual_order
            ');
        $page     = 1;
        $pageSize = \Yii::$app->params['defaultPageSize'];
        if (isset($params['page']))
            $page = (int)$params['page'];
        if (isset($params['pageSize']))
            $pageSize = (int)$params['pageSize'];

        if (!$sort instanceof \yii\data\Sort)
            $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'paytime' => SORT_DESC,
        );
        if (isset($params['sortBy']) && !empty($params['sortBy']))
            $sortBy = $params['sortBy'];
        if (isset($params['sortOrder']) && !empty($params['sortOrder']))
            $sortOrder = strtoupper($params['sortOrder']) == 'ASC' ? SORT_ASC : SORT_DESC;
        if (!empty($sortBy)) {
            $sort->attributes[$sortBy] = [
                'label' => $this->getAttributeLabel($sortBy),
                'desc'  => [$sortBy => SORT_DESC],
                'asc'   => [$sortBy => SORT_ASC]
            ];
            $sort->setAttributeOrders([$sortBy => $sortOrder]);
        }  
        if (isset($params['buyer_id']) && !empty($params['buyer_id'])) {
            $query->andWhere('buyer_id = :buyer_id', [':buyer_id' => $params['buyer_id']]);
        }
        if (isset($params['account_id']) && !empty($params['account_id'])) {
            $query->andWhere('account_id = :account_id', [':account_id' => $params['account_id']]);
        }
        if (isset($params['order_id']) && !empty($params['order_id'])) {
            $query->andWhere('order_id = :order_id', [':order_id' => $params['order_id']]);
        }
        if (isset($params['platform_order_id']) && !empty($params['platform_order_id'])) {
            $query->andWhere('platform_order_id = :platform_order_id', 
                [':platform_order_id' => $params['platform_order_id']]);
        }
        $dataProvider = new \yii\data\ActiveDataProvider([
            'query'      => $query,
            'sort'       => $sort,
            'pagination' => [
                'pageSize' => $pageSize,
                'page'     => ($page - 1)
            ]
        ]);
       
        $models       = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * @author alpha
     * @desc 平台id获取仓库id
     * @param $platform_order_id
     * @return int|mixed
     */
    public static function getWarehouseId($platform_order_id)
    {
        $warehouse_id = self::find()
            ->select('warehouse_id')
            ->where('platform_order_id = :platform_order_id', [':platform_order_id' => $platform_order_id])
            ->asArray()
            ->one();
        return isset($warehouse_id) ? $warehouse_id['warehouse_id'] : 0;
    }
              /**
     * @author alpha
     * @desc 返回site
     * @param $platform_order_id
     * @return mixed
     */
    public static function getSiteByPlatformId($platform_order_id) {
        $site_arr = self::find()->select(['t1.site'])
                        ->from(self::tableName() . ' t')
                        ->join('LEFT JOIN', '{{%order_aliexpress_detail}} t1', 't.order_id = t1.order_id')
                        ->andWhere(['t.platform_order_id' => $platform_order_id])
                        ->asArray()->one();
        if (!empty($site_arr)) {
            return $site_arr['site'];
        } else {
            unset($query);
            $site_arr = self::find()->select(['t1.site'])
                            ->from('{{%order_aliexpress_copy}} t')
                            ->join('LEFT JOIN', '{{%order_aliexpress_detail_copy}} t1', 't.order_id = t1.order_id')
                            ->andWhere(['t.platform_order_id' => $platform_order_id])
                            ->asArray()->one();
            if (!empty($site_arr)) {
                return $site_arr['site'];
            }
        }
    }
}
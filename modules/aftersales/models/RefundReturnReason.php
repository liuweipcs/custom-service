<?php

namespace app\modules\aftersales\models;

use Yii;

/**
 * This is the model class for table "{{%refund_return_reason}}".
 *
 * @property integer $id
 * @property string $content
 * @property string $mark
 */
class RefundReturnReason extends AfterSalesModel
{
    const REASON_NOT_SEND = 16;
    const REASON_NOT_RECEIVE = 7;

    public static $returnReasonMaps = array(
        'OUT_OF_STOCK'              => '16',
        'BUYER_CANCEL_ORDER'        => '19',
        'VALET_DELIVERY_ISSUES'     => '19',
        'VALET_UNAVAILABLE'         => '19',
        'EXPIRED_ITEM'              => '20',
        'FOUND_BETTER_PRICE'        => '20',
        'NO_LONGER_NEED_ITEM'       => '20',
        'NO_REASON'                 => '20',
        'ORDERED_ACCIDENTALLY'      => '20',
        'ORDERED_WRONG_ITEM'        => '20',
        'OTHER'                     => '20',
        'RETURNING_GIFT'            => '20',
        'WRONG_SIZE'                => '20',
        'DIFFERENT_FROM_LISTING'    => '23',
        'NOT_AS_DESCRIBED'          => '23',
        'MISSING_PARTS'             => '24',
        'ORDERED_DIFFERENT_ITEM'    => '24',
        'ARRIVED_LATE'              => '20',
        'ARRIVED_DAMAGED'           => '26',
        'DEFECTIVE_ITEM'            => '27',
        'FAKE_OR_COUNTERFEIT'       => '27',
        'BUYER_NO_SHOW'             => '12',
        'BUYER_NOT_SCHEDULED'       => '12',
        'BUYER_REFUSED_TO_PICKUP'   => '12',
        'IN_STORE_RETURN'           => '12',
    );
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%refund_return_reason}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['content', 'mark', 'sort_order'], 'required'],
            [['content'], 'string', 'max' => 250],
            [['mark'], 'string', 'max' => 200],
            [['create_time', 'modify_time'], 'safe'],
            [['create_by','modify_by'], 'string', 'max' => 50],
            ['sort_order', 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'content' => '内容',
            'mark' => '备注',
            'create_by' => '创建人',
            'modify_by' => '修改人',
            'create_time' => '创建时间',
            'modify_time' => '修改时间',
            'sort_order' => '排序',
        ];
    }


    /**
     * @desc search list
     * @param unknown $params
     * @param string $query
     */
    public function searchList($params = [])
    {
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'id' => SORT_ASC
        );

        $query = self::find();
        $dataProvider = parent::search(null, $sort, $params);
        $models = $dataProvider->getModels();
        
        /*
        foreach ($models as  $key => $model) {
            $models[$key]->setAttribute('status_text', self::getStatusList($model->status));
        }*/

        $dataProvider->setModels($models);
        return $dataProvider;
    }
    /**
     * 获取所有的退款原因数据
     * 
     */
    public static function getList($returnType = "")
    {
        $model = self::find()->from(self::tableName())->select("id,content")->orderBy(['sort_order' => SORT_ASC])->all();
        if($returnType == 'Array'){
            foreach ($model as $value) {
                $result[$value->id] = $value->content;
            }
        }else{
            $result = $model;
        }
        return $result;
    }
    
    /**
     * 根据指定退货退款原因id获取原因内容
     * @param int $reason_id 退货退款原因id
     */
    public static function getReasonContent($reason_id)
    {
        static $reasonList = [];
        if (empty($reasonList))
        {
            $res = self::getList();
            if (!empty($res))
            {
                foreach ($res as $row)
                    $reasonList[$row['id']] = $row['content'];
            }
        }
        if (array_key_exists($reason_id, $reasonList))
            return $reasonList[$reason_id];
        return '';
    }



}

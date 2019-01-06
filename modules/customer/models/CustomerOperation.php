<?php

namespace app\modules\customer\models;
use app\components\Model;

class CustomerOperation extends Model{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%customer_operation}}';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['buyer_id'], 'integer'],
            [['create_time'], 'safe'],
            [['follow_status', 'mark', 'action', 'create_by'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'buyer_id' => 'buyer_id',
            'action' => 'action',
            'follow_status' => 'follow_status',
            'remark' => 'mark',
            'create_time' => 'Create Time',
            'create_by' => 'Create By',
        ];
    }

    /**
     * 获取跟进状态信息
     * @param type $buyer_id
     * @return type
     * @author zhangchu
     */
    public static function getFollowData($id) {
 		if (!$id) {
 			return '';
 		}
    	$follow_log = self::find()
    	                 ->select('id,action,follow_status,mark,buyer_id')
    	                 ->where(['action'=>'更新跟进状态','buyer_id'=>$id])
    	                 ->orderBy('create_time DESC')
    	                 ->one();

        return $follow_log;
    }

    /**
     * 保存操作日志
     * @param type $attributes
     * @return type
     * @author allen <2018-03-27>
     */
    public static function addData($attributes) {
        $model = new CustomerOperation();
        $model->attributes = $attributes;
        return $model->save(false);
    }


}
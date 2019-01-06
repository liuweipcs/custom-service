<?php

namespace app\modules\mails\models;

use Yii;

/**
 * This is the model class for table "amazon_review_log".
 *
 * @property integer $id
 * @property integer $review_data_id
 * @property integer $action
 * @property string $remark
 * @property string $create_time
 * @property string $create_by
 */
class AmazonReviewLog extends \yii\db\ActiveRecord {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%amazon_review_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['review_data_id'], 'integer'],
            [['create_time'], 'safe'],
            [['remark', 'action', 'create_by'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'review_data_id' => 'Review Data ID',
            'action' => 'Action',
            'remark' => 'Remark',
            'create_time' => 'Create Time',
            'create_by' => 'Create By',
        ];
    }

    /**
     * 保存操作日志
     * @param type $attributes
     * @return type
     * @author allen <2018-03-27>
     */
    public static function addData($attributes) {
        $model = new AmazonReviewLog();
        $model->attributes = $attributes;
        return $model->save();
    }
    
    
    /**
     * 获取操作记录
     * @param type $id
     * @author allen <2018-03-27>
     */
    public static function getLogData($id){
        return self::find()->where(['review_data_id'=>$id])->orderBy('create_time desc')->asArray()->all();
    }

}

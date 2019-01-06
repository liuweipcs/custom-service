<?php

namespace app\modules\blacklist\models;

use Yii;

/**
 * This is the model class for table "{{%blacklist}}".
 *
 * @property integer $id
 * @property integer $platfrom_id
 * @property string $platfrom_code
 * @property string $username
 * @property string $create_time
 * @property string $modify_time
 */
class BlackList extends \yii\db\ActiveRecord {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%blacklist}}';
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->create_time = date('Y-m-d H:i:s',time());
            } else {
                $this->modify_time = date('Y-m-d H:i:s',time());
            }
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['platfrom_id'], 'integer'],
            ['platfrom_id', 'unique', 'message' => '当前所选平台已存在,请前往列表找到对应平台进行更新操作!'],
            [['platfrom_id'], 'required'],
            [['username','myself_username'], 'string'],
            [['create_time', 'modify_time'], 'safe'],
            [['platfrom_code'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'platfrom_id' => '平台',
            'platfrom_code' => 'Platfrom Code',
            'username' => 'GBC黑名单(请用英文输入法的逗号隔开多个会员ID)',
            'myself_username' => '自己平台设置的黑名单',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
        ];
    }
    
    /**
     * 判断当前客户是否在黑名单中存在
     * @param type $platfromId  平台ID
     * @param type $userName 用户ID
     * @return boolean  true:已存在  false:不存在
     */
    public static function checkIsBlack($platfromId,$userName){
        $bool = FALSE;
        $model = self::find()->select('username,myself_username')->where(['platfrom_id'=>$platfromId])->asArray()->one();
        if(!empty($model)){
            $usernameArr = explode(',',$model['username']);
            if(in_array($userName,$usernameArr)){
                $bool = TRUE;
            }
            
            $myselfArr = explode(',',$model['myself_username']);
            if(in_array($userName,$myselfArr)){
                $bool = TRUE;
            }
        }
        
        
        return $bool;
    }

}

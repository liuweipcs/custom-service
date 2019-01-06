<?php

namespace app\modules\systems\models;

use Yii;

/**
 * This is the model class for table "{{%basic_config}}".
 *
 * @property integer $id
 * @property integer $parent_id
 * @property string $name
 * @property string $text
 * @property string $create_time
 * @property integer $create_id
 * @property string $create_name
 */
class BasicConfig extends \yii\db\ActiveRecord {

    public $level_two;
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%basic_config}}';
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->create_time = date('Y-m-d H:i:s', time());
                $this->create_id = USER_ID;
                $this->create_name = USER_NAME;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['parent_id', 'name', 'create_time', 'create_id', 'create_name'], 'required'],
            [['parent_id','level', 'create_id', 'status'], 'integer'],
            [['text'], 'string'],
            [['create_time'], 'safe'],
            [['name', 'create_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '编号',
            'parent_id' => '类型',
            'name' => '名称',
            'level' => '级别',
            'status' => '状态',
            'text' => '备注',
            'create_time' => '创建时间',
            'create_id' => 'Create ID',
            'create_name' => '创建人',
            'level_two' => '二级分类'
        ];
    }

    /**
     * 获取一级目录列表数据
     * @return type
     * @author allen <2018-02-11>
     */
    public static function getParentList($parentId = 0) {
        if($parentId == 0){
            $arr = [0 => '一级分类'];
        }else{
            $arr = [' ' => '---请选择---'];
        }
        $model = self::find()->where(['parent_id' => $parentId, 'status' => 2])->all();
        if (!empty($model)) {
            foreach ($model as $value) {
                $arr[$value->id] = $value->name;
            }
        }
        
        return $arr;
    }
    
    /**
     * 获取所有可用配置数据
     * @return type
     */
    public static function getAllConfigData(){
        $arr = [];
        $model = self::find()->where(['status'=>2])->all();
        if(!empty($model)){
            foreach ($model as $value) {
                $arr[$value->id] = $value->name;
            }
        }
        return $arr;
    }

}

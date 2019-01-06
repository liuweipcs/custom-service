<?php

namespace app\modules\systems\models;
use app\modules\systems\models\Rule;
//use Yii;

/**
 * This is the model class for table "{{%tag}}".
 *
 * @property integer $id
 * @property integer $platform_id
 * @property string $tag_name
 * @property integer $parent_tag_id
 * @property string $tag_en_name
 * @property integer $status
 * @property integer $tag_type
 * @property integer $sort_order
 * @property string $create_by
 * @property string $create_time
 * @property string $modify_by
 * @property string $modfiy_time
 */
class SubjectPreg extends SystemsModel
{
    const TAG_STATUS_VALID = 1;
    const TAG_STATUS_INVALID = 0;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ebay_subject_preg}}';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['preg_str'], 'required'],
            [['status'], 'integer'],
            [['create_time', 'modify_time'], 'safe'],
            [['create_by', 'modify_by'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID', 
            'preg_str'   => '包含内容',
            'status'        => \Yii::t('tag', 'Status'),
            'create_by'     => '创建人',
            'create_time'   => \Yii::t('tag', 'Create Time'),
            'modify_by'     => \Yii::t('tag', 'Modify By'),
            'modify_time'   => \Yii::t('tag', 'Modify Time'),
        ];
    }

    public function filterOptions()
    {
        return [
            [
                'name' => 'preg_str',
                'type' => 'text',
                'search'=>'=',
            ],
            [
                'name' => 'status',
                'type' => 'dropDownList',
                'data' => array(0=>'禁用',1=>'启用'),
                'search' => '=',
            ],

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

        foreach ($models as  $key => $model) {
            $models[$key]->setAttribute('status', self::getStatusList($model->status));
        }

        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * @desc 获取状态列表
     * @param string $key
     * @return Ambigous <\yii\string>|string|multitype:\yii\string
     */
    public static function getStatusList($key = null)
    {
        $list = [
            '0' => \Yii::t('system', 'Invalid'),
            '1' => \Yii::t('system', 'Valid'),
        ];
        if (!is_null($key))
        {
            if (array_key_exists($key, $list))
                return $list[$key];
            else
                return '';
        }
        return $list;
    }

}

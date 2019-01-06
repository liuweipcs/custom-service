<?php

namespace app\modules\systems\models;
use app\modules\accounts\models\Platform;
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
class Keyboard extends SystemsModel
{   
    //const TAG_TYPE_SELF = 1;
    //const TAG_TYPE_AUTO_SEARCH = 2;
    const TAG_STATUS_VALID = 1;
    const TAG_STATUS_INVALID = 0;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_keyboard}}';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'tag_id', 'key_code','key_basic'], 'required'],
            [['status'], 'integer'],
            [['create_time', 'modify_time'], 'safe'],
            [[ 'tag_name', 'key_name','create_by', 'modify_by','platform_code'], 'string', 'max' => 50],
        ];
    }
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\ActiveRecord::attributes()
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = ['status_text'];              //状态
        return array_merge($attributes, $extraAttributes);
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
            $models[$key]->setAttribute('status_text', self::getStatusList($model->status));
        }

        $dataProvider->setModels($models);
        return $dataProvider;
    }

    public function dynamicChangeFilter(&$filterOptions,&$query,&$params)
    {
        $platformArray = isset(\Yii::$app->user->identity->role->platform_code) ? explode(',',\Yii::$app->user->identity->role->platform_code) : Platform::getPlatformAsArray();

        if(empty($params['platform_code']))
        {
            $query->andWhere(['in','platform_code',$platformArray]);
        }

    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID', 
            'user_id'   => 'UserId',
            'tag_id' => '标签名',
            'tag_name' => '标签名',
            'key_code'   => 'KeyCode',
            'key_name'   => '快捷键',
            'platform_code'      => '所属平台',
            'status'        => \Yii::t('tag', 'Status'),
            'create_by'     => '使用人',
            'create_time'   => \Yii::t('tag', 'Create Time'),
            'modify_by'     => \Yii::t('tag', 'Modify By'),
            'modify_time'   => \Yii::t('tag', 'Modify Time'),
            'status_text'   => \Yii::t('tag', 'Status Text'),
        ];
    }

    public function filterOptions()
    {
        $platformArray = isset(\Yii::$app->user->identity->role->platform_code) ? explode(',',\Yii::$app->user->identity->role->platform_code) : array();
        $platform = array();
        $allplatform = Platform::getPlatformAsArray();
        if($platformArray)
        {
            foreach ($platformArray as $value)
            {
                $platform[$value] = isset($allplatform[$value]) ? $allplatform[$value] : $value;
            }
        }
        $platform = !empty($platform) ? $platform : $allplatform;
        return [
//            [
//                'name' => 'create_by',
//                'type' => 'text',
//                'search'=>'=',
//            ],
            [
                'name' => 'platform_code',
                'type' => 'dropDownList',
                'data' =>  $platform,
                'htmlOptions' => [],
                'search' => '='
            ],

        ];
    }

    /**
     * 一次性获取有效的标签并且组合成树状格式
     * @param string   $platform_code
     * @return array $list 返回的结果
     */
    public static function getKeyboardsAsArray($platform_code)
    {
        $query = new \yii\db\Query();
        $query ->from(self::tableName())->select('tag_id,key_code,key_basic');

        $query->where('platform_code = :platform_code',[':platform_code'=>$platform_code]);

        $keyboards = $query->all();

        $key_basic_map = ['shift','ctrl','alt'];

        $list = '';

        for($i = 0; $i<3; $i++)
        {
            foreach($keyboards as $keyboard)
            {
                if($keyboard['key_basic'] == $key_basic_map[$i])
                {
                    $list[$key_basic_map[$i]][$keyboard['key_code']] = $keyboard['tag_id'];
                }
            }
        }

        return $list;
    }

}

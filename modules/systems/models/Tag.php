<?php

namespace app\modules\systems\models;

use app\modules\systems\models\Rule;
use app\modules\accounts\models\Platform;
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
class Tag extends SystemsModel
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
        return '{{%tag}}';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['platform_code', 'tag_name','tag_en_name','status'], 'required'],
            [['status', 'sort_order'], 'integer'],
            [['create_time', 'modfiy_time'], 'safe'],
            [['tag_name', 'tag_en_name', 'create_by', 'modify_by','platform_code'], 'string', 'max' => 50],
        ];
    }

    /**
     * 搜索筛选项
     */
    public function filterOptions()
    {
        return [
            [
                'name' => 'platform_code',
                'type' => 'dropDownList',
                'data' => self::platformDropdown(),
                'search' => '='
            ],
            [
                'name' => 'tag_name',
                'type' => 'text',
                'search' => '=',
            ]
        ];
    }

    /**
     * 返回平台下拉框数据
     */
    public static function platformDropdown()
    {
        return Platform::getPlatformAsArray();
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
    /**
     * 根据签id获取标签名称
     * @param $id
     */
    public static function getTagNameById($id)
    {   
        if ($id==0) {
            return \Yii::t('tag', 'No Higher Level');
        }

        $query = new \yii\db\Query();
        $data = $query->from(self::tableName())
              ->select('tag_name')
              ->where('id = :id', [':id' => $id])
              ->one();
        return $data['tag_name'];
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID', 
            'platform_id'   => \Yii::t('tag', 'platform Id'),
            'platform_code' => \Yii::t('tag', 'platform Code'),
            'tag_name'      => \Yii::t('tag', 'tag Name'),
            'parent_tag_id' => \Yii::t('tag', 'parent Tag Id'),
            'tag_en_name'   => \Yii::t('tag', 'tag En Name'),
            'tag_type'      => \Yii::t('tag', 'tag Type'),
            'sort_order'    => \Yii::t('tag', 'sort Order'),
            'status'        => \Yii::t('tag', 'Status'),
            'create_by'     => \Yii::t('tag', 'Create By'),
            'create_time'   => \Yii::t('tag', 'Create Time'),
            'modify_by'     => \Yii::t('tag', 'Modify By'),
            'modfiy_time'   => \Yii::t('tag', 'Modify Time'),
            'status_text'   => \Yii::t('tag', 'Status Text'),
            'parent_tag_name' => \Yii::t('tag', 'parent Tag Id'),
            'tag_type_name' => \Yii::t('tag', 'tag Type'),
        ];
    }
    /**
     * 一次性获取有效的标签并且组合成树状格式
     * @param int   $parentId 父标签id
     * @param int   $level 控制构造标签树状格式的层级
     * @param int   $status 标签状态
     * @param array $list 返回的结果
     */
    public static function getTagAsArray($platform_code,$ids=[],$status = self::TAG_STATUS_VALID)
    {   
        $query = new \yii\db\Query();
        $query ->from(self::tableName())->select('id,tag_name');

        //查找有效状态的标签
        $query->andWhere('status = :status and platform_code = :platform_code', [':status' => (int)$status,':platform_code'=>$platform_code]);

        if (!empty($ids)) {
            $query->andWhere(['in', 'id', $ids]);
        }
        
        //获取数据
        $data = $query->all();

        //没有标签数据
        if (empty($data)) {
            return [];
        }
        
        //组装返回下拉格式的数据
        $list = [];
        foreach ($data as $key => $value) {
            $list[$value['id']] = $value['tag_name'];
        }

        return $list;
    }
    /**
     * 判断指定标签的id是否允许删除
     * @param int      $tag_id 标签id
     * @return boolean 返回是否允许被删除
     */
    public static function isAlowDeleteById($tag_id)
    {
        $count = Rule::getCountByTypeAndRelationId(Rule::RULE_TYPE_TAG,$tag_id);

        //该标签已经被绑定规则不允许删除
        if ($count) {
            return false;
        }

        //该允许删除
        return true;
    } 
    /**
     * 根据给的要删除的数组id,返回允许删除的id数组
     * @param  array  $idArray 由标签id组成的数组
     * @return array  返回允许删除的标签Ids数组数据
     */
    public static function getAllowDeleteId($idArray)
    {
        $result = [];
        foreach ($idArray as $key => $value) {
            if (static::isAlowDeleteById($value)) {
                $result[] = $value;
            }
        }
        return $result;
    }
    
    /**
     * @desc 获取平台标签列表
     * @param unknown $platformId
     */
    public static function getPlatformTagList($platformCode)
    {
        $query = self::find()
        ->from(self::tableName())
        ->select("*")
        ->where("platform_code = :platform_code", [':platform_code' => $platformCode])
        ->andWhere("status = " . self::TAG_STATUS_VALID)
        ->orderBy(['sort_order' => SORT_ASC]);
        return $query->all();
    }
}

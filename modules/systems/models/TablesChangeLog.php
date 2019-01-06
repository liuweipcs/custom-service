<?php

namespace app\modules\systems\models;

use Yii;
use app\components\Model;
/**
 * This is the model class for table "{{%tables_change_log}}".
 *
 * @property integer $id
 * @property string $table_name
 * @property string $create_by
 * @property string $create_time
 * @property integer $change_type
 * @property string $change_content
 */
class TablesChangeLog extends \yii\db\ActiveRecord
{   
    public static $tableChangeLogEnabled = false;        //是否记录数据表操作日志
    
    const CHANGE_TYPE_INSERT = 1;
    const CHANGE_TYPE_UPDATE = 2;
    const CHANGE_TYPE_DELETE = 3;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tables_change_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['table_name', 'create_by', 'create_time', 'change_type', 'change_content'], 'required'],
            [['id', 'change_type'], 'integer'],
            [['create_time'], 'safe'],
            [['change_content'], 'string'],
            [['table_name'], 'string', 'max' => 200],
            [['create_by','create_ip'], 'string', 'max' => 50],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'table_name' => '表名称',
            'create_by' => '操作人',
            'create_time' => '操作时间',
            'change_type' => '操作类型',
            'change_content' => '操作内容',
            'create_ip' => '操作ip',
            'change_type_text' => '操作类型',
        ];
    }
    public static function get_change_type_data($key='')
    {
        $list = [
             static::CHANGE_TYPE_INSERT => 'insert',
             static::CHANGE_TYPE_UPDATE => 'update',
             static::CHANGE_TYPE_DELETE => 'delete',
        ];
        
        if (!empty($key)) {
            return $list[$key];
        }
        
        return $list;
    }
    /**
     * 根据表操作情景来新增日志
     * @param string $table_name 数据变动的表名称
     * @param int    $change_type 变动类型(1insert，2update，3delete)
     * @param string $change_content 变动的内容描述
     */
    public static function save_tables_change_log_data($table_name,$change_type,$change_content)
    {
        $model = new self();
        $model->table_name = $table_name;
        $model->create_time = date('Y-m-d H:i:s');
        $model->create_by = Model::SYSTEM_USER;
        $user = null;
        if (isset(\Yii::$app->user))
            $user = \Yii::$app->user->getIdentity();

        if ($user != null) {
            $model->create_by = $user->login_name;
        }
        //系统自动脚本操作的不记录日志
        if ($model->create_by == Model::SYSTEM_USER)
            return true;
        $model->change_type = $change_type;
        $model->change_content = $change_content;
        $model->create_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        
        if (!$model->save()) {
            throw new \Exception(current(current($model->getErrors()))); 
        }

        return $model->id;
    }
    /**
     * @desc search list
     * @param unknown $params
     * @param string $query
     */
    public  function searchList($params = [])
    {   
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'id' => SORT_DESC
        );

        $query = self::find();
        $dataProvider = self::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        foreach ($models as  $key => $model) {
            $models[$key]->setAttribute('change_type_text', self::get_change_type_data($model->change_type));
        }
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = ['change_type_text'];             
        return array_merge($attributes, $extraAttributes);
    }

    public function setFilterOptions($query, $params)
    {
        $filterOptions = $this->filterOptions();
        if (!empty($filterOptions)) {
            foreach ($filterOptions as $row) {
                $name = isset($row['name']) ? trim($row['name']) : '';
                if (empty($name)) continue;
                $search = isset($row['search']) ? trim($row['search']) : '=';
                $value = '';
                if (isset($params[$name]))
                    $value = $params[$name];
                if (!is_array($value))
                    $value = trim($value);
                if ($value === '') continue;
                $alias = isset($row['alias']) ? trim($row['alias']) : '';
                $fieldName = $name;
                if (!empty($alias))
                    $fieldName = $alias . '.' . $name;
                switch ($search)
                {
                    case '=':
                        $query->andWhere([$fieldName => $value]);
                        break;
                    case 'LIKE':
                        $query->andWhere(['like', $fieldName, $value . '%', false]);
                        break;
                }
            }
        }
    }

    public function filterOptions()
    {
        return [
            [
                'name' => 'table_name',
                'type' => 'text',
                'htmlOptions' => [],
                'search' => 'LIKE'
            ],
        ];
    }

    protected  function search($query = null, $sort = null, $params = [])
    { 
        if (!$query instanceof QueryInterface) {
            $query = self::find();
            $query->from(static::tableName() . ' as t');
        }
        
        $this->setFilterOptions($query, $params);

        $page = 1;
        $pageSize = \Yii::$app->params['defaultPageSize'];

        if (isset($params['page'])) {
            $page = (int)$params['page'];
        }

        if (isset($params['pageSize'])) {
            $pageSize = (int)$params['pageSize'];
        }
        
        if (!$sort instanceof \yii\data\Sort) {
            $sort = new \yii\data\Sort();
        }
        
        if (isset($params['sortBy']) && !empty($params['sortBy'])) {
            $sortBy = $params['sortBy'];
        }

        if (isset($params['sortOrder']) && !empty($params['sortOrder'])) {
            $sortOrder = strtoupper($params['sortOrder']) == 'ASC' ? SORT_ASC : SORT_DESC;
        }
        
        if (!empty($sortBy)) {
            $sort->attributes[$sortBy] = [
                'label' => $this->getAttributeLabel($sortBy),
                'desc' => [$sortBy => SORT_DESC],
                'asc' => [$sortBy => SORT_ASC]
            ];
            $sort->setAttributeOrders([$sortBy => $sortOrder]);
        }

        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
            'sort' => $sort,
            'pagination' => [
                'pageSize' => $pageSize,
                'page' => ($page - 1)
            ]
        ]);
        return $dataProvider;
    }
}

<?php

/**
 * @desc Model base class
 * @author Fun
 */

namespace app\components;

use yii\db\ActiveRecord;
use yii\db\QueryInterface;
use yii\base\ModelEvent;
use app\modules\systems\models\TablesChangeLog;
use app\modules\accounts\models\Platform;

class Model extends ActiveRecord {

    /**
     * @desc 系统用户常量
     * @var unknown
     */
    const SYSTEM_USER = 'system';
    const EVENT_DELETE_BEFORE = 'deletebefore';
    const EVENT_DELETE_AFTER = 'deleteafter';

    /**
     * @desc 观察者列表
     * @var Array
     */
    protected $observers = [];
    public static $tableChangeLogEnabled = true;          //是否记录数据表修改日志

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\BaseActiveRecord::init()
     */

    public function init() {
        parent::init();
        $this->on(self::EVENT_BEFORE_INSERT, [$this, 'beforeInserEvent']);
        $this->on(self::EVENT_BEFORE_UPDATE, [$this, 'beforeUpdateEvent']);
        $this->on(self::EVENT_AFTER_INSERT, [$this, 'afterInsertEvent']);
        $this->on(self::EVENT_AFTER_UPDATE, [$this, 'afterUpdateEvent']);
        $this->on(self::EVENT_DELETE_BEFORE, [$this, 'beforeDeleteEvent']);
        $this->on(self::EVENT_DELETE_AFTER, [$this, 'afterDeleteEvent']);
    }

    /**
     * @desc 自动填充创建人，创建时间
     * @param unknown $event
     */
    public function beforeInserEvent($event) {
        if (isset(\Yii::$app->user)) {
            $user = \Yii::$app->user->getIdentity();
            $createBy = '';
            if ($user != null) {
                $createBy = $user->user_name;
            }
        } else {
            $createBy = self::SYSTEM_USER;
        }
        $modifyBy = $createBy;
        $time = date('Y-m-d H:i:s');
        if ($this->hasAttribute('create_by') && $this->create_by === null) {
            $this->create_by = $createBy;
        }
        if ($this->hasAttribute('create_time') && $this->create_time === null) {
            $this->create_time = $time;
        }
        if ($this->hasAttribute('modify_by') && $this->modify_by === null) {
            $this->modify_by = $modifyBy;
        }
        if ($this->hasAttribute('modify_time') && $this->modify_time === null) {
            $this->modify_time = $time;
        }
    }

    /**
     * @desc 自动填充修改人，修改时间
     * @param unknown $event
     */
    public function beforeUpdateEvent($event) {
        $createBy = self::SYSTEM_USER;
        $modifyBy = self::SYSTEM_USER;
        if (isset(\Yii::$app->user)) {
            $user = \Yii::$app->user->getIdentity();
            if ($user != null) {
                $createBy = $user->user_name;
                $modifyBy = $user->user_name;
            }
        }
        if ($this->hasAttribute('modify_by') &&
                $this->oldAttributes['modify_by'] == $this->attributes['modify_by']) {
            $this->modify_by = $modifyBy;
        }
        if ($this->hasAttribute('modify_time') &&
                $this->oldAttributes['modify_time'] == $this->attributes['modify_time']) {
            $this->modify_time = date('Y-m-d H:i:s');
        }
        if (static::$tableChangeLogEnabled) {
            $this->save_tables_change_log(TablesChangeLog::CHANGE_TYPE_UPDATE);
        }
    }

    public function beforeDeleteEvent($event) {
        //throw new \Exception("Value must be 1 or below"); 
        //var_dump($this->attributes);die();
        //var_dump(self::getTableSchema());die();
    }

    public function afterDeleteEvent($event) {
        if (static::$tableChangeLogEnabled)
            $this->save_tables_change_log(TablesChangeLog::CHANGE_TYPE_DELETE);
    }

    public function afterInsertEvent($event) {
        if (static::$tableChangeLogEnabled)
            $this->save_tables_change_log(TablesChangeLog::CHANGE_TYPE_INSERT);
    }

    public function afterUpdateEvent($event) {
        //$this->save_tables_change_log(TablesChangeLog::CHANGE_TYPE_UPDATE);
    }

    /**
     * 存取系统数据表变动日志的公共方法
     * @param int $change_type 变动类型(1insert，2update，3delete)
     */
    public function save_tables_change_log($change_type, $content = '') {
        $table_name = $this->getTableSchema()->name;
        $change_content = $content;
        if (empty($content)) {
            $change_content = $this->get_content_by_change_type($change_type);
        }
        TablesChangeLog::save_tables_change_log_data($table_name, $change_type, $change_content);
    }

    /**
     * @desc search list
     * @param string $query
     * @param string $sort
     * @param unknown $params
     * @return \yii\data\ActiveDataProvider
     */
    public function search($query = null, $sort = null, $params = []) {
        if (!$query instanceof QueryInterface) {
            $query = self::find();
            $query->from(static::tableName() . ' as t');
        }

        $this->setFilterOptions($query, $params);
         
        $page = 1;
        $pageSize = \Yii::$app->params['defaultPageSize'];
        if (isset($params['page']))
            $page = (int) $params['page'];
        if (isset($params['pageSize']))
            $pageSize = (int) $params['pageSize'];
        if (!$sort instanceof \yii\data\Sort)
            $sort = new \yii\data\Sort();
        if (isset($params['sortBy']) && !empty($params['sortBy']))
            $sortBy = $params['sortBy'];
        if (isset($params['sortOrder']) && !empty($params['sortOrder']))
            $sortOrder = strtoupper($params['sortOrder']) == 'ASC' ? SORT_ASC : SORT_DESC;
        if (!empty($sortBy)) {
            $sort->attributes[$sortBy] = [
                'label' => $this->getAttributeLabel($sortBy),
                'desc' => [$sortBy => SORT_DESC],
                'asc' => [$sortBy => SORT_ASC]
            ];
            $sort->setAttributeOrders([$sortBy => $sortOrder]);  
        }
        
//        if(Platform::PLATFORM_CODE_EB == 'EB' && static::tableName() == '{{%ebay_feedback}}')
//        {
//            $query->SELECT('{{%ebay_feedback}}.*,b.status')->leftJoin(['{{%ebay_feedback_response}} b'],'{{%ebay_feedback}}.feedback_id = b.feedback_id');
//        }

        $this->setSearchQuerySession(['query' => $query, 'sort' => $sort]);
       
        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
            'sort' => $sort,
            'pagination' => [
                'pageSize' => $pageSize,
                'page' => ($page - 1)
            ]
        ]);  
      //  echo $query->createCommand()->getRawSql();exit;
        return $dataProvider;
    }

    public function setSearchQuerySession($data) {
        $session = \Yii::$app->session;
        $session->set(get_class($this) . '_model_search_query', serialize($data));
    }

    public function getSearchQuerySession() {
        $session = \Yii::$app->session;
        $return = $session->get(get_class($this) . '_model_search_query');
        return empty($return) ? $return : unserialize($return);
    }

    /**
     * @desc 搜索选项
     * @return multitype:
     */
    public function filterOptions() {
        return [];
    }

    /**
     * @desc 处理列表数据
     * @param unknown $models
     * @return unknown
     */
    public function addition(&$models) {
        return $models;
    }

    /**
     * @desc set filter options
     * @param unknown $query
     * @param unknown $params
     */
    public function setFilterOptions($query, $params, $checkAccountId = 1) {

        //$query = self::find();
        $filterOptions = $this->filterOptions();

        if (method_exists($this, 'dynamicChangeFilter')) {
            $this->dynamicChangeFilter($filterOptions, $query, $params);
        }

        if (!empty($filterOptions)) {
            foreach ($filterOptions as $row) {
                $is_filtered = (isset($row['is_filtered']) && $row['is_filtered'] == false) ? false : true;
                if ($is_filtered == false)
                    continue;
                $name = isset($row['name']) ? trim($row['name']) : '';
                if (!$checkAccountId && $name == 'account_id')
                    continue;

                if (empty($name))
                    continue;
                $search = isset($row['search']) ? trim($row['search']) : '=';
                $value = isset($row['value']) ? $row['value'] : '';
                if (isset($params[$name]))
                    $value = $params[$name];
                if (!is_array($value))
                    $value = trim($value);
                if ($value === '')
                    continue;
                $alias = isset($row['alias']) ? trim($row['alias']) : '';
                
                /**
                 * 注释 by huwenjun
                 */
                /* if(Platform::PLATFORM_CODE_EB == 'EB' && static::tableName() == '{{%ebay_feedback}}')
                  $alias ='{{%ebay_feedback}}'; */

                $fieldName = $name;
                if (!empty($alias))
                    $fieldName = $alias . '.' . $name;
                
 
                switch ($search) {
                    case '=':
                        if($fieldName!="is_aftersale"){
                             $query->andWhere([$fieldName => $value]);    
                        }          
                        break;
                    case 'FULL LIKE':
                        $query->andWhere(['like', $fieldName, '%' . $value . '%', false]);
                        break;
                    case 'LIKE':
                        $query->andWhere(['like', $fieldName, $value . '%', false]);
                        break;
                }
            }
        }
        
    }

    /**
     * @desc 根据ID获取记录
     * @param unknown $id
     * @return \yii\db\static
     */
    public static function findById($id) {
        return self::findOne($id);
    }

    /**
     * @desc 根据ID删除记录
     * @param unknown $id
     * @return \yii\db\int
     */
    public function deleteById($id) {
        $model = self::find()->where('id = :id', [':id' => $id])->one();
        return $model->delete();
    }

    /**
     * @desc 根据id列表删除所有记录
     * @param unknown $ids
     * @return \yii\db\int
     */
    public function deleteByIds($ids) {
        return $this->deleteAll(['in', 'id', $ids]);
    }

    /**
     * 对yii2的delete源码中的deleteInternal根据业务进覆写
     */
    protected function deleteInternal() {
        if (!$this->beforeDelete()) {
            return false;
        }

        // we do not check the return value of deleteAll() because it's possible
        // the record is already deleted in the database and thus the method will return 0
        $condition = $this->getOldPrimaryKey(true);
        $lock = $this->optimisticLock();
        if ($lock !== null) {
            $condition[$lock] = $this->$lock;
        }

        $command = static::getDb()->createCommand();
        $command->delete(static::tableName(), $condition, []);

        $result = $command->execute();

        if ($lock !== null && !$result) {
            throw new StaleObjectException('The object being deleted is outdated.');
        }
        $this->setOldAttributes(null);
        $this->afterDelete();

        return $result;
    }

    /**
     * 覆写yii2里面的beforeDelete绑定自定义的事件名称
     */
    public function beforeDelete() {
        $event = new ModelEvent;
        $this->trigger(self::EVENT_DELETE_BEFORE, $event);

        return $event->isValid;
    }

    /**
     * 覆写yii2里面的afterDelete绑定自定义的事件名称
     */
    public function afterDelete() {
        $this->trigger(self::EVENT_DELETE_AFTER);
    }

    /**
     * 对yii2的deleteAll方法就行复写目的是为了其支持绑定事件
     */
    public static function deleteAll($condition = '', $params = []) {
        //触发删除之前的事件处理函数
        //(new static())->trigger(self::EVENT_DELETE_BEFORE);

        $command = static::getDb()->createCommand();
        $command->delete(static::tableName(), $condition, $params);
        $result = $command->execute();

        //触发删除之后的事件处理函数存取批量删除的日志记录
        //(new static())->trigger(self::EVENT_DELETE_AFTER);

        $content = self::get_content_when_delete_all($condition, $params);

        //存取批量删除操作的日志
        if (static::$tableChangeLogEnabled)
            $log_result = (new static())->save_tables_change_log(TablesChangeLog::CHANGE_TYPE_DELETE, $content);

        return $result;
    }

    /**
     * 重写yii2 updateAll方法为了存取日志的业务
     */
    /*
      public static function updateAll($attributes, $condition = '', $params = [])
      {
      $command = static::getDb()->createCommand();
      $command->update(static::tableName(), $attributes, $condition, $params);

      $result = $command->execute();

      $content = self::get_content_when_update_all($attributes, $condition, $params);

      //存取批量更新操作的日志
      $log_result = (new static())->save_tables_change_log(TablesChangeLog::CHANGE_TYPE_UPDATE,$content);

      return $result;
      } */

    public static function get_content_when_update_all($attributes, $condition = '', $params = []) {
        $attributes_attributes = $attributes;

        if (is_array($attributes)) {
            $attributes_attributes = json_encode($attributes);
        }

        $condition_condition = $condition;

        if (is_array($condition)) {
            $condition_condition = json_encode($condition);
        }

        $params_params = $params;

        if (is_array($params)) {
            $params_params = json_encode($params);
        }

        $content = TablesChangeLog::get_change_type_data(TablesChangeLog::CHANGE_TYPE_UPDATE);

        return $content . ":attributes=>" . $attributes_attributes . ",condition=>" . $condition_condition . ",params:" . $params_params;
    }

    /**
     * 获取批量删除情景的日志内容
     * @param unknow $condition 查询条件
     * @param unknow $params 条件选项值
     * @return string
     */
    public static function get_content_when_delete_all($condition = '', $params = []) {
        $condition_condition = $condition;

        if (is_array($condition)) {
            $condition_condition = json_encode($condition);
        }

        $params_params = $params;

        if (is_array($params)) {
            $params_params = json_encode($params);
        }

        $content = TablesChangeLog::get_change_type_data(TablesChangeLog::CHANGE_TYPE_DELETE);

        return $content . ":condition=>" . $condition_condition . ",params:" . $params_params;
    }

    /**
     * @desc 根据变动类型组装变动的内容描述
     * @param int $change_type 变动类型(1insert,2update,3delete)
     */
    public function get_content_by_change_type($change_type) {
        $content_prefix = TablesChangeLog::get_change_type_data($change_type) . ":";

        if ($change_type == TablesChangeLog::CHANGE_TYPE_INSERT) {
            $id = isset($this->id) ? $this->id : null;
            $content = isset($id) ? '新增id值为' . $id . '的记录' : '新增记录请看结合表名和日志新增时间';
            return $content_prefix . $content;
        }

        if ($change_type == TablesChangeLog::CHANGE_TYPE_DELETE) {
            $id = isset($this->id) ? $this->id : null;
            $content = isset($id) ? '删除id值为' . $id . '的记录' : '删除记录请看结合表名和日志新增时间';
            return $content_prefix . $content;
        }

        if ($change_type == TablesChangeLog::CHANGE_TYPE_UPDATE) {
            list($update_after_data, $update_before_data, $content) = [$this->attributes, $this->oldattributes, $content_prefix];
            foreach ($update_before_data as $key => $value) {
                if ($value != $update_after_data[$key]) {
                    $content = $content . $key . ":" . $value . "=>" . $update_after_data[$key] . ",";
                }
            }
            return $content;
        }

        return null;
    }

    /**
     * @desc 获取状态列表
     * @param string $key
     * @return Ambigous <\yii\string>|string|multitype:\yii\string
     */
    public static function getStatusList($key = null) {
        $list = [
            '0' => \Yii::t('system', 'Invalid'),
            '1' => \Yii::t('system', 'Valid'),
            2   => '指定时间区间内有效',
        ];
        if (!is_null($key)) {
            if (array_key_exists($key, $list))
                return $list[$key];
            else
                return '';
        }
        return $list;
    }

}

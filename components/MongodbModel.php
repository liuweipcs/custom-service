<?php
/**
 * @desc Mongodb Model base class
 * @author Fun
 */
namespace app\components;
use yii\mongodb\ActiveRecord;
use yii\db\QueryInterface;
class MongodbModel extends ActiveRecord
{
    /**
     * @desc 系统用户常量
     * @var unknown
     */
    const SYSTEM_USER = 'system';
    
    /**
     * @desc 观察者列表
     * @var Array
     */
    protected $observers = [];
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\BaseActiveRecord::init()
     */
    public function init()
    {
        parent::init();
        $this->on(self::EVENT_BEFORE_INSERT, [$this, 'beforeInserEvent']);
        $this->on(self::EVENT_BEFORE_UPDATE, [$this, 'beforeUpdateEvent']);
        $this->on(self::EVENT_AFTER_INSERT, [$this, 'afterInsertEvent']);
        $this->on(self::EVENT_AFTER_UPDATE, [$this, 'afterUpdateEvent']);
    }
    
    /**
     * @desc 自动填充创建人，创建时间
     * @param unknown $event
     */
    public function beforeInserEvent($event)
    {
        if (isset(\Yii::$app->user))
        {
            $user = \Yii::$app->user->getIdentity();
            $createBy = '';     
            if ($user != null)
                $createBy = $user->login_name;
        }
        else
            $createBy = self::SYSTEM_USER;
        $modifyBy = $createBy;
        $time = date('Y-m-d H:i:s');
        if ($this->hasAttribute('create_by') && $this->create_by === null)
            $this->create_by = $createBy;
        if ($this->hasAttribute('create_time') && $this->create_time === null)
            $this->create_time = $time;
        if ($this->hasAttribute('modify_by') && $this->modify_by === null)
            $this->modify_by = $modifyBy;
        if ($this->hasAttribute('modify_time') && $this->modify_time === null)
            $this->modify_time = $time;
        
    }
    
    /**
     * @desc 自动填充修改人，修改时间
     * @param unknown $event
     */
    public function beforeUpdateEvent($event)
    {
        $createBy = self::SYSTEM_USER;
        $modifyBy = self::SYSTEM_USER;
        if (isset(\Yii::$app->user))
        {
            $user = \Yii::$app->user->getIdentity();
            if ($user != null)
            {
                $createBy = $user->login_name;
                $modifyBy = $user->login_name;
            }
        }
        if ($this->hasAttribute('modify_by') && 
            $this->oldAttributes['modify_by'] == $this->attributes['modify_by'])
            $this->modify_by = $modifyBy;
        if ($this->hasAttribute('modify_time') && 
            $this->oldAttributes['modify_time'] == $this->attributes['modify_time'])
            $this->modify_time = date('Y-m-d H:i:s');
    }
    
    public function afterInsertEvent($event)
    {
        //create operate notify observer
    }
    
    public function afterUpdateEvent($event)
    {
        //update operate notify observer
    }
    
    /**
     * @desc search list
     * @param string $query
     * @param string $sort
     * @param unknown $params
     * @return \yii\data\ActiveDataProvider
     */
    public function search($query = null, $sort = null, $params = [])
    {
        if (!$query instanceof QueryInterface)
        {
            $query = self::find();
            $query->from(self::tableName() . ' as t');
        }
        $this->setFilterOptions($query, $params);
        $page = 1;
        $pageSize = \Yii::$app->params['defaultPageSize'];
        if (isset($params['page']))
            $page = (int)$params['page'];
        if (isset($params['pageSize']))
            $pageSize = (int)$params['pageSize'];
        
        if (!$sort instanceof \yii\data\Sort)
            $sort = new \yii\data\Sort();

        if (isset($params['sortBy']) && !empty($params['sortBy']))
            $sortBy = $params['sortBy'];
        if (isset($params['sortOrder']) && !empty($params['sortOrder']))
            $sortOrder = strtoupper($params['sortOrder']) == 'ASC' ? SORT_ASC : SORT_DESC;
        if (!empty($sortBy))
        {
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
    
/*     public function insert($runValidation = true, $attributes = null)
    {
        if (isset($this->create_by) && empty($this->create_by))
            $this->create_by = \Yii::$app->user->getIdentity();
        parent::insert($runValidation, $attributes);
    } */
    /*
    public function update()
    {
        
    } */
    
    /**
     * @desc 搜索选项
     * @return multitype:
     */
    public function filterOptions()
    {
        return [];
    }
    
    /**
     * @desc set filter options
     * @param unknown $query
     * @param unknown $params
     */
    public function setFilterOptions($query, $params)
    {
        //$query = self::find();
        $filterOptions = $this->filterOptions();
        if (!empty($filterOptions))
        {
            foreach ($filterOptions as $row)
            {
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
    
    /**
     * @desc 根据ID获取记录
     * @param unknown $id
     * @return \yii\db\static
     */
    public static function findById($id)
    {
        return self::findOne($id);
    }
    
    /**
     * @desc 根据ID删除记录
     * @param unknown $id
     * @return \yii\db\int
     */
    public function deleteById($id)
    {
        return $this->deleteAll("id = :id", ['id' => $id]);
    }
    
    /**
     * @desc 根据id列表删除所有记录
     * @param unknown $ids
     * @return \yii\db\int
     */
    public function deleteByIds($ids)
    {
        return $this->deleteAll(['in', 'id', $ids]);
    }
    
/*     public function notify()
    {
        foreach ($this->observers[] as $observer)
        {
            $observer->update($this);
        }
    }
    
    public function registerObserver(\app\components\Observer $observer)
    {
        $className = get_class($observer);
        if (!isset($this->observers[$className]))
            $this->observers[$className];
    } */
        
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
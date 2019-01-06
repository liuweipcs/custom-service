<?php
/**
 * @desc 资源模型
 * @author Fun
 */
namespace app\modules\systems\models;
class Resource extends SystemsModel
{
    const RESOURCE_TYPE_FILE = 1;       //文件资源
    const RESOURCE_TYPE_ACTION = 2;     //动作资源
    const RESOURCE_TYPE_BUTTON = 3;     //按钮资源
    
    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName()
    {
        return '{{%resource}}';
    }
    
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\base\Model::rules()
     */
    public function rules()
    {
        return [
            [['menu_name'], 'required'],
            ['resource_description', 'safe'],
            [['parent_id', 'resource_type'], 'integer']
        ];
    }
    
    public function refreshResource()
    {
        $moduleDir = \Yii::getAlias('@app/modules');
        if (empty($moduleDir))
            return false;
        //认证忽略的模块资源不需要收集
        $authIgnores = \Yii::$app->params['authIgnores'];
        $ignoreModules = [];
        if (isset($authIgnores['modules']))
            $ignoreModules = $authIgnores['modules'];
        return $this->refreshResourceRecurisive($moduleDir, $ignoreModules);
    }
    
    /**
     * @desc 刷新资源
     * @param unknown $dirPath
     * @param unknown $ignoreDirs
     * @param string $moduleName
     * @return boolean
     */
    public function refreshResourceRecurisive($dirPath, $ignoreDirs = [], $moduleName = '')
    {
        
        $dir = dir($dirPath);
        if (empty($dir))
            return false;
        if (in_array($dirPath, $ignoreDirs))
            return true;
        $appDir = \Yii::getAlias('@app');
        while ($filename = $dir->read())
        {
            if ($filename == '.' || $filename == '..' || $filename == '.svn') continue;
            $filePath = $dirPath . DIRECTORY_SEPARATOR . $filename;
            $controllerDir = $filePath . DIRECTORY_SEPARATOR . 'controllers';
            $moduleDir = $filePath . DIRECTORY_SEPARATOR . 'modules';
            if (is_file($filePath) && strpos($filename, 'Controller') != false)
            {
                //收集资源插入到资源表
                $namespace = str_replace([$appDir, '/'], ['app', '\\'], $dirPath);
                $filename = trim($filename, '.php');
                $className = $namespace . '\\' . $filename;
                $fileResourceName = ucfirst($moduleName) . '_' . $filename;
                $fileResourceValue = $fileResourceName;
                //插入资源
                $resourceInfo = $this->exists($fileResourceName);
                if (!empty($resourceInfo))
                {
                    $resourceId = $resourceInfo->id;
                }
                else
                {
                    $this->resource_name = $fileResourceName;
                    $this->resource_description = $fileResourceValue;
                    $this->parent_id = 0;
                    $this->resource_type =  self::RESOURCE_TYPE_FILE;
                    $this->id = null;
                    $this->isNewRecord = true;
                    $flag = $this->save(false);
                    if (empty($flag)) continue;
                    $resourceId = $this->id;
                }
/*                 if ($resourceId == 640)
                {
                    exit('a');
                } */
                $actions = get_class_methods($className);
                if (empty($actions)) continue;
                foreach ($actions as $methodName)
                {
                    if (!preg_match('/action([A-Z]){1}\w+/', $methodName)) continue;
                    $actionResourceName = $fileResourceName . '_' . $methodName;
                    $actionResourceValue = $actionResourceName;
                    $resourceInfo = $this->exists($actionResourceName);
                    if (!empty($resourceInfo)) continue;
                    $this->resource_name = $actionResourceName;
                    $this->resource_description = $actionResourceValue;
                    $this->parent_id = $resourceId;
                    $this->resource_type =  self::RESOURCE_TYPE_ACTION;
                    $this->id = null;
                    $this->isNewRecord = true;
                    $flag = $this->save(false);
                }
                continue;
            }
            //过滤不需要收集资源的模块
            if (in_array($filename, $ignoreDirs)) continue;
            if (is_dir($moduleDir))
            {
                $this->refreshResourceRecurisive($moduleDir, $ignoreDirs);
                continue;
            }
            if (is_dir($controllerDir))
            {
                $moduleName = ltrim(strrchr($filePath, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);
                $this->refreshResourceRecurisive($controllerDir, $ignoreDirs, $moduleName);
                continue;
            }
        }
        return true; 
    }
    
    /**
     * @desc 资源是否存在
     * @param unknown $resourceName
     * @return Ambigous <\yii\db\ActiveRecordInterface, \yii\db\array, \yii\db\null>
     */
    public function exists($resourceName)
    {
        return $this->findByCondition(['resource_name' => $resourceName])->one();
    }
    
    public function getResourceRecurisive($parentId = 0, $level = 1)
    {
        $datas = [];
        $resources = $this->getChildResources($parentId);
        if (!empty($resources))
        {
            foreach ($resources as $row)
            {
                $resourceId = $row['id'];
                $parentId = $resourceId;
                $datas[$resourceId] = $row;
                $datas[$resourceId]['level'] = $level;
                $datas[$resourceId]['children'] = [];
                $childResources = $this->getChildResources($parentId, $level+1);
                $datas[$resourceId]['children'] = $childResources;
            }
        }
        return $datas;
    }
    
    /**
     * @desc 获取子资源
     * @param unknown $resourceId
     * @return \yii\db\array
     */
    public function getChildResources($resourceId)
    {
        $query = new \yii\db\Query();
        $query->from(self::tableName())
            ->select('*')
            ->where('parent_id = :parent_id', ['parent_id' => $resourceId]);
        return $query->all();
    }
    
    /**
     * @desc 获取所有资源
     * @return \yii\db\array
     */
    public function getAllResources()
    {
        $query = new \yii\db\Query();
        $query->from(self::tableName())
        ->select('*');
        return $query->all();        
    }
    
    /**
     * @desc 获取角色对应资源
     * @param unknown $roleIds
     * @return \yii\db\array
     */
    public static function getRoleResources($roleIds, $type = 1)
    {
        $query = new \yii\db\Query();
        $query->from(self::tableName() . ' as t')
            ->innerJoin(\app\modules\systems\models\RoleResource::tableName(), 'id = resource_id')
            ->select('t.*')
            ->distinct('t.id')
            ->where(['role_id' => $roleIds])
            ->andWhere(['type' => $type]);
        return $query->all();
    }
}
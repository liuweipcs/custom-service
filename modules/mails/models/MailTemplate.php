<?php
/**
 * Created by PhpStorm.
 * User: wuyang
 * Date: 2017/4/19 0011
 * Time: 上午 11:03
 */

namespace app\modules\mails\models;

use app\modules\accounts\models\Platform;
use app\modules\accounts\models\UserAccount;
use Yii;

class MailTemplate extends MailsModel
{
    const MAIL_TEMPLATE_STATUS_VALID = 1;//有效
    const MAIL_TEMPLATE_STATUS_INVALID = 0; //无效

    const MAIL_TEMPLATE_STATUS_PRIVATE = 1; // 私有
    const MAIL_TEMPLATE_STATUS_PUBLIC = 2; // 共有

    const MAIL_TEMPLATE_TYPE_MESSAGE = 1; // 消息模板
    const MAIL_TEMPLATE_TYPE_ORDER = 2; // 自动回信模板
    const MAIL_TEMPLATE_TYPE_CUSTOMER = 3; //客户跟进模板

    public static $questionTypeMap = array(1 => 'CustomizedSubject', 2 => 'General', 3 => 'MultipleItemShipping', 4 => 'None', 5 => 'Payment', 6 => 'Shipping');
    public $moban_replacement;

    public static function tableName()
    {
        return '{{%mail_template}}';
    }

    /**
     * 获取模板数据供下拉选择
     */
    public static function getMailTemplateDataAsArray($platform_code)
    {
        $query = new \yii\db\Query();
        $data = $query->from(self::tableName())
            ->select('id,template_name')
            ->where('status = :status and platform_code=:platform_code', [
                ':status' => static::MAIL_TEMPLATE_STATUS_VALID,
                'platform_code' => $platform_code])
            ->all();

        //组装并且符合下拉selct格式的结果
        $result = [];
        foreach ($data as $key => $value) {

            $result[$value['id']] = $value['template_name'];

        }
        return $result;
    }

    /**
     * 获取客户跟进模板数据供下拉选择
     */
    public static function getCustomerDataAsArray($platform_code)
    {
        $query = new \yii\db\Query();
        $data = $query->from(self::tableName())
            ->select('id,template_name')
            ->andWhere('status = :status and platform_code=:platform_code', [
                ':status' => static::MAIL_TEMPLATE_STATUS_VALID,
                'platform_code' => $platform_code])
            ->andWhere(['template_type' => self :: MAIL_TEMPLATE_TYPE_CUSTOMER])
            ->all();

        //组装并且符合下拉selct格式的结果
        $result = [];
        foreach ($data as $key => $value) {

            $result[$value['id']] = $value['template_name'];

        }
        return $result;
    }

    /**
     * 获取模板数据供下拉选择(区分共有/私有模板)
     */
    public static function getMailTemplateDataAsArrayByUserId($platform_code)
    {
        $user_name = Yii::$app->user->identity->user_name;

        $data = self::find()
            ->select('id,template_name')
            ->andWhere(['platform_code' => $platform_code])
            ->andWhere(['status' => static::MAIL_TEMPLATE_STATUS_VALID])
            ->andWhere([
                'or',
                [
                    'and',
                    ['private' => static::MAIL_TEMPLATE_STATUS_PUBLIC],
                    ['template_type' => self::MAIL_TEMPLATE_TYPE_MESSAGE],
                ],
                [
                    'and',
                    ['private' => static::MAIL_TEMPLATE_STATUS_PRIVATE],
                    ['like', 'create_by', $user_name],
                    ['template_type' => self::MAIL_TEMPLATE_TYPE_MESSAGE]
                ],
            ])
            ->all();

        //组装并且符合下拉selct格式的结果
        $result = [];
        foreach ($data as $key => $value) {

            $result[$value['id']] = $value['template_name'];

        }
        return $result;
    }

    /**
     * 获取自已的消息模板
     */
    public static function getMyMailTemplate($platformCode, $categoryId = 0)
    {

        $query = self::find()
            ->alias('t')
            ->select('t.id, t.category_id, c.category_name, t.template_name')
            ->leftJoin(['c' => MailTemplateCategory::tableName()], 'c.id = t.category_id AND c.platform_code = :platform_code', [':platform_code' => $platformCode])
            ->andWhere(['t.status' => self::MAIL_TEMPLATE_STATUS_VALID, 't.platform_code' => $platformCode, 't.template_type' => self::MAIL_TEMPLATE_TYPE_MESSAGE])
            ->andWhere([
                'or',
                ['t.private' => self::MAIL_TEMPLATE_STATUS_PRIVATE, 't.create_by' => Yii::$app->user->identity->user_name],
                ['t.private' => self::MAIL_TEMPLATE_STATUS_PUBLIC],
            ])
            ->orderBy('t.sort_order ASC, t.id DESC');

        if (!empty($categoryId)) {
            $query->andWhere(['t.category_id' => $categoryId]);
        }

        $data = $query->asArray()->all();
        if (!empty($data)) {
            $tmp = [];
            foreach ($data as $item) {
                $tmp[$item['category_id']][] = $item;
            }
            $data = $tmp;
        }

        //对模板进行排序
        ksort($data);

        return $data;
    }

    /**
     * 搜索邮件模板
     */
    public static function searchMailTemplate($name = '', $platformCode = '')
    {
        $query = self::find()
            ->alias('t')
            ->select('t.id, t.category_id, c.category_name, t.template_name')
            ->leftJoin(['c' => MailTemplateCategory::tableName()], 'c.id = t.category_id')
            ->andWhere(['t.status' => self::MAIL_TEMPLATE_STATUS_VALID, 't.template_type' => self::MAIL_TEMPLATE_TYPE_MESSAGE])
            ->orderBy('t.sort_order ASC, t.id DESC');

        if (!empty($name)) {
            $query->andWhere([
                'or',
                ['like', 't.template_name', $name],
                ['like', 't.template_title', $name],
            ]);
        }
        if (!empty($platformCode)) {
            $query->andWhere(['t.platform_code' => $platformCode]);
        }

        $data = $query->asArray()->all();
        if (!empty($data)) {
            $tmp = [];
            foreach ($data as $item) {
                $tmp[$item['category_id']][] = $item;
            }
            $data = $tmp;
        }

        return $data;
    }

    /**
     * 获取自动回信模板数据供下拉选择
     */
    public static function getOrderTemplateDataAsArray($platform_code)
    {
        $query = new \yii\db\Query();

        $user_name = Yii::$app->user->identity->user_name;

        $data = $query->from(self::tableName())
            ->select('id,template_name')
            ->where('status = ' . static::MAIL_TEMPLATE_STATUS_VALID . ' and platform_code="' . $platform_code . '" and template_type="' . self::MAIL_TEMPLATE_TYPE_ORDER . '"')
            ->all();

        //组装并且符合下拉selct格式的结果
        $result = [];
        foreach ($data as $key => $value) {

            $result[$value['id']] = $value['template_name'];

        }
        return $result;
    }

    /**
     * 按帐号显示模板标签
     */
    public static function AccordingToAccountShow($platform_code)
    {
        $query = new \yii\db\Query();

        $data = $query->from(self::tableName())
            ->select('id,template_name')
            ->where('create_by = :username or private = :private and platform_code = :platform_code and status = :status ',
                [':username' => Yii::$app->user->identity->user_name, 'private' => 2,
                    ':platform_code' => $platform_code, ':status' => static::MAIL_TEMPLATE_STATUS_VALID])
            ->all();

        foreach ($data as $key => $value) {

            $result[$value['id']] = $value['template_name'];
        }
        return $result;
    }

    /**
     * 按帐号、模版名称、模版代码查询模板标签
     */
    public static function getMailsendingtemplate($platform_code, $template_name, $template_title)
    {

        $query = new \yii\db\Query();

        $query->from(self::tableName())
            ->select('id,template_name,template_content');
        if (!empty($template_name)) {
            $query->where('(create_by = :username or private = :private) and platform_code = :platform_code and status = :status and template_type = :template_type and template_name = :template_name',
                [':username' => Yii::$app->user->identity->user_name, ':private' => 2, ':platform_code' => $platform_code, ':status' => static::MAIL_TEMPLATE_STATUS_VALID, ':template_type' => 1, ':template_name' => $template_name]);
            return $query->one();
        } else if (!empty($template_title)) {
            $query->where('(create_by = :username or private = :private) and platform_code = :platform_code and status = :status and template_type = :template_type and template_title = :template_title',
                [':username' => Yii::$app->user->identity->user_name, ':private' => 2, ':platform_code' => $platform_code, ':status' => static::MAIL_TEMPLATE_STATUS_VALID, ':template_type' => 1, ':template_title' => $template_title]);
            return $query->one();
        } else if (!empty($platform_code)) {
            $query->where('(create_by = :username or private = :private) and platform_code = :platform_code and status = :status and template_type = :template_type',
                [':username' => Yii::$app->user->identity->user_name, ':private' => 2, ':platform_code' => $platform_code, ':status' => static::MAIL_TEMPLATE_STATUS_VALID, ':template_type' => 1]);
            return $query->all();
        }
        return null;
    }

    /**
     * 根据模板id获取模板名称
     * @param int $template_id 模板id
     */
    public static function getTemplateNameById($template_id)
    {
        $query = new \yii\db\Query();
        $data = $query->from(self::tableName())
            ->select('template_name')
            ->where('id = :id', [':id' => $template_id])
            ->one();
        return empty($data) ? null : $data['template_name'];
    }

    /**
     * 返回模版类型
     */
    public static function getTemplatePrivate()
    {
        return [
            '1' => '私有模版',
            '2' => '公共模版',
        ];
    }

    public function attributeLabels()
    {
        return [

            'category_id' => '模板分类',
            'template_name' => '模板名称',
            'template_content' => '模板内容',
            'template_title' => '模板编号/主题',
            'template_description' => '模板描述',
            'platform_code' => '所属平台',
            'template_type' => '模板类型',
            'template_language' => '模板语言',
            'status' => '是否可用',
            'sort_order' => '排序(值越小越前)',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
            'private' => '模版属性',
            'moban_replacement' => '模版变量'
        ];
    }

    /**
     * 回复模板查询
     */
    public function searchList($params = [])
    {
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'id' => SORT_ASC
        );
        $delete_arr = [
            'cs_amazonmanager',
            'cs_aliexpress-manager',
            'cs_ebay-manager',
            'cs_cdwish-manager',
            'cs_shopee-manager',
            'cs_site-manager',
            'admin'
        ];
        $session = Yii::$app->user->identity->role;       
        $role_code = $session->role_code;
        //组员只能查看自己创建的模板
       // if (!in_array($role_code, $delete_arr)) {
        //   $params['create_by'] = Yii::$app->user->identity->user_name;
       // }
         $platform_code=Yii::$app->user->identity->role->platform_code;
//         echo '<pre>';
//         var_dump($platform_code);
//         die;
         $platformcode= explode(',', $platform_code);
        if(empty($params['platform_code'])){
         $params['platform_code']=$platformcode;
        }else{
          if(!in_array($params['platform_code'],$platformcode)){
              $params['platform_code']="lsesdffr";
          }       
        } 
        $dataProvider = parent::search(null, $sort, $params);
        $models = $dataProvider->getModels();
        foreach ($models as $key => $model) {
            $models[$key]->setAttribute('private', self::getprivateList($model->private));
            $models[$key]->setAttribute('status', self::getStatusList($model->status));
            $models[$key]->setAttribute('template_type', self::gettemplatetypeList($model->template_type));
            $models[$key]->setAttribute('category_id', self::getcategoryList($model->category_id));
        }
        $dataProvider->setModels($models);

        return $dataProvider;
    }

    /**
     * 获取模板分类名称
     */
    public static function getcategoryList($key = null)
    {
        $data = MailTemplateCategory::find()
            ->select('id, category_name')
            ->asArray()
            ->all();

        if (!empty($data)) {
            $data = array_column($data, 'category_name', 'id');
        }

        if (!is_null($key)) {
            return array_key_exists($key, $data) ? $data[$key] : '';
        }

        return $data;
    }


    /**
     * @desc 获取模版属性列表
     * @param string $key $key值为表中字段所对应的值。 这里面是1或者2， 与数组list元素的下标进行匹配。
     * @return
     */
    public static function getprivateList($key = null)
    {
        $list = [

            '1' => \Yii::t('system', 'Private Template'),
            '2' => \Yii::t('system', 'Public Template'),
        ];
        if (!is_null($key)) {
            if (array_key_exists($key, $list))
                return $list[$key];
            else
                return '';
        }
        return $list;
    }


    /**
     * @desc 获取模版类型列表
     * @param string $key， $key值为表中字段所对应的值。 这里指模版类型所对应的值。即 1为消息模板， 2为邮件模版，3客户跟进模板
     * @return
     */

    public static function gettemplatetypeList($key = null)
    {
        $list = [

            '1' => \Yii::t('system', 'Message Template'),
            '2' => \Yii::t('system', 'Order Template'),
            '3' => \Yii::t('system', '客户跟进模板'),
        ];
        if (!is_null($key)) {
            if (array_key_exists($key, $list))
                return $list[$key];
            else
                return '';
        }
        return $list;
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
            $query = self::find();
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

    public function dynamicChangeFilter(&$filterOptions, &$query, &$params)
    {
        $platformArray = isset(\Yii::$app->user->identity->role->platform_code) ? explode(',', \Yii::$app->user->identity->role->platform_code) : Platform::getPlatformAsArray();

        if (empty($params['platform_code'])) {
            $query->andWhere(['in', 'platform_code', $platformArray]);
        }

    }

    /**
     * @desc 搜索过滤项
     * @return multitype:multitype:string multitype:  multitype:string multitype:string
     */
    public function filterOptions()
    {
        $platformArray = isset(\Yii::$app->user->identity->role->platform_code) ? explode(',', \Yii::$app->user->identity->role->platform_code) : array();
        
        $platform = array();
        //$allplatform = Platform::getPlatformAsArray();
        $allplatform = UserAccount::getLoginUserPlatformAccounts();
       
        if ($platformArray) {
            foreach ($platformArray as $value) {
                $platform[$value] = isset($allplatform[$value]) ? $allplatform[$value] : $value;
            }
        }
        $platform = !empty($platform) ? $platform : $allplatform;
       
        return [
            [
                'name' => 'platform_code',
                'type' => 'dropDownList',
                'data' => $platform,
                'htmlOptions' => [],
                'search' => '='
            ],

            [
                'name' => 'template_type',
                'type' => 'dropDownList',
                'data' => self::gettemplatetypeList(),
                'htmlOptions' => [],
                'search' => '='
            ],

            [
                'name' => 'template_name',
                'type' => 'text',
                'htmlOptions' => [],
                'search' => '='
            ],

            [
                'name' => 'template_title',
                'type' => 'text',
                'htmlOptions' => [],
                'search' => '='
            ],

            [
                'name' => 'create_by',
                'type' => 'text',
                'htmlOptions' => [],
                'search' => 'LIKE'
            ],

            [
                'name' => 'private',
                'type' => 'dropDownList',
                'data' => self::getprivateList(),
                'htmlOptions' => [],
                'search' => '='
            ],
        ];
    }


    /**
     * @状态删除一条记录， 执行update 操作
     * @param @id
     * @return \yii\db\IntegrityException
     * @author wuyang
     *
     */

    public function deletestatus($id)
    {
        return $this->updateAll(array('status' => 0), "id='" . $id . "'");
    }


    /**
     * @更新编辑信息
     * @desc 根据ID,将更新后的内容插入到模版信息表中
     *
     */

    public function updatefield()
    {
        $id = $this->id;
        if (empty($id))
            return false;
        $dbTransaction = $this->getDb()->beginTransaction();
        try {
//           $model= self::findOne($id);
            $flag = $this->save();
            if (!$flag)
                throw new \yii\base\Exception(\Yii::t('system', 'Update Failed'));
            $dbTransaction->commit();
            return true;
        } catch (\yii\base\Exception $e) {
            $dbTransaction->rollBack();
            return false;
        }
    }


    public function rules()
    {

        return [
            [['template_description', 'sort_order', 'template_type'], 'safe'],
            [['category_id', 'private', 'template_name', 'template_title', 'template_content'], 'required'],
            ['platform_code', 'compare', 'compareValue' => 0, 'operator' => '>', 'message' => '请选择所属平台']

        ];
    }


    /**
     * @数据库连接
     * @author  wuyang
     * @date: 2017 04 22
     */

    public function connection()
    {
        return \Yii::$app->db;
    }

    /**
     * @向数据库中插入数据
     * @author  wuyang
     * @date: 2017 04 22
     *
     */

    public function Add($modelname, $arr)
    {
        $model_name = $modelname::tableName();
        $key_str = '(' . implode(',', array_keys($arr)) . ')';
        $value_str = join(',', array_map(function ($v) {
            return "'$v'";
        }, $arr));
        $value_str = '(' . $value_str . ')';
        $sql = "INSERT INTO " . $model_name . ' ' . $key_str . ' VALUES ' . $value_str;
        $result = $this->connection()->createCommand($sql)->execute();
        if ($result) {
            $last_id_sql = "select id from " . $model_name . " order by id desc limit 1";
            $last_id = $this->connection()->createCommand($last_id_sql)->queryOne();
            return $last_id['id'];

        } else {
            return false;
        }

    }



    /**
     * @更改数据库中的数据例子
     * @author wuyang
     * @date 2017 04 22
     * @$uparr 是要更新的字段及要更新的值
     * @$model 是存放封装方法的类
     * @categorymodel 是实例化的类。要向其对应的数据表中更新数据
     * @where 是修改的条件
     * $rs 是修改后的返回值。 未修改任何数据，返回0， 修改成功后，返回修改成功条数
     * public function actionTest(){
     * $uparr=[
     * 'parent_id'=>'101',
     * 'category_name'=>'update test 101'
     * ];
     * $model= New Mailtemplate;
     * $categorymodel= New MailTemplateCategory();
     * $where="where id >25";
     * $rs= $model->Updata($categorymodel,$uparr,$where);
     * var_dump($rs);
     * }
     *
     */

    /**
     * @查询数据库中的数据
     * @author  wuyang
     * @date: 2017 04 22
     *
     */
    public function Getdata($modelname, $target_data, $num = 'All', $where = '', $order = '', $limit = '', $group = '')
    {
        $model_name = $modelname::tableName();
        if ($order) {
            $order = ' order by ' . $order;
        }
        if ($limit) {
            $limit = ' limit ' . $limit;
        }
        if ($group) {
            $group = ' group by ' . $group;
        }

        $sql = "select " . $target_data . ' FROM ' . $model_name . ' ' . $where . '' . $order . $limit . $group;

        $command = \Yii::$app->db->createCommand($sql);

        if ($num == 'one') {

            $rs = $command->queryOne();

        } else {

            $rs = $command->queryAll();
        }

        return $rs;
    }


    /**
     * @func purpose 通用功能：用于跨库查询字段
     * @author wuyang
     * @date 2017 04 25
     */

    public function Getreplacedata($modelname, $target_data, $num = 'one', $where = '', $order = '', $limit = '', $group = '')
    {

        if ($order) {
            $order = ' order by ' . $order;
        }
        if ($limit) {
            $limit = ' limit ' . $limit;
        }
        if ($group) {
            $group = ' group by ' . $group;
        }
        $sql = "select " . $target_data . ' FROM ' . $modelname . ' ' . $where . '' . $order . $limit . $group;
        $command = \Yii::$app->db->createCommand($sql);
        if ($num == 'one') {
            $rs = $command->queryOne();
        } else {
            $rs = $command->queryAll();
        }
        return $rs;
    }


    /**
     * 获取自动发信模板列表
     * @param type $tempLateId
     * @return type
     * @author allen <2018-1-29>
     */
    public static function getTemplateName($tempLateId = "")
    {
        $data = [];
        $model = self::find()->where(['template_type' => 2, 'status' => 1])->asArray()->all();
        if (!empty($model)) {
            foreach ($model as $value) {
                $data[$value['id']] = $value['template_name'];
            }
        }
        if (!empty($tempLateId)) {
            return isset($data[$tempLateId]) ? $data[$tempLateId] : '-';
        }
        return $data;
    }

}
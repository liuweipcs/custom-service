<?php
/**
 * Created by PhpStorm.
 * User: wuyang
 * Date: 2017/4/19 0011
 * Time: 上午 11:03
 */

namespace app\modules\mails\models;

use Yii;
use app\modules\accounts\models\Platform;

class FeedbackTemplate extends MailsModel
{

    public static function tableName()
    {
        return '{{%feedback_template}}';
    }

    public function rules()
    {
        return [
            [['create_by', 'create_time', 'modify_by', 'modify_time', 'template_name', 'template_code'], 'safe'],
            [['template_content', 'platform_code'], 'required'],
            ['template_content', 'string'],
        ];

    }

    /**
     * 获取模板数据供下拉选择
     */
    public static function getTemplateDataAsArray($platform_code)
    {
        $query = new \yii\db\Query();
        $data = $query->from(self::tableName())
            ->select('id,template_name')
            ->where('platform_code=:platform_code', [
                'platform_code' => $platform_code])
            ->all();

        //组装并且符合下拉selct格式的结果
        $result = [];
        foreach ($data as $key => $value) {
            $result[$value['id']] = $value['template_name'];
        }
        return $result;
    }

    public function attributeLabels()
    {
        return [

            'template_name' => '模板名称',
            'template_content' => '模板内容',
            'template_code' => '模板编号',
            'platform_code' => '所属平台',
            'status' => '是否可用',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
        ];
    }

    public function searchList($params = [])
    {
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'id' => SORT_ASC
        );
        $dataProvider = parent::search(null, $sort, $params);

        $models = $dataProvider->getModels();
        $dataProvider->setModels($models);

        return $dataProvider;
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

    /**
     * @desc 搜索过滤项
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
                'name' => 'template_content',
                'type' => 'text',
                'search' => 'FULL LIKE',
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
     * @desc 随机获取模板内容
     * @return string
     */
    public static function Gettemplatename()
    {
        $datas = self::find()->asArray()->all();
        if ($datas) {
            $counts = count($datas);
            $key = mt_rand(0, $counts - 1);
            return $datas[$key]['template_content'];
        } else {
            return false;
        }

    }
   /**
     * @desc 根据平台code随机获取模板内容
     * @return string
    * @param type $code string
    * @author  harvin<2018-11-22>
     */
    
    public static function Getemplatenamecode($code){
       $temp= self::find()->where(['platform_code'=>$code])->asArray()->all();
           if ($temp) {
            $counts = count($temp);
            $key = mt_rand(0, $counts - 1);
            
            $res=[$temp[$key]['id'],$temp[$key]['template_content']];
            
            return $res;
        } else {
            return false;
        }   
    }
    
    
    
}
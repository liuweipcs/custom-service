<?php
namespace app\modules\mails\models;
use app\components\Model;
use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\systems\models\EbayAccount;

class EbayDisputes extends Model
{
    public static function getDb()
    {
        return \Yii::$app->db;
    }
    
    
    public static function tableName()
    {
        return '{{%ebay_disputes}}';
    }

    public function searchList($params = [])
    {
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'id' => SORT_ASC
        );
        //       $query=
        $dataProvider = self::search(NULL, $sort, $params);
        $models = $dataProvider->getModels();
        $casetype=$this->casetype();
        $makesiderole=$this->makesiderole();
        $accountmodel= New EbayAccount();
        foreach($models as $key=>$value){
                         
                if (array_key_exists($value->case_type, $casetype))
                {
                    $value->case_type=$casetype[$value->case_type];
                }   
                
                if(array_key_exists($value->make_side_role, $makesiderole)){
                    $value->make_side_role=$makesiderole[$value->make_side_role];
                }
                
                if(array_key_exists($value->other_side_role, $makesiderole)){
                   $value->other_side_role=$makesiderole[$value->other_side_role];
                }
                
                $storename=$accountmodel->find(['id'=>$value->account_id])->one();
                $value->account_id=$storename['user_name'];
                
                $value->setAttribute('case_id', Html::a($value->case_id, Url::toRoute(['/mails/ebaydisputes/showorder', 'order_id' => $value->transaction_id]),
                    ['class' => 'add-button', '_width' => '90%', '_height' => '90%']));
                
                
        }
        

        
        
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
        
        
/*        
        $query->andFilterWhere(
            ['status' => 1]);
*/        
        
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
    
    
    
    public function attributeLabels()
    {
        return [
            'case_id'                 => '纠纷ID',
            'case_type'               => '纠纷类型',
            'case_amount'             => '纠纷金额',
            'case_quantity'           => '纠纷数量',
            'creation_date'           => '纠纷开启时间',
            'item_id'                 => '产品ID',
            'item_title'              => '产品名称',
            'transaction_id'          => '交易ID',
            'last_modified_date'      => '最后一次修改时间',
            'make_side_role'          => '发起方角色',
            'other_side_role'         => '另一方角色',
            'respond_by_date'         => '下一个诉讼日期',
            'case_status'             => '案件状态',
            'account_id'              => '账号ID',
//            'account_id'                    => 'eBay账号',
            'siteid'                        => '站点',
            'create_by'                     => '创建者',
            'create_time'                   => '创建时间',
            'modify_by'                     => '修改者',
            'create_time'                   => '修改时间'
    ];
}
    public function rules()
    {
    
        return  [
            [['case_id', 'case_type', 'case_amount', 'case_quantity', 'creation_date', 'item_id',  'item_title','buyer','currency', 'transaction_id', 'last_modified_date', 'make_side_role', 'other_side_role', 'respond_by_date','case_status', 'account_id','siteid'],'safe']
        ];
        /*
         *
         return [
         [['role_name', 'role_code', 'status', 'parent_id'], 'required'],
         ['role_code', 'checkRoleCode']
         ];
        */
    }
    
    /**
     * @desc 搜索过滤项
     * @return multitype:multitype:string multitype:  multitype:string multitype:string
     */
    public function filterOptions()
    {
        return [
    
    
            [
                'name' => 'case_id',
                'type' => 'text',
                'htmlOptions' => [],
                'search' => '='
            ]
    
            /*
             [
                 'name' => 'status',
                 'type' => 'dropDownList',
                 'data' => self::dropdown(),
                 'search' => '=',
             ]
        */
        ];
    }
        
    /**
     * @desc case type 数组
     * @return 所有的case type 类型
     */
    public function casetype(){
        $arr=[     
          '1'=>'CANCEL_TRANSACTION',  
          '2'=>'EBP_INR',
          '3'=>'EBP_SNAD',
          '4'=>'INR',
          '5'=>'PAYPAL_INR',
          '6'=>'PAYPAL_SNAD',
          '7'=>'RETURN',
          '8'=>'SNAD',
          '9'=>'UPI'
        ];
        return $arr;
    }
    
    
    /**
     * @desc make side role 数组
     * @return 发起方角色数组
     * 
     */
    
    public function makesiderole(){
        $arr=[
            '1'=>'BUYER',
            '2'=>'EBAY',
            '3'=>'OTHER',
            '4'=>'SELLER'           
        ];
        return $arr;
    }
}
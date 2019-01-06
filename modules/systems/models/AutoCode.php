<?php
namespace app\modules\systems\models;
use app\modules\systems\models\SystemsModel;
/**
 * @package Ueb.modules.systems.models
 * 
 * @author Bob <Foxzeng>
 */
class AutoCode extends SystemsModel {
    
    public static $tableChangeLogEnabled = false;        //是否记录数据表操作日志
    
    const INCREATE_TYPE_DEF = 0;
    
    const INCREATE_TYPE_MON = 1;
    
    const INCREATE_TYPE_DAY = 2;
    
    const INCREATE_TYPE_HOUR = 3;
    
    const PURCHASE_ORDER_NO 	= 'purchase_order_no';//采购类型
	const PURCHASE_ORDER_LS_NO 	= 'purchase_order_ls_no';//
	const STOCK_IN_NO 			= 'purchase_order_stockin';//入库单类型
	const PURCHASE_RECEIPT_QC 	= 'purchase_receipt_qc';//采购到货质检单
	const APPROPRIATE_NO 		= 'appropriate_no';//调拨单类型
	const STOCK_TAKING_NO 		= 'stock_taking_no';//轮盘点号
	const STOCK_TAKING_TASK_NO 	= 'stock_take_task';//轮盘详情任务号
	const STOCK_MOVE            = 'stock_move'  ;//库存转移

    /**
     * @return string the associated database table name
     */
    public static function tableName() {
        return '{{%auto_code}}';
    }
    
    /**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{	      
		return array(
			array('code_type, code_format', 'required'),
            array('code_min_num,code_max_num,code_fix_length,code_increate_type', 'numerical', 'integerOnly' => true),
            array('code_prefix,code_suffix', 'length', 'max'=>10),
			array('code_min_num,code_max_num,code_fix_length', 'compare', 'compareValue'=>'0', 'operator'=>'>'),
		);
	}	

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(			
			'code_type'         => Yii::t('system', 'Type'),
			'code_prefix'       => Yii::t('system', 'Prefix'),	
            'code_suffix'       => Yii::t('system', 'Suffix'),
            'code_format'       => Yii::t('system', 'Formate'),   
            'code_min_num'      => Yii::t('system', 'Min Value'),
            'code_max_num'      => Yii::t('system', 'Max Value'),
            'code_fix_length'   => Yii::t('system', 'Fix Length'),
            'code_increate_type'=> Yii::t('system', 'Increate Type'),
		);
	}
	
	/**
	 * filter search options
	 * @return type
	 */
	public function filterOptions() {
		return array(
				array(
						'name'          => 'code_type',
						'type'          => 'text',
						'search'        => '=',
						'htmlOptions'   => array(),
				)
		);
		
	}
	
	/**
	 * order field options
	 * @return $array
	 */
	public function orderFieldOptions() {
		return array(
				'code_type'
		);
	}
      
    /**
     * get page list
     * 
     * @return array
     */
    public function getPageList() {      
        $this->_initCriteria();         
        if (! empty($_REQUEST['code_type']) ) {
            $codeType = trim($_REQUEST['code_type']);
            $this->criteria->addCondition("code_type = '{$codeType}'");  
        }
        $this->_initPagination( $this->criteria);
        $models = $this->findAll($this->criteria);
        
        return array($models, $this->pages);
    }
    
    /**
     * get index nav tab id 
     * 
     * @return type
     */
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/systems/autocode/list');
    }   
    
    /**
     * get serial code by the type
     * 
     * @param type $type
     */   
    public static function getCode($type) {
       $info = self::getByCodeType($type);
       $formate = $info['code_format'];
       $datetime = date('Y-m-d H:i:s');  
       $datetime = str_replace(array(' ', ':'), array('-','-'), $datetime);
       $timeArr = explode("-", $datetime);     
       $timeArr[0] = substr($timeArr[0], 2, 2);         
       $search = array('{Y}','{M}','{D}','{H}','{prefix}', '{suffix}');
       $replace = array($timeArr[0], $timeArr[1], $timeArr[2], $timeArr[3],$info['code_prefix'], $info['code_suffix']);
       $formate = str_replace($search, $replace, $formate);     
       $reset = self::reset($timeArr, $info['code_increate_type'], $info['code_increate_tag']);
       if (! empty($info['code_fix_length']) ) {
           $numLen = $info['code_fix_length'] + 5 - strlen($formate);
           if ( $numLen < 1 ) {
               throw new \Exception('Fixed length setting is not enough');
           }
           if ( empty($info['code_increase_num']) || $reset ) {
               $num = $info['code_min_num'];
           } else {
               $num = $info['code_increase_num'] + 1;
               if ( strlen($num) > $numLen ) {
                   throw new \Exception('excep','Value is beyond the fixed length.');
               }
           }
           if (! empty( $info['code_max_num']) && $num > $info['code_max_num']) {
               throw new \Exception('excep','Yii application can only be created once.');
           }
           $codeNum = str_pad($num, $numLen, '0', STR_PAD_LEFT); 
       } else {
           if ( empty($info['code_increase_num']) || $reset) {
               $num = $info['code_min_num'];
           } else {
               $num = $info['code_increase_num'] + 1;              
           }           
           if ( ! empty( $info['code_max_num']) &&$num > $info['code_max_num']) {
               throw new \Exception('excep','Yii application can only be created once.');
           }
           $codeNum = $num;
       }        
       $formate = str_replace('{num}', $codeNum,  $formate);
       $data = array(
           'code_increase_num'  => $num, 
           'code_increate_tag'  => self::getIncreateTag($timeArr,  $info['code_increate_type']),
        );
       $flag = self::updateAll($data, 'id = ' . $info['id']);
        if (! $flag ) {           
            return self::getCode($type);
        }
      
       return $formate;
    }
    
    /**
     * check reset
     * 
     * @param type $timeArr
     * @param type $increateType
     * @param type $increateTag
     */
    public static function reset($timeArr, $increateType, $increateTag) {      
        switch ($increateType) {
            case self::INCREATE_TYPE_DEF;
                return false;
                break;
            case self::INCREATE_TYPE_MON:
                if ( $timeArr[1] == $increateTag ) {
                    return false;
                }               
                break;
            case self::INCREATE_TYPE_DAY :
                if ( $timeArr[2] == $increateTag ) {
                    return false;
                }               
                break;
            case self::INCREATE_TYPE_HOUR:
                if ( $timeArr[3] == $increateTag ) {
                    return false;
                }               
                break;
            default:
                throw new CException(Yii::t('excep', 'Increate type error'));               
        }
        
        return true;
    }
    
    
    public static function getIncreateTag($timeArr, $increateType) {
        switch ($increateType) {
            case self::INCREATE_TYPE_DEF;
                return null;
                break;
            case self::INCREATE_TYPE_MON:               
                return $timeArr[1];
                break;
            case self::INCREATE_TYPE_DAY :              
                return $timeArr[2];
                break;
            case self::INCREATE_TYPE_HOUR:               
                return $timeArr[3];
                break;
            default:
                throw new CException(Yii::t('excep','Increate type error'));
                
        }
    }

    /**
     * get row by the code type
     * 
     * @param type $codeType
     * @return type
     * @throws CException
     */
    public static function getByCodeType($codeType) {
        $query = new \yii\db\Query();
        $info = $query->select('*')
			->from(self::tableName())
			->where("code_type = :type", array( ':type' => $codeType))
			->one();
        if ( empty($info) ) {
             throw new \Exception('No configuration code type information');
        }
        return $info;
    }
    
    public function getcodePrefixbyCodeType($codeType){
        $auto_code = $this->getByCodeType($codeType); 
        return $auto_code['code_prefix'];
    }
    
    
    
    
    
    
    
    
}
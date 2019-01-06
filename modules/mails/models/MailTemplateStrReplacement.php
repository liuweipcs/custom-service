<?php
/**
 * Created by PhpStorm.
 * User: wuyang
 * Date: 2017/4/24
 * Time: 下午 15:26
 */

namespace app\modules\mails\models;
use app\common\VHelper;
use app\modules\orders\models\Order;
use yii\helpers\Json;
class MailTemplateStrReplacement extends MailsModel
{
     public $final_content;
    public static function tableName()
    {
        return '{{%mail_template_str_replacement}}';
    }
    
    public function getarr(){
        $model = New MailTemplate();
        $mailmodel = New MailTemplateStrReplacement();
        $rs_arr=$model->getData($mailmodel,'id, field, position_str','All',"where status=1");
        $ret_arr=[];
        foreach($rs_arr as $key=>$value){
            $ret_arr[trim($value['field'])]=trim($value['position_str']);
        }
        return $ret_arr;
    }
    
    /**
     * 通用匹配函数
     * author: wuyang
     * date: 2017 04 25
     */
    public function match_common($pattern, $target){       
        preg_match($pattern, $target, $matches);
        return $matches;        
    }
    
    /**
     * 对传入的内容进行循环匹配，并返回匹配成功的值
     * author: wuyang
     * date: 2017 04 25
     */
    public function circlematch($content){
        $filter_arr = $this->getarr();
//        var_dump($filter_arr);
//        exit;
       
        $all_match=[];
        foreach($filter_arr as $key=>$value){
            $pattern='/'.$value.'/';
//            echo $pattern.'<br>';
            $rs     = $this->match_common($pattern, $content);
            if($rs){
            $all_match[$rs[0]] = $key;
            }         
        }
        return $all_match;
    }
    
    /**
     * 循环替换订单内容之后，返回订单内容
     * author: wuyang
     * date: 2017 04 25
     */
    
    public function replace_content_str($match_arr=null,$content=null){      
        $this->final_content = $content;
      foreach($match_arr as $key=>$value){
         $this->final_content= str_replace($key, $value, $this->final_content);
      }      
      return $this->final_content;       
    }
    
    
    /**
     * 传入匹配的数据，并将数组中的value值换成最终需要替换的值
     * @author  wuyang
     * @date  2017 04 26
     * 
     */
    public function replace_arr_value($match_arr=null, $plat, $order_id,$item_id=null){
/*        
        $match_arr=[
            '{Item_Title}'=>'inbox.item_title',
            '{Tracking}' => 'package.tracking'
        ];
*/        
/*         $plat=strtoupper($plat);
        $string='order_id='.$order_id.'&token=5E17C4488C2AC591';        
        $retuelt = VHelper::getSendreQuest($string, false, $plat);
        $retuelt = json_decode($retuelt,true);
        if (empty($retuelt) || (isset($retuelt['ack']) && $retuelt['ack'] != false))
            $retuelt = []; */
        $orderInfo = [];
        $retuelt = Order::getOrderStack($plat, $order_id);
        if (!empty($retuelt))
            $orderInfo = Json::decode(json_encode($retuelt), true);
        $buyerId = '';
        if (!empty($orderInfo) && isset($orderInfo['info']))
            $buyerId = isset($orderInfo['info']['buyer_id']) ? $orderInfo['info']['buyer_id'] : '';
        foreach($match_arr as $key=>$value){
            $field_arr=explode('.',$value);
            $target_database    =   trim($field_arr[0]);
            $target_field        =   trim($field_arr[1]);
            if($target_field=='buyer_id' && isset($orderInfo['info']['buyer_id'])){
                $match_arr[$key]=$orderInfo['info']['buyer_id'];
            }elseif($target_field=='tracking' && isset($retuelt['orderPackage']['0']['ship_code'])){
                $match_arr[$key]=$orderInfo['orderPackage']['0']['ship_code'];
            }elseif($target_field=='price' && isset($orderInfo['info']['total_price'])){
                $match_arr[$key]=$orderInfo['info']['total_price'];
            }elseif($target_field=='shipping' && isset($orderInfo['orderPackage']['0']['ship_code'])){
                $match_arr[$key]=$orderInfo['orderPackage']['0']['ship_code'];
            }elseif(($target_field=='dispatch_date') && isset($orderInfo['orderPackage']['0']['shipped_date'])){
                $match_arr[$key]=$orderInfo['orderPackage']['0']['shipped_date'];
            }elseif(($target_field=='payment_date') && isset($orderInfo['trade']['0']['order_pay_time'])){
                $match_arr[$key]=$orderInfo['trade']['0']['order_pay_time'];
            }elseif(($target_field=='days_count') && isset($orderInfo['orderPackage']['0']['shipped_date'])){
                $shipped_date=$orderInfo['orderPackage']['0']['shipped_date'];
                
                $compare= date("Y-m-d H:i:s",time());
                $target_compare=date_create($compare);
                $diff=date_diff($target_compare, $shipped_date);
                
                $match_arr[$key] = $diff;
            }elseif(($target_field=='price_history')){
                $historyOrders = Order::getHistoryOrders($plat, $buyerId);
                $historyOrderPriceTotal = 0.00;
                if (!empty($historyOrders))
                {
                    foreach ($historyOrders as $order)
                        $historyOrderPriceTotal += $order->total_price;
                }
                $match_arr[$key] = round($historyOrderPriceTotal, 2);
            }elseif($target_database=='inbox' && $target_field=='name'){
                $result=$this->get_inbox_table_value($plat, $target_field, $order_id);
                $match_arr[$key]=$result['name'];
            }
//            elseif($target_database=='summary'&& $plat=='ALI'){
//                $result=$this->get_inbox_table_value($plat, $target_field, $order_id);
//                $match_arr[$key]=$result['product_name'];
//            }
            elseif($target_field=='item_title'){
                foreach($orderInfo['product'] as $product)
                {
                    if($product['item_id'] == $item_id)
                        $match_arr[$key] = $product['title'];
                }
//                $match_arr[$key]=$orderInfo[''];
            }else{}
        }
        
        return $match_arr;
    }
    
    
   
    
    
    /**
     * 从订单表中获取所需要的数据
     * author: wuyang
     * date: 2017 04 25
     * 
     */
    public function get_order_table_value($platform, $target_field, $orderid){
 
        if(strtoupper($platform)=='ALI'){
        
            $modelname='{{%order}}.{{%order_aliexpress}}';
            $where="where platform_order_id='".$orderid."'";
            if($target_field=='price'){
                $target_data='total_price';
            }elseif($target_field=='payment_date'){
                $target_data='paytime';
            }elseif($target_field=='transaction_id'){
                $target_data='platform_order_id';
            }else{}
        
        }elseif(strtoupper($platform)=='AMAZON'){
            $modelname='{{%order}}.{{%order_amazon}}';
            $where="where platform_order_id='".$orderid."'";
            if($target_field=='price'){
                $target_data='total_price';
            }elseif($target_field=='payment_date'){
                $target_data='paytime';
            }elseif($target_field=='transaction_id'){
                $target_data='platform_order_id';
            }else{}
             
             
        }elseif(strtoupper($platform)=='EB'){
            $modelname='{{%order}}.{{%order_ebay}}';
            $where="where platform_order_id='".$orderid."'";
            if($target_field=='price'){
                $target_data='total_price';
            }elseif($target_field=='payment_date'){
                $target_data='paytime';
            }elseif($target_field=='transaction_id'){
                $target_data='platform_order_id';
            }else{}
        
        }else{}
      $Mailmodel= New MailTemplate();
      $result = $Mailmodel->Getreplacedata($modelname, $target_data, 'one',$where);
      return $result;
        
    }
    
    /**
     * 从留言表内获取所需要的数据
     * author: wuyang
     * date: 2017 04 25
     *
     */
    
    public function get_inbox_table_value($platform, $target_field, $orderid){
        
        if(strtoupper($platform)=='ALI'){
            
            $modelname='{{%crm}}.{{%aliexpress_inbox}}';
            $where="where channel_id='".$orderid."'";
            if($target_field=='buyer_id'){
                $target_data='other_login_id';
            }elseif($target_field=='name'){
                $target_data='other_name as name';
                
            }elseif($target_field=='item_title'){
        
                $modelname      =   '{{%crm}}.{{%aliexpress_summary}}';
                $target_data    =   'product_name';
                $where          =   "where message_id='".$orderid."'";   //如果是EBAY站内信，需要传入EBAY站内信的messageid
            }else{}
        
                       
        }elseif(strtoupper($platform)=='AMAZON'){
            $modelname='{{%crm}}.{{%amazon_inbox}}';
            $where="where order_id='".$orderid."'";
            if($target_field=='buyer_id'){
                return false;//{{%crm}}.{{%amazon_inbox里面不存在}} buyer_id
                //$target_data='other_login_id';
            }elseif($target_field=='name'){
                $target_data='sender as name';
            }elseif($target_field=='item_title'){
                return flase;//{{%crm}}.{{%amazon_inbox里面不存在item_title}}
                //$target_data    =   'product_name';
            }else{}
             
        }elseif(strtoupper($platform)=='EB'){
            $modelname='{{%crm}}.{{%ebay_inbox}}';
            $where    ="where platform_order_id='".$orderid."'";
            if($target_field=='buyer_id'){
                $target_data='sender';
            }elseif($target_field=='name'){
                $modelname='{{%order}}.{{%order_ebay}}';
                $target_data='ship_name as name';
            }elseif($target_field=='item_title'){
                $target_data='item_id';
            }else{}
        
        }else{}
         
        
        $Mailmodel= New MailTemplate();
        $result = $Mailmodel->Getreplacedata($modelname, $target_data, 'one',$where);
        
//        if(strtoupper($platform)=='ALI' && !$result){
//            $where="where channel_id='".$orderid."'";
            
//        }
        
        
 //       return $result;
        if(strtoupper($platform)=='AMAZON' && $target_field=='name'){
            $arr=explode('-', $result['name']);
            $result['name']=trim($arr[0]);
            return $result;
        }elseif(strtoupper($platform)=='EB' && $target_field=='item_title'){
            $result['item_id']="www.ebay.com/itm/".$result['item_id'];
            return $result;
        }else{
            return $result;
        }
           
        
    }
    
    
    
    /**
     * 从仓库发货列表中获取所需要的数据
     * author: wuyang
     * date: 2017 04 25
     *
     */
    
    public function get_packge_value($platform, $target_field, $orderid){
        
        if(strtoupper($platform)=='ALI'){            
            $modelname='{{%order}}.{{%order_aliexpress}}';
            $where="where platform_order_id='".$orderid."'";
            $target_data="order_id";                    
        }elseif(strtoupper($platform)=='AMAZON'){
            $modelname='{{%order}}.{{%order_amazon}}';
            $where="where platform_order_id='".$orderid."'";
            $target_data="order_id";
        }elseif(strtoupper($platform)=='EB'){
            $modelname='{{%order}}.{{%order_ebay}}';
            $where="where platform_order_id='".$orderid."'";
            $target_data="order_id";
        }
        
        $Mailmodel= New MailTemplate();
        $result = $Mailmodel->Getreplacedata($modelname, $target_data, 'one',$where);
        $order_id=$result['order_id'];
        
        $checkmodelname='{{%order}}.{{%order_package}}';
        $where="where order_id='".$order_id."'";
        
        if($target_field=='tracking'){
            $target_data_final='tracking_number_1';
        }elseif($target_field=='shipping'){
            $target_data_final='ship_code';
        }elseif($target_field=='dispatch_date'){
            $target_data_final='shipped_date';        
        }elseif($target_field=='days_count'){
            $target_data_final='shipped_date';      
        }else{}
        
        
        $final_result = $Mailmodel->Getreplacedata($checkmodelname, $target_data_final, 'one',$where);
        return $final_result;
        
       
    }
    
    /**
     * @desc 替换内容里面的占位符
     * @param unknown $content
     * @param unknown $platformCode
     * @param unknown $platformOrderId
     * @return Ambigous <string, mixed>
     */
    public static function replaceContent($content, $platformCode, $platformOrderId)
    {
        $mailmodel  = New MailTemplateStrReplacement();
        $match_arr   = $mailmodel->circlematch($content);
        $match_value = $mailmodel->replace_arr_value($match_arr, $platformCode, $platformOrderId);
        $content = $mailmodel->replace_content_str($match_value,$content);
        return $content;
    }
    
 
}
?>
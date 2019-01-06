<?php
// namespace app\modules\services\modules\wish\components;
namespace wish\components;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\ErpAccountApi;
use app\modules\mails\models\WishInbox;
//use function foo\func;
class WishApi{
	const SANDBOX = false;
	
	protected $Url = null;
	protected $TOKEN = null;
	protected $verificationUrl = null;
	protected  $curl = null;
	
	private $Action_Url = null;
	
	public function __construct($token){
		if(self::SANDBOX){
			$this->Url = 'https://sandbox.merchant.wish.com/api/v2/';
		}else{
			//$this->Url = 'https://merchant.wish.com/api/v1/';
			$this->Url = 'https://china-merchant.wish.com/api/v2/';   
		}
// 		$this->verificationUrl = Yii::app()->basePath.'/components/cacert.pem';
		$this->TOKEN = $token;
		$this->curl = curl_init();
	}
	
	protected function getActionUrl($action,$data=array()){
		$actionUrl = array(
				'ticket'=>'ticket',
				'ticketList'=>'ticket/get-action-required',
				'ticketReply'=>'ticket/reply',
				'ticketClose'=>'ticket/close',
				'ticketSupport'=>'ticket/appeal-to-wish-support',
				'ticketReopen'=>'ticket/re-open',
				'orderSearch'=>'order',
				'getInfractions'=>'get/infractions',
				'getUniviewedNoti'=>'noti/fetch-unviewed',
				'viewedNoti'=>'noti/mark-as-viewed',
				'countNoti'=>'noti/get-unviewed-count',
				'getSysNoti'=>'fetch-sys-updates-noti',
				'getAnnouncements'=>'fetch-bd-announcement',
				'refund' => 'order/refund'
		);
		$url = isset($actionUrl[$action])?$this->Url.$actionUrl[$action]:'';
		$url.= '?access_token='.$this->TOKEN;
		if(!empty($data)){
			$url .='&'.http_build_query($data);
		}
		return $url;
	}
	
	protected function getResult($action,array $data){
		$action_url = $this->getActionUrl($action,$data);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$action_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 300);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		$data = curl_exec($ch);
		curl_close($ch);
		return $this->getResponseResult($data);
	}
	
	protected function postResult($action,array $data){
		$action_url = $this->getActionUrl($action);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$action_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 300);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result = curl_exec($ch);
		curl_close($ch);
		return $this->getResponseResult($result);
	}
	
	public function getUrlResult($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 300);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		$result = curl_exec($ch);
		curl_close($ch);
		return $this->getResponseResult($result);
	}
	
	protected function getResponseResult($result){
		$obj = json_decode($result, false, 512, JSON_BIGINT_AS_STRING);
		return (empty($obj) || $obj->code==0)?$obj:false;
	}
	/**
	 * 获取单个ticket
	 * @param unknown $id  
	 * @return boolean
	 */
	public function getTicketById($id){
		return $this->getResult('ticket',array(
			'id'=>$id	
		));
	}
	
	/**
	 * 获取ticket列表
	 * @param number $start 
	 * @param number $limit
	 * @return boolean
	 */
	public function getTicketList($start=0,$limit=500){
		$condition['limit'] = $limit;
		if($start>0){
			$condition['start'] = $start;
		}
		return $this->getResult('ticketList', $condition);
	}
	/*
	 * 回复ticket 步骤一
	 * @param $account_id 店铺id
	 * @param $platform_id 站内信编号ID
	 * @param $content 回复内容
	 * @return boolean
	 * 获取账号信息*/
    public function getKeyAccount($account_id,$platform_id,$content){
        $accountM = Account::findById($account_id);
        if (empty($accountM)) return false;
        $accountName = $accountM->account_name;
        $params = ['platformCode' => Platform::PLATFORM_CODE_WISH, 'accountName' => $accountName];
        $ErpAccountApi = new ErpAccountApi();
        $ErpAccountApi->setApiMethod('getAccount')
            ->sendRequest($params, 'get');
        if ($ErpAccountApi->isSuccess()) {
            $response = $ErpAccountApi->getResponse();
            $accountM = $response->account;
            $this->TOKEN = $accountM->access_token;
            return $this->replyTicket($platform_id,$content);
        }else{
            return false;
        }
    }
	/**
	 * 回复ticket 步骤二
	 * @param unknown $id
	 * @param unknown $msg ticket的回复内容
	 * @return boolean
	 */
	public function replyTicket($id,$msg){

		return $this->getResult('ticketReply', array(
				'id'=>$id,
				'reply'=>$msg
		));
	}
	
	/**
	 *关闭ticket
	 * @param unknown $id
	 * @return boolean
	 */
	public function closeTicket($id){
		return $this->getResult('ticketClose', array(
				'id'=>$id
		));
	}
	
	/**
	 * 交给wish平台介入
	 * @param unknown $id
	 * @return boolean
	 */
	public function supportTicket($id){
		return $this->getResult('ticketSupport', array(
				'id'=>$id
		));
	}
	public function refundPostResult($action,array $data)
	{
	    $action_url = $this->getActionUrl($action);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$action_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 300);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result = curl_exec($ch);
		curl_close($ch);

		$obj = json_decode((string)$result);
		return $obj;
	}
	/** wish退款接口 **/
	public function refund($params=array())
	{   
	    //导入wish退款参数
		extract($params);

        if (empty($id) || empty($reason_code)) {
        	return [false, 'id or reason_code is empty'];
        }

		$result = $this->refundPostResult('refund', ['id' => $id, 'reason_code' => $reason_code]);

		//请求的时候出现未知错误
		if (empty($result)) {
            return [false,'unknow error'];
		}

        //请求成功,但是退款失败
		if ($result->code != 0) {
            return [false,$result->message];
		}
		
		//退款成功
		return [true,'refund success'];
	}
	/**
	 * 重新打开一个ticket
	 * @param unknown $id
	 * @param unknown $msg 打开的原因
	 * @return boolean
	 */
	public function reOpenTicket($id,$msg){
		return $this->getResult('ticketReopen', array(
			'id'=>$id,
			'reply'=>$msg
		));
	}
	
	public function order($oid){
		return $this->getResult('orderSearch', array(
			'id'=>$oid	
		));
	}
	
	public function getInfractions(){
		return $this->getResult('getInfractions', array());
	}
	
	public function getNotifiaction(){
		return $this->getResult('getUniviewedNoti', array());
	}
	
	public function checkNofitiaction($id){
		$result =  $this->getResult('viewedNoti', ['id'=>$id]);
		if($result->code==0){
			return true;
		}
		return false;
	}
	
}
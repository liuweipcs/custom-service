<?php 
/**
 * @package Ueb.modules.services.Tokencontroller
 * @auther Tom
 */
class AliexpresstokenController extends UebController{
	
    public function actionGetToken(){

		set_time_limit(3600);
		if (isset($_REQUEST['account']))
		{
			$model = new AliexpressAccount();
			$account = trim($_REQUEST['account']);
			$shortName         				 = UebModel::model('AliexpressAccount')->getAccountInfoByAccount($account);
			$grant_type     = urlencode('refresh_token');
			$client_id      = urlencode($shortName['app_key']);
			$client_secret  = urlencode($shortName['secret_key']);
			$refresh_token  = urlencode($shortName['refresh_token']);
			//构造参数
			$url            = sprintf("https://gw.api.alibaba.com/openapi/param2/1/system.oauth2/getToken/{$client_id}?client_id=%s&client_secret=%s&refresh_token=%s&grant_type=%s",$client_id, $client_secret, $refresh_token,  $grant_type);
			$context        = stream_context_create(array(
				'http' => array(
					'method'        => 'POST',
					'ignore_errors' => true,
				),
			));

			$response       = file_get_contents($url, TRUE, $context);
			$response       = json_decode($response,true);
			if ($response)
			{
				$data = array(
					'access_token' 	=> $response['access_token'],
					'expires_in' 	=> $response['expires_in'],
				);
				$model->updateByPk($account, $data);
			} else {
				echo '保存失败';
			}

		} else {
			$AliAccounts = UebModel::model('AliexpressAccount')->getAccountList();
			if (!empty($AliAccounts))
			{
				foreach ($AliAccounts as $id=>$val){
					MHelper::runThreadSOCKET('/services/aliexpress/aliexpressToken/getToken/account/'.$id);
					sleep(2);
				}
			} else {
				die('there are no any account!');
			}

        }     
    }
}
?>
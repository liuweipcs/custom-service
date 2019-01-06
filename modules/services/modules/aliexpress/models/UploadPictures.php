<?php
namespace app\modules\services\modules\aliexpress\models;
use app\common\VHelper;
use app\modules\services\modules\aliexpress\components\AliexpressApi;
use app\modules\systems\models\AliexpressLog;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
/**
 * 站内信/订单留言更新处理状态
 */
class UploadPictures
{
    protected $access;
    protected $app_key;
    protected $secret_key;
    protected $_taskId = 0;
    protected $account_id = 0;
    protected $_shortName = '';
    protected $_totalNumber = 0;
    protected $parent_id = 0;
    protected $errorMsg = '';


    /**
     * 获取店铺接口信息
     * @param $accountId 账号ID
     * @param $fileName 图片名
     * @param $fileData 二进制数据流
     * @return  bool
     */
    public function queryAccount($accountId,$fileName,$fileData)
    {

        $this->account_id = $accountId;
        $account = Account::findById($accountId);
        if (empty($account)) return false;
        $accountName = $account->account_name;
        $erpAccount = Account::getAccountFromErp(Platform::PLATFORM_CODE_ALI, $accountName);
        if (empty($erpAccount))
            return false;
        $this->_shortName = $erpAccount->short_name;
        $this->access =  $erpAccount->access_token;
        $this->app_key = $erpAccount->app_key;
        $this->secret_key = $erpAccount->secret_key;
        return $this->sendRequest($fileName,$fileData);
    }

   public function sendRequest($fileName,$fileData)
    {
        //实例化这个类
        $orderObj = new WherePictures();
        //构造参数
        $response_s = $orderObj->setAccessToken($this->access);
        $response_s = $response_s->putOtherTextParam('app_key', $this->app_key);
        $response_s = $response_s->putOtherTextParam('secret_key', $this->secret_key);
        $response_s = $response_s->putOtherTextParam('fileName', $fileName);
        $response_s = $response_s->putOtherTextParam('imageBytes', $fileData);
        //引入发送类
        $client = new AliexpressApi();
        //压入参数
        $client->setRequest($response_s);
        //发送请求
        $response = $client->exec();
        /*如果接口报错*/
        if(!empty($response) && !empty($response->error_code)){
            //如果接口提示未授权，清空账号缓存

            $aliexpressLogModel = new AliexpressLog();
            $arr['create_user_name'] = 'system';
            $arr['account_id'] = $this->account_id;
            $arr['update_content'] = '上传失败，错误代码为：'.$response->error_message;
            $arr['create_time'] = date('Y-m-d H:i:s');
            $arr['channel_id'] = '';
            $aliexpressLogModel->getAdd($arr);
            $this->errorMsg = $response->error_message;
            return false;
        }else{
            /*上传成功*/
            if ($response->success) {
                $aliexpressLogModel = new AliexpressLog();
                $Log['create_user_name'] = 'system';
                $Log['account_id'] = $this->account_id;
                $Log['update_content'] = '上传成功！';
                $Log['create_time'] = date('Y-m-d H:i:s');
                $Log['channel_id'] = '';
                $aliexpressLogModel->getAdd($Log);

               return $response->photobankUrl;
            } else {
                /*上传失败*/
                $aliexpressLogModel = new AliexpressLog();
                $daraArr['create_user_name'] = 'system';
                $daraArr['account_id'] = $this->account_id;
                $daraArr['channel_id'] = '';
                $daraArr['update_content'] = $response->status;
                $daraArr['create_time'] = date('Y-m-d H:i:s');
                $aliexpressLogModel->getAdd($daraArr);
                $this->errorMsg = $response->status;
                return false;
            }
        }

    }
    
    /**
     * @desc 获取错误信息
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }

}
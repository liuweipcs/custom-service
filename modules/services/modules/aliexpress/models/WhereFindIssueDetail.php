<?php
namespace app\modules\services\modules\aliexpress\models;
/** 
 *  @category    aliexpress
 *  @package     aliexpress
 *  @auther Bob <Foxzeng>
 */
class WhereFindIssueDetail {
    
    private $apiParas = [];
    private $fileName= null;
    private $access_token = null;

    public function setFileName($fileName) {
        $this->fileName = $fileName;
		$this->apiParas["fileName"] = $fileName;
        return $this;
    }

    public function setAccessToken($accessToken) {
        $this->access_token = $accessToken;
		$this->apiParas["access_token"] = $accessToken;
        return $this;
    }

    public function getApiMethodName() {
        return "alibaba.ae.issue.findIssueDetailByIssueId";
    }
	
	public function getApiParas() {
		return $this->apiParas;
	}
	
	public function check(){}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
        return $this;
	}
}
?>

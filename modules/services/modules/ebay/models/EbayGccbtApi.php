<?php
namespace app\modules\services\modules\ebay\models;

class EbayGccbtApi
{
    const SAN_HOST_URL = 'https://gccbtapi.sandbox.ebay.com.hk';
    const PRO_HOST_URL = 'https://gccbtapi.ebay.com.hk';

    /**
     * 获取卖家刊登在某个时段内的基于Best Match 排名的转化率数据
     */
    public static function itemBestMatches($accessToken)
    {
        $url = '/gccbtapi/v1/item_best_matches	';
        return self::request($url, $accessToken);
    }

    /**
     * 获取卖家账户全量的政策状态及详细数据，包含以下11项数据。
     */
    public static function accountData($accessToken)
    {
        $url = '/gccbtapi/v1/dashboard/account_data';
        return self::request($url, $accessToken);
    }

    /**
     * 获取卖家账户的所有12项政策状态，但不包含详细数据。卖家可以根据状态选择查看对应账号的卖家中心查看详情。
     */
    public static function accountOverview($accessToken)
    {
        $url = '/gccbtapi/v1/dashboard/account_overview';
        return self::request($url, $accessToken);
    }

    /**
     * 政策细分： 获取物流标准政策--美国小于5美金及其他25个主要国家的物流使用合规比例状态及详细数据。
     */
    public static function edsShippingPolicy($accessToken)
    {
        $url = '/gccbtapi/v1/dashboard/eds_shipping_policy';
        return self::request($url, $accessToken);
    }

    /**
     * 政策细分： 获取物流标准政策--美国>$5交易的物流使用状态及详细数据。
     */
    public static function epacketShippingPolicy($accessToken)
    {
        $url = '/gccbtapi/v1/dashboard/epacket_shipping_policy';
        return self::request($url, $accessToken);
    }

    /**
     * 政策细分：获取综合表现状态及详细数据
     */
    public static function ltnp($accessToken)
    {
        $url = '/gccbtapi/v1/dashboard/ltnp';
        return self::request($url, $accessToken);
    }

    /**
     * 政策细分：获取非货运表现状态及详细数据
     */
    public static function tci($accessToken)
    {
        $url = '/gccbtapi/v1/dashboard/tci';
        return self::request($url, $accessToken);
    }

    /**
     * 政策细分：获取商业计划追踪表现的状态及详细数据
     */
    public static function pgcTracking($accessToken)
    {
        $url = '/gccbtapi/v1/dashboard/pgc_tracking';
        return self::request($url, $accessToken);
    }

    /**
     * 政策细分：获取待处理刊登列表的信息
     */
    public static function qclist($accessToken)
    {
        $url = '/gccbtapi/v1/dashboard/qclist';
        return self::request($url, $accessToken);
    }

    /**
     * 政策细分：获取买家未收到物品提醒信息列表
     */
    public static function sellerInr($accessToken)
    {
        $url = '/gccbtapi/v1/dashboard/seller_inr';
        return self::request($url, $accessToken);
    }

    /**
     * 政策细分：获取货运表现（1-8周）的状态及详细数据
     */
    public static function ship1to8($accessToken)
    {
        $url = '/gccbtapi/v1/dashboard/ship1to8';
        return self::request($url, $accessToken);
    }

    /**
     * 政策细分：获取货运表现（5-12周）的状态及详细数据
     */
    public static function ship5to12($accessToken)
    {
        $url = '/gccbtapi/v1/dashboard/ship5to12';
        return self::request($url, $accessToken);
    }

    /**
     * 政策细分：获取海外仓标准的状态及详细数据
     */
    public static function sdWarehouse($accessToken)
    {
        $url = '/gccbtapi/v1/dashboard/sd_warehouse';
        return self::request($url, $accessToken);
    }

    /**
     * 政策细分：获取SpeedPAK 物流管理方案
     */
    public static function speedPakListData($accessToken)
    {
        $url = '/gccbtapi/v1/speedPak/list_data';
        return self::request($url, $accessToken);
    }

    /**
     * 政策细分：获取卖家设置SpeedPAK物流选项
     */
    public static function speedPakMisuseData($accessToken)
    {
        $url = '/gccbtapi/v1/speedPak/misuse_data';
        return self::request($url, $accessToken);
    }
    /***
     * 政策细分：获取SpeedPAK 物流管理方案及其他符合政策要求的物流服务使用状态相关交易下载数据
     * **/
    public static function listdownload($accessToken){
         $url = '/gccbtapi/v1/speedPak/list_download';
         return self::request($url, $accessToken);

    }
   /***
    * 政策细分：买家选择SpeedPAK物流选项时卖家正确使用SpeedPAK物流管理方案表现相关交易下载数据
    * ***/
    public static function misusedownload($accessToken){
         $url = '/gccbtapi/v1/speedPak/misuse_download';
         return self::request($url, $accessToken);  
    }

  

  /**
     * 请求方法
     * @param string $accessToken 这里的是Oauth token不是Auth Token,不要搞错了.
     */
    public static function request($url, $accessToken, $mode = 'pro')
    {
        //设置头信息
        $headers = [
            "Authorization: Bearer {$accessToken}",
        ];

        if ($mode == 'pro') {
            $url = self::PRO_HOST_URL . $url;
        } else if ($mode == 'san') {
            $url = self::SAN_HOST_URL . $url;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        $data = curl_exec($curl);
        if ($data === false) {
            throw new \Exception(curl_errno($curl) . ':' . curl_error($curl) . "\n");
            return false;
        }
        curl_close($curl);
        $data = json_decode($data, true, 512, JSON_BIGINT_AS_STRING);
        if ($data === false) {
            throw new \Exception("json_decode() failure \n");
            return false;
        }
        if (!empty($data['errorMessage']) && !empty($data['errorMessage']['error'])) {
            throw new \Exception($url . ':' . $data['errorMessage']['error'][0]['message'] . "\n");
            return false;
        }
        $data = $data['data'];
        return $data;
    }
}
<?php

namespace app\modules\services\modules\shopee\components;

class ShopeeApi
{
    var $api_version = '1.0';
    var $api_key = '';
    var $api_account = '';
    var $datastamp = '';
    private $shop_id, $partner_id, $secret_key, $url, $site;

    function __construct($shopId, $partnerId, $secretKey, $site = '')
    {

        //获取配置信息
        $this->shop_id    = intval($shopId);
        $this->partner_id = intval($partnerId);
        $this->secret_key = trim($secretKey);
        //基础接口域名，可能会根据账号而不同
        $this->url  = 'https://partner.shopeemobile.com';
        $this->site = $site;
    }


    private function signature($url, $post)
    {
        $hashstr = $url . '|' . $post;
        $auth    = hash_hmac('sha256', $hashstr, $this->secret_key);
        return $auth;
    }

    //统一的请求函数
    private function request($path, $param, $post = 0)
    {
        //默认参数
        $param['shopid']     = $this->shop_id;
        $param['partner_id'] = $this->partner_id;
        $param['timestamp']  = time();
        //请求地址
        $url      = $this->url . $path;
        $postjson = json_encode($param);
        //echo $postjson,'<br />';
        //echo $url,'<br />';
        //签名计算
        $signature = self::signature($url, $postjson);

        $headers   = [];
        $headers[] = 'Authorization: ' . $signature;
        $headers[] = 'Content-Type:  application/json';
        $ch        = curl_init();
        if ($post) {
            $api_url = $url;
        } else {
            $api_url = $url . '?' . http_build_query($param);
        }
        //var_dump($api_url);exit;
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // Save response to the variable $data
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postjson);
        }

        $json = curl_exec($ch);
        curl_close($ch);
        return $json;
    }


    //统一返回解码
    private function decoderes($json)
    {
        $r = json_decode($json, true);
        return $r;
    }


    //可能会有的更新token的方法
    function refreshtoken()
    {

    }


    public function getCategory()
    {
        $path   = '/api/v1/item/categories/get';
        $result = self::request($path, array(), 1);
        return self::decoderes($result);
    }

    public function getAttr($categoryId)
    {
        $key    = 'shopee_' . $this->site . '_' . $categoryId;
        $result = $attr = Yii::app()->cache->get($key);
        if (empty($result)) {
            $path   = '/api/v1/item/attributes/get';
            $result = self::request($path, ['category_id' => $categoryId], 1);
            Yii::app()->cache->set($key, $result, 3600 * 24);
        }
        return empty($result) ? false : self::decoderes($result);
    }


    //获得订单列表
    function getorderlist()
    {
        //获取订单列表
        $path                                 = '/api/v1/orders/basics';
        $param                                = [];
        $param['create_time_from']            = time() - 3600 * 24 * 15;
        $param['create_time_to']              = time();
        $param['pagination_entries_per_page'] = 50;
        $json                                 = self::request($path, $param, 1);
        $r                                    = self::decoderes($json);
        if (isset($r['orders'])) {
            $orderid_arr = [];
            foreach ($r['orders'] as $row) {
                $orderid_arr[] = $row['ordersn'];
            }
            $orders = $this->getorderdetail($orderid_arr);
            return $orders;
        }
        exit;
    }

    /**
     * @author alpha
     * @desc 获取商品列表
     * @param int $offset
     * @return mixed
     */
    function getItemList($offset = 0)
    {
        $path      = '/api/v1/items/get';
        $condition = [
            'pagination_entries_per_page' => 100,
            'pagination_offset'           => $offset
        ];
        return self::decoderes(self::request($path, $condition, 1));
    }

    /**
     * @author alpha
     * @desc 获取商品详情
     * @param $itemId
     * @return mixed
     */
    function getItemDetail($itemId)
    {
        $path      = '/api/v1/item/get';
        $condition = [
            'item_id' => intval($itemId)
        ];
        return self::decoderes(self::request($path, $condition, 1));
    }

    //更新产品

    function uploadProduct($array)
    {
        $path = '/api/v1/item/add';
        return self::decoderes(self::request($path, $array, 1));
    }

    //更新产品信息
    function updateProduct($array)
    {
        $path = '/api/v1/item/update';
        return self::decoderes(self::request($path, $array, 1));
    }

    //更新库存
    function updateStock($array)
    {
        $path = '/api/v1/items/update_stock';
        return self::decoderes(self::request($path, $array, 1));
    }
    //更新价格
    //item_id=>price
    function updatePrice($array)
    {
        $path = '/api/v1/items/update_price';
        return self::decoderes(self::request($path, $array, 1));
    }

    //更新子sku的价格：item_id，variation_id，price
    function updateVariationPrice($array)
    {
        $path = '/api/v1/items/update_variation_price';
        return self::decoderes(self::request($path, $array, 1));
    }

    //更新子sku的库存  item_id,variation_id,stock
    function updateVariationStock($array)
    {
        $path = '/api/v1/items/update_variation_stock';
        return self::decoderes(self::request($path, $array, 1));
    }

    /**
     * 描述:删除主item
     * @param $array
     * @return mixed
     */
    public function itemDelete($array)
    {
        $path = '/api/v1/item/delete';
        return self::decoderes(self::request($path, $array, 1));
    }


    /**
     * 描述:删除子item
     * @param $array
     * @return mixed
     */
    public function itemDeleteVariation($array)
    {
        $path = '/api/v1/item/delete_variation';
        return self::decoderes(self::request($path, $array, 1));
    }


    //更新图片
    function updateImage($array)
    {
        $path = '/api/v1/item/img/add';
        return self::decoderes(self::request($path, $array, 1));
    }

    /**
     * @author alpha
     * @desc 删除图片
     * @param $array
     * @return mixed
     */
    function deleteImage($array)
    {
        $path = '/api/v1/item/img/delete';
        return self::decoderes(self::request($path, $array, 1));
    }

    /**
     * @author alpha
     * @desc
     * @param array $condition
     * @return mixed
     */
    function getorders($condition = array())
    {
        $path                                     = '/api/v1/orders/basics';
        $condition['pagination_entries_per_page'] = 50;
        return self::decoderes(self::request($path, $condition, 1));
    }

    //获得订单详情
    function getorderdetail($orderid_arr)
    {
        $path                  = "/api/v1/orders/detail";
        $param                 = array();
        $param['ordersn_list'] = array_values($orderid_arr);
        $json                  = self::decoderes(self::request($path, $param, 1));
        if (!empty($json['orders'])) {
            return array_column($json['orders'], null, 'ordersn');
        }
        return false;
    }

    public function getorderincome($orderId)
    {
        $path = '/api/v1/orders/my_income';
        return self::decoderes(self::request($path, ['ordersn' => $orderId], 1));
    }

    /**
     * @author alpha
     * @desc 订单状态获取订单信息
     * @param $condition
     * @return mixed
     */
    public function getOrderByStatus($condition)
    {
        $path = '/api/v1/orders/get';
        return self::decoderes(self::request($path, $condition, 1));
    }

    /**
     * @author alpha
     * @desc 获得快递列表
     * @return mixed
     */
    public function listservice()
    {
        $path = '/api/v1/logistics/get';
        return self::decoderes(self::request($path, [], 1));
    }

    //标记发货信息
    function fillOrder($ordersn, $serviceName, $tracking_number)
    {
        $path               = "/api/v1/logistics/tracking_number/set_mass";
        $param              = array();
        $param['info_list'] = array(0 => ['ordersn' => $ordersn, 'tracking_number' => $tracking_number]);
        return self::decoderes(self::request($path, $param, 1));
    }


    /**
     * @author alpha
     * @desc 获取退款退货列表
     * @param $condition
     * @return mixed
     */
    public function GetReturnList($condition)
    {
        $path = '/api/v1/returns/get';
        $json = self::request($path, $condition, 1);
        $res  = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
        return $res;
    }

    /**
     * @author alpha
     * @desc 同意买家交易
     * @param $ordersn
     * @return mixed 返回 modified_time
     */
    public function AcceptBuyerCancellation($ordersn)
    {
        $path             = '/api/v1/orders/buyer_cancellation/accept';
        $param['ordersn'] = $ordersn;
        $json             = self::request($path, $param, 1);
        $res              = self::decoderes($json);
        return $res;
    }

    /**
     * @author alpha
     * @desc 拒绝买家交易
     * @param $ordersn
     * @return mixed 返回 modified_time
     */
    public function RejectBuyerCancellation($ordersn)
    {
        $path             = '/api/v1/orders/buyer_cancellation/reject';
        $param['ordersn'] = $ordersn;
        $json             = self::request($path, $param, 1);
        $res              = self::decoderes($json);
        return $res;

    }

    /**
     * @author alpha
     * @desc 确认退款退货
     * @param $returnsn
     * @return mixed 返回 msg returnsn
     */
    public function ConfirmReturn($returnsn)
    {
        $path              = '/api/v1/returns/confirm';
        $param['returnsn'] = $returnsn;
        $json              = self::request($path, $param, 1);
        $res               = self::decoderes($json);
        return $res;

    }


    /**
     * @author alpha
     * @desc纠纷退款退货
     * @param $returnsn
     * @param $email
     * @param $dispute_reason
     * @param $dispute_text_reason
     * @param $images
     * @return mixed
     */
    public function DisputeReturn($returnsn, $email, $dispute_reason, $dispute_text_reason, $images)
    {
        $path                         = '/api/v1/returns/dispute';
        $param['returnsn']            = $returnsn;
        $param['email']               = $email;
        $param['dispute_reason']      = $dispute_reason;
        $param['dispute_text_reason'] = $dispute_text_reason;
        $param['images']              = $images;
        $json                         = self::request($path, $param, 1);
        $res                          = self::decoderes($json);
        return $res;
    }

    /**
     * @author alpha
     * @desc 上传图片
     * @param $images
     * @return mixed
     */
    public function UploadImage($images)
    {
        $path            = '/api/v1/image/upload';
        $param['images'] = $images;
        $json            = self::request($path, $param, 1);
        $res             = self::decoderes($json);
        return $res;
    }
}

?>
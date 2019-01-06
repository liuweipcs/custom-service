<?php

namespace app\common;

use yii\base\Model;
use yii\db\ActiveRecord;
use PayPal\PayPalAPI\RefundTransactionRequestType;
use PayPal\PayPalAPI\RefundTransactionReq;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use PayPal\PayPalAPI\ReverseTransactionReq;
use PayPal\EBLBaseComponents\ReverseTransactionRequestDetailsType;
use PayPal\PayPalAPI\ReverseTransactionRequestType;
use PayPal\PayPalAPI\PayPal\PayPalAPI;
use PayPal\CoreComponentTypes\BasicAmountType;
use PayPal\PayPalAPI\GetTransactionDetailsReq;
use PayPal\PayPalAPI\GetTransactionDetailsRequestType;
use PayPal\PayPalAPI\TransactionSearchReq;
use PayPal\PayPalAPI\TransactionSearchRequestType;
//测试中1
use app\modules\systems\models\ErpSystemApi;
use app\modules\systems\models\Transactions;
use app\modules\systems\models\CurrencyRateKefu;

/**
 * View Helper Class
 * @package Application.components
 * @auther Bob <Foxzeng>
 */
class VHelper {

    /**
     * 代码调试
     */
    public static function dump() {
        //测试中
        $args = func_get_args();
        header('Content-type: text/html; charset=utf-8');
        echo "<pre>\n---------------------------------调试信息---------------------------------\n";
        foreach ($args as $value) {
            if (is_null($value)) {
                echo '[is_null]';
            } elseif (is_bool($value) || empty($value)) {
                var_dump($value);
            } else {
                print_r($value);
            }
            echo "\n";
        }
        //测试中2
        $trace = debug_backtrace();
        $next = array_merge(
                array(
            'line' => '??',
            'file' => '[internal]',
            'class' => null,
            'function' => '[main]'
                ), $trace[0]
        );
        //测试3-秦枫
        //测试4-秦枫
        /* if(strpos($next['file'], ZEQII_PATH) === 0){
          $next['file'] = str_replace(ZEQII_PATH, DS . 'library' . DS, $next['file']);
          }elseif (strpos($next['file'], ROOT_PATH) === 0){
          $next['file'] = str_replace(ROOT_PATH, DS . 'public' . DS, $next['file']);
          } */
        echo "\n---------------------------------输出位置---------------------------------\n\n";
        echo $next['file'] . "\t第" . $next['line'] . "行.\n";
        if (in_array('debug', $args)) {
            echo "\n<pre>";
            echo "\n---------------------------------跟踪信息---------------------------------\n";
            print_r($trace);
        }
        echo "\n---------------------------------调试结束---------------------------------\n";
        exit();
    }

    public static function runThreadSOCKET($urls, $hostname = '', $port = 80) {
        if (!$hostname) {
            $hostname = $_SERVER['HTTP_HOST'];
        }
        if (!is_array($urls)) {
            $urls = (array) $urls;
        }
        foreach ($urls as $url) {
            $fp = fsockopen($hostname, $port, $errno, $errstr, 18000);
            stream_set_blocking($fp, true);
            stream_set_timeout($fp, 18000);
            fputs($fp, "GET " . $url . "\r\n");
            fclose($fp);
        }
    }

    /**
     * PHP发送字符串
     *
     * @param $url 请求url
     * @param $jsonStr 发送的字符串
     * @return array
     */
    public static function http_post_json($url, $jsonStr) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($result);
        if (!empty($response))
            return $response;
        $result = str_replace(array("\t", "\r", "\n", "\r\n"), ' ', $result);
        $response = json_decode($result);
        if (!empty($response))
            return $response;
        $response = json_decode(mb_convert_encoding($result, 'UTF-8', 'auto'));
        return $response;
    }

    /*
     * 发送请求程序
     * @param $url 请求url
     * @param $jsonStr 发送的字符串
     * */

    public static function getDataApi($url, $jsonStr) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); //curl链接超时

        curl_setopt($ch, CURLOPT_TIMEOUT, 10); //curl响应超时

        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        if ($curl_errno && $curl_errno == 28) {
            return json_encode(array('error_code' => 28, 'error' => '查询超时，稍后再试'));
        } else {
            return $result;
        }
    }

    /* 订单详情获取 */

    public static function setRefund($string, $platform) {
        $string = $string . '&platform=' . $platform;
        $retuelt = VHelper::getDataApi('http://erp.yibai.cc/services/api/order/index/method/setrefund', $string);
        return $retuelt;
    }

    /* 订单详情获取 */

    public static function getSendreQuest($string, $fal = false, $platform) {
        $string = $string . '&platform=' . $platform;
        $retuelt = VHelper::getDataApi('http://erp.yibai.cc/services/api/order/index/method/mailrelatedorder', $string);
        if ($fal) {
            $retuelt = json_decode($retuelt, true);
            if (!empty($retuelt['code']) && $retuelt['code'] == 2035) {
                $retuelt = [];
            }
        }
        return $retuelt;
    }

    /* 历史订单列表获取 */

    public static function getHistoricalOrder($string, $platform) {
        $string = $string . '&platform=' . $platform;
        $retuelt = VHelper::getDataApi('http://erp.yibai.cc/services/api/Order/index/method/historicalorder', $string);
        $retuelt = json_decode($retuelt, true);
        if (!empty($retuelt['code']) && $retuelt['code'] == 2036) {
            $retuelt = [];
        }
        return $retuelt;
    }

    /* 获取产品库存和在途数量 */

    public static function getProductStockAndOnCount($sku, $warehouse_code) {
        //组装请求参数
        list($data['sku'], $data['warehouse_code']) = [$sku, $warehouse_code];
        $stock[] = $data;
        $string = "stock=" . json_encode($stock);
        //组装请求url
        $config = include \Yii::getAlias('@app') . '/config/purchase_api.php';
        $url = isset($config['baseUrl']) ? $config['baseUrl'] : '';

        if (empty($url)) {
            return ['available_stock' => null, 'on_way_stock' => null];
        }
        //获取请求结果
        $retuelt = VHelper::getDataApi($url, $string);
        $retuelt = json_decode($retuelt, true);
        //获取数据失败
        if ($retuelt === null || $retuelt['error'] != 0) {
            return ['available_stock' => null, 'on_way_stock' => null];
        }
        return !empty($retuelt['data']) ? current($retuelt['data']) : "";
    }

    /* 获取收款帐号与付款帐号 */

    public static function getTransactionAccount($transactionId) {

        $data = Transactions::find()->select("receiver_business,payer_email,order_time,amt,fee_amt,payment_status,currency")->where('transaction_id=:transaction', array(':transaction' => $transactionId))->all();

        return $data;
    }

    /** 根据站点code和产品asinval获取产品详情的链接 */
    public static function getProductDetailLinkHref($site_code, $asinval) {
        list($result['title'], $result['href']) = ['#', '#'];

        if (empty($site_code) || empty($asinval)) {
            return $result;
        }

        $list = include \Yii::getAlias('@app') . '/config/amazonin_site.php';

        if (empty($list)) {
            return $result;
        }
        if ($site_code == 'us') {
            $result['title'] = 'United States';
            $result['href'] = 'https://www.amazon.com/gp/product/' . $asinval;
        } else if ($site_code == 'sp') {
            $result['title'] = 'Spain';
            $result['href'] = 'https://www.amazon.es/dp/product/' . $asinval;
        } else {
            foreach ($list as $key => $value) {

                if (strpos($value, $site_code) !== false) {
                    $result['title'] = $key;
                    $result['href'] = $value . $asinval;
                }
            }
        }

        return $result;
    }

    /** ebay退票（退款接口） * */
    public static function ebayRefund($params) {
        //导入参数变量
        extract($params);

        //获取ebay退款的相关配置参数
        $config = include \Yii::getAlias('@app') . '/config/ebay_refund.php';

        if (!empty($refund_config)) {
            $config = array_merge($config, $refund_config);
        }


        $refundReqest = new RefundTransactionRequestType();

        //退款类型Full为全部退款Partial为部分退款
        $refundReqest->RefundType = $refund_type;

        //只有是部分退款才要设置货币代码和退款金额
        if (!empty($refund_amount) && !empty($currency_code) && strtoupper($refund_type) != "FULL") {
            $refundReqest->Amount = new BasicAmountType($currency_code, $refund_amount);
        }

        //设置交易号
        $refundReqest->TransactionID = $transaction_id;


        $refundReq = new RefundTransactionReq();
        $refundReq->RefundTransactionRequest = $refundReqest;
        $paypalService = new PayPalAPIInterfaceServiceService($config);

        try {
            $refundResponse = $paypalService->RefundTransaction($refundReq);
        } catch (\Exception $ex) {
            $ex_detailed_message = 'unknow error'; //退款相关配置的异常信息

            if (isset($ex)) {
                if ($ex instanceof PPConnectionException) {
                    $ex_detailed_message = "Error connecting to " . $ex->getUrl();
                }

                if ($ex instanceof PPMissingCredentialException || $ex instanceof PPInvalidCredentialException) {
                    $ex_detailed_message = $ex->errorMessage();
                }

                if ($ex instanceof PPConfigurationException) {
                    $ex_detailed_message = "Invalid configuration. Please check your configuration file";
                }
            }
            return [false, $ex_detailed_message];
        }

        if ($refundResponse->Ack == 'Failure') {
            $error_messgae = current($refundResponse->Errors);
            return [false, $error_messgae->LongMessage];
        }

        return [true, 'refund success'];
    }

    /** ebay 获取交易信息接口 * */
    public static function ebTransactionSearch($params) {
        //导入参数变量
        extract($params);

        //获取ebay退款的相关配置参数
        $config = include \Yii::getAlias('@app') . '/config/ebay_refund.php';

        if (!empty($search_config)) {
            $config = array_merge($config, $search_config);
        }

        $transactionSearchRequest = new TransactionSearchRequestType();
        $transactionSearchRequest->StartDate = $startDate;
        $transactionSearchRequest->EndDate = $endDate;
        $transactionSearchRequest->TransactionID = '';

        $tranSearchReq = new TransactionSearchReq();
        $tranSearchReq->TransactionSearchRequest = $transactionSearchRequest;

        $paypalService = new PayPalAPIInterfaceServiceService($config);
        try {
            $transactionSearchResponse = $paypalService->TransactionSearch($tranSearchReq);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            echo $ex->getFile();
            echo $ex->getLine();
            $ex_detailed_message = 'unknow error';
            if ($ex instanceof PPConnectionException) {
                $ex_detailed_message = "Error connecting to " . $ex->getUrl();
            } else if ($ex instanceof PPMissingCredentialException || $ex instanceof PPInvalidCredentialException) {
                $ex_detailed_message = $ex->errorMessage();
            } else if ($ex instanceof PPConfigurationException) {
                $ex_detailed_message = "Invalid configuration. Please check your configuration file";
            } else if (strpos($ex->getMessage(), 'timed out') !== false) {
                $ex_detailed_message = "Operation getting list timed out";
            }
            return [false, $ex_detailed_message];
        }

        if ($transactionSearchResponse->Ack == 'Failure') {
            $error_messgae = current($transactionSearchResponse->Errors);
            return [false, $error_messgae->LongMessage];
        }

        return [$transactionSearchResponse, 'getData success'];
    }

    /** 获取eb交易详情接口 * */
    public static function ebTransactionDeail($params) {
        //导入参数变量
        extract($params);

        //获取ebay退款的相关配置参数
        $config = include \Yii::getAlias('@app') . '/config/ebay_refund.php';

        if (!empty($detail_config)) {
            $config = array_merge($config, $detail_config);
        }

        $transactionDetails = new GetTransactionDetailsRequestType();
        $transactionDetails->TransactionID = $transID;
        $request = new GetTransactionDetailsReq();
        $request->GetTransactionDetailsRequest = $transactionDetails;
        $paypalService = new PayPalAPIInterfaceServiceService($config);

        try {
            $transDetailsResponse = $paypalService->GetTransactionDetails($request);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            echo $ex->getFile();
            echo $ex->getLine();
            $ex_detailed_message = 'unknow error';

            if ($ex instanceof PPConnectionException) {
                $ex_detailed_message = "Error connecting to " . $ex->getUrl();
            } else if ($ex instanceof PPMissingCredentialException || $ex instanceof PPInvalidCredentialException) {
                $ex_detailed_message = $ex->errorMessage();
            } else if ($ex instanceof PPConfigurationException) {
                $ex_detailed_message = "Invalid configuration. Please check your configuration file";
            } else if (strpos($ex->getMessage(), 'timed out') !== false) {
                $ex_detailed_message = "Operation getting detail timed out";
            }
            return [false, $ex_detailed_message];
        }

        if ($transDetailsResponse->Ack == 'Failure') {
            $error_messgae = current($transDetailsResponse->Errors);
            return [false, $error_messgae->LongMessage];
        }

        return [$transDetailsResponse, 'getData success'];
    }

    /**
     * @desc send multiple thread
     * @param unknown $url
     * @return boolean
     */
    public static function curl_post_async($url) {
        $ch = curl_init();
        $curlConfig = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 1
        );
        curl_setopt_array($ch, $curlConfig);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * @desc send multiple thread
     * @param unknown $url
     * @param unknown $params
     * @param string $type
     * @param number $timeout
     * @return boolean
     */
    public static function throwTheader($url, $params = array(), $type = 'GET', $timeout = 60) {
        $urlInfo = parse_url($url);
        if (!isset($urlInfo['host']) || empty($urlInfo['host']))
            $urlInfo = parse_url($_SERVER['HTTP_HOST']);
        $host = isset($urlInfo['host']) ? $urlInfo['host'] : $_SERVER['HTTP_HOST'];
        $scheme = isset($urlInfo['scheme']) ? $urlInfo['scheme'] : '';
        $hostStr = $scheme . "://" . $host;
        $uri = str_replace($hostStr, '', $url);
        $port = isset($urlInfo['port']) ? $urlInfo['port'] : '80';
        if (empty($host))
            return false;
        $socket = fsockopen($host, $port, $errno, $error, $timeout);
        if (!$socket)
            return false;
        stream_set_blocking($socket, false);
        $data = '';
        $body = '';
        if (is_array($params)) {
            foreach ($params as $key => $value)
                $data .= strval($key) . '=' . strval($value) . '&';
        } else
            $data = $params;
        $header = '';
        if ($type == 'GET') {
            if (strpos($uri, '?') !== false) {
                $uri .= '&' . $data;
            } else {
                $uri .= '?' . $data;
            }
            $header .= "GET " . $uri . ' HTTP/1.0' . "\r\n";
        } else {
            $header .= "POST " . $uri . ' HTTP/1.0' . "\r\n";
            $header .= "Content-length: " . strlen($data) . "\r\n";
            $body = $data;
            //$header .=
        }
        $header .= "Host: " . $host . "\r\n";
        $header .= 'Cache-Control:no-cache' . "\r\n";
        $header .= 'Connection: close' . "\r\n\r\n";
        $header .= $body;
        //file_put_contents('./test.log', $header . "\r\n\r\n", FILE_APPEND);
        fwrite($socket, $header, strlen($header));
        usleep(300);   //解决nginx服务器连接中断的问题
        fclose($socket);
        return true;
    }

    /* 判断图片地址是否有效 */

    public static function fileExists($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, 1); // 不下载
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (curl_exec($ch) !== false)
            return true;
        else
            return false;
    }

    public static function getModelErrors($model) {
        $return = '';
        if ($model instanceof Model) {
            $errors = $model->getErrors();
            if (!empty($errors)) {
                foreach ($errors as $errorV) {
                    $return .= implode('', $errorV);
                }
            }
        }
        return $return;
    }

    public static function _toDate($str) {
        $localTime = substr($str, 0, 14);
        $timezone1 = substr($str, -5, 3);
        $zonetimediff = (8 - (int) $timezone1) * 3600;
        return date('Y-m-d H:i:s', strtotime($localTime) + $zonetimediff);
    }

    /**
     * @desc 获取目标货币金额,如果转换失败则返回false否则返回转换后的货币金额
     * @param string $base_currency_code 原货币code
     * @param string $base_currency_amt 原货币金额
     * @param string $target_currency_code 目标货币code
     * @return float
     */
    public static function getTargetCurrencyAmt($base_currency_code, $target_currency_code, $base_currency_amt) {
        //参数缺失
        if (empty($base_currency_code) || empty($target_currency_code) || $base_currency_amt === null) {
            return false;
        }

        //实例化erp请求接口对象,并且获取请求结果
        $erpSystemApi = new ErpSystemApi();
        $rate = $erpSystemApi->getCurrencyRate($base_currency_code, $target_currency_code);

        //请求接口获取汇率失败
        if ($rate === false) {
            return false;
        }

        //用汇率进行计算，返回换算后的金额
        $target_currency_amt = $base_currency_amt * $rate;
        return sprintf("%.2f", $target_currency_amt);
    }

    /**
     * 获取目标货币金额，从库获取
     * @param $base_currency_code 原货币code
     * @param $target_currency_code 目标货币code
     * @param $base_currency_amt 原货币金额
     */
    public static function getTargetCurrencyAmtKefu($base_currency_code, $target_currency_code, $base_currency_amt) {
        if (empty($base_currency_code) || empty($target_currency_code) || empty($base_currency_amt)) {
            return false;
        }

        //根据原货币以及目标货币获取对应的汇率
        $rate = CurrencyRateKefu::getRateByCondition($base_currency_code, $target_currency_code);

        //没有找到对应的汇率
        if ($rate === false) {
            return false;
        }

        //用汇率进行计算，返回换算后的金额
        $target_currency_amt = $base_currency_amt * $rate;
        return sprintf("%.2f", $target_currency_amt);
    }

    public static function getTargetCurrencyAmtAll($rateMonth) {


        //根据原货币以及目标货币获取对应的结果集
        $info = CurrencyRateKefu::gerRateConditionAll('CNY', $rateMonth);
        //没有找到对应的汇率
        if ($info === false) {
            return false;
        }
        return $info;
        //用汇率进行计算，返回换算后的金额
        /* $target_currency_amt = $base_currency_amt * $rate;
          return sprintf("%.2f", $target_currency_amt); */
    }

    /**
     * 处理图片地址
     * @param type $imgUrl
     * @param type $imgSize 尺寸大小
     * @param type $is_href 返回是否带连接
     * @return string
     * @author allen <2017-12-28>
     */
    public static function processingPic($imgUrl, $imgSize, $is_href = FALSE) {
        if (!strstr($imgUrl, 'http://')) {
            $imgUrl = 'http://ae01.alicdn.com/kf/' . $imgUrl;
        }

        if (!strstr($imgUrl, '_' . $imgSize)) {
            $ex = explode('.', $imgUrl);
            $suffix = end($ex);
            $imgUrl = $imgUrl . '_' . $imgSize . '.' . $suffix;
        } else {
            $ex = explode('.', $imgUrl);
            $suffix = end($ex);
        }

        if ($is_href) {
            $cutStr = '_' . $imgSize . '.' . $suffix;
            $href_src = rtrim($imgUrl, $cutStr) . '.' . $suffix;
            $imgUrl = '<a href="' . $href_src . '" target="_balnk">' . '<img src="' . $imgUrl . '"/></a>';
        }
        return $imgUrl;
    }

    /**
     * 语言编码
     * @return type
     * @author allen <2018-1-4>
     */
    public static function googleLangCode($code = "") {
        $arrCode = [
            'es' => '西班牙语',
            'de' => '德语',
            'en' => '英语',
            'ru' => '俄语',
            'fr' => '法语',
            'en' => '英语',
            'it' => '意大利语',
            'pt' => '葡萄牙语',
            'zh-CN' => '中文',
            'zh-TW' => '中文(繁体)',
            'sq' => '阿尔巴尼亚语',
            'ar' => '阿拉伯语',
            'am' => '阿姆哈拉语',
            'az' => '阿塞拜疆语',
            'ga' => '爱尔兰语',
            'et' => '爱沙尼亚语',
            'eu' => '巴斯克语',
            'be' => '白俄罗斯语',
            'bg' => '保加利亚语',
            'is' => '冰岛语',
            'pl' => '波兰语',
            'bs' => '波斯尼亚语',
            'fa' => '波斯语',
            'af' => '布尔语(南非荷兰语)',
            'da' => '丹麦语',
            'tl' => '菲律宾语',
            'fi' => '芬兰语',
            'fy' => '弗里西语',
            'km' => '高棉语',
            'ka' => '格鲁吉亚语',
            'gu' => '古吉拉特语',
            'kk' => '哈萨克语',
            'ht' => '海地克里奥尔语',
            'ko' => '韩语',
            'ha' => '豪萨语',
            'nl' => '荷兰语',
            'ky' => '吉尔吉斯语',
            'gl' => '加利西亚语',
            'ca' => '加泰罗尼亚语',
            'cs' => '捷克语',
            'kn' => '卡纳达语',
            'co' => '科西嘉语',
            'hr' => '克罗地亚语',
            'ku' => '库尔德语',
            'la' => '拉丁语',
            'lv' => '拉脱维亚语',
            'lo' => '老挝语',
            'lt' => '立陶宛语',
            'lb' => '卢森堡语',
            'ro' => '罗马尼亚语',
            'mg' => '马尔加什语',
            'mt' => '马耳他语',
            'mr' => '马拉地语',
            'ml' => '马拉雅拉姆语',
            'ms' => '马来语',
            'mk' => '马其顿语',
            'mi' => '毛利语',
            'mn' => '蒙古语',
            'bn' => '孟加拉语',
            'my' => '缅甸语',
            'hmn' => '苗语',
            'xh' => '南非科萨语',
            'zu' => '南非祖鲁语',
            'ne' => '尼泊尔语',
            'no' => '挪威语',
            'pa' => '旁遮普语',
            'ps' => '普什图语',
            'ny' => '齐切瓦语',
            'ja' => '日语',
            'sv' => '瑞典语',
            'sm' => '萨摩亚语',
            'sr' => '塞尔维亚语',
            'st' => '塞索托语',
            'si' => '僧伽罗语',
            'eo' => '世界语',
            'sk' => '斯洛伐克语',
            'sl' => '斯洛文尼亚语',
            'sw' => '斯瓦希里语',
            'gd' => '苏格兰盖尔语',
            'ceb' => '宿务语',
            'so' => '索马里语',
            'tg' => '塔吉克语',
            'te' => '泰卢固语',
            'ta' => '泰米尔语',
            'th' => '泰语',
            'tr' => '土耳其语',
            'cy' => '威尔士语',
            'ur' => '乌尔都语',
            'uk' => '乌克兰语',
            'uz' => '乌兹别克语',
            'iw' => '希伯来语',
            'el' => '希腊语',
            'haw' => '夏威夷语',
            'sd' => '信德语',
            'hu' => '匈牙利语',
            'sn' => '修纳语',
            'hy' => '亚美尼亚语',
            'ig' => '伊博语',
            'yi' => '意第绪语',
            'hi' => '印地语',
            'su' => '印尼巽他语',
            'id' => '印尼语',
            'jw' => '印尼爪哇语',
            'yo' => '约鲁巴语',
            'vi' => '越南语'
        ];

        if (!empty($code)) {
            return $arrCode[$code];
        } else {
            return $arrCode;
        }
    }

    /**
     * 售后单审核状态
     * @author allen <2018-1-9>
     */
    public static function afterSalesStatusList() {
        return [
            1 => '待审核',
            2 => '审核通过',
            3 => '审核不通过',
            4 => '完结'
        ];
    }

    /**
     * 订单状态数组
     * @author allen <2018-1-11>
     */
    //0=初始化，1=正常订单，5=异常订单，10=缺货订单，13=已备货订单，15=待发货订单，17=超期订单，19=部分发货订单，20=已发货订单，25=暂扣订单，40=已取消订单，45=已完成订单
    public static function orderStatusList() {
        return [
            0 => '初始化',
            1 => '正常订单',
            5 => '异常订单',
            10 => '缺货订单',
            13 => '已备货订单',
            15 => '待发货订单',
            17 => '超期订单',
            19 => '部分发货订单',
            20 => '已发货订单',
            25 => '暂扣订单',
            40 => '已取消订单',
            45 => '已完成订单',
            99 => '通途处理订单',
        ];
    }

    /**
     * 退款状态
     * @param type $status
     * @return string
     * @author allen <2018-1-16>
     */
    public static function refundStatus($status = '') {
        $statusList = [
            0 => '<b style="color:green;">未发起退款</b>',
            1 => '<b style="color:green;">部分退款</b>',
            2 => '<b style="color:green;">全额退款</b>'
        ];
        if (!empty($status) || $status == '0') {
            $statusList = $statusList[$status];
        }
        return $statusList;
    }

    /**
     * 通过缩略图地址获取图片原地址
     * @param type $imgUrl 缩略图地址
     * @return type
     * @author allen <2018-1-18>
     */
    public static function getOriginalImgUrl($imgUrl) {
        $data = [];
        $thumbnailsImgSrc = str_replace('10.170.32.66', '120.24.249.36', $imgUrl);
        $data['thumbnailsImgSrc'] = $thumbnailsImgSrc;
        $img_src = str_replace('120.24.249.36', 'images.yibainetwork.com', $thumbnailsImgSrc);
        $originalImgSrc = str_replace('Thumbnails', 'assistant', $img_src);
        $data['originalImgSrc'] = $originalImgSrc;
        return $data;
    }

    /**
     * 订单类型
     * @param type $status
     * @author allen <2018-1-19>
     */
    public static function getOrderType($status) {
        $statusList = [
            1 => '<b style="color:green;">普通订单</b>',
            2 => '<b style="color:green;">合并后的订单</b>',
            3 => '<b style="color:green;">被合并的订单</b>',
            4 => '<b style="color:green;">拆分的主订单</b>',
            5 => '<b style="color:green;">拆分后的子订单</b>',
            6 => '<b style="color:green;">普通订单[已创建过重寄单]</b>',
            7 => '<b style="color:green;">重寄后的订单</b>',
            8 => '<b style="color:green;">客户补款的订单</b>'
        ];

        if (!empty($status) || $status == '0') {
            $statusList = $statusList[$status];
        }
        return $statusList;
    }

    public static function getOrderTypeText($status) {
        $statusList = [
            1 => '普通订单',
            2 => '合并后的订单',
            3 => '被合并的订单',
            4 => '拆分的主订单',
            5 => '拆分后的子订单',
            6 => '普通订单[已创建过重寄单',
            7 => '重寄后的订单',
            8 => '客户补款的订单'
        ];

        if (!empty($status) || $status == '0') {
            $statusList = $statusList[$status];
        }
        return $statusList;
    }

    /**
     * 翻译表情替换常量
     * @param type $str
     * @author allen <2018-1-26>
     */
    public static function ContentConversion($str) {
        $str = str_replace('😄', 'UEBSMILE', $str);
        $str = str_replace('😫', 'UEBSMILE1', $str);
        $str = str_replace('😞', 'UEBSMILE2', $str);
        $str = str_replace('😘', 'UEBSMILE3', $str);
        $str = str_replace('🙂', "UEBSMILE4", $str);
        $str = str_replace('😬', "UEBSMILE5", $str);
        $str = str_replace('😉', "UEBSMILE6", $str);
        return $str;
    }

    /**
     * 翻译常量替换表情
     * @param type $str
     * @author allen <2018-1-26>
     */
    public static function ReContentConversion($str) {
        $str = str_replace("UEBSMILE6", '😉', $str);
        $str = str_replace("UEBSMILE5", '😬', $str);
        $str = str_replace("UEBSMILE4", '🙂', $str);
        $str = str_replace('UEBSMILE3', '😘', $str);
        $str = str_replace('UEBSMILE2', '😞', $str);
        $str = str_replace('UEBSMILE1', '😫', $str);
        $str = str_replace('UEBSMILE', '😄', $str);
        return $str;
    }

    /**
     * 错误信息格式化
     * @param type $array
     * @return string
     * @author hezhen <2018-02-11>
     */
    public static function errorToString($array) {
        $str = "";
        if (is_array($array) && !empty($array)) {
            foreach ($array as $value) {
                $str .= $value[0] . '
';
            }
        }
        return $str;
    }

    /**
     * curl post请求数据
     * @param type $url
     * @param type $postData
     * @return type
     * @author allen <2018-03-22>
     */
    public static function curl_post($url, $postData, $method = 'post') {
        $curl_post = 1;
        if (strtoupper($method) == 'GET') {
            $curl_post = 0;
        }
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        
        //post请求
        if ($curl_post) {
            //设置post方式提交
            curl_setopt($curl, CURLOPT_POST, 1);
            //设置post数据
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        }
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);

        return $data;
    }

    /**
     * 导出数据为Excel
     * @param array $fieldArr 标题数组
     * @param array $dataArr 数据数组
     * @param string $fileName 文件名
     */
    public static function exportExcel($fieldArr, $dataArr, $fileName = '') {
        if (empty($fieldArr) || empty($dataArr)) {
            return;
        }

        //创建PHPExcel对象
        $obj = new \PHPExcel();
        //创建excel写入对象
        $writer = new \PHPExcel_Writer_Excel5($obj);
        //得到当前工作表对象
        $curSheet = $obj->getActiveSheet();

        $fieldNum = count($fieldArr);
        $dataRow = count($dataArr) + 2;

        for ($col = 0; $col < $fieldNum; ++$col) {
            $cellName = \PHPExcel_Cell::stringFromColumnIndex($col) . '1';
            $curSheet->setCellValue($cellName, $fieldArr[$col]);
        }

        for ($row = 2; $row < $dataRow; ++$row) {
            for ($col = 0; $col < $fieldNum; ++$col) {
                $cellName = \PHPExcel_Cell::stringFromColumnIndex($col) . $row;
                $curSheet->setCellValue($cellName, $dataArr[$row - 2][$col]);
            }
        }

        $fileName = !empty($fileName) ? $fileName : date('YmdHis', time());
        header('Content-Type: application/vnd.ms-execl');
        header('Content-Disposition: attachment;filename="' . $fileName . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    /**
     * 去除emoji表情符号
     * @param $text
     */
    public static function removeEmoji($text) {
        $clean_text = '';
        // Match Emoticons
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clean_text = preg_replace($regexEmoticons, '', $text);
        // Match Miscellaneous Symbols and Pictographs
        $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clean_text = preg_replace($regexSymbols, '', $clean_text);
        // Match Transport And Map Symbols
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clean_text = preg_replace($regexTransport, '', $clean_text);
        // Match Miscellaneous Symbols
        $regexMisc = '/[\x{2600}-\x{26FF}]/u';
        $clean_text = preg_replace($regexMisc, '', $clean_text);
        // Match Dingbats
        $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
        $clean_text = preg_replace($regexDingbats, '', $clean_text);
        return $clean_text;
    }

    /**
     * 将秒数转换成天时分秒的字符串格式
     * @param $second 秒数
     * @return string
     */
    public static function sec2string($second) {
        $day = floor($second / (3600 * 24));
        $second = $second % (3600 * 24);
        $hour = floor($second / 3600);
        $second = $second % 3600;
        $minute = floor($second / 60);
        $second = $second % 60;
        $day = $day ? $day . '天' : '';
        $hour = $hour ? $hour . '时' : ($day && ($hour || $minute || $second) ? '0时' : '');
        $minute = $minute ? $minute . '分' : ($hour && $second ? '0分' : '');
        $second = $second ? $second . '秒' : '';
        return $day . $hour . $minute . $second;
    }

    /**
     * 将秒数转换成天
     * @param $second 秒数
     */
    public static function sec2day($second) {
        $day = floor($second / (3600 * 24));
        return $day;
    }

    /**
     * 生成树型数据
     * @param array $items 数组数据
     * @param string $id ID字段名
     * @param string $pid 父级ID字段名
     * @param string $son 子类数组下标名
     */
    public static function genTree($items, $id = 'id', $pid = 'pid', $son = 'child') {
        $tree = array();
        $tmpMap = array();

        foreach ($items as $item) {
            $tmpMap[$item[$id]] = $item;
        }

        foreach ($items as $item) {
            if (isset($tmpMap[$item[$pid]])) {
                $tmpMap[$item[$pid]][$son][] = &$tmpMap[$item[$id]];
            } else {
                $tree[] = &$tmpMap[$item[$id]];
            }
        }
        unset($tmpMap);
        return $tree;
    }

    /**
     * 删除字符串中的html标签，css样式，多余空格
     */
    public static function removeTags($content) {
        //删除html标签
        $content = preg_replace('/<[^>]*>/', '', $content);

        //删除控制符
        $content = str_replace("\r", '', $content);
        $content = str_replace("\n", '', $content);
        $content = str_replace("\t", '', $content);

        //去除css样式
        $content = preg_replace('/[\.\#]?\w+[^{]+\{[^}]*\}/i', '', $content);

        //删除多余空格
        $content = preg_replace('/\s+/', ' ', $content);

        return $content;
    }

    public static function clearStyle($content) {
        $content = preg_replace("/style=.+?[\'|\"]/i", '', $content);
        $content = preg_replace("/class=.+?[\'|\"]/i", '', $content);
        $content = preg_replace("/id=.+?[\'|\"]/i", '', $content);
        $content = preg_replace("/lang=.+?[\'|\"]/i", '', $content);
        $content = preg_replace("/width=.+?[\'|\"]/i", '', $content);
        $content = preg_replace("/height=.+?[\'|\"]/i", '', $content);
        $content = preg_replace("/border=.+?[\'|\"]/i", '', $content);
        $content = preg_replace("/face=.+?[\'|\"]/i", '', $content);
        $content = preg_replace("/face=.+?[\'|\"]/", '', $content);
        return $content;
    }

    /**
     * html文本过长换行
     * @param $str 字符串
     * @param $num 字数
     * @return string
     */
    public static function break_string($str, $num) {
        preg_match_all("/./u", $str, $arr); //将所有字符转成单个数组

        $strstr = '';
        $width = 0;
        $arr = $arr[0];
        foreach ($arr as $key => $string) {
            $strlen = strlen($string); //计算当前字符的长度，一个字母的长度为1，一个汉字的长度为3

            if ($strlen == 3) {

                $width += 1;
            } else {

                $width += 0.5;
            }

            $strstr .= $string;

            //计算当前字符的下一个
            if (array_key_exists($key + 1, $arr)) {
                $_strlen = strlen($arr[$key + 1]);
                if ($_strlen == 3) {
                    $_width = 1;
                } else {
                    $_width = 0.5;
                }
                if ($width + $_width > $num) {
                    $width = 0;
                    $strstr .= "<br>";
                }
            }
        }
        return $strstr;
    }

    /**
     * 遍历文件夹下所有文件
     * @param $dir
     * @return array|bool
     */
    public static function read_all($dir) {
        if (!is_dir($dir))
            return false;
        $handle = opendir($dir);
        $temp_list = [];
        if ($handle) {
            while (($fl = readdir($handle)) !== false) {
                $temp = $dir . DIRECTORY_SEPARATOR . $fl;
                if (is_dir($temp) && $fl != '.' && $fl != '..') {
                    self::read_all($temp);
                } else {
                    if ($fl != '.' && $fl != '..') {
                        $temp_list[] = $fl;
                    }
                }
            }
        }
        return $temp_list;
    }

    /**
     * 判断数组里是否有有效值
     * @param type $arr
     * @return boolean
     * @autor allen <2018-09-14>
     */
    public static function arrIsVal($arr) {
        $bool = FALSE;
        if (is_array($arr) && !empty($arr)) {
            foreach ($arr as $value) {
                if ($value) {
                    $bool = TRUE;
                }
            }
        }
        return $bool;
    }

    /**
     * 二维数组排序方法
     * @param type $arr 二维数组  必填字段
     * @param type $keys  排序的下标
     * @param type $type  排序方式 默认desc
     * @param type $newIndex  下标是否重新索引  默认true
     * @return $arr 新排序后数组
     * @author allen <2018-11-21>
     */
    public static function array_sort($arr, $keys, $type = 'desc',$newIndex = true) {
        $key_value = $new_array = array();
        foreach ($arr as $k => $v) {
            $key_value[$k] = $v[$keys];
        }
        if ($type == 'asc') {
            asort($key_value);
        } else {
            arsort($key_value);
        }
        reset($key_value);
        foreach ($key_value as $k => $v) {
            if($newIndex){
                $new_array[] = $arr[$k];
            }else{
                $new_array[$k] = $arr[$k];
            }
        }
        return $new_array;
    }

}

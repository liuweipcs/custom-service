<?php

namespace app\modules\aftersales\models;

use Yii;
use app\components\Model;
use app\modules\accounts\models\Platform;
use app\modules\aftersales\models\ComplaintdetailModel;
use app\modules\orders\models\Warehouse;

class ComplaintModel extends Model {

    public static function getDb() {
        return \Yii::$app->db;
    }

    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName() {
        return '{{%warehouse_customer_complaint}}';
    }

    public function getComplian() {
        return $this->hasMany(ComplaintdetailModel::className(), ['complaint_order_id' => 'id']);
    }

    /*     * *
     * 生成客诉单号
     * 客诉号规则 ：保证唯一性，生成“KS”＋年月日+随机4位数字
     * * */

    public static function getComplaintorder() {
        //生产随机4位数据
        $res = rand(1000, 9999);
        $ComplainOrder = "KS" . date('Ymd') . $res;
        return $ComplainOrder;
    }

    public static function getcomplianorder($type = null, $platformCode = null, $key = null, $is_expedited = null, $is_overtime = null, $status = null, $get_date = null, $begin_date = null, $end_date = null, $account_id = null) {
        $query = self::find();
        $res = [];
        $time = '';
        $keys = '';
        if ($type != null) {
            $res['type'] = $type;
        }
        if ($platformCode != null) {
            $res['platform_code'] = $platformCode;
        }
        if ($key != null) {
            $keys = ['or', ['buyer_id' => $key], ['order_id' => $key], ['platform_order_id' => $key]];
        }
        if ($is_expedited != null) {
            $res['is_expedited'] = $is_expedited;
        }
        if ($is_overtime != null) {
            $res['is_overtime'] = $is_overtime;
        }
        if ($status != null) {
            $res['status'] = $status;
        }
        if ($account_id != null) {
            $res['account_id'] = $account_id;
        }
        if ($begin_date != null && $end_date != null) {
            $time = ['between', $get_date, $begin_date, $end_date];
        }
        $data = $query->joinWith('complian')
                        ->where($res)->andWhere($time)->andWhere($keys)
                        ->orderBy('create_time desc')->asArray()->all();
        return $data;
    }

    /**
     * 设置处理最晚时间
     * 12:00-14:00/18:00-次日早上08:00 加2个小时 
     * * */
    public static function getProcessingTime($state) {
        //获取当前时间戳
        $time = time();
        if ($state == 2) {
            //2个小时时间戳
            $addtime = 7200;
            //8:00至10:00
            if ($time >= strtotime(date("Y-m-d 08:00:00")) && $time <= strtotime(date("Y-m-d 10:00:00"))) {
                $last_processing_time = date('Y-m-d H:s:i', $time + $addtime);
                return $last_processing_time;
            } elseif ($time > strtotime(date("Y-m-d 10:00:00")) && $time <= strtotime(date("Y-m-d 12:00:00"))) {
                $last_processing_time = date('Y-m-d H:s:i', $time + $addtime + $addtime);
                return $last_processing_time;
            } elseif ($time > strtotime(date("Y-m-d 12:00:00")) && $time < strtotime(date("Y-m-d 14:00:00"))) {
                $last_processing_time = date('Y-m-d 16:00:00');
                return $last_processing_time;
            } elseif ($time >= strtotime(date("Y-m-d 14:00:00")) && $time <= strtotime(date("Y-m-d 16:00:00"))) {
                $last_processing_time = date('Y-m-d H:s:i', $time + $addtime);
                return $last_processing_time;
            } elseif ($time > strtotime(date("Y-m-d 16:00:00")) && $time <= strtotime(date("Y-m-d 18:00:00"))) {
                //时间差
                $strtime = strtotime(date("Y-m-d 18:00:00")) - $time;
                $last_time = strtotime(date('Y-m-d 08:00:00', strtotime("+1 days")));
                $last_processing_time = date('Y-m-d H:s:i', $strtime + $last_time);
                return $last_processing_time;
            } elseif ($time > strtotime(date("Y-m-d 18:00:00")) && $time < strtotime(date("Y-m-d 23:59:59"))) {
                $last_processing_time = date('Y-m-d 10:00:00', strtotime("+1 days"));
                return $last_processing_time;
            } elseif ($time > strtotime(date("Y-m-d 00:00:00")) && $time < strtotime(date("Y-m-d 08:00:00"))) {
                $last_processing_time = date('Y-m-d 10:00:00');
                return $last_processing_time;
            }
        } elseif ($state == 1) {
            //加急1个小时
            //1个小时时间戳
            $addtime = 3600;
            $atime = 7200;
            if ($time >= strtotime(date("Y-m-d 08:00:00")) && $time <= strtotime(date("Y-m-d 11:00:00"))) {
                $last_processing_time = date('Y-m-d H:s:i', $time + $addtime);
                return $last_processing_time;
            } elseif ($time > strtotime(date("Y-m-d 11:00:00")) && $time <= strtotime(date("Y-m-d 12:00:00"))) {
                $last_processing_time = date('Y-m-d H:s:i', $time + $atime + $addtime);
                return $last_processing_time;
            } elseif ($time > strtotime(date("Y-m-d 12:00:00")) && $time < strtotime(date("Y-m-d 14:00:00"))) {
                $last_processing_time = date('Y-m-d 13:00:00');
                return $last_processing_time;
            } elseif ($time >= strtotime(date("Y-m-d 14:00:00")) && $time <= strtotime(date("Y-m-d 17:00:00"))) {
                $last_processing_time = date('Y-m-d H:s:i', $time + $addtime);
                return $last_processing_time;
            } elseif ($time > strtotime(date("Y-m-d 17:00:00")) && $time <= strtotime(date("Y-m-d 18:00:00"))) {
                //时间差
                $strtime = strtotime(date("Y-m-d 18:00:00")) - $time;
                $last_time = strtotime(date('Y-m-d 08:00:00', strtotime("+1 days")));
                $last_processing_time = date('Y-m-d H:s:i', $strtime + $last_time);
                return $last_processing_time;
            } elseif ($time > strtotime(date("Y-m-d 18:00:00")) && $time < strtotime(date("Y-m-d 23:59:59"))) {
                $last_processing_time = date('Y-m-d 09:00:00', strtotime("+1 days"));
                return $last_processing_time;
            } elseif ($time > strtotime(date("Y-m-d 00:00:00")) && $time < strtotime(date("Y-m-d 08:00:00"))) {
                $last_processing_time = date('Y-m-d 09:00:00');
                return $last_processing_time;
            }
        }
    }

    /*
     * 根据审核状态判断
     */

    public static function getstatus($status) {
        switch ($status) {
            case -2:
                return "删除";
                break;
            case -1:
                return "<span style='color:red'>审核不通过</span>";
                break;
            case 0:
                return "<span style='color:#0cf149'>待审核</span>";
                break;
            case 1:
                return "<span style='color:#d1f12c'>推送成功待仓库处理</span>";
                break;
            case 2:
                return "<span style='color:#8ea51b'>推送失败</span>";
                break;
            case 3:
                return "<span style='color:#cbf10f'>仓库处理完成待确认</span>";
                break;
            case 4:
                return "<span style='color:#8ea51b'>重新推送失败待重新推送</span>";
                break;
            case 5:
                return "<span style='color:#8ea51b'>重新推送成功待仓库处理</span>";
                break;
            case 6:
                return "<span style='color:#abcc0af0'>已完成</span>";
                break;
        }
    }

    /*     * *
     * 获取数据客诉信息
     * * */

    public static function getComplaindata($complaint_order) {
        $query = self::find();
        $data = $query->joinWith('complian')
                ->where(['complaint_order' => $complaint_order])->asArray()
                ->one();
        return $data;
    }

    public static function getComplaindatas($complaint_order) {
        $query = self::find();
        $data = $query->joinWith('complian')
                ->where(['complaint_order' => $complaint_order])
                ->one();
        return $data;
    }

    public static function getreason($platform) {

        switch ($platform) {
            case Platform::PLATFORM_CODE_ALI:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/aliexpress_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_EB:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/ebay_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_WISH:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/wish_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_AMAZON:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/amazon_reason_code.php';
                break;
            case Platform::PLATFORM_CODE_SHOPEE:
                $reasonCodeList = include \Yii::getAlias('@app') . '/config/shopee_reason_code.php';
                break;
            default:
                $reasonCodeList = array();
        }

        return $reasonCodeList;
    }

    public static function curlPost($url, $post_data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        //返回获得的数据
        return $output;
    }

    /*
     * 删除操作
     */

    public static function getdelete($complaint_order) {
        $complain = self::find()->select('id')->where(['complaint_order' => $complaint_order])->asArray()->one();
        $connection = Yii::$app->db->beginTransaction();
        try {
            self::deleteAll(['complaint_order' => $complaint_order]);
            ComplaintdetailModel::deleteAll(['complaint_order_id' => $complain['id']]);
            $connection->commit();
            return json_encode(['state' => 1, 'msg' => '删除成功']);
        } catch (Exception $exc) {
            $connection->rollBack();
            return json_encode(['state' => 0, 'msg' => '删除失败']);
        }
    }

    /**
     * 返回两个时间的相距时间，*年*月*日*时*分*秒
     * @param int $one_time 时间一
     * @param int $two_time 时间二
     * @param int $return_type 默认值为0，0/不为0则拼接返回，1/*秒，2/*分*秒，3/*时*分*秒/，4/*日*时*分*秒，5/*月*日*时*分*秒，6/*年*月*日*时*分*秒
     * @param array $format_array 格式化字符，例，array('年', '月', '日', '时', '分', '秒')
     * @return String or false
     */
    public static function getRemainderTime($one_time, $two_time, $return_type = 0, $format_array = array('年', '月', '日', '时', '分', '秒')) {
        if ($return_type < 0 || $return_type > 6) {
            return false;
        }
        if (!(is_int($one_time) && is_int($two_time))) {
            return false;
        }
        $remainder_seconds = abs($one_time - $two_time);
        //年
        $years = 0;
        if (($return_type == 0 || $return_type == 6) && $remainder_seconds - 31536000 > 0) {
            $years = floor($remainder_seconds / (31536000));
        }
        //月
        $monthes = 0;
        if (($return_type == 0 || $return_type >= 5) && $remainder_seconds - $years * 31536000 - 2592000 > 0) {
            $monthes = floor(($remainder_seconds - $years * 31536000) / (2592000));
        }
        //日
        $days = 0;
        if (($return_type == 0 || $return_type >= 4) && $remainder_seconds - $years * 31536000 - $monthes * 2592000 - 86400 > 0) {
            $days = floor(($remainder_seconds - $years * 31536000 - $monthes * 2592000) / (86400));
        }
        //时
        $hours = 0;
        if (($return_type == 0 || $return_type >= 3) && $remainder_seconds - $years * 31536000 - $monthes * 2592000 - $days * 86400 - 3600 > 0) {
            $hours = floor(($remainder_seconds - $years * 31536000 - $monthes * 2592000 - $days * 86400) / 3600);
        }
        //分
        $minutes = 0;
        if (($return_type == 0 || $return_type >= 2) && $remainder_seconds - $years * 31536000 - $monthes * 2592000 - $days * 86400 - $hours * 3600 - 60 > 0) {
            $minutes = floor(($remainder_seconds - $years * 31536000 - $monthes * 2592000 - $days * 86400 - $hours * 3600) / 60);
        }
        //秒
        $seconds = $remainder_seconds - $years * 31536000 - $monthes * 2592000 - $days * 86400 - $hours * 3600 - $minutes * 60;
        $return = false;
        switch ($return_type) {
            case 0:
                if ($years > 0) {
                    $return = $years . $format_array[0] . $monthes . $format_array[1] . $days . $format_array[2] . $hours . $format_array[3] . $minutes . $format_array[4] . $seconds . $format_array[5];
                } else if ($monthes > 0) {
                    $return = $monthes . $format_array[1] . $days . $format_array[2] . $hours . $format_array[3] . $minutes . $format_array[4] . $seconds . $format_array[5];
                } else if ($days > 0) {
                    $return = $days . $format_array[2] . $hours . $format_array[3] . $minutes . $format_array[4] . $seconds . $format_array[5];
                } else if ($hours > 0) {
                    $return = $hours . $format_array[3] . $minutes . $format_array[4] . $seconds . $format_array[5];
                } else if ($minutes > 0) {
                    $return = $minutes . $format_array[4] . $seconds . $format_array[5];
                } else {
                    $return = $seconds . $format_array[5];
                }
                break;
            case 1:
                $return = $seconds . $format_array[5];
                break;
            case 2:
                $return = $minutes . $format_array[4] . $seconds . $format_array[5];
                break;
            case 3:
                $return = $hours . $format_array[3] . $minutes . $format_array[4] . $seconds . $format_array[5];
                break;
            case 4:
                $return = $days . $format_array[2] . $hours . $format_array[3] . $minutes . $format_array[4] . $seconds . $format_array[5];
                break;
            case 5:
                $return = $monthes . $format_array[1] . $days . $format_array[2] . $hours . $format_array[3] . $minutes . $format_array[4] . $seconds . $format_array[5];
                break;
            case 6:
                $return = $years . $format_array[0] . $monthes . $format_array[1] . $days . $format_array[2] . $hours . $format_array[3] . $minutes . $format_array[4] . $seconds . $format_array[5];
                break;
            default:
                $return = false;
        }
        return $return;
    }

    /*     * *
     * 获取客诉单对应的仓库接口地址
     * $type 1.推送客诉申请到wms 2.加急申请推到wms 3.确认结果推到wms
     * * */

    public static function getcomplainWarehouse($warehouse_id, $type = '') {
        $host_url = include Yii::getAlias('@app') . '/config/vms_api.php';
        //东莞仓库规则
        $dongguan = ['SZ_AA', 'ZDXNC', 'FBA_SZ_AA', 'HW_XNC', 'TS', 'LAZADA-XNC','HW_XNC'];
        //美国仓库
        $usd = ['United States-yb'];
        //获取仓库code
        $code = Warehouse::find()->select("warehouse_code")->where(['id' => $warehouse_id])->one();
        $url = "";
        //获取不同的接口
        switch ($type) {
            case 1:
                //东莞仓
                if (in_array($code->warehouse_code, $dongguan)) {
                   // $url = "http://1m7597h064.iok.la:10021/Api/Order/CustomerComplain/add"; //测试环境
                     $url=$host_url['dongguan']."/Api/Order/CustomerComplain/add";//正式环境
                }
                //美国仓
                if (in_array($code->warehouse_code, $usd)) {
                   // $url = "http://47.251.52.109/Api/Order/CustomerComplain/add"; //测试环境
                     $url = $host_url['us']."/Api/Order/CustomerComplain/add";//正式环境
                }
                break;
            case 2:
                //东莞仓
                if (in_array($code->warehouse_code, $dongguan)) {
                    //$url = "http://1m7597h064.iok.la:10021/Api/Order/CustomerComplain/setOverTime"; //测试环境
                      $url = $host_url['dongguan']."/Api/Order/CustomerComplain/setOverTime";//正式环境
                }
                //美国仓
                if (in_array($code->warehouse_code, $usd)) {
                  //  $url = "http://47.251.52.109/Api/Order/CustomerComplain/setOverTime"; //测试环境
                     $url =  $url = $host_url['us']."/Api/Order/CustomerComplain/setOverTime";//正式环境
                }
                break;
            case 3:
                //东莞仓
                if (in_array($code->warehouse_code, $dongguan)) {
                   // $url = "http://1m7597h064.iok.la:10021/Api/Order/CustomerComplain/confirmResult"; //测试环境
                     $url = $host_url['dongguan']."/Api/Order/CustomerComplain/confirmResult";//正式环境
                }
                //美国仓
                if (in_array($code->warehouse_code, $usd)) {
                   // $url = "http://47.251.52.109/Api/Order/CustomerComplain/confirmResult"; //测试环境
                     $url =  $url = $host_url['us']."/Api/Order/CustomerComplain/confirmResult";//正式环境
                }

                break;
        }
        return $url;
    }

    /*     * *
     * 判断可建的客诉单
     * * */

    public static function getcomplainwns($warehouse_id) {
        //东莞仓库规则
        $dongguan = ['SZ_AA', 'ZDXNC', 'FBA_SZ_AA', 'HW_XNC', 'TS', 'LAZADA-XNC','HW_XNC'];
        //美国仓库
        $usd = ['United States-yb'];
        //海外虚拟仓
        $fictitious = [];
        if (empty($warehouse_id)) {
            return false;
        }
        //获取仓库code
        $code = Warehouse::find()->select("warehouse_code")->where(['id' => $warehouse_id])->one();

        if (in_array($code->warehouse_code, $dongguan)) {
            //判断是否东莞仓
            return true;
        } elseif (in_array($code->warehouse_code, $usd)) {
            //判断是否美国仓
            return true;
        } elseif (in_array($code->warehouse_code, $fictitious)) {
            //判断是否是海外虚拟仓
            return true;
        } else {
            return false;
        }
    }

}

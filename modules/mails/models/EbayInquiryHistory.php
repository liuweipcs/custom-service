<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/11 0011
 * Time: 下午 8:43
 */

namespace app\modules\mails\models;

use app\components\GoogleTranslation;

class EbayInquiryHistory extends MailsModel {

    public static $actorMap = [1 => 'BUYER', 2 => 'CSR', 3 => 'SELLER', 4 => 'SYSTEM', 5 => 'UNKNOWN'];

    public static function tableName() {
        return '{{%ebay_inquiry_history}}';
    }

    public static function actorMap($value = null) {
        if (is_numeric($value) && isset(EbayInquiry::$initiatorMap[$value]))
            return EbayInquiry::$initiatorMap[$value];
        else if (is_string($value)) {
            $key = array_search($value, EbayInquiry::$initiatorMap);
            if ($key !== false)
                return $key;
        }
        return EbayInquiry::$initiatorMap;
    }

}

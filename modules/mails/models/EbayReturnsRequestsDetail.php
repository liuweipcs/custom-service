<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/15 0015
 * Time: 下午 4:05
 */

namespace app\modules\mails\models;


class EbayReturnsRequestsDetail extends MailsModel
{
    public static function tableName()
    {
        return '{{%ebay_returns_requests_detail}}';
    }
    
    
    
    /**
     * 根据returnID获取最早一条留言信箱
     * @param type $returnId
     * @return arr
     * @author allen <2018-10-27>
     */
    public static function getReturnNotes($returnId){
        return self::find()->select('notes')->where(['return_id' => $returnId])->asArray()->one();
    }
}
<?php

namespace app\modules\mails\models;
use app\common\VHelper;

/**
 * This is the model class for table "{{%aliexpress_relation_list}}".
 *
 * @property integer $id
 * @property string $msg_sources
 * @property integer $unread_count
 * @property string $channel_id
 * @property string $last_message_id
 * @property integer $read_stat
 * @property string $ast_message_content
 * @property integer $ast_message_is_own
 * @property string $child_name
 * @property string $message_time
 * @property string $child_id
 * @property string $other_name
 * @property string $other_login_id
 * @property integer $deal_stat
 * @property integer $rank
 */
    class AliexpressExpression extends MailsModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%aliexpress_expression}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
        ];
    }
    public function queryLabel($symbol = null){
       return $this->find()->where(['symbol'=>$symbol])->select('expression_url')->asArray()->one();
    }
        public function queryLabelSymbol($label = null){
            return $this->find()->where(['label'=>$label])->select('symbol')->asArray()->one();
        }
    public function getList(){
        return $this->find()->select('expression_url,label')->asArray()->all();
    }

    /*
     *表情替换
     */
    public function queryExpression($content = null){
         $data = [];
         $str = '/(\/:[0-9]{0,3})/';
        /*匹配内容里所有的标签字符串*/
         preg_match_all($str,$content,$data);
        if(!empty($data[0])){
            /*同个表情只留下一个用以匹配*/
            $arr = array_unique($data[0]);
            if(!empty($arr)){
                foreach ($arr as $value){
                    $row = $this->queryLabel($value);
                    if(!empty($row)){
                        $expression_url = '<img src="'.$row['expression_url'].'" />';
                        $content = str_replace($value,$expression_url,$content);
                    }
                }
            }
        }
        return $content;
    }
    /*替换*/
    public function replyContentReplace($content = null){
        $data = [];
        $str = '/\[.*?\]/';
        /*匹配内容里所有的标签字符串*/
        preg_match_all($str,$content,$data);
        if(!empty($data[0])){
            $arr = array_unique($data[0]);
            if(!empty($arr)){
                foreach ($arr as $value){
                    $row = $this->queryLabelSymbol($value);
                    if(!empty($row)){
                        $content = str_replace($value,$row['symbol'],$content);
                    }
                }
            }
        }
        return $content;
    }
}

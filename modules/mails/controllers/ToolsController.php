<?php

namespace app\modules\mails\controllers;

use app\components\Controller;
use app\modules\mails\models\EbayInbox;
use app\components\GoogleTranslation;

class ToolsController extends Controller {

    public function actionTranslate() {
        $arr = array('<div id="UserInputtedText">' => "");
        $query =  EbayInbox::find();
        $res = $query->where('new_message <> "" AND language_code IS NULL')->all();
//        echo $query->createCommand()->getRawSql();die;
        if($res){
            foreach ($res as $value) {
                $message =  strtr($value->new_message,$arr);
                $message = rtrim($message,'</div>');
                $afterTranslationJson = GoogleTranslation::translate($message,'auto','en');
                $afterTranslation = json_decode($afterTranslationJson);
                if(is_array($afterTranslation) && !empty($afterTranslation)){
                    $new_message_en = isset($afterTranslation[0]) ? $afterTranslation[0] : "";
                    $language_code = isset($afterTranslation[1]) ? $afterTranslation[1] : "";
                    
                    $value-> new_message_en = $new_message_en;
                    $value->language_code = $language_code;
                    $flag = $value->save();
                    if($flag === FALSE){
                        echo $value->message_id.'翻译失败!<br/>';
                    }else{
                        echo $value->message_id.'翻译成功!<br/>';
                    }
                }
            }
        }
    }

}

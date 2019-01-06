<?php
/**
 * @desc 邮件标签映射表
 * @author Fun
 */
namespace app\modules\mails\models;
use app\modules\systems\models\Tag;
use app\modules\accounts\models\Platform;

class MailSubjectTag extends MailsModel
{   
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%mail_subject_tag}}';
    }
    
    /**
     * @desc 保存主题标签关联
     * @param unknown $platformCode
     * @param unknown $subject_id
     * @param unknown $tagIds
     */
    public static function saveMailTags($platformCode, $subject_id, $tagIds)
    {
        $dbCommand = self::getDb()->createCommand();
        $columns = ['platform_code', 'tag_id', 'subject_id'];
        $rows = [];
        foreach ($tagIds as $tagId)
            $rows[] = [$platformCode, $tagId, $subject_id];
        return $dbCommand->batchInsert(self::tableName(), $columns, $rows)
                    ->execute();
    }
    
    /**
     * @desc 删除主题标签关联$subject_id
     * @param unknown $platformCode
     * @param unknown $subject_id
     * @return \yii\db\int
     */
    public static function deleteMialTags($platformCode, $subject_id)
    {
        return self::deleteAll('platform_code = :platform_code and subject_id = :subject_id', [
            'platform_code' => $platformCode,
            'subject_id' => $subject_id,
        ]);
    }

    /**
     * 针对自动移动邮件到指定标签功能批量或者单条的维护主题标签关联
     * @param string $platform_code 平台code
     * @param array  $tag_id 标签id
     * @param array  subject_id 主题id
     */
    public static function batch_save_mail_tags($platform_code,$tag_id,$subject_id)
    {   
        foreach ($subject_id as $key_subject => $value_subject)
        {
            foreach ($tag_id as $key_tag => $value_tag) 
            {   
                $exist = self::findOne(['platform_code'=>$platform_code,'subject_id'=>$value_subject,'tag_id'=>$value_tag]);
                if ($exist !== null) {
                    continue;
                }

                $model = new self();
                $model->platform_code = $platform_code;
                $model->subject_id = $value_subject;
                $model->tag_id = $value_tag;

                //存取数据
                if (!$model->save()) {
                    return [false,current(current($model->getErrors()))];
                }
            }
        }

        return [true,'操作成功'];
    }
    /**
     * 获取指定平台和指定主题的标签id
     */
    public static function get_tag_ids_by_platformcode_and_subject($platform_code,$subject_id)
    {
        $query = new \yii\db\Query();
        $query ->from(self::tableName())->select('tag_id');

        //查找有效状态的标签
        if (is_array($subject_id)) {
            $query->andWhere('platform_code = :platform_code', [':platform_code'=>$platform_code]);
            $query->andWhere(['in', 'subject_id', $subject_id]);
        } else {
             $query->andWhere('platform_code = :platform_code and subject_id = :subject_id', [
                 ':platform_code'=>$platform_code,
                 ':subject_id' => (int)$subject_id
             ]);
        }
        
        //获取数据
        $data = $query->all();
        
        if (empty($data)) {
            return [];
        }

        $tag_ids = [];

        foreach ($data as $key => $value) {
            $tag_ids[] = $value['tag_id'];
        }

        return array_unique($tag_ids);
    }

    /**
     * 获取指定平台存在的标签id
     */
    public static function get_tag_by_platformcode_and_subject($platform_code,$subject_id,$tag_id)
    {
        $result = self::findOne(['platform_code'=>$platform_code,'subject_id'=>$subject_id,'tag_id'=>$tag_id]);

        if(!$result){
            return false;
        }
        return $result;
    }

    /**
     * 获取指定平台和主题的所有的标签
     */
    public static function get_tags_by_platformcode_and_subject($platform_code,$subject_id)
    {
        $tag_ids = self::get_tag_ids_by_platformcode_and_subject($platform_code,$subject_id);
        
        if (empty($tag_ids)) {
            return [];
        }

        //根据标签id获取标签名称数据
        $tag_data = Tag::getTagAsArray($platform_code,$tag_ids);

        return $tag_data;
    }

    /**
     * 移除指定平台和指定主题的的标签
     */
    public static function delete_mail_tag($platform_code, $subject_id, $tag_ids)
    { 
        foreach ($tag_ids as $key => $value) {
            $result = self::deleteAll('platform_code = :platform_code and subject_id = :subject_id and tag_id = :tag_id',[
                ':platform_code' => $platform_code,
                ':subject_id' => $subject_id,
                ':tag_id' => $value,
            ]);

            if (!$result) {
                return false;
            }
        }

        return true;
    }
}

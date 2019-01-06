<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/2 0002
 * Time: 下午 5:57
 */

namespace app\models;


use yii\base\Model;

class UploadForm extends Model
{
    public $imageFile;

    protected $extensions = 'gif,jpg,png,jpeg,tif,bmp';
    protected $filePath;

    public function setExtensions($string)
    {
        if (empty($string) || !is_string($string))
            return false;
        else {
            $this->extensions = $string;
            return true;
        }
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    public function rules()
    {
        return [
            [['imageFile'], 'file', 'skipOnEmpty' => false, 'extensions' => $this->extensions, 'maxSize' => 1024 * 1024],
        ];
    }

    public function upload($path = null, $hasNamed = false)
    {
        if ($this->validate()) {
            if (empty($path)) {
                $hasNamed = false;
                $path = 'uploads/' . date('Y') . '/' . date('m') . '/' . date('d') . '/';
                if (!file_exists($path)) {
                    mkdir($path, 0760, true);
                }
            }
            $base_name = date('His') . rand(1000, 9999);
            $this->filePath = $hasNamed ? $path : $path . time() . '_' . $base_name . '.' . $this->imageFile->extension;
            $this->imageFile->saveAs($this->filePath);
            return true;
        } else {
            return false;
        }
    }
}
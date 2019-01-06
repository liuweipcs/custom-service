<?php
/**
 * @desc bootstrap table asset class
 * @author Fun
 */
namespace app\components;
use yii\web\AssetBundle;
class GridViewAsset extends AssetBundle
{
    /**
     * @desc　source path
     * @var unknown
     */
    public $sourcePath = '@bower/bootstrap-table/dist';
    
    /**
     * @desc css file list
     * @var unknown
     */
    public $css = [
        'bootstrap-table.min.css',
    ];
    
    /**
     * @desc js file list
     * @var unknown
     */
    public $js = [
        'bootstrap-table.js',
        'locale/bootstrap-table-zh-CN.min.js'
    ];
}
<?php
function findClass($class,$type = 0,$isExit = 1){
    echo '<pre>';
    if($type == 0){
        var_dump(ReflectionClass::export($class));
    }else{
        var_dump($class);
    }
    if($isExit == 1)
        exit;
}
// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'prod');

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

//$config = require(__DIR__ . '/../config/web.php');
error_reporting(E_ALL & ~E_NOTICE);
require_once (__DIR__ . '/../config/Configuration.php');
$config = \app\config\Configuration::getConfigs(YII_ENV, false);
define('DS', DIRECTORY_SEPARATOR);
define('CONF_PATH', dirname(__FILE__) . DS . '..' . DS . 'config' . DS);
(new yii\web\Application($config))->run();

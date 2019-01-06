<?php

$params = require(__DIR__ . '/params.php');
//$db = require(__DIR__ . '/db.php');

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'memcache' => [
            'class' => 'app\components\Memcache',
            'defaultDuration' => 3600,
            'servers' => [
                [
                    'host' => '192.168.71.210',
                    'port' => 11211,
                    'weight' => 60,
                ],
            ]
        ],        
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=192.168.71.175;port=3306;dbname=' . DB_PREFIX . 'crm',
            'emulatePrepare' => false,
            'enableSchemaCache' => false,
            'schemaCacheDuration' => 3600,
            'tablePrefix' => DB_TABLE_PREFIX,
            'username' => 'crmuser',
            'password' => 'crm@123456',
            'charset' => 'utf8mb4',
        ],
        'db_system' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=192.168.71.174;port=3306;dbname=' . ERP_DB_PREFIX . 'system',
            'emulatePrepare' => false,
            'enableSchemaCache' => false,
            'schemaCacheDuration' => 3600,
            'tablePrefix' => ERP_DB_TABLE_PREFIX,
            'username' => 'crmerpuser',
            'password' => 'crm@123456',
            'charset' => 'utf8',
        ],
        'db_order' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=192.168.71.174;port=3306;dbname=' . ERP_DB_PREFIX . 'order',
            'emulatePrepare' => false,
            'enableSchemaCache' => false,
            'schemaCacheDuration' => 3600,
            'tablePrefix' => ERP_DB_TABLE_PREFIX,
            'username' => 'crmerpuser',
            'password' => 'crm@123456',
            'charset' => 'utf8',
        ],
        'db_product' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=192.168.71.174;port=3306;dbname=' . ERP_DB_PREFIX . 'product',
            'emulatePrepare' => false,
            'enableSchemaCache' => false,
            'schemaCacheDuration' => 3600,
            'tablePrefix' => ERP_DB_TABLE_PREFIX,
            'username' => 'crmerpuser',
            'password' => 'crm@123456',
            'charset' => 'utf8',
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'mongodb' => include 'mongodb.php',
    ],
    'params' => $params,
    'timeZone' => 'Asia/Shanghai',
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;

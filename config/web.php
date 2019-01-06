<?php

$params = require(__DIR__ . '/params.php');
Yii::$classMap['simple_html_dom'] = '@app/libs/simple_html_dom.php';

$config = [
    'id' => 'basic',
	'aliases'=>[
		'@wish'=>'@app/modules/services/modules/wish',
	],
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        'log',
        'bootstrap' => [
            'class' => 'app\components\Bootstrap',
        ]
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '40dJSDgb06jr099tXZcpSXZFboxzCyN8',
            'enableCsrfValidation' => false,
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'memcache' => [
            'class' => 'app\components\Memcache',
            'defaultDuration' => 3600,
            'servers' => [
                [
                    'host' => '10.29.70.87',
                    'port' => 11211,
                    'weight' => 60,
                ],
            ]
        ],
        'user' => [
            'identityClass' => 'app\modules\users\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        //'db' => require(__DIR__ . '/db.php'),
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                "<controller:\w+>/<action:\w+>/<id:\d+>"=>"<controller>/<action>",
                "<controller:\w+>/<action:\w+>"=>"<controller>/<action>"
			],
        ],
        'assetManager' => [
            'bundles' => [
                'yii\web\JqueryAsset' => false
            ]
        ],
        'view' => [
            'class' => 'app\components\View',
        ],
        'i18n' => [
            'translations' => [
                '*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages'
                ],
            ]
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'mongodb' => include 'mongodb.php',      
    ],
    'params' => $params,
    'modules' => [
        'users' => [
            'class' => 'app\modules\users\UsersModule'
        ],
        'systems' => [
            'class' => 'app\modules\systems\SystemsModule'
        ],
        'aftersales' => [
            'class' => 'app\modules\aftersales\AfterSalesModule'
        ],
        'blacklist' => [
            'class' => 'app\modules\blacklist\BlacklistModule'
        ],
        'services' => [
            'class' => 'app\modules\services\ServicesModule',
            'modules' => [
                'ebay' => [
                    'class' => 'app\modules\services\modules\ebay\EbayModule',
                ],
				'wish' => [
                    //'class' => 'app\modules\services\modules\wish\WishModule',
					'class' => 'wish\WishModule',
                ],
                'aliexpress'=>[
                    'class' => 'app\modules\services\modules\aliexpress\AliexpressModule',
                ],
                'amazon'=>[
                    'class' => 'app\modules\services\modules\amazon\AmazonModule',
                ],
                'api' => [
                    'class' => 'app\modules\services\modules\api\ApiModule'
                ],
                'paypal' => [
                    'class' => 'app\modules\services\modules\paypal\PaypalModule',
                ],
                'account' => [
                    'class' => 'app\modules\services\modules\account\AccountModule'
                ],
                'order' => [
                    'class' => 'app\modules\services\modules\order\OrderModule'
                ],
		'walmart' => [
                    'class' => 'app\modules\services\modules\walmart\WalmartModule'
                ],
		'shopee' => [
                    'class' => 'app\modules\services\modules\shopee\ShopeeModule'
                ],
                'cdiscount' => [
                    'class' => 'app\modules\services\modules\cdiscount\CdiscountModule'
                ],
                'jumia' => [
                    'class' => 'app\modules\services\modules\jumia\JumiaModule'
                ],
                'lazada' => [
                    'class' => 'app\modules\services\modules\lazada\LazadaModule'
                ],
                'mall' => [
                    'class' => 'app\modules\services\modules\mall\MallModule'
                ],
		'joom' => [
                    'class' => 'app\modules\services\modules\joom\JoomModule'
                ],
		'user' => [
                    'class' => 'app\modules\services\modules\user\UserModule'
                ],
               'automail' => [
                    'class' => 'app\modules\services\modules\automail\AutomailModule'
                ],
		'aftersales' => [
        	    'class' => 'app\modules\services\modules\aftersales\AftersalesModule'
		],
		'gbc' => [
                    'class' => 'app\modules\services\modules\gbc\GbcModule'
                ],
            ],
        ],
        'accounts' => 'app\modules\accounts\AccountsModule',
        'mails' => 'app\modules\mails\MailsModule',
        'admin' => [
            'class' => 'mdm\admin\Module',
            'layout' => 'left_menu'
        ],
        'products' => 'app\modules\products\ProductsModule',
        'orders' => 'app\modules\orders\OrdersModule',
	'reports' => 'app\modules\reports\ReportsModule',
	'customer' => 'app\modules\customer\CustomerModule',
    ],
    'defaultRoute' => 'systems/index/index',
    'layout' => '@app/views/layouts/layout',
    'language' => 'zh-CN',
    'timeZone' => 'Asia/Shanghai',
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    //$config['bootstrap'][] = 'debug';
    //$config['modules']['debug'] = [
        //'class' => 'yii\debug\Module',
         //uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    //];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];
}

return $config;

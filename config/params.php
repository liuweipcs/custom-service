<?php
const DB_PREFIX = 'ueb_';     //数据库和表前缀
const DB_TABLE_PREFIX = 'ueb_';     //数据库和表前缀
const ERP_DB_PREFIX = 'ueb_';     //ERP数据库和表前缀
const ERP_DB_TABLE_PREFIX = 'ueb_';     //ERP数据库和表前缀
return [
    'UE_VideoUrlPrefix' => 'http://192.168.71.210:30081', //视频前缀
    'UE_FileUrlPrefix' => 'http://192.168.71.210:30081', //上传文件前缀
    'UE_ImageUrlPrefix' => 'http://192.168.71.210:30081', //图片前缀
    'defaultPageSize' => 10,
    'erp_order_url' => 'http://192.168.71.210:30080',
    'adminEmail' => 'admin@example.com',
    'authIgnores' => [
        'modules' => ['services', 'api'], //忽略认证的模块
        'routes' => ['users/user/login', 'users/user/logout'], //忽略认证的路由
    ],
    // 图片服务器的域名设置，拼接保存在数据库中的相对地址，可通过web进行展示
    'domain' => '/',
    'imageUploadRelativePath' => 'complaint/sku/',// 图片默认上传的目录
    'imageUploadSuccessPath'=>'complaint/sku/', //图片上传成功后，路径前缀
    'webuploader' => [
        // 后端处理图片的地址，value 是相对的地址
     //   'uploadUrl' => '/aftersales/complaint/getUpload',
        // 多文件分隔符
        'delimiter' => ',',
        // 基本配置
        'baseConfig' => [
            'defaultImage' => 'http://http://192.168.71.210:30081/it/u=2056478505,162569476&fm=26&gp=0.jpg',
            'disableGlobalDnd' => true,
            'accept' => [
                'title' => 'Images',
                'extensions' => 'gif,jpg,jpeg,bmp,png',
                'mimeTypes' => 'image/*',
            ],
            'pick' => [
                'multiple' => false,
            ],
        ],
    ],
];

<?php
return [
    // 默认平台
    'default' => 'taobao',
    
    // 平台配置
    'platforms' => [
        'youzan' => [
            'class' => \royfee\xshop\Platforms\Youzan\Youzan::class,
            'config' => [
                'client_id' => 'youzan_cient_id',
                'client_secret' => '5457418745151',
                'debug' => true, // 调试模式
                'kdt_id' => '123', // 店铺ID(仅自用模式下填写)
                'exception_as_array' => true, // 错误返回数组还是异常
                'version' => '4.0.0',
                'log' => [
                    'name' => 'youzan',
                    'file' => __DIR__.'/youzan.log',
                    'level'      => 'debug',
                    'permission' => 0777,
                ]
            ]
        ],
        'taobao' => [
            'class' => \royfee\xshop\Platforms\Taobao\Taobao::class,
            'config' => [
                'app_key' => 'your_taobao_app_key',
                'app_secret' => 'your_taobao_app_secret',
                'access_token' => 'your_taobao_access_token',
                'gateway' => 'https://eco.taobao.com/router/rest',
                'format' => 'json',
                'version' => '2.0',
                'sign_method' => 'md5',
                'timeout' => 30,
            ]
        ],
        'yueyan' =>[
            'class' => \royfee\xshop\Platforms\Yueyan\Yueyan::class,
            'config' => [
                'app_id' => 'dw4lKzsMotKxOpHjTd',
                'app_secret' => 'otaxrS49J1VPjEBqjYfLtQP2jed0ITUc',
                'auth_code' => 'x7Ot2OvoMYE5rMZS0C9KdRRJB4LRanA4',
                'log' => [
                    'name' => 'yueyan',
                    'file' => __DIR__.'/yueyan.log',
                    'level'      => 'debug',
                    'permission' => 0777,
                ]
            ]
        ]
        /*
        'jd' => [
            'class' => \royfee\xshop\Platforms\JD\JD::class,
            'config' => [
                'app_key' => 'your_jd_app_key',
                'app_secret' => 'your_jd_app_secret',
                'access_token' => 'your_jd_access_token',
                'gateway' => 'https://api.jd.com/routerjson',
                'format' => 'json',
                'version' => '2.0',
                'timeout' => 30,
            ]
        ],
        'pdd' => [
            'class' => \royfee\xshop\Platforms\Pinduoduo\Pinduoduo::class,
            'config' => [
                'client_id' => 'your_pdd_client_id',
                'client_secret' => 'your_pdd_client_secret',
                'access_token' => 'your_pdd_access_token',
                'gateway' => 'https://gw-api.pinduoduo.com/api/router',
                'format' => 'json',
                'version' => 'V1',
                'timeout' => 30,
            ]
        ]
            */
    ],

    // 全局配置
    'global' => [
        'log_enabled' => true,
        'log_path' => __DIR__.'/xshop.log',
        'retry_times' => 3,
        'retry_sleep' => 100, // 毫秒
    ]
];
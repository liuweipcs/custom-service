<?php
/**
 * @desc 配置解析类
 * @author Fun
 */
namespace app\config;
class Configuration
{
    const APP_EVN_TEST = 'test';            //测试环境
    const APP_EVN_DEV = 'dev';      //开发环境
    const APP_EVN_PRO = 'prod';       //生成环境
    
    /**
     * @desc 获取配置信息
     * @param unknown $env 环境变量
     * @param boolean $readCache 是否读取缓存配置
     * @param string $configPath 配置文件mul
     * @return mixed
     */
    public static function getConfigs ($env, $readCache = false)
    {
        $configCacheFile = __DIR__ . '/config_' . $env . '-cache.php';
        if ($readCache && file_exists($configCacheFile))
        {
            $config = unserialize(file_get_contents($configCacheFile));
        }
        else
        {
            $commonConfig = self::getCommonConfigs($env);
            $dbConfig = self::getDbConfig($env);
            $cacheConfig = self::getCacheConfig($env);
            $config = array_merge_recursive($commonConfig, $dbConfig, $cacheConfig);
            file_put_contents($configCacheFile, serialize($config));
        }
        return $config;
    }
    
    /**
     * @desc 获取通用配置
     * @param string $env
     * @return multitype:
     */
    public static function getCommonConfigs($env = '')
    {
        $config = [];
        $file = __DIR__ . '/web.php';
        if (file_exists($file))
            $config = include $file;
        return $config;
    }
    
    /**
     * @desc 获取数据库配置
     * @param string $env
     * @return Ambigous <multitype:, multitype:string unknown >
     */
    public static function getDbConfig($env = '')
    {
        $config = [];
        $file = __DIR__ . '/db.php';
        if (file_exists($file)) 
        {
            $dbConfig = include $file;
            if (is_array($dbConfig) && isset($dbConfig[$env]))
            {
                foreach ($dbConfig[$env] as $serverKey => $servers)
                {
                    foreach ($servers['dbname'] as $dbKey => $dbName)
                    {
                        $config['components'][$dbKey] = [
                            'class' => $servers['class'],
                            'dsn' => $servers['driver'] . ':host=' . $servers['host'] . ';port=' . $servers['port'] . ';dbname=' . $dbName,
                            'emulatePrepare' => $servers['emulatePrepare'],
                            'enableSchemaCache' => $servers['enableSchemaCache'],
                            'schemaCacheDuration' => $servers['schemaCacheDuration'],
                            'tablePrefix' => $servers['tablePrefix'],
                            'username' => $servers['username'],
                            'password' => $servers['password'],
                            'charset' => $servers['charset'],
                        ];
                    }
                }
            }
        }
        return $config;
    }
    
    /**
     * @desc 获取缓存配置
     * @param string $env
     * @return multitype:
     */
    public static function getCacheConfig($env = '')
    {
        return [];
    }
}
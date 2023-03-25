<?php
/**
 * 缓存基类
 * 封装一些常用的方法，可以根据配置的驱动来自适应
 * 一些有区别的，还是要自己手动调用使用. 如memcache没有的数据类型: hash, list, set, zset
 *
 */
namespace library\Cache;

use library\Cache\Drivers\Redis;
use Psr\Log\LoggerInterface;

class Base {

    /**
     * 实例
     *
     * @var array
     */
    protected static $_instances = [];

    /**
     * 缓存配置
     *
     * @var array
     */
    protected static $_config = [];

    /**
     * 默认缓存配置的key
     * @var string
     */
    protected static $_default_cache_key = 'default';

    /**
     * 存储配置路径，在需要的时候调用闭包函数读取
     *
     * @var string
     */
//    protected static $_config_path;
    protected static $_config_path = CONFIG_PATH. 'cache_config.ini';

    /**
     * 读取配置的闭包函数
     *
     * @var callable
     */
    protected static $_get_config_callable;

    /**
     * @var LoggerInterface
     */
    protected static $_logger;

    /**
     * 初始化缓存配置，暂时记下来，真实需要用的时候才去执行获取配置
     *
     * @param string $config_path 配置路径，存起来，在需要使用的时候才读取
     * @param callable $callable 闭包函数，返回配置数组
     * @param LoggerInterface $logger
     */
    public static function init($config_path, callable $callable, LoggerInterface $logger) {
        self::$_config_path = $config_path;
//        self::$_get_config_callable = $callable;
        self::$_get_config_callable = 'callable_fuc';
        self::$_logger = $logger;
//        $reader = CBox::instance()->Zend_Config_Reader_Ini();
//        self::$_config = $reader->fromFile($config_path);
//        if (!is_array(self::$_config) || empty(self::$_config)) {
//            throw new \Exception('Please configure cache_config');
//        }

    }


    /**
     * 获取缓存实例
     *
     * @param string $cache_config_key 根据缓存配置key获取指定缓存配置 或 Redis Server地址(兼容已有使用方式. 已废弃)
     *
     * @return Redis|self
     *
     * @throws \Exception 只有配置错误的时候才会抛出异常，上层来决定怎么处理
     */
    public static function get_instance($cache_config_key='') {
        $cache_config_key = $cache_config_key ?: self::$_default_cache_key;
        if (empty(self::$_instances[$cache_config_key])) {
            self::$_instances[$cache_config_key] = self::factory($cache_config_key);
        }
        return self::$_instances[$cache_config_key];
    }

    /**
     * 初始化读取配置
     *
     * @return array|mixed
     */
    protected static function get_config() {
        if (!self::$_config) {
//            self::$_config = call_user_func_array(self::$_get_config_callable, [self::$_config_path]);
            self::$_config = call_user_func_array(function ($cache_config_path){
                $config = \Common::fromFile($cache_config_path);
                if (empty($config) || !is_array($config)) {
                    throw new \Exception('Please configure cache_config');
                }
                return $config;
            }, [self::$_config_path]);
        }
        return self::$_config;
    }

    /**
     * 连接缓存中间件
     *
     * @param string $cache_config_key 根据缓存配置key获取指定缓存配置
     *
     * @return null|Redis
     * @throws \Exception
     */
    protected static function factory($cache_config_key) {
        // 验证是否是ipv4地址, 如果是ipv4地址，就默认使用redis, 使用redis默认配置连接
//        if (self::verify_ipv4_is_valid($cache_config_key)) {
//            self::$_config[$cache_config_key] = [
//                'driver' => 'redis',
//                'host' => $cache_config_key,
//            ];
//        }
        // 初始化配置
        self::get_config();
        if (!isset(self::$_config[$cache_config_key]['driver'])) {
            throw new \Exception("Please configure cache '{$cache_config_key}.driver' in cache_config!", 500);
        }
        $instance = null;
        $driver = self::$_config[$cache_config_key]['driver'];
        switch($driver) {
            case 'redis':
//                Redis::set_logger(self::$_logger);
                $instance = new Redis(self::$_config[$cache_config_key], $cache_config_key);
                break;
//            case 'memcache':
//                $instance = null;
//                break;
            default:
                throw new \Exception("This drive '{$driver}' is not supported");
                break;
        }
        return $instance;
    }

    /**
     * 验证ipv4地址是否有效
     *
     * @param $ip
     *
     * @return bool
     */
    protected static function verify_ipv4_is_valid($ip) {
        return preg_match('/^(?:25[0-5]|2[0-4]\d|1\d{2}|[1-9]\d?)(?:\.(?:25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)){3}$/', $ip) ? true : false;
    }
}

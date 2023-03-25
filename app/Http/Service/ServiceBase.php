<?php
/**
 * Service 业务层基类: 相对具体的业务逻辑服务层。
 * 复用性较低，这里推荐每一个controller方法都得对应一个service,不要把业务编排放在controller中去做，为什么呢？
 * 如果我们把业务编排放在controller层去做的话，如果以后我们要接入thrift,我们这里又需要把业务编排在做一次，
 * 这样会导致我们每接入一个入口层这个代码都得重新复制一份
 *
 * 注意:
 * 1、所有的参数都通过方法传入，不允许通过构造方法共享(微服务的时候由于类只初始化一次，所以类的状态会被后续请求改变)
 * 2、所有Service公开(public)接口返回结果如果有多种状态返回，都要通过\Common::format_return_result()方法构造固定返回结构
 * 3、所有的服务不能有状态，以免影响后续请求(微服务常驻内存时，不会重置类状态)
 *
 * eg:
 * Controller、Service、Manager中获取容器调用方法

 */
namespace App\Http\Service;

use library\Cache\Base;
use library\Cache\Drivers\Redis;
//use library\AutoLoad\CBox;
//use library\AutoLoad\Traits\LogCommonTrait;
use library\Constants\StatusConstants;
use library\Utils\Lock;

class ServiceBase {
//    use LogCommonTrait;

    /**
     * @var CBox
     */
    protected $_container;
    /**
     * @var Redis
     */
    protected $_redis;

    protected $model;  // 获取model路径

    protected $return_data = [
        'code' => StatusConstants::SUCCESS,
        'msg'  => '',
        'data' => []
    ];

    public function __construct()
    {
//        $this->_container = CBox::instance();
        $this->_redis = $this->get_redis();
        $this->model =  \common::getModelPath();

    }

    /**
     * 获取缓存实例
     *
     * @param string $cache_config_key 根据缓存配置key获取指定缓存配置
     *
     * @return Redis|null
     */
    public function get_redis($cache_config_key = '') {
        return Base::get_instance($cache_config_key);
    }

    /**
     * 去除数组空值
     * @param array $data
     * @return array
    */
    public function del_array_null($data)
    {
        // 去除数组空值
        foreach ($data as $key => $item){
            if (empty($item)){
                unset($data[$key]);
            }
        }
        return $data;
    }


    /**
     * redis锁
     *
     * @param string $strMutex
     * @param float $intTimeout 例$intTimeout 1秒 = 1，500毫秒 = 0.5
     * @param int $localTimeout 本地超时时间，超过这个时间也算加锁成功，最好要比intTimeout大，否则会出现锁定时间还未到，就获取到了锁;
     * @param bool $is_millisecond 单位是否是毫秒true=毫秒 EX-秒 PX-毫秒
     * @param int $retry_loop_us 重试间隔时间 默认100ms
     *
     * @return bool|int 如果结果===1，说明是本地超时
     * @throws \Exception redis连不上会抛异常
     */
    public function lock($strMutex, $intTimeout, $localTimeout = 0, $is_millisecond=false, $retry_loop_us = 100)
    {
        return Lock::fast_instance()->lock($strMutex, $intTimeout, $localTimeout, $is_millisecond, $retry_loop_us);
    }

    /**
     * 解redis锁
     *
     * @param array|string $strMutex
     *
     * @return int
     */
    public function unlock($strMutex)
    {
        return Lock::fast_instance()->unlock($strMutex);
    }
}

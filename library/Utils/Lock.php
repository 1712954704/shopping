<?php
/**
 * 锁操作相关
 */
namespace library\Utils;

use library\Cache\Base;
use library\Traits\GetInstanceTrait;

class Lock {

    use GetInstanceTrait;

    protected $_redis;

    public function __construct($cache_config_key='')
    {
        $this->_redis = Base::get_instance($cache_config_key);
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
        // 重试间隔100ms
        $lock_retry_loop_us = $retry_loop_us * 1000;

        if($is_millisecond === true){
            $unit = 'PX';
            $intTimeout = $intTimeout * 1000;
        }else{
            $unit = 'EX';
        }

        if ($localTimeout > 0) {
            $lockStartTime = microtime(true);
        }
        do {
            $locked = $this->_redis->set($strMutex,1,[$unit=>$intTimeout, 'NX']);
            // 没有设置本地超时，则只执行一次
            if ($localTimeout == 0) {
                break;
            } elseif ($localTimeout > 0) {// 设置了本地超时，循环等锁，直到本地超时
                // 检查是否超过本地允许锁定的时间, 超过的话就返回true。防止redis不可用时，一直死循环等锁
                if ($locked || (microtime(true) - $lockStartTime) >= $localTimeout) {
                    // 如果是超时，就返回整数型1，外部可以用全等来区分
                    if (!$locked) {
                        $locked = 1;
                    }
                    break;
                }
            } else {// 一直等锁
                if ($locked) {
                    break;
                }
            }
            usleep($lock_retry_loop_us);
        } while(true);

        return $locked;
    }

    /**
     * 解锁
     *
     * @param array|string $strMutex
     *
     * @return int
     */
    public function unlock($strMutex)
    {
        return $this->_redis->del($strMutex);
    }
}

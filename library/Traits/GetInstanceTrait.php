<?php
namespace library\Traits;
/**
 * 提供静态获取实例方法
 *
 * Trait GetInstanceTrait
 */

trait GetInstanceTrait {
    protected static $_instance;
    /**
     * 获取实例
     *
     * @return self
     */
    public static function instance()
    {
        $args = func_get_args();
        $id = $args ? md5(serialize($args)) : null;
        if (!isset(self::$_instance[$id])) {
            if ($args) {
                self::$_instance[$id] = new self(...$args);
            }
            else {
                self::$_instance[$id] = new self();
            }
        }
        return self::$_instance[$id];
    }

    /**
     * 没有参数，快速实例化
     *
     * @return self
     */
    public static function fast_instance()
    {
        $id = null;
        if (!isset(self::$_instance[$id])) {
            self::$_instance[$id] = new self();
        }
        return self::$_instance[$id];
    }
}

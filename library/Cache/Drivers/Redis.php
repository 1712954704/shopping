<?php
namespace library\Cache\Drivers;

use App\Drivers\Base;
//use library\AutoLoad\Utils\ETS;
use library\Traits\Log;
use Psr\Log\LoggerInterface;

/**
 * @mixin \Redis
 * Interface defining a client able to execute commands against Redis.
 *
 * All the commands exposed by the client generally have the same signature as
 * described by the Redis documentation, but some of them offer an additional
 * and more friendly interface to ease programming which is described in the
 * following list of methods:
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Redis {
    /**
     * @var \Redis
     */
    protected $_redis = null;

    protected $_host;
    protected $_port = 6379;

    protected $_password = '';

    /**
     * Connect timeout
     * @var int|mixed
     */
    protected $_timeout = 1;

    /**
     * Read data timeout
     * @var float
     */
    protected $_read_timeout = 0.5;

    /**
     * Whether to open a persistent connection
     * @var int|bool
     */
    protected $_persist = 1;

    /**
     * 持久连接的id
     * 不同的id是不同的连接
     * @var mixed|string
     */
    protected $_persistent_id = '';

    /**
     * 选择db
     *
     * @var int
     */
    protected $_db = null;

    /**
     * Cache config key
     * @see cache_config.ini
     * @var string
     */
    protected $_config_key;

    /**
     * @var LoggerInterface
     */
    protected static $_logger;

    /**
     * 最近一次命令执行成功还是失败
     *
     * @var bool
     */
    protected $_last_exec_bool = true;

    /**
     * 是否支持lua脚本
     * @var bool
     */
    protected $_support_lua_script = true;

    /**
     * 脚本列表
     * lua脚本的返回值是redis原始结构，不可以像php里的redis函数一样使用如（zRevRange等）需要自己处理返回值
     * @var array
     */
    protected $_func_table = [
        // 增量计数器，第一次增量计数的时候，给key加上过期时间，解决并发问题 eg: eval_script('incr', 'key', expire)
        'incr' => [
            'sha1' => '727c0136efce8e1e7b34a5d1a29c87b77a9348ff',
            'script' => "local count = redis.call('incr',KEYS[1]); if tonumber(count) == 1 then redis.call('expire',KEYS[1],ARGV[1]); end; return count;"
        ],
        // 增量计数器，并在增量值超过最大值时，重置为0 eg: eval_script('incr_reset', 'key', [max_counter，expire])
        'incr_reset' => [
            'sha1' => '064e70749675e1c315270a18e5c38ae3f314498a',
            'script' => "local count = redis.call('incr',KEYS[1]); if tonumber(count) == 1 then redis.call('expire',KEYS[1],ARGV[2]); end; if tonumber(count) > tonumber(ARGV[1]) then redis.call('set', KEYS[1], 0); return 0; end; return count;",
        ],
        // 增量计数器，并在超出最大值时，重置为0；或主动重置为0. eg: eval_script('incr_out_max_reset', 'key', [本次要加的数量, 最大值, 本次是否要重置(0,1), 重置的值])
        'incrby_out_max_reset' => [
            'sha1' => 'dc0d20d0b62522f5682b2f8b365e7b237b63d7f1',
            'script' => "local is_reset, count = 0, 0; if tonumber(ARGV[3]) == 1 then redis.call('set', KEYS[1], tonumber(ARGV[4])); is_reset = 1; count = tonumber(ARGV[4]); else count = redis.call('incrby',KEYS[1], ARGV[1]); if tonumber(count) > tonumber(ARGV[2]) then redis.call('set', KEYS[1], tonumber(ARGV[4])); is_reset = 1; count = tonumber(ARGV[4]); end; end; return {is_reset, count};",
        ],
        // 增量计数器，如果当前值没有大于限定值，才可以加一并返回[1, 累加后的值]，否则返回[0, 当前值] eg: eval_script('incr_max'', 'key', [max_counter, expire])
        'incr_max' => [
            'sha1' => '56a52dbab84bd9b0fc0a8330caff45c31d2df9ab',
            'script' => "local count = redis.call('get',KEYS[1]); if ( count == false or tonumber(count) < tonumber(ARGV[1]) ) then count = redis.call('incr', KEYS[1]); if count == 1 then redis.call('expire',KEYS[1],ARGV[2]); end; return {1, count}; else return {0, count}; end;",
        ],
        // 存在才将 key 中储存的数字值减一 eg: eval_script('decr_exist', 'key')
        'decr_exist' => [
            'sha1' => 'b8fdb9f741719829325bcc7253b93eed7b526ccb',
            'script' => "local count = redis.call('exists',KEYS[1]); if tonumber(count) == 1 then count = redis.call('decr',KEYS[1]); end; return count;"
        ],
        // 先获取榜首信息再加积分
        'zincrby_get_top' => [
            'sha1' => '66448d0c16571ae1e5039d243174382377435aa5',
            'script' => "local top1 = redis.call('zRevRange',KEYS[1],0,0,'WITHSCORES'); local score = redis.call('zincrby',KEYS[1],ARGV[1],ARGV[2]); return {top1, score}"
        ],
        // hash结构批量增加计数器，返回每个域增加后的值
        'hmincrby' => [
            'sha1' => 'd72dfd94813190f2f0eb6e0e935e7e362ea4f873',
            'script' => "local n = {}; for i=1, #(ARGV)/2 do n[i] = redis.call('hincrby',KEYS[1],ARGV[i*2-1],ARGV[i*2]); end; return n;",
        ],
        // hash 浮点型精度增量计数器，解决使用 hIncrByFloat出现的精度问题
        'bc_hIncrByFloat' => [
            'sha1' => 'bca55cec1f9de26e9a18ebb1875030b8e00399ab',
            'script' => "local val = redis.call('hGet',KEYS[1],ARGV[1]); local inc_val; if val ~=false then inc_val = tostring(val + ARGV[2]) else inc_val = tostring(ARGV[2]) end; local res = redis.call('hSet',KEYS[1],ARGV[1],inc_val); if res >= 0 then return inc_val else return res end",
//            'script' => "local val = redis.call('hGet',KEYS[1],ARGV[1]); local multiple = 10*ARGV[3]; local inc_val; if val ~=false then inc_val = tostring((val*multiple + ARGV[2]*multiple)/multiple) else inc_val = tostring(ARGV[2]) end; local res = redis.call('hSet',KEYS[1],ARGV[1],inc_val); if res >= 0 then return inc_val else return res end",
        ],
    ];

    /**
     * Redis constructor.
     *
     * @param array $config
     * @param string $config_key
     *
     * @throws \Exception
     */
    public function __construct(array $config = [], $config_key='')
    {
        $this->_config_key = $config_key;
        $config_key = $config_key !== '' ? $config_key .'.' : '';
        if (empty($config['host'])) {
            throw new \Exception("Configure the {$config_key}'host' parameter in the cache_config configuration");
        }
        $this->_host = $config['host'];

        if (!empty($config['port'])) {
            $this->_port = $config['port'];
        }

        if (isset($config['password'])) {
            $this->_password = $config['password'];
        }

        if (isset($config['timeout'])) {
            $this->_timeout = $config['timeout'];
        }

        if (isset($config['read_timeout'])) {
            $this->_read_timeout = $config['read_timeout'];
        }

        if (isset($config['persist'])) {
            $this->_persist = $config['persist'];
        }

        if (!empty($config['persistent_id'])) {
            $this->_persistent_id = $config['persistent_id'];
        }

        // 选择DB
        if (isset($config['db'])) {
            $this->_db = $config['db'];
        }

//        if (!is_object(self::$_logger)) {
//            throw new \Exception('Please set the logger object');
//        }

        // 使用到的时候才建立连接，为了兼容CLI模式下的多线程处理
//        $this->connect();
    }

    /**
     * 日志记录对象
     *
     * @param LoggerInterface $logger
     * @see Base::factory()
     */
    public static function set_logger($logger) {
        self::$_logger = $logger;
    }

    /**
     * 连接Redis
     *
     * @return bool
     * @throws \Exception
     */
    public function connect() {
        if ($this->_redis === null) {
//            ETS::start(ETS::STAT_ET_REDIS_CONNECT);
            $connect_method = 'connect';
            $init_args = [
                $this->_host, $this->_port, $this->_timeout
            ];
            if ($this->_persist) {
                $connect_method = 'pconnect';
                $init_args = [
                    $this->_host, $this->_port, $this->_timeout, $this->_persistent_id
                ];
            }
            $retry = 1;
            // 如果值是true, 下次不再进行ping操作
            $retry_connect = false;
            retry:
            $msg = "Redis error: Connect {$this->_host}:{$this->_port} failed!";
            try {
                $this->_redis = new \Redis();
                // 连接缓存中间件
                $conn = $this->_redis->{$connect_method}(...$init_args);
                if (!$conn) {
                    throw new \Exception('connect return false', 500);
                }
            } catch (\Exception $e) {
                $this->_redis = null;
                // 如果是重连
                if ($retry !== 1) {
                    $msg = "Redis error: Retry connect {$this->_host}:{$this->_port} failed!";
                }
                $msg .= ' Exception: ' . $e->getMessage();
                $this->_log_error($msg, [
                    'host'         => $this->_host,
                    'port'         => $this->_port,
                    'timeout'      => $this->_timeout,
                    'read_timeout' => $this->_read_timeout,
                    'persist'      => $this->_persist
                ]);
                // 重新尝试连接
                if ($retry-- > 0) {
                    goto retry;
                }
                throw $e;
            }
            // 如果需要验证密码
            if (!empty($this->_password)) {
                $auth_result = $this->_redis->auth($this->_password);
                if (!$auth_result) {
                    $msg = "Redis error: Auth password failed!";
                    $this->_log_error($msg, [
                        'host'         => $this->_host,
                        'port'         => $this->_port,
                        'timeout'      => $this->_timeout,
                        'read_timeout' => $this->_read_timeout,
                        'persist'      => $this->_persist
                    ]);
                    throw new \Exception($msg, 500);
                }
            }
            // 如果是长连做一次心跳检测，检测连接是否是通的
            if ($this->_persist && $retry_connect !== true) {
                try {
                    $ping = $this->_redis->ping();
                    if ((is_string($ping) && strpos($ping, 'PONG') === false) || (is_bool($ping) && $ping === false)) {
                        throw new \Exception('ping = ' . var_export($ping, true), 500);
                    }
                } catch (\Exception $e) {
                    $msg = 'Redis error: Connection has already been closed(Ping failed); Exception: ' . $e->getMessage();
                    $this->_log_error($msg, [
                        'host'         => $this->_host,
                        'port'         => $this->_port,
                        'timeout'      => $this->_timeout,
                        'read_timeout' => $this->_read_timeout,
                        'persist'      => $this->_persist,
                    ]);
                    // 关闭连接重新连接
                    $this->_redis->close();
                    $retry_connect = true;
                    $this->_redis = null;
                    goto retry;
                }
            }
            // 设置读取超时时间
            $this->_redis->setOption(\Redis::OPT_READ_TIMEOUT, $this->_read_timeout);
            // 如果需要选择db
            if (isset($this->_db)) {
                $this->_redis->select($this->_db);
            }
//            ETS::end(ETS::STAT_ET_REDIS_CONNECT, 'host: '. $this->_host . ':' . $this->_port);
        }
        return true;
    }

    /**
     * 拓展incr指令，可以原子性的设置过期时间
     *
     * @param $key
     * @param null $expire
     *
     * @return bool|int|mixed
     *
     * @warning TODO 不能在multi中使用，否则脚本无法执行load，导致命令无效
     */
    public function incr($key, $expire = null)
    {
        $this->connect();
//        ETS::start(ETS::STAT_ET_REDIS_QUERY);
        $result = null;
        $redis_cmd = __FUNCTION__;
        $this->_last_exec_bool = true;
        try {
            if (is_null($expire)) {
                $result = $this->_redis->incr($key);
            }
            else {
                $result = $this->eval_script($redis_cmd, $key, $expire);
            }
        } catch (\Exception $e) {
            $this->_last_exec_bool = false;
            $msg = "Redis error: [node: {$this->_host}:{$this->_port}] [cmd: {$redis_cmd}] [msg: {$e->getMessage()}]";
            $this->_log_error($msg, ['key' => $key, 'expire' => $expire]);
        }
//        ETS::end(ETS::STAT_ET_REDIS_QUERY,'', function() use($redis_cmd, $key, $expire) {
//            return $redis_cmd . ' =>' . json_encode([$key, $expire]);
//        }, ['host' => $this->_host, 'port' => $this->_port]);
        return $result;
    }

    /**
     * 拓展zincrby_get_top指令，可以原子性的加积分前先获取榜首信息
     *
     * @date 2021/9/27
     * @param $key
     * @param $increment
     * @param $member
     * @return array|bool
     * @throws \Exception
     */
    public function zincrby_get_top($key, $increment, $member)
    {
        $this->connect();
//        ETS::start(ETS::STAT_ET_REDIS_QUERY);
        $redis_cmd = __FUNCTION__;
        $this->_last_exec_bool = true;
        try {
            if ($this->_support_lua_script) {
                $result = $this->eval_script($redis_cmd, $key, [$increment,$member]);

                /**
                 * 因为zRevRange 返回的是 [key=>value]
                 * lua返回的zRevRange是原始的redis结构 [0=>key,1=>value]
                 * 为了跟php的zRevRange返回值保持一致所以在此重新组装top1
                 */
//                $top1 = $result[0];
//                $len = count($top1);
//                if($len >1){
//                    for ($i=0;$i<$len;$i+=2){
//                        $val[$top1[$i]] = $top1[$i+1];
//                    }
//                    $result[0] = $val;
//                }
                // 转换成键值对数组
                if(!empty($result[0])){
                    $result[0] = [$result[0][0] => $result[0][1]];
                }
            }
            else {
                $top1= $this->zRevRange($key, 0, 0, true);
                $score = $this->zincrby($key, $increment, $member);
                $result = [$top1,$score];
            }
        } catch (\Exception $e) {
            $result = false;
            $this->_last_exec_bool = false;
            $msg = "Redis error: [node: {$this->_host}:{$this->_port}] [cmd: {$redis_cmd}] [msg: {$e->getMessage()}]";
            $this->_log_error($msg, ['key' => $key, 'increment' => $increment,'member'=>$member]);
        }
//        ETS::end(ETS::STAT_ET_REDIS_QUERY,'', function() use($redis_cmd, $key, $increment, $member) {
//            return $redis_cmd . ' =>' . json_encode([$key, $increment, $member]);
//        }, ['host' => $this->_host, 'port' => $this->_port]);
        return $result;
    }

    /**
     * 拓展bc_hIncrByFloat指令，可以原子性保证计算增量值的精度
     *
     * @date 2022/4/21
     * @param $key
     * @param $field
     * @param $increment
     * @param int $scale 精度计算保留小数位
     * @return bool|float|mixed
     * @throws \Exception
     */
    public function bc_hIncrByFloat($key, $field, $increment, $scale = 10)
    {
        $this->connect();
//        ETS::start(ETS::STAT_ET_REDIS_QUERY);
        $redis_cmd = __FUNCTION__;
        $this->_last_exec_bool = true;
        try {
            if ($this->_support_lua_script) {
                $result = $this->eval_script($redis_cmd, $key,[$field,$increment,$scale]);
            }
            else {
                $result = $this->hIncrByFloat($key, $field, $increment);
            }
        } catch (\Exception $e) {
            $result = false;
            $this->_last_exec_bool = false;
            $msg = "Redis error: [node: {$this->_host}:{$this->_port}] [cmd: {$redis_cmd}] [msg: {$e->getMessage()}]";
            $this->_log_error($msg, ['key' => $key,'field'=>$field, 'increment' => $increment,'scale'=>$scale]);
        }
//        ETS::end(ETS::STAT_ET_REDIS_QUERY,'', function() use($redis_cmd, $key, $field, $increment, $scale) {
//            return $redis_cmd . ' =>' . json_encode([$key, $field, $increment, $scale]);
//        }, ['host' => $this->_host, 'port' => $this->_port]);
        return $result;
    }

//    /**
//     * 自增达到最大值就重置为0
//     *
//     * @param $key
//     * @param $max_counter
//     * @param $expire
//     *
//     * @return bool|int|null
//     *
//     * @warning TODO 不能在multi中使用，否则脚本无法执行load，导致命令无效
//     */
//    public function incr_reset($key, $max_counter, $expire)
//    {
//        $this->connect();
//        $result = null;
//        $redis_cmd = __FUNCTION__;
//        try {
//            $result = $this->eval_script($redis_cmd, $key, [$max_counter, $expire]);
//        } catch (\Exception $e) {
//            $msg = "Redis error: [node: {$this->_host}:{$this->_port}] [cmd: {$redis_cmd}] [msg: {$e->getMessage()}]";
//            $this->_log_error($msg, ['key' => $key, 'maxCounter' => $max_counter, 'expire' => $expire]);
//        }
//        return $result;
//    }
    /**
     * 增量计数器，超出最大值重置，或主动重置；重置可设置为自定义的值
     *
     * @param string $key
     * @param int $increment
     * @param int $max_counter 最大值
     * @param int $is_reset 本次是否要重置：0=不重置 1=重置
     * @param int $reset_value 重置后的值
     *
     * @return array|bool [是否重置(0,1)，当前值]
     * @throws \Exception
     */
    public function incrby_out_max_reset($key, $increment, int $max_counter, int $is_reset = 0, int $reset_value = 0)
    {
        $this->connect();
//        ETS::start(ETS::STAT_ET_REDIS_QUERY);
        $redis_cmd = __FUNCTION__;
        $this->_last_exec_bool = true;
        try {
            if ($this->_support_lua_script) {
                $result = $this->eval_script($redis_cmd, $key, [$increment, $max_counter, $is_reset, $reset_value]);
            }
            else {
                if (!$is_reset) {
                    $count = intval($this->incrby($key, $increment));
                    $result = [0, $count];
                    if ($count > $max_counter) {
                        $is_reset = 1;
                    }
                }
                if ($is_reset) {
                    $bool = $this->set($key, $reset_value);
                    if (!$bool) {
                        throw new \Exception('reset value failed');
                    }
                    $result = [1, $reset_value];
                }
            }
        } catch (\Exception $e) {
            $result = false;
            $msg = "Redis error: [node: {$this->_host}:{$this->_port}] [cmd: {$redis_cmd}] [msg: {$e->getMessage()}]";
            $this->_log_error($msg, ['key' => $key, 'increment' => $increment, 'maxCounter' => $max_counter, 'is_reset' => $is_reset, 'reset_value' => $reset_value]);
        }
//        ETS::end(ETS::STAT_ET_REDIS_QUERY,'', function() use($redis_cmd, $key, $increment, $max_counter, $is_reset, $reset_value) {
//            return $redis_cmd . ' =>' . json_encode([$key, $increment, $max_counter, $is_reset, $reset_value]);
//        }, ['host' => $this->_host, 'port' => $this->_port]);
        return $result;
    }


    /**
     * @param string $script_Key
     * @param array|string $keys 键名
     * @param array|string $args 参数
     *
     * @return bool|mixed
     * @throws \Exception
     *
     * @example
     * obj->eval_script('incr', 'test', 100); // incr, key, 过期时间
     * 增量计数器，超过最大值就重置为0
     * obj->eval_script('incr_reset', 'test', [max_counter, 100]); // incr_reset, key, [最大值, 过期时间]
     * 增量计数器，如果当前值没有大于限定值，才可以加一并返回[1, 累加后的值]，否则返回[0, 当前值]
     * obj->eval_script('incr_max', 'test', [max_counter, 100]); // incr_max, key, [最大值, 过期时间]
     *
     * @warning TODO 不能在multi中使用，否则脚本无法执行load，导致命令无效
     * @warning TODO 只在单机节点和客户端实现集群调用策略上验证过，云redis暂未测试过
     */
    public function eval_script($script_Key, $keys, $args=[]) {
        if(!isset($this->_func_table[$script_Key]) || empty($this->_func_table[$script_Key]['script'])) {
            // 开发中解决错误
            throw new \Exception(__METHOD__ . ' 请先配置'.$script_Key.'脚本');
        }
        if(empty($this->_func_table[$script_Key]['sha1'])) {
            $this->_func_table[$script_Key]['sha1'] = sha1($this->_func_table[$script_Key]['script']);
        }
        $this->connect();
        $sha1 = $this->_func_table[$script_Key]['sha1'];

        $redis_cmd = __FUNCTION__;
        try {
            if(!is_array($keys)) {
                $keys = [$keys];
            }
            else {
                // 不需要键名索引，用数字重新建立索引
                $keys = array_values($keys);
            }
            if(!is_array($args)) {
                $args = [$args];
            }
            else {
                // 不需要键名索引，用数字重新建立索引
                $args = array_values($args);
            }
            $key_count = count($keys);
            $args = array_merge($keys, $args);
            for($i =0; $i < 2; $i++) {
                $result = $this->_redis->evalSha($sha1, $args, $key_count);
                if($result === false && $i === 0) {
                    $errorMsg = $this->_redis->getLastError();
                    $this->_redis->clearLastError();
                    // 该脚本不存在该节点上，需要执行load
                    if(stripos($errorMsg, 'NOSCRIPT') !== false) {
                        $loadParams = [
                            'load',
                            $this->_func_table[$script_Key]['script']
                        ];
                        // load脚本
                        $serverSha1 = $this->_redis->script(...$loadParams);
                        // 在开发阶段解决这个错误
                        if($serverSha1 !== $sha1) {
                            throw new \Exception($script_Key.'脚本的sha1与服务端返回的sha1不一致'.$sha1.'=='.$serverSha1, 999);
                        }
                        continue;
                    }
                }
                break;
            }
        } catch (\Exception $e) {
            // 如果是开发阶段能解决的错误，就抛出去
            if($e->getCode() === 999) {
                throw new \Exception($e->getMessage(), $e->getCode());
            }
            $result = false;
            $msg = "Redis error: [node: {$this->_host}:{$this->_port}] [cmd: {$redis_cmd}] [msg: {$e->getMessage()}]";
            $this->_log_error($msg, func_get_args());
        }
        return $result;
    }

    /**
     * 批量向sort set中添加元素
     *
     * @param string $key sort set的key
     * @param array $elems 待添加元素的集合，每一项为array('val' => score)
     *
     * @return int|bool
     *
     * @throws \Exception
     */
    public function zAddArray(string $key, array $elems)
    {
        $this->connect();
//        ETS::start(ETS::STAT_ET_REDIS_QUERY);
        if (!$key || !is_array($elems)){
            return false;
        }

        $p = [];
        foreach ($elems as $k => $v){
            $p[] = $v;
            $p[] = $k;
        }
        $this->_last_exec_bool = true;
        $redis_cmd = __FUNCTION__;
        try {
            $result = $this->_redis->zadd($key, ...$p);
            // redis使用优化，操作命令失败抛异常
            $this->_err_check();
        } catch (\Exception $e) {
            $result = false;
            $this->_last_exec_bool = false;
            $msg = "Redis error: [node: {$this->_host}:{$this->_port}] [cmd: {$redis_cmd}] [msg: {$e->getMessage()}]";
            $this->_log_error($msg, func_get_args());
        }
//        ETS::end(ETS::STAT_ET_REDIS_QUERY,'', function() use($redis_cmd, $p) {
//            return $redis_cmd . ' =>' . json_encode($p);
//        }, ['host' => $this->_host, 'port' => $this->_port]);
        return $result;
    }

    /**
     * 批量向 setBit中添加元素
     *
     * @param string $key setBit 的key
     * @param array $elems  待添加元素的集合，每一项为array('key' => value)
     * @param string $size 大小 u1代表无符号1位
     *
     * @return int|bool
     *
     * @throws \Exception
     */
    public function setBitArray(string $key, array $elems, string $size = 'u1')
    {
        $this->connect();
//        ETS::start(ETS::STAT_ET_REDIS_QUERY);
        if (!$key || !is_array($elems)){
            return false;
        }

        $p = [];
        foreach ($elems as $k => $v){
            $p[] = 'set';
            $p[] = $size;
            $p[] = $k;
            $p[] = $v;
        }
        $this->_last_exec_bool = true;
        $redis_cmd = __FUNCTION__;
        try {
            $result = $this->_redis->rawCommand('bitField', $key, ...$p);
            // redis使用优化，操作命令失败抛异常
            $this->_err_check();
        } catch (\Exception $e) {
            $result = false;
            $this->_last_exec_bool = false;
            $msg = "Redis error: [node: {$this->_host}:{$this->_port}] [cmd: {$redis_cmd}] [msg: {$e->getMessage()}]";
            $this->_log_error($msg, func_get_args());
        }

//        ETS::end(ETS::STAT_ET_REDIS_QUERY,'', function() use($redis_cmd, $p) {
//            return $redis_cmd . ' =>' . json_encode($p);
//        }, ['host' => $this->_host, 'port' => $this->_port]);
        return $result;
    }

    /**
     * 批量查询 getBit 元素值
     *
     * @param string $key
     * @param array $data 查询元素的数组集合，每一项为array('key','key1','key2'.....'keyN')
     * @param string $size
     *
     * @return array|bool array('key'=>val,'key1'=>val,...)
     *
     * @throws \Exception
     */
    public function getBitArray(string $key, array $data, string $size = 'u1')
    {
        $this->connect();
//        ETS::start(ETS::STAT_ET_REDIS_QUERY);
        if (!$key || !is_array($data)){
            return false;
        }

        $redis_cmd = __FUNCTION__;
        $p = [];
        foreach ($data as $k => $v){
            $p[] = 'get';
            $p[] = $size;
            $p[] = $v;
        }

        $this->_last_exec_bool = true;
        try {
            $result1 = $this->_redis->rawCommand('bitField',$key, ...$p);
            // redis使用优化，操作命令失败抛异常
            $this->_err_check();
            if($result1){
                $result =[];
                foreach ($data as $k => $v){
                    $result[$v] = $result1[$k];
                }
            }else{
                $result = false;
            }
        } catch (\Exception $e) {
            $result = false;
            $this->_last_exec_bool = false;
            $msg = "Redis error: [node: {$this->_host}:{$this->_port}] [cmd: {$redis_cmd}] [msg: {$e->getMessage()}]";
            $this->_log_error($msg, func_get_args());
        }

//        ETS::end(ETS::STAT_ET_REDIS_QUERY,'', function() use($redis_cmd, $p) {
//            return $redis_cmd . ' =>' . json_encode($p);
//        }, ['host' => $this->_host, 'port' => $this->_port]);
        return $result;
    }

    /**
     * 查询全部bitmap的数据
     * @date 2021/10/27
     * @param string $key
     * @return array|bool
     * @throws \Exception
     */
    public function getBitAll(string $key)
    {
        $this->connect();
//        ETS::start(ETS::STAT_ET_REDIS_QUERY);
        if (!$key) return false;

        $redis_cmd = __FUNCTION__;
        $this->_last_exec_bool = true;
        $result = false;
        try {

            $value = $this->_redis->get($key);
            $this->_err_check();
            if($value) {
//                /**
//                 * 解包(redis返回来的是二进制字符串，我们需要把它解成对应的数字)
//                 * 关于unpack的用法，如果不了解，大家可以网上搜索学习，改天可以单独写篇文章分享
//                 */
//                $bitmap = unpack('C*', $value);
//                if ($bitmap) {
//                    foreach ($bitmap as $key => $number) {
//                        // 下标是从1开始的; 1个字节8位
//                        $offset = ($key - 1) * 8;
//                        // 过滤没有标记的字节段
//                        if ($number) {
//                            for ($i = 0; $i < 8; $i++) {
//                                // 遍历这个字节的每一位，是否有为1的值，如果有，那就记录这个位置的偏移量，就是用户id
//                                if (($number >> $i & 1) == 1) {
//                                    // 8位范围是0~7,因为redis是高位到低位存储，所以要反过来计算偏移量
//                                    $result[] = $offset + (7 - $i);
//                                }
//                            }
//                        }
//                    }
//                }
                $result = \Common::get_bitmap_offsets($value);
            }

        } catch (\Exception $e) {
            $result = false;
            $this->_last_exec_bool = false;
            $msg = "Redis error: [node: {$this->_host}:{$this->_port}] [cmd: {$redis_cmd}] [msg: {$e->getMessage()}]";
            $this->_log_error($msg, func_get_args());
        }

//        ETS::end(ETS::STAT_ET_REDIS_QUERY,'', function() use($redis_cmd,$key) {
//            return $redis_cmd . ' =>' . json_encode($key);
//        }, ['host' => $this->_host, 'port' => $this->_port]);
        return $result;
    }

    /**
     * 扫描指定的key
     *
     * @param string $pattern
     * @param int $count 每次扫码的行数
     * @param int $length 获取最大数量
     *
     * @return array
     *
     * @throws \Exception
     */
    public function scan(string $pattern, int $count=5000, $length = null): array
    {
        $this->connect();
        $iterator = null;
        $data = [];
        while(false !== ($keys = $this->_redis->scan($iterator, $pattern, $count))) {
            foreach($keys as $key) {
                $data[] = $key;
            }

            //限制获取数组的长度
            if($length != null){
                if(count($data) >= $length){
                    $data = array_slice($data,0,$length);
                    break;
                }
            }
        }
        return $data;
    }

    /**
     * @param $key
     * @param null $pattern
     * @param int $count 一次扫描多少个field
     *
     * @return array
     * @throws \Exception
     */
    public function hscan($key, $pattern = null, $count = 3000): array
    {
        $this->connect();
        $iterator = null;
        $data = [];
        while($elements = $this->_redis->hscan($key, $iterator, $pattern, $count)) {
            foreach($elements as $key => $value) {
                $data[$key] = $value;
            }
        }
        return $data;
    }

    /**
     * @param $key
     * @param null $pattern
     * @param int $count
     *
     * @return array
     * @throws \Exception
     */
    public function sscan($key, $pattern = null, $count = 3000): array
    {
        $this->connect();
        $iterator = null;
        $data = [];
        while($members = $this->_redis->sscan($key, $iterator, $pattern, $count)) {
            foreach($members as $member) {
                $data[] = $member;
            }
        }
        return $data;
    }

    /**
     * @param $key
     * @param null $pattern
     * @param int $count
     *
     * @return array
     * @throws \Exception
     */
    public function zscan($key, $pattern = null, $count = 3000): array
    {
        $this->connect();
        $iterator = null;
        $data = [];
        while($members = $this->_redis->zscan($key, $iterator, $pattern, $count)) {
            foreach($members as $member => $score) {
                $data[$member] = $score;
            }
        }
        return $data;
    }

    /**
     * @param $name
     * @param $args
     * @return mixed|false 指令执行失败报错时，返回false
     *
     * @throws \Exception
     */
    public function __call($name, $args)
    {
        $this->connect();
        $this->_last_exec_bool = true;
//        ETS::start(ETS::STAT_ET_REDIS_QUERY);
        $redis_cmd = $name;
        try {
            $result = $this->_redis->{$redis_cmd}(...$args);
            // redis使用优化，操作命令失败抛异常
            $this->_err_check();
        } catch (\Exception $e) {
            $result = false;
            $this->_last_exec_bool = false;
            $msg = "Redis error: [node: {$this->_host}:{$this->_port}] [cmd: {$redis_cmd}] [msg: {$e->getMessage()}]";
            $this->_log_error($msg, func_get_args());
        }
//        ETS::end(ETS::STAT_ET_REDIS_QUERY,'', function() use($redis_cmd, $args) {
//            return $redis_cmd . ' =>' . json_encode($args);
//        }, ['host' => $this->_host, 'port' => $this->_port]);
        return $result;
    }

    /**
     * 检测是否有错误
     *
     * @throws \Exception
     */
    private function _err_check()
    {
        $err = $this->_redis->getLastError();
        if (null != $err) {
            $this->_redis->clearLastError();
            throw new \Exception($err, 500);
        }
    }

    /**
     * 记录错误日志
     *
     * @param string $msg
     * @param array $context
     *
     * @return void
     */
    protected function _log_error(string $msg, array $context = [])
    {
        Log::NewInfo($context,' redis_error:'.$msg);
//        self::$_logger->error($msg, $context);
    }

    /**
     * 获取最后执行命令成功或失败(失败表示命令执行超时、连接断开等情况)
     *
     * @return bool
     */
    public function get_last_exec_bool(): bool {
        return $this->_last_exec_bool;
    }

    /**
     * @return bool
     */
    public function close() {
        if ($this->_redis) {
            $bool = $this->_redis->close();
            $this->_redis = null;
            return $bool;
        }
        return true;
    }

    /**
     * 销毁对象，就关闭短连接
     */
    public function __destruct()
    {
        if (!$this->_persist && $this->_redis) {
            $this->_redis->close();
        }
    }

    /**
     * hash结构批量增加计数器
     * @param $key
     * @param array $elems ['k1' => 需要累加的值, 'k2' => 需要累加的值]
     *
     * @return array|bool ['k1' => 累加后的值, 'k2' => 累加后的值]
     * @throws \Exception
     */
    public function hmincrby($key, array $elems) {
        // 是否是kv关联数组
        if (!\Common::is_assoc($elems)) {
            throw new \Exception('invalid param elems');
        }
        $this->connect();
//        ETS::start(ETS::STAT_ET_REDIS_QUERY);
        $redis_cmd = __FUNCTION__;
        $this->_last_exec_bool = true;
        try {
            if ($this->_support_lua_script) {
                $data = [];
                foreach ($elems as $k => $v) {
                    $data[] = $k;
                    $data[] = $v;
                }
                $result_data = $this->eval_script($redis_cmd, $key, $data);
                $result = $result_data;
                // 构建键值对返回值
                if ($result_data) {
                    $result = [];
                    $i = 0;
                    foreach ($elems as $k => $v) {
                        $result[$k] = $result_data[$i];
                        $i++;
                    }
                }
            }
            else {
                $result = [];
                // 构建键值对返回值
                foreach ($elems as $k => $v) {
                    $result[$k] = $this->hincrby($key, $k, $v);
                }
            }
        } catch (\Exception $e) {
            $result = false;
            $this->_last_exec_bool = false;
            $msg = "Redis error: [node: {$this->_host}:{$this->_port}] [cmd: {$redis_cmd}] [msg: {$e->getMessage()}]";
            $this->_log_error($msg, ['key' => $key, 'elems' => $elems]);
        }
//        ETS::end(ETS::STAT_ET_REDIS_QUERY,'', function() use($redis_cmd, $key, $elems) {
//            return $redis_cmd . ' =>' . json_encode([$key, $elems]);
//        }, ['host' => $this->_host, 'port' => $this->_port]);
        return $result;
    }

    public function get_support_lua_script() {
        return $this->_support_lua_script;
    }
}

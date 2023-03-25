<?php
/**
 *
 * 规范：
 * 1、不要定义一级key,在key前面追加模块前缀； 如用户模块 self::MODULE_USER . 'key';
 * 2、如果是非String类型的key,要在常量名称上体现；如集合key SET_KEY_NAME = 模块常量 . 'key:%s:%s'; // %s(1) = 用户id或其他参数; 使用$key = sprintf(SET_KEY_NAME, $param1, $param2);
 * 3、key能设置过期时间的，就设置过期时间，尽量节省内存
 * 4、避免设计bigkey 和 hotkey
 * 5、业务实现时，要考虑到以后的访问量、数据量来选择对的数据结构。要注意时间复杂度
 */

class CacheConstants {
    /** 缓存过期时间 */
    // 一年
    const CACHE_EXPIRE_TIME_YEAR = 31536000;
    // 半年
    const CACHE_EXPIRE_TIME_HALF_YEAR = 15552000;
    // 一个月
    const CACHE_EXPIRE_TIME_ONE_MONTH = 2592000;
    // 一周
    const CACHE_EXPIRE_TIME_HALF_ONE_WEEK = 604800;
    // 一天
    const CACHE_EXPIRE_TIME_ONE_DAY = 86400;
    // 两个小时
    const CACHE_EXPIRE_TIME_HALF_TWO_HOUR = 7200;
    //12小时
    const CACHE_EXPIRE_TIME_TWELVE_HOURS = 43200;
    // 一个小时
    const CACHE_EXPIRE_TIME_HALF_ONE_HOUR = 3600;
    // 半个小时
    const CACHE_EXPIRE_TIME_HALF_AN_HOUR = 1800;
    // 一分钟
    const CACHE_EXPIRE_TIME_HALF_ONE_MINUTE = 60;
    // 1秒
    const CACHE_EXPIRE_TIME_ONE_SECOND = 1;

    /** 缓存过期时间 End */
    //用户上下线信息
    const USER_ONLINE_INFO = 'user_online_info:';

    // 区号+手机号:类型:短信验证码后缀
    const MOBILE_SMS_VERIFY_CODE_SUFFIX = ':sms_verify_code';
    // 区号+手机号:类型:短信验证码错误次数计数器后缀
    const MOBILE_SMS_VERIFY_CODE_ERR_COUNTER_SUFFIX = ':sms_verify_code_err_counter';
    // 区号+手机号:类型:短信验证码通过标记后缀
    const MOBILE_SMS_VERIFY_CODE_PASSED_FLAG_SUFFIX = ':sms_verify_code_passed_flag';
    // 区号+手机号:密码登陆错误标记后缀
    const MOBILE_PASSED_NUM = 'num:%s:%s:mobile_passed_num';

    /**
     * 模块名前缀，用常量定义，到时候可以统一换
     */
    const MODULE_AUTH = 'auth:';
    const MODULE_USER = 'user:';
    const SYSTEM_EHR = 'ehr:';

    // token key前缀
    const CACHE_REDIS_TOKEN_KEY_PREFIX = 'token:';

    const VERIFY_CODE_MOBILE_LOCK = self::MODULE_AUTH.'vc:mob:%s:%s:%s:%s'; // 验证码手机号锁 auth:vc:mob:(去除+)区号:手机号:来源:短信类型
    const VERIFY_CODE_IP_LIMIT_DAILY_SMS_NUM = self::MODULE_AUTH.'vc:ip_d_sms_num:%s'; // 验证码IP锁 auth:vc:ip_d_sms_num:ip地址
    const VERIFY_CODE_MOBILE_LIMIT_DAILY_SMS_NUM = self::MODULE_AUTH.'vc:mob_d_sms_num:%s:%s'; // 手机号每天限制条数 auth:vc:mob_d_sms_num:(去除+)区号:手机号


//    const HASH_USER_INFO_PREFIX = self::MODULE_USER . 'info:';                  // 用户信息缓存 user:info:用户id
//    const HASH_USER_AUTH_INFO_PREFIX = self::MODULE_USER . 'auth:info:';        // 用户权限信息缓存 user:auth:info:用户id


    /**
     * 锁相关
    */
    // 解决考评流程审核并发请求问题 ehr:approval_lock:user_id
    const EHR_APPROVAL_LOCK = self::SYSTEM_EHR.'approval_lock:%s'; // 扭蛋抽奖锁 %s(1) = 考评表id

}

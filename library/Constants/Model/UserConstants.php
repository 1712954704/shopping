<?php
/**
 * 用户配置
 * User: Jack
 * Date: 2023/02/27
 */

namespace library\Constants\Model;

class UserConstants extends ModelConstants
{

    /**
     * 通用常量
    */
    const USER_LOGIN_LIMIT_TYPE_ERROR = 1; //用户登录失败
    const USER_LOGIN_LIMIT_TYPE_SUCCESS = 2; //用户登录、登出成功
    const USER_LOGIN_LIMIT_TYPE_INFO = 3; //用户登录信息


    // 性别 男
    const GENDER_BODY = 1;
    // 性别 女
    const GENDER_GIRL = 2;

    // 学历常量 小学
    const EDUCATION_GRADE_SCHOOL = 1;
    // 学历常量 初中
    const EDUCATION_JUNIOR_HIGH_SCHOOL = 2;
    // 学历常量 初中
    const EDUCATION_HIGH_SCHOOL = 3;
    // 学历常量 大专
    const EDUCATION_JUNIOR_COLLEGE = 4;
    // 学历常量 本科
    const education_bachelor = 5;
    // 学历常量 硕士
    const EDUCATION_MASTER = 6;
    // 学历常量 博士
    const EDUCATION_LEARNED_SCHOLAR = 7;

    // 学历map  学历 1=小学 2=初中 3=高中 4=大专 5=本科 6=硕士 7=博士
    const EDUCATION_MAP = [
        self::EDUCATION_GRADE_SCHOOL => '小学',
        self::EDUCATION_JUNIOR_HIGH_SCHOOL => '初中',
        self::EDUCATION_HIGH_SCHOOL => '高中',
        self::EDUCATION_JUNIOR_COLLEGE => '大专',
        self::education_bachelor => '本科',
        self::EDUCATION_MASTER => '硕士',
        self::EDUCATION_LEARNED_SCHOLAR => '博士',
    ];


    /**
     * 数据状态常量
    */
    const COMMON_STATUS_LOCK = 2;      // 状态 - 锁定
    const COMMON_STATUS_DISABLE = 3;      // 状态 - 禁用


    /**
     * redis缓存
    */
    const HASH_USER_INFO_PREFIX = \CacheConstants::MODULE_USER . 'info:';                  // 用户信息缓存 user:info:用户id
    const HASH_USER_AUTH_PREFIX = \CacheConstants::MODULE_USER . 'auth:';          // 用户权限信息缓存 user:auth:用户id
    const HASH_USER_ROUTE_PREFIX = \CacheConstants::MODULE_USER . 'route:';        // 用户路由表信息缓存(和前端对照使用) user:route:用户id

    // token key前缀
    const CACHE_REDIS_TOKEN_KEY_PREFIX = 'token:';

    //用户登录封禁限制 %s(1) 登录账号
    const HASH_USER_LOGIN_LIMIT = \CacheConstants::MODULE_USER.'user_login_limit:%s';
}

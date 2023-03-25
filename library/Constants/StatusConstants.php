<?php

namespace library\Constants;

/**
 * Class StatusConstants
 * @package library\AutoLoad\Constants
 *
 * 状态码设计参考微信公众号设计
 * https://developers.weixin.qq.com/doc/offiaccount/Getting_Started/Global_Return_Code.html
 */
class StatusConstants
{
    const SUCCESS = 0;
    // 错误
    const ERROR = 1;
    const ERROR_DATABASE = 500; // 系统错误:数据库错误
    const ERROR_GENERATE_CACHE_FAILED = 501; // 生成缓存失败
    const ERROR_PASSWORD_OR_ACCOUNT = 501001; // 密码登录失败
    const ERROR_PASSWORD_CHECK_FAIL = 501002; // 密码检测不通过
    const ERROR_ACCOUNT_CHECK_FAIL = 501003; // 用户名不存在，请联系系统管理员！
    const ERROR_CHECK_FAIL_PASSWORD = 501004; // 密码错误，请确认密码！
    const ERROR_SERVICE_EXCEPTION = 502; // 服务异常
    const ERROR_ACCESS_WX_API_EXCEPTION = 504;  // 访问微信API异常
    const ERROR_ACCESS_ROBOT_SERVICE_API_EXCEPTION = 505;  // 访问机器人API异常
    const ERROR_NEED_UPGRADE = 5001; // 需要升级app
    const ERROR_CUSTOM_APP_MSG = 5002; // 自定义app提示消息
    const ERROR_MIDDLEWARE_EXCEPTION = 5003; // 中间件异常 自定义app提示消息
    const ERROR_CACHE_MIDDLEWARE_EXCEPTION = 5004; // 缓存中间件异常
    const ERROR_CACHE_CONFIG_EXCEPTION = 5005; // 缓存配置异常
    const ERROR_GET_CONFIG_EXCEPTION = 5006; // 获取配置异常
    const ERROR_UPGRADE_APP_VERSION = 5007; // 请升级App版本
    const ERROR_UPGRADE_AUTH_LEVEL = 5008; // 请升级权限等级
    const ERROR_UPGRADE_AUTH_LOCK = 5009; // 用户已被锁定
    const ERROR_DATABASE_REPEAT_DELETE = 5010; // 数据不能重复删除
    const ERROR_UPGRADE_PASSWORD_ERROR = 5011; // 用户密码错误次数超过3次(包含)

    // 不合法错误范围段
    const ERROR_ILLEGAL = 40000;
    // 不合法参数
    const ERROR_ILLEGAL_PARAMS = 40001;
    const ERROR_INVALID_REQUEST_METHOD = 40002; // 无效的请求
    const ERROR_INVALID_PARAMS = 40003; // 无效的参数

    //未验证错误范围段(app接收到401会登出app)
    const ERROR_UNAUTHORIZED = 40100;
    //无效的token
    const ERROR_UNAUTHORIZED_TOKEN = 40101;

    // 被禁止的范围段
    const ERROR_FORBIDDEN = 40300;

    // 并发限制
    const ERROR_FORBIDDEN_CONCURRENCY_LIMIT = 40310;
    // 加锁失败
    const ERROR_LOCK_FAILED = 40311;
    // 用户未过审
    const ERROR_FORBIDDEN_UNCHECKED = 40312;
    // 不支持的支付方式
    const ERROR_FORBIDDEN_UNSUPPORTED_PAYMENT_METHOD = 40313;
    const ERROR_FORBIDDEN_CHECKING = 40314;                   // 审核中
    const ERROR_FORBIDDEN_CHECKED = 40315;                    // 已审核
    const ERROR_FORBIDDEN_USER_REVIEWED = 40316;        // 用户已经评审过该用户

    // 不存在的错误范围段
    const ERROR_NOT_FOUND = 40400;
    // 用户缓存信息不存在
    const ERROR_USER_REDIS_NOT_EXIST = 40401;
    // 找不到用户信息
    const ERROR_NOT_FOUND_USER_INFO = 40402;
    const ERROR_INVALID_PARAMS_STRUCTURE = 40403; // 无效的部门信息
    const ERROR_INVALID_PARAMS_HANDLERS = 40405; // 无效的审批操作者
    const ERROR_INVALID_ROLE_HR = 40406; // 无效的HR角色
    const ERROR_INVALID_CHECK_NOT_START = 40407; // 未开始的考评审核
    const ERROR_INVALID_USER_EMAIL = 40408; // 用户邮箱不存在
    const ERROR_INVALID_USER_EMAIL_CODE = 40409; // 用户邮箱重置验证码错误

    // 数据冲突
    const ERROR_DATA_CONFLICT = 40900;
    const ERROR_DATA_CONFLICT_CHILD_EXIST = 40901;  // 数据下有子集不能删除
    const ERROR_DATA_NUMERIC_VALUE_EXIST = 22003;  // 数据下有子集不能删除


    //版本过低
    const ERROR_VERSION_OUTDATED = 43101;

    //44200 ~ 44300 CancelRecordService 用户账号注销 返回的是403状态
    const ERROR_FORBIDDEN_USER_CANCEL_UNDERWAY = 44200; //注销中
    const ERROR_FORBIDDEN_USER_LOGOUT = 44201; //已注销
    const ERROR_FORBIDDEN_CANCEL_ACCOUNT_PROHIBIT = 44202; //注销账户限制（有未完成的条件）

    //成功未处理返回204范围
    const SUCCESS_UNHANDLED = 20400;

    /** 状态码转Http Code映射 */
    const STATUS_TO_CODE_MAPS = [
        self::ERROR_ILLEGAL_PARAMS                       => 400,
        self::ERROR_ILLEGAL                              => 400,
        self::ERROR_UNAUTHORIZED                         => 401,
        self::ERROR_UNAUTHORIZED_TOKEN                   => 401,
        self::ERROR_USER_REDIS_NOT_EXIST                 => 404,
        self::ERROR_FORBIDDEN                            => 403,
        self::ERROR_NOT_FOUND                            => 404,
        self::ERROR_NOT_FOUND_USER_INFO                  => 404,
        self::ERROR_INVALID_PARAMS_STRUCTURE             => 404,
        self::ERROR_INVALID_PARAMS_HANDLERS              => 404,
        self::ERROR_INVALID_ROLE_HR                      => 404,
        self::ERROR_INVALID_CHECK_NOT_START              => 404,
        self::ERROR_INVALID_USER_EMAIL                   => 404,
        self::ERROR_INVALID_USER_EMAIL_CODE              => 404,
        self::ERROR_DATA_CONFLICT                        => 409,
        self::ERROR_DATA_CONFLICT_CHILD_EXIST            => 409,
        self::ERROR_DATA_NUMERIC_VALUE_EXIST             => 409,
        self::ERROR_DATABASE                             => 500,
        self::ERROR                                      => 500,
        self::ERROR_GENERATE_CACHE_FAILED                => 500,
        self::ERROR_SERVICE_EXCEPTION                    => 500,
        self::ERROR_FORBIDDEN_CONCURRENCY_LIMIT          => 403,
        self::ERROR_NEED_UPGRADE                         => 501,
        self::ERROR_VERSION_OUTDATED                     => 501,
        self::ERROR_LOCK_FAILED                          => 403,
        self::ERROR_FORBIDDEN_UNCHECKED                  => 403,
        self::ERROR_CUSTOM_APP_MSG                       => 501,
        self::ERROR_FORBIDDEN_UNSUPPORTED_PAYMENT_METHOD => 403,
        self::ERROR_ACCESS_WX_API_EXCEPTION              => 500,
        self::ERROR_INVALID_REQUEST_METHOD               => 400,
        self::ERROR_INVALID_PARAMS                       => 400,
        self::ERROR_FORBIDDEN_CHECKING                   => 403,
        self::ERROR_FORBIDDEN_CHECKED                    => 403,
        self::ERROR_FORBIDDEN_USER_REVIEWED              => 403,
        self::ERROR_MIDDLEWARE_EXCEPTION                 => 501,
        self::ERROR_ACCESS_ROBOT_SERVICE_API_EXCEPTION   => 500,
        self::ERROR_CACHE_MIDDLEWARE_EXCEPTION           => 500,
        self::ERROR_CACHE_CONFIG_EXCEPTION               => 500,
        self::ERROR_GET_CONFIG_EXCEPTION                 => 500,
        self::ERROR_UPGRADE_APP_VERSION                  => 500,
        self::ERROR_UPGRADE_AUTH_LEVEL                   => 500,
        self::ERROR_UPGRADE_AUTH_LOCK                    => 500,
        self::ERROR_DATABASE_REPEAT_DELETE               => 500,
        self::ERROR_UPGRADE_PASSWORD_ERROR               => 500,
        self::ERROR_FORBIDDEN_USER_CANCEL_UNDERWAY       => 403,
        self::ERROR_FORBIDDEN_USER_LOGOUT                => 403,
        self::ERROR_PASSWORD_OR_ACCOUNT                  => 501,
        self::ERROR_PASSWORD_CHECK_FAIL                  => 501,
        self::ERROR_ACCOUNT_CHECK_FAIL                   => 501,
        self::ERROR_CHECK_FAIL_PASSWORD                  => 501,
        self::ERROR_FORBIDDEN_CANCEL_ACCOUNT_PROHIBIT    => 403,

    ];

    /** 状态码文案 **/
    const ERROR_TO_MSG_COPY = [
        self::ERROR_INVALID_USER_EMAIL                 => '邮箱未登记，NOT FOUND USER EMAIL，请联系管理员',
        self::ERROR_INVALID_USER_EMAIL_CODE            => '邮箱重置密码,验证码错误',
        self::ERROR_INVALID_CHECK_NOT_START            => 'NOT START CHECK',
        self::ERROR_CHECK_FAIL_PASSWORD                => '密码错误，请确认密码!',
        self::ERROR_ACCOUNT_CHECK_FAIL                 => '用户名不存在，请联系系统管理员!',
        self::ERROR_INVALID_ROLE_HR                    => 'Error Invalid Role Hr',
        self::ERROR_INVALID_PARAMS_HANDLERS            => 'Error Invalid Params Handlers',
        self::ERROR_INVALID_PARAMS_STRUCTURE           => 'Error Invalid Params Structure',
        self::ERROR_DATA_CONFLICT                      => 'Data Conflict',
        self::ERROR_DATA_CONFLICT_CHILD_EXIST          => 'Child Exist',
        self::ERROR_DATABASE_REPEAT_DELETE             => 'Repeat Delete',
        self::ERROR_UPGRADE_AUTH_LOCK                  => 'User is Lock',
        self::ERROR_UPGRADE_PASSWORD_ERROR             => 'Password Incorrect Three ',
        self::ERROR_UPGRADE_AUTH_LEVEL                 => 'Please Upgrade The Auth',
        self::ERROR_UPGRADE_APP_VERSION                => 'Please Upgrade The Version',
        self::ERROR_NEED_UPGRADE                       => '您当前的版本过低，请更新到最新版本App',
        self::ERROR_MIDDLEWARE_EXCEPTION               => '系统繁忙请稍后再试，由此给您带来的不便，我们深表歉意。',
        self::ERROR_ACCESS_ROBOT_SERVICE_API_EXCEPTION => '通知机器人服务失败',
        self::ERROR_CACHE_MIDDLEWARE_EXCEPTION         => 'Cache Middleware Exception',
        self::ERROR_CACHE_CONFIG_EXCEPTION             => 'Cache Config Exception',
        self::ERROR_GENERATE_CACHE_FAILED              => 'Generate Cache Failure',
        self::ERROR_GET_CONFIG_EXCEPTION               => 'Get Config Exception',
        self::ERROR_FORBIDDEN_CONCURRENCY_LIMIT        => 'Concurrency Limit',
        self::ERROR_DATABASE                           => 'Database Error',
        self::ERROR_PASSWORD_OR_ACCOUNT                => '手机号或密码错误，请您重试',
        self::ERROR_PASSWORD_CHECK_FAIL                => '旧密码与原密码不一致',
        self::ERROR_FORBIDDEN_CANCEL_ACCOUNT_PROHIBIT  => 'Cancel Account Prohibit',
        self::ERROR_FORBIDDEN_USER_LOGOUT              => 'User Logout',
        self::ERROR_FORBIDDEN_USER_CANCEL_UNDERWAY     => 'Account Cancel Underway',
        self::ERROR_NOT_FOUND_USER_INFO                => 'Not Found User Info',
        self::ERROR_INVALID_PARAMS                     => 'Invalid Param',
        self::ERROR_UNAUTHORIZED_TOKEN                 => 'Invalid Token',
        self::ERROR_ACCESS_WX_API_EXCEPTION            => 'Wx Api Exception',
        self::ERROR_DATA_NUMERIC_VALUE_EXIST           => 'Numeric value out of range',
        self::ERROR_INVALID_REQUEST_METHOD             => 'invalid request',
    ];

}

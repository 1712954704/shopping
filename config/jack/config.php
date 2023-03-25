<?php
/**
 * 这里的都是是不太敏感的配置，优先使用my_config.ini里的配置
 * 注意类型要与my_config.ini一致，以免出bug
 */

//线上环境-api-域名常量
//define('PROD_API_MICRO_WORLD_URL', 'https://api.onemicroworld.com');

return [
    /** 系统内部相关配置 */
    'default_time_zone' => 'PRC', // 默认时区

    'enable_record_timeout_sql' => 1, // 开启记录超时sql
    'sms_mob_limit' => 10, // 针对手机号获取验证码次数限制
    'sms_mob_limit_expire' => 86400, // 针对手机号获取验证码次数限制key过期时间
    'sms_ip_limit' => 100, // 针对ip获取验证码次数限制
    'sms_ip_limit_expire' => 86400, // 针对ip获取验证码次数限制key过期时间
    'system_type' => [
        1 => 'ehr',
    ],   // 系统类型对照表
    'route_system_type' => [
        1 => 'ehr',
    ],   // 系统类型对照表-路由
    'no_login' => [
        'api/user/login',
        'api/user/clear_user_lock',
        'api/user/register',
//        'api/file/add_file', // 需要上传者信息/需要验证
        'api/nation_list',
        'api/region',  // 获取地区
        'api/user/user_reset', // 重置用户缓存
        'api/user/get_staff_found_info', // 获取员工分布信息
        'api/structure/get_structure_list', // 搜索组织-部门树形结构
    ],  // 免登录接口
    // 需要默认赋予用户权限的路由
    'default_auth_routes' => [
        'api/user/reset_pwd', // 重置用户密码
        'api/notice', // 查看系统公告
        'api/file/add_file', // 需要上传者信息/需要验证
        'api/user/info',     // 获取用户信息
        'api/calendar', // 查看日程
        'api/user/get_new_staff_list', // 查看用户分布
    ],  // 免登录接口
    /** 业务配置 */
    'is_singapore'                       => 0,
    //用户安全
    'user_safe'=>[
        'default_password'                    => 'password@123',  // 用户密码(默认值)
        'login_verify_num'                    => 3,  // 用户密码登录错误超过次数开启验证码
        'login_lock_num'                      => 6,  // 用户密码登录错误超过锁定次数
        'login_fail_lock_time'=>CacheConstants::CACHE_EXPIRE_TIME_TWELVE_HOURS,//登录失败账号锁定12小时
    ],
    // 默认头像地址
    'default_avatar_url' => 'http://192.168.182.250:8022/img/2023-03-20/156C7EDA-15DE-DD54-1818-B845B202D788.png',
    // 上传相关
//    'img_domain_url'=>'https://www.bio-cloud.com.cn/img/', // 图片域名地址
//    'file_domain_url'=>'https://www.bio-cloud.com.cn/file/', // 文件域名地址
//    'upload_img_file' => dirname(__FILE__) . '/../../file/img/', // 用于后台上传图片确定存储位置
//    'upload_file' => dirname(__FILE__) . '/../../file/file/', // 用于后台上传图片确定存储位置
    // 上传相关
    'img_domain_url'=>'http://192.168.182.250:8022/img/', // 图片域名地址
    'file_domain_url'=>'http://192.168.182.250:8022/file/', // 文件域名地址
    'upload_img_file' => '/home/Item/oa/Code/Resources/file/img/', // 用于后台上传图片确定存储位置
    'upload_file' => '/home/Item/oa/Code/Resources/file/file/', // 用于后台上传图片确定存储位置
    // log相关
    'write_debug' => 1, // 是否写入bug
    'response_body_keep_length' =>1000, //body 长度
    // 超级管理员账号信息
    'admin' => [
        'account' => ['admin','bio-admin','bio-jack','bio-lijiao','jack','gongwei','liwei','denglijiao','xuzhen','guomengtao',
            'demo',
            'demot',
            'demos',
        ]  // 账号名称
    ]


];

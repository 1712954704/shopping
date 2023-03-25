<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */

namespace App\Http\Service\Common;

use App\Http\Manager\Common\UserManager;
use App\Http\Service\Hr\AssessmentService;
use App\Http\Service\ServiceBase;
use App\Models\Common\Nation;
use App\Models\Common\Position;
use App\Models\Common\Structure;
use App\Models\Common\User;
use App\Models\Common\UserInfo;
use App\Models\Common\UserRole;
use App\Models\Common\UserToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use library\Constants\Model\ModelConstants;
use library\Constants\Model\UserConstants;
use library\Constants\StatusConstants;
use library\Traits\Log;

class UserService extends ServiceBase
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 根据token获取用户信息
     * @param string $token 用户token
     * @param string $fields 字段名
     * @return array
     */
    public function get_user_info_by_token($token, $fields = '')
    {
        $return_data  = [
            'code' => 200,
            'data' => []
        ];
        $user_manager = new UserManager();
        $token_key    = $user_manager->get_token_key($token);
        // 判断key是否存在
        if (!$this->_redis->exists($token_key)) {
            $return_data['code'] = 201;
            return $return_data;
        }

        // 使用token获取用户缓存信息
        if ($fields) {
            $data = $this->_redis->hmget($token_key, $fields);  // 获取全部信息
        } else {
            $data = $this->_redis->hgetall($token_key);  // 获取全部信息
        }

        if (!$data) {  // token缓存不存在则查询数据库用户token是否存在及状态
            $where = [
                'token' => $token
            ];
            $list  = UserToken::where($where)->first();
            $list  = \Common::laravel_to_array($list);
            // 用户token存在则重新设置用户信息
            if ($list['status'] == UserToken::STATUS_NORMAL) {
                $data = [
                    'id'   => $list['user_id'],
                    'type' => $list['type'],
                ];
                $this->_redis->hmset($token_key, $data);  // 设置token信息
                $data = $this->_redis->hmget($token_key, $fields);  // 获取全部信息
            }
        }
        $return_data['data'] = $data;
        return $return_data;
    }

    /**
     * 根据用户id获取用户权限信息
     * @param string $user_id 用户id
     * @param array $fields 字段名
     * @return array
     */
    public function get_user_auth_info_by_id($user_id, $fields = [])
    {
        try {
            $user_manager = new UserManager();
            $redis_key    = $user_manager->get_user_auth_cache_key($user_id);
            // 添加超级管理员
//            $user_auth_info = [
//                'ehr' => ["*"]
//            ];
//            foreach($user_auth_info as &$item){
//                $item = json_encode($item,JSON_UNESCAPED_UNICODE);
//            }
//            $this->_redis->hMset($redis_key, $user_auth_info);
//            var_dump($user_auth_info);die();
            // 使用token获取用户缓存信息
            if ($fields) {
                $data = $this->_redis->hmget($redis_key, $fields);  // 获取全部信息
            } else {
                $data = $this->_redis->hgetall($redis_key);  // 获取全部信息
            }
            if (!$this->_redis->exists($redis_key) || !$data || array_values($fields) != array_keys($data)) {  // 用户缓存信息查不到则生成
                // todo 目前只获取了hr系统的权限 后续需要扩充
                $user_auth_info = $this->_inner_get_user_auth_info_for_cache($user_id);
                if ($user_auth_info) {
//                $user_auth_info_new = array_column($user_auth_info,'name'); // 只获取单列
                    // 数组转json存储
                    foreach ($user_auth_info as &$item) {
                        $item = json_encode($item, JSON_UNESCAPED_UNICODE);
                    }
                    $this->_redis->hMset($redis_key, $user_auth_info);
                    $data = $this->_redis->hgetall($redis_key);  // 获取全部信息
                }
            }
            // 解码
            foreach ($data as &$value) {
                $value = json_decode($value, true);
            }
            return $data;
        } catch (\Exception $e) {
            // 记录错误日志
        }
    }

    /**
     * 获取要存储到权限缓存里的用户信息数据
     * @scope 内部使用
     *
     * @param int $user_id 用户id
     * @param string $database_name 数据库连接
     *
     * @return array
     */
    public function _inner_get_user_auth_info_for_cache($user_id)
    {
        // 查询用户权限信息
        $sql                          = "select DISTINCT(a.id), a.name,a.type,a.code,a.pid,a.method,a.title,a.icon from common_auth_rule a left join common_role_auth_rule b on a.id = b.auth_rule_id and b.status = 1
        left join common_user_role c on b.role_id=c.role_id and c.status = 1 where a.status = 1 and c.user_id = " . $user_id;
        $result                       = DB::connection('mysql_common')->select($sql); // 查询公共库
        $return_data[NOW_SYSTEM_TYPE] = $this->clear_auth_array_data(array_map('get_object_vars', $result));
        return $return_data;
    }

    /**
     * 整理路由规则数据格式
     * @param array $data
     * @return array
     */
    public function clear_auth_array_data($data)
    {
        $arr = [];
        foreach ($data as $item) {
            if ($item['name']) {
                $arr[$item['name']][] = $item['method'];
            }
        }
        return $arr;
    }

    /**
     * 缓存token
     *
     * @param $token
     * @param array $data
     * @param int $expire_time
     *
     * @return bool
     */
    public function cache_token($token, array $data, $expire_time = \CacheConstants::CACHE_EXPIRE_TIME_HALF_TWO_HOUR)
    {
        if (empty($token)) return false;
        $token = $this->get_token_key($token);
        // 最近一次刷新token时间
        $data['refresh_token_time'] = time();
        $redis                      = $this->get_redis();
        $bool                       = $redis->hMset($token, $data);
        if ($bool) {
            $redis->expire($token, $expire_time);
        }
        return $bool;
    }

    /**
     * 刷新token过期时间
     *
     * @param $token
     * @param int $expire_time
     * @param int $refresh_token_time 最近一次刷新token时间
     *
     * @return int
     */
    public function refresh_token_expire($token, $refresh_token_time, $expire_time = \CacheConstants::CACHE_EXPIRE_TIME_HALF_TWO_HOUR)
    {
        if (empty($token)) return 0;
        // 一个小时刷新一次，避免频繁刷新
        if ((time() - $refresh_token_time) >= \CacheConstants::CACHE_EXPIRE_TIME_HALF_ONE_HOUR) {
            $user_manager = new UserManager();
            return $this->_redis->expire($user_manager->get_token_key($token), $expire_time);
        }
        return 0;
    }

    /**
     * 登录
     *
     * @param string $account
     * @param string $pwd
     * @param string $type 登录系统类型
     * @return array
     */
    public function login($account, $pwd, $type)
    {
        try {
            $user_manager = new UserManager();
            $my_config    = \Common::get_config();
            // 检测用户是否锁定
            $key         = $user_manager->get_last_key(UserConstants::HASH_USER_LOGIN_LIMIT, $account);
            $expire_time = \Common::get_config('user_safe')['login_fail_lock_time'];
            $is_lock     = $this->_redis->hMGet($key, ['is_lock'])['is_lock'] ?? 0;
            if ($is_lock == 1) {
                throw new \Exception('', StatusConstants::ERROR_UPGRADE_AUTH_LOCK);
            }
            // 查询用户信息
            $where     = [
                'account' => $account,
                'status'  => UserConstants::COMMON_STATUS_NORMAL,
            ];
            $user_info = User::where($where)->first();
            if (!$user_info) {
                throw new \Exception('', StatusConstants::ERROR_ACCOUNT_CHECK_FAIL);
            }
            // 检测密码
            if ($user_info->pwd != sha1($user_info->salt . sha1($pwd))) {
                // 密码错误次数记录 超过次数则锁定
                $lock_data        = $this->user_login_limit($expire_time, $account, UserConstants::USER_LOGIN_LIMIT_TYPE_ERROR);
                $login_verify_num = \Common::get_config('user_safe')['login_verify_num'];
                $login_lock_num   = \Common::get_config('user_safe')['login_lock_num'];
                if ($lock_data['num'] >= $login_verify_num && $lock_data['num'] < $login_lock_num) {
                    throw new \Exception('', StatusConstants::ERROR_UPGRADE_PASSWORD_ERROR);
                } elseif ($lock_data['num'] >= $login_lock_num) {
                    $this->lock_user(\Common::laravel_to_array($user_info));
                    throw new \Exception('', StatusConstants::ERROR_UPGRADE_AUTH_LOCK);
                } else {
                    throw new \Exception('', StatusConstants::ERROR_CHECK_FAIL_PASSWORD);
                }
            }
            // 更新用户token 1.生成用户token 2.查询用户token是否存在,存在更新不存在则创建
            $where      = [
                'user_id' => $user_info->id,
            ];
            $result     = UserToken::where($where)->first();
            $result     = \Common::laravel_to_array($result);
            $token_data = [
                'token'  => \Common::gen_token($user_info->id),
                'status' => UserConstants::COMMON_STATUS_NORMAL,
                'type'   => $type,
            ];
            if ($result) {
                UserToken::where($where)->update($token_data);
            } else {
                $token_data['user_id'] = $user_info->id;
                UserToken::where($where)->insert($token_data);
            }
            // 第一次登录无token
            if ($result && isset($result['token'])) {
                $token_key = $user_manager->get_token_key($result['token']);
                // 查询旧token是否存在,并删除 创建新token保存
                if ($this->_redis->exists($token_key)) {
                    $this->_redis->del($token_key);
                }
            }
            $data          = [
                'id'   => $user_info->id,
                'type' => $my_config['system_type'][$type],
            ];
            $new_token_key = $user_manager->get_token_key($token_data['token']);
            $this->_redis->hmset($new_token_key, $data);  // 设置token信息
            // 登录成功操作处理
            $this->user_login_limit($expire_time, $account, UserConstants::USER_LOGIN_LIMIT_TYPE_SUCCESS);
            $this->return_data['data']['token'] = $token_data['token'];
        } catch (\Exception $e) {
            $code = $e->getCode();
            if (in_array($code, array_keys(StatusConstants::STATUS_TO_CODE_MAPS))) {
                $this->return_data['code'] = $code;
            } else {
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        }
        return $this->return_data;
    }

    /**
     * 登录限制redis处理
     * @param int $expire_time 登录账号
     * @param string $account 登录账号
     * @param int $type 1登录失败处理  2登录成功处理  3检测redis记录
     */
    public function user_login_limit($expire_time, $account, $type)
    {
        $user_manager = new UserManager();
        //检测redis是否存在对应的键值，不存在存默认1，存在自增
        $key  = $user_manager->get_last_key(UserConstants::HASH_USER_LOGIN_LIMIT, $account);
        $data = [];
        if ($type == 1) {
            $result         = $this->_redis->hIncrBy($key, 'num', 1);
            $login_lock_num = \Common::get_config('user_safe')['login_lock_num'];
            //锁定账号
            if ($result >= $login_lock_num) {
                $this->_redis->hMSet($key, ['is_lock' => 1]);
                //有效期12小时3600*12
                $this->_redis->expire($key, $expire_time);
            }
            $data = [
                'num'     => $result,
                'is_lock' => $result['is_lock'] ?? 0,
            ];
        } else if ($type == 2) {
            //重置redis值
            $this->_redis->hMSet($key, [
                'num'     => 0,
                'is_lock' => 0
            ]);
            $this->_redis->expire($key, $expire_time);
        } else if ($type == 3) {
            $result = $this->_redis->hGetAll($key);
            if (isset($result['num'])) {
                $data = [
                    'num'     => $result['num'],
                    'is_lock' => $result['is_lock'] ?? 0,
                ];
            }
        }
        return $data;
    }

    /**
     * 锁定用户
     * @param array $user_info
     * @return mixed
     */
    public function lock_user($user_info)
    {
        // 用户锁定操作 todo 后续需要修改为mq异步操作
        User::where('id', $user_info['id'])->update(['status' => UserConstants::COMMON_STATUS_LOCK]);
    }

    /**
     * 清除用户锁定(缓存)
     * @param string $account 用户账号
     * @return array
     * @author jack
     * @dateTime 2023-03-02 13:21
     */
    public function clear_user_lock($account)
    {
        try {
            $user_manager = new UserManager();
            $expire_time  = \Common::get_config('user_safe')['login_fail_lock_time'];
            $key          = $user_manager->get_last_key(UserConstants::HASH_USER_LOGIN_LIMIT, $account);
            //重置redis值
            $this->_redis->hMSet($key, [
                'num'     => 0,
                'is_lock' => 0
            ]);
            $this->_redis->expire($key, $expire_time);
            // 重置用户状态为正常
            $where = [
                'account' => $account
            ];
            User::where($where)->update(['status' => ModelConstants::COMMON_STATUS_NORMAL]);
        } catch (\Exception $e) {
            $code = $e->getCode();
            if (in_array($code, array_keys(StatusConstants::STATUS_TO_CODE_MAPS))) {
                $this->return_data['code'] = $code;
            } else {
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        }
        return $this->return_data;
    }

    /**
     * 获取用户路由表信息
     * @param string $user_id 用户token
     * @param string $system_type 系统类型
     * @return array
     * @author jack
     * @dateTime 2023-03-02 13:21
     */
    public function user_route_info($user_id, $system_type)
    {
        try {
            // 获取用户权限缓存信息
            $my_config    = \Common::get_config();
            $type         = $my_config['system_type'][$system_type];
            $user_manager = new UserManager();
            $redis_key    = $user_manager->get_user_route_cache_key($user_id);
            // 获取对应系统的路由表
            $fields = [$my_config['system_type'][$system_type]];
            // 使用token获取用户缓存信息
            if ($fields) {
                $data = $this->_redis->hmget($redis_key, $fields);  // 获取全部信息
            } else {
                $data = $this->_redis->hgetall($redis_key);  // 获取全部信息
            }
            if (!$this->_redis->exists($redis_key) || !$data) {  // 用户缓存信息查不到则生成
                $user_route_info = $this->_inner_get_user_routes_info_for_cache($user_id);
                if ($user_route_info) {
                    $tree = $this->getTree($user_route_info, 0);
                    if ($tree) {
                        $route_new[$type] = $tree;
                        // 数组转json存储
                        foreach ($route_new as &$item) {
                            $item = json_encode($item, JSON_UNESCAPED_UNICODE);
                        }
                        $this->_redis->hMset($redis_key, $route_new);
                        $data = $this->_redis->hgetall($redis_key);  // 获取全部信息
                    }
                }
            }
            // 解码
            foreach ($data as &$value) {
                $value = json_decode($value, true);
            }
            $this->return_data['data'] = $data[$type] ?? [];
        } catch (\Exception $e) {
            $code = $e->getCode();
            if (in_array($code, array_keys(StatusConstants::STATUS_TO_CODE_MAPS))) {
                $this->return_data['code'] = $code;
            } else {
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        }
        return $this->return_data;
    }

    /**
     * 获取要存储到权限缓存里的用户信息数据
     * @scope 内部使用
     *
     * @param int $user_id 用户id
     * @param string $database_name 数据库连接
     *
     * @return array
     */
    public function _inner_get_user_routes_info_for_cache($user_id = null)
    {
        // 查询用户权限信息
        if ($user_id) {
            $sql = "select DISTINCT(a.id),a.code,a.pid,a.type,a.title,a.icon,a.order from common_auth_rule a left join common_role_auth_rule b on a.id = b.auth_rule_id
        left join common_user_role c on b.role_id=c.role_id where a.type != 5 and c.user_id = " . $user_id;
        } else {
            $sql = "select DISTINCT(a.id),a.code,a.pid,a.type,a.title,a.icon,a.order from common_auth_rule a left join common_role_auth_rule b on a.id = b.auth_rule_id
        left join common_user_role c on b.role_id=c.role_id where a.type != 5 ";
        }
        $result = DB::connection('mysql_common')->select($sql);
        return array_map('get_object_vars', $result);
    }

    /**
     * 整理路由表无限级分类
     * @param array $data
     * @param int $pid
     * @return array
     */
    public function getTree($data, $pid = 0)
    {
        $tree = [];
        foreach ($data as $k => $v) {
            if ($v['pid'] == $pid) {
                $arr['code']  = $v['code'];
                $arr['type']  = $v['type'];
                $arr['title'] = $v['title'];
                $arr['icon']  = $v['icon'];
                $arr['order'] = $v['order'];
                $arr['child'] = $this->getTree($data, $v['id']);
                $tree[]       = $arr;
                unset($arr);
                unset($data[$k]);
            }
        }
        return $tree;
    }

    /**
     * 整理路由表无限级分类
     * @param array $data
     * @param int $pid
     * @return array
     */
    public function get_tree_by_department($data, $pid = 0)
    {
        $tree = [];
        foreach ($data as $k => $v) {
            if ($v['pid'] == $pid) {
                $arr['name']          = $v['name'];
                $arr['user_id']       = $v['user_id'];
                $arr['department_id'] = $v['department_id'];
                $arr['child']         = $this->get_tree_by_department($data, $v['department_id']);
                $tree[]               = $arr;
                unset($arr);
                unset($data[$k]);
            }
        }
        return $tree;
    }

    /**
     * 注册 todo 缺少更新用户缓存信息步骤
     * @param array $params
     * @param int $type 1=新增 2=编辑
     * @return mixed
     * @author jack
     * @dateTime 2023-03-03 12:55
     */
    public function register_or_edit($params, $type)
    {
        try {
            $user_id = $params['user_id'];
            // 主表信息
            $user = [
                'account'        => $params['account'] ?? '',
                'name'           => $params['name'] ?? '',
                'gender'         => $params['gender'] ?? 0,
                'job_number'     => $params['job_number'] ?? '',
                'email'          => $params['email'] ?? '',
                'structure_id'   => $params['structure_id'] ?? 0,
                'department_id'  => $params['department_id'] ?? 0,
                'manager_id'     => $params['manager_id'] ?? 0,
                'position_id'    => $params['position_id'] ?? 0,
                'job_type'       => $params['job_type'] ?? 0,
                'status'         => $params['status'] ?? 0,
                'phone'          => $params['phone'] ?? '',
                'landline_phone' => $params['landline_phone'] ?? '',
                'avatar'         => $params['avatar'] ?? '',
            ];
            // 副表信息
            $user_info = [
                'nation_id'                  => $params['nation_id'] ?? 0,
                'native_place'               => $params['native_place'] ?? 0,
                'entry_date'                 => $params['entry_date'] ?? null,
                'become_data'                => $params['become_data'] ?? null,
                'id_number'                  => $params['id_number'] ?? '',
                'birth_date'                 => $params['birth_date'] ?? null,
                'education'                  => $params['education'] ?? 0,
                'address'                    => $params['address'] ?? '',
                'emergency_contact_name'     => $params['emergency_contact_name'] ?? '',
                'emergency_contact_relation' => $params['emergency_contact_relation'] ?? '',
                'emergency_contact_phone'    => $params['emergency_contact_phone'] ?? '',
                'emergency_contact_address'  => $params['emergency_contact_address'] ?? '',
                'remark'                     => $params['remark'] ?? '',
            ];

            $user = $this->del_array_null($user);
            $user_info = $this->del_array_null($user_info);

            // 开启事务
            DB::connection('mysql_common')->beginTransaction();
            switch ($type) {
                case 1:  // 新增
                    // 加密用户密码 使用配置默认密码
                    $my_config            = \Common::get_config();
                    $salt                 = \Common::get_random_str(4);
                    $user['uuid']         = \Common::guid();
                    $user['salt']         = $salt;
                    $user['pwd']          = sha1($salt . sha1($my_config['user_safe']['default_password'])); // 初始密码为默认设置;
                    $create_user_result   = User::create($user);
                    $user_id              = $create_user_result->id;
                    $user_info['user_id'] = $user_id;
                    UserInfo::create($user_info);  // todo 需要修改为创建完成后设置缓存 改为rabbitmq操作
                    // 用户角色关系 创建关联关系
                    $role_id = $params['role_id'];
                    if ($role_id && is_array($role_id)) {
                        $user_role_insert_arr = [];
                        foreach ($role_id as $item) {
                            $user_role_insert_arr[] = [
                                'user_id' => $create_user_result->id,
                                'role_id' => $item,
                            ];
                        }
                        UserRole::insert($user_role_insert_arr);
                    }
                    break;
                case 2:
                    $where = [
                        'id' => $user_id,
                    ];
                    if ($user){
                        User::where($where)->update($user);
                    }
                    if ($user_info){
                        UserInfo::where($where)->update($user_info);  // todo 需要修改为创建完成后设置缓存 改为rabbitmq操作
                    }
                    // 用户角色关系 先删除再创建关联关系
                    $role_id = $params['role_id'];
                    if ($role_id && is_array($role_id)) {
                        UserRole::where(['user_id' => $user_id])->update(['status' => ModelConstants::COMMON_STATUS_DELETE]);
                        $user_role_insert_arr = [];
                        foreach ($role_id as $item) {
                            $user_role_insert_arr[] = [
                                'user_id' => $user_id,
                                'role_id' => $item,
                            ];
                        }
                        UserRole::insert($user_role_insert_arr);
                    }
                    break;
                default:
                    break;
            }
            DB::connection('mysql_common')->commit();
        } catch (\Exception $e) {
            DB::connection('mysql_common')->rollBack();
            var_dump($e->getLine());
            var_dump($e->getMessage());
            die();
            // todo 记录log
            $code = $e->getCode();
            if (in_array($code, array_keys(StatusConstants::STATUS_TO_CODE_MAPS))) {
                $this->return_data['code'] = $code;
            } else {
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        } finally {
            // 更新用缓存
            $this->user_reset([$user_id], 1);
            return $this->return_data;
        }
    }

    /**
     * 重置用户缓存
     * @param array $ids
     * @param int $system_type 1=hr 默认1
     * @return array
     */
    public function user_reset($ids, $system_type = 1)
    {
        try {
            $file_service = new FileService();
            $my_config    = \Common::get_config();
            $type         = $my_config['system_type'][$system_type];
            $user_manager = new UserManager();
            foreach ($ids as $user_id) {
                // 更新info信息
                $redis_key_info = $user_manager->get_user_cache_key($user_id);
                $user_info      = $this->_inner_get_user_info_for_cache($user_id);
//                var_dump($user_info);die();
                if ($user_info) {
                    // 数组转json存储
//                    foreach($user_info as $item){
//                        //根据id获取name todo暂时写在此处,rabbitmq部署好后需要到更改回调队列里
//                        $item['structure_name'] = '';
//                        $item['department_name'] = '';
//                        if ($item['structure_id']){
//                            $item['structure_name'] = Structure::find($item['structure_id'])['name'];
//                        }
//                        if ($item['department_id']){
//                            $item['department_name'] = Structure::find($item['department_id'])['name'];
//                        }

//                        $item = json_encode($item);
//                    }

                    //根据id获取name todo暂时写在此处,rabbitmq部署好后需要到更改回调队列里
                    $user_info['structure_name']  = '';
                    $user_info['department_name'] = '';
                    $user_info['position_name']   = '';
                    $user_info['nation_name']     = '';
                    // 设置用户头像地址 没有则设置为默认头像地址
                    $user_info['avatar'] = $file_service->get_file_url($user_info['avatar'])['data']['file_url'] ?? $my_config['default_avatar_url'];
                    // 组织名称
                    if (isset($user_info['structure_id']) && $user_info['structure_id']) {
                        $user_info['structure_name'] = \Common::laravel_to_array(Structure::find($user_info['structure_id']))['name'] ?? '';
                    } else {
                        $user_info['structure_name'] = '';
                    }
                    // 部门
                    if (isset($user_info['department_id']) && $user_info['department_id']) {
                        $department                   = \Common::laravel_to_array(Structure::find($user_info['department_id']));
                        $user_info['department_name'] = $department['name'] ?? ''; // 部门名称
                        $user_info['manager_name']    = $this->get_user_info_by_id($department['manager_id'], ['name'])['data']['name'] ?? '';  // 直属领导名称
                        $user_info['handler_name']    = $this->get_user_info_by_id($department['handler_id'], ['name'])['data']['name'] ?? '';  // 部门负责人名称
                    } else {
                        $user_info['department_name'] = '';
                        $user_info['manager_name']    = '';
                        $user_info['handler_name']    = '';
                    }
                    // 职位名称
                    if (isset($user_info['position_id']) && $user_info['position_id']) {
                        $user_info['position_name'] = \Common::laravel_to_array(Position::find($user_info['position_id']))['name'] ?? '';
                    } else {
                        $user_info['position_name'] = '';
                    }
                    // 民族名称
                    if (isset($user_info['nation_id']) && $user_info['nation_id']) {
                        $user_info['nation_name'] = \Common::laravel_to_array(Nation::find($user_info['nation_id']))['name'] ?? '';
                    } else {
                        $user_info['nation_name'] = '';
                    }

                    // 学历名称
                    if (isset($user_info['education']) && $user_info['education']) {
                        $user_info['education_name'] = UserConstants::EDUCATION_MAP[$user_info['education']] ?? '';
                    } else {
                        $user_info['education_name'] = '';
                    }

                    // 获取用户角色名
                    $sql    = "select a.id,a.name from common_role a left join common_user_role b on a.id = b.role_id where b.user_id = $user_id group by a.id";
                    $result = DB::connection('mysql_common')->select($sql);
                    if ($result) {
                        $user_info['role_name'] = implode(',', array_column(array_map('get_object_vars', $result), 'name'));
                        $user_info['role_id'] = json_encode(array_column(array_map('get_object_vars', $result), 'id'),JSON_UNESCAPED_UNICODE);
                    } else {
                        $user_info['role_name'] = '';
                        $user_info['role_id'] = [];
                    }
                    // 获取HR角色组用户名称
                    $sql    = "select a.id,b.user_id,c.name from common_role a left join common_user_role b on a.id = b.role_id left join common_user c on b.user_id = c.id where a.name = 'HR'";
                    $result = DB::connection('mysql_common')->select($sql);
                    $result = array_map('get_object_vars', $result);
                    if ($result) {
                        $user_info['hr_name'] = implode(',', array_column($result, 'name'));
                    } else {
                        $user_info['hr_name'] = '';
                    }
                    // 获取用户下属
                    $sql    = "select a.id department_id,a.pid,b.id user_id,b.name from common_structure a left join common_user b on a.id = b.department_id where a.manager_id = $user_id or a.handler_id = $user_id or a.duty_id = $user_id";
                    $result = DB::connection('mysql_common')->select($sql);
                    $result = array_map('get_object_vars', $result);
                    if ($result) {
//                        var_dump($this->get_tree_by_department($result));die();
//                        $user_info['subaltern_tree'] = $this->get_tree_by_department($result);
//                        $user_info['subaltern_tree'] = json_encode($this->get_tree_by_department($result),JSON_UNESCAPED_UNICODE);
                        $user_info['subaltern_tree'] = json_encode($result, JSON_UNESCAPED_UNICODE);
                    } else {
                        $user_info['subaltern_tree'] = json_encode([], JSON_UNESCAPED_UNICODE);
                    }
                    $this->_redis->hMset($redis_key_info, $user_info);
                }
                // 更新auth权限信息
                $redis_key_auth = $user_manager->get_user_auth_cache_key($user_id);
                // todo 目前只获取了hr系统的权限 后续需要扩充
                $user_auth_info = $this->_inner_get_user_auth_info_for_cache($user_id);
                if ($user_auth_info) {
                    // 判断是否为超级管理员
                    if (in_array($user_info['account'], $my_config['admin']['account'])) {
                        // 添加超级管理员权限
                        $user_auth_info = [
                            'ehr' => ["*"] // ehr系统权限
                        ];
                        // todo 后续修改为以下方式
//                        $user_auth_info = [];
//                        $admin_auth = ["*"];
//                        $system_type = $my_config['system_type'];
//                        foreach ($system_type as $key => $val){
//                            $user_auth_info[$val] = $admin_auth;
//                        }
                    }
                    // 数组转json存储
                    foreach ($user_auth_info as &$item) {
                        $item = json_encode($item, JSON_UNESCAPED_UNICODE);
                    }
                    $this->_redis->hMset($redis_key_auth, $user_auth_info);
                }
                // 更新用户路由表信息 route
                $redis_key_routes = $user_manager->get_user_route_cache_key($user_id);
                // 判断是否为超级管理员
                if (in_array($user_info['account'], $my_config['admin']['account'])) {
                    // 添加超级管理员权限
                    $user_route_info = $this->_inner_get_user_routes_info_for_cache();
                } else {
                    $user_route_info = $this->_inner_get_user_routes_info_for_cache($user_id);
                }
                if ($user_route_info) {
                    $tree = $this->getTree($user_route_info, 0);
                    if ($tree) {
                        $tmp = array_column($tree, 'order');
//                        array_multisort($tmp, SORT_ASC, $tree);  // 正序
                        array_multisort($tmp, SORT_DESC, $tree); // 倒序
                        $route_new[$type] = $tree;
                        // 数组转json存储
                        foreach ($route_new as &$item) {
                            $item = json_encode($item, JSON_UNESCAPED_UNICODE);
                        }
                        $this->_redis->hMset($redis_key_routes, $route_new);
                    }
                }
            }
        } catch (\Exception $e) {
            var_dump($e->getLine());
            var_dump($e->getMessage());
            die();
            // todo 记录log
//            $code = $e->getCode();
//            if (in_array($code,array_keys(StatusConstants::STATUS_TO_CODE_MAPS))){
//                $this->return_data['code'] = $code;
//            }else{
//                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
//            }
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        } finally {
            return $this->return_data;
        }
    }

    /**
     * 获取要存储到缓存里的用户信息数据
     * @scope 内部使用
     *
     * @param int $user_id 用户id
     *
     * @return array
     */
    public function _inner_get_user_info_for_cache($user_id)
    {
        $user_model = new User();
        $user       = $user_model->get_user_by_id($user_id, UserConstants::COMMON_STATUS_NORMAL);
        $user       = \Common::laravel_to_array($user);
        // 查询副表用户信息
        $user_info_model = new UserInfo();
        $user_info       = $user_info_model->get_user_info($user_id);
        $user_info       = \Common::laravel_to_array($user_info);
        // 合并用户信息数组并返回
        return array_merge($user, $user_info);
    }

    /**
     * 检测user主表使user_info副表与主表保持一致
     */
    public function check_user_to_unity()
    {
        try {
            // 获取usr表数据
            $user_ids = User::select(['id'])->get();
            $user_ids = array_column(\Common::laravel_to_array($user_ids), 'id');
            // 获取副表数据
            $user_info_ids = UserInfo::select(['user_id'])->get();
            $user_info_ids = array_column(\Common::laravel_to_array($user_info_ids), 'user_id');
            // 获取差集
            $array_diff = array_diff($user_ids, $user_info_ids);
            // 更新差集数据到副表
            if ($array_diff) {
                $data_insert = [];
                foreach ($array_diff as $item) {
                    $data_insert[] = [
                        'user_id' => $item
                    ];
                }
                UserInfo::insert($data_insert);
            }
        } catch (\Exception $e) {
            var_dump($e->getLine());
            var_dump($e->getMessage());
            die();
        } finally {
            return $this->return_data;
        }
    }

    /**
     * 删除用户
     * @param string $user_id 用户id
     * @return array
     * @author jack
     * @dateTime 2023-03-02 13:21
     */
    public function user_del($user_id)
    {
        try {
            $where['id'] = $user_id;
            DB::connection('mysql_common')->beginTransaction();
            User::where($where)->update(['status' => ModelConstants::COMMON_STATUS_DELETE]);
            DB::connection('mysql_common')->commit();
        } catch (\Exception $e) {
            DB::connection('mysql_common')->rollBack();
            $code = $e->getCode();
            if (in_array($code, array_keys(StatusConstants::STATUS_TO_CODE_MAPS))) {
                $this->return_data['code'] = $code;
            } else {
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        }
        return $this->return_data;
    }


    /**
     * 根据旧密码重置用户密码
     * @param array $params 密码参数
     * @param string $user_id 用户id
     * @return array
     * @author jack
     * @dateTime 2023-03-06 18:10
     */
    public function user_reset_pwd($params, $user_id)
    {
        try {
            $old_pwd    = $params['old_pwd'];
            $pwd        = $params['pwd'];
            $repeat_pwd = $params['repeat_pwd'];
            // 获取用户缓存信息
            $user_info = $this->get_user_info_by_id($user_id)['data'];
            // 检测旧密码是否正确
            $old_pwd = sha1($user_info['salt'] . sha1($old_pwd));
            if ($old_pwd != $user_info['pwd']) {
                throw new \Exception('', StatusConstants::ERROR_PASSWORD_CHECK_FAIL);
            }
            // 检测两次密码是否一致
            if ($pwd != $repeat_pwd) {
                throw new \Exception('', StatusConstants::ERROR_PASSWORD_CHECK_FAIL);
            }
            $new_pwd     = sha1($user_info['salt'] . sha1($pwd));
            $where['id'] = $user_id;
            DB::connection('mysql_common')->beginTransaction();
            User::where($where)->update(['pwd' => $new_pwd]);
            DB::connection('mysql_common')->commit();
        } catch (\Exception $e) {
            DB::connection('mysql_common')->rollBack();
            $code = $e->getCode();
            if (in_array($code, array_keys(StatusConstants::STATUS_TO_CODE_MAPS))) {
                $this->return_data['code'] = $code;
            } else {
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        } finally {
            $this->user_reset([$user_id]);
            return $this->return_data;
        }
    }

    /**
     * 根据用户id获取用户信息
     * @param string $user_id 用户id
     * @param array $fields 字段名
     * @return array
     */
    public function get_user_info_by_id($user_id, $fields = [])
    {
        $return_data  = [
            'code' => 200,
            'data' => []
        ];
        $user_manager = new UserManager();
        $redis_key    = $user_manager->get_user_cache_key($user_id);

        // 使用token获取用户缓存信息
        if ($fields) {
            $data = $this->_redis->hmget($redis_key, $fields);  // 获取全部信息
        } else {
            $data = $this->_redis->hgetall($redis_key);  // 获取全部信息
        }
        if (!$data && !$this->_redis->exists($redis_key)) {  // 用户缓存信息查不到则生成
            $user_info = $this->_inner_get_user_info_for_cache($user_id);
            if ($user_info) {
//                // 数组转json存储
//                foreach($user_info as &$item){
//                    $item = json_encode($item);
//                }
                $this->_redis->hMset($redis_key, $user_info);
                $data = $this->_redis->hgetall($redis_key);  // 获取全部信息
            }
        }
        // 解码
//        foreach ($data as $key => &$value){
//            if (isset($value['subaltern_tree'])){
//                var_dump($value['subaltern_tree']);die();
//                $value['subaltern_tree'] = json_decode($value['subaltern_tree'],true);
//            }
//        }

        if (isset($data['subaltern_tree'])) {
            $data['subaltern_tree'] = json_decode($data['subaltern_tree'], true);
        }
        if (isset($data['role_id'])) {
            $data['role_id'] = json_decode($data['role_id'], true);
        }
        $return_data['data'] = $data;
        return $return_data;
    }

    /**
     * 重置用户密码为默认密码
     * @param array $params 密码参数
     * @param string $user_id 用户id
     * @return array
     * @author jack
     * @dateTime 2023-03-06 18:10
     */
    public function user_reset_default_pwd($user_id)
    {
        try {
            // 获取用户缓存信息
            $user_info   = $this->get_user_info_by_id($user_id)['data'];
            $my_config   = \Common::get_config();
            $salt        = \Common::get_random_str(4);
            $new_pwd     = sha1($user_info['salt'] . sha1($my_config['user_safe']['default_password']));
            $where['id'] = $user_id;
            DB::connection('mysql_common')->beginTransaction();
            User::where($where)->update(['salt' => $salt, 'pwd' => $new_pwd]);
            DB::connection('mysql_common')->commit();
        } catch (\Exception $e) {
            DB::connection('mysql_common')->rollBack();
            $code = $e->getCode();
            if (in_array($code, array_keys(StatusConstants::STATUS_TO_CODE_MAPS))) {
                $this->return_data['code'] = $code;
            } else {
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        }
        return $this->return_data;
    }

    /**
     * 获取用户列表
     * @param array $params [
     *      name => xxx   // 待搜索的值
     * ]
     * @param int $id
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function get_list($params, $id = null, $offset = 0, $limit = 10)
    {
        $key_word      = $params['key_word'] ?? '';
        $job_type        = $params['job_type'] ?? '';
        $department_id   = $params['department_id'] ?? '';
        $where = "1 and is_admin = 0 ";
        if ($id) {
            $where .= " and a.id = " .$id;
        }
        if ($job_type) {
            $where .= " and a.job_type = " . $job_type;
        }

        if ($department_id) {
            $where .= " and (a.department_id = " . $department_id . " or a.structure_id = " . $department_id . ")";
        }

        if ($key_word) {
            $where .= " and (a.job_number like '%" . $key_word . "%' or a.name like '%" . $key_word . "%')";
        }
        $limit = "LIMIT $limit OFFSET $offset";
        try {
            $time = date('Y-m-d');
            // 只获取id 然后从缓存获取用户信息
            $sql = "SELECT a.id FROM common_user a WHERE $where ORDER BY a.created_at DESC " . $limit;
            $sql_count = "SELECT count(1) total FROM common_user a WHERE $where ORDER BY a.created_at DESC ";
            $result = DB::connection('mysql_common')->select($sql);
            $result_total = DB::connection('mysql_common')->select($sql_count);
            $result = array_map('get_object_vars', $result);
            $result_total = array_map('get_object_vars', $result_total);

            $this->return_data['data']['total'] = $result_total[0]['total'];
            $this->return_data['data']['data'] = $result;
            foreach ($this->return_data['data']['data'] as &$item) {
                // 获取用户缓存信息
                $user_info             = $this->get_user_info_by_id($item['id'])['data'] ?? '';
                $item['job_number']    = $user_info['job_number'] ?? '';
                $item['account']       = $user_info['account'] ?? '';
                $item['name']          = $user_info['name'] ?? '';
                $item['phone']         = $user_info['phone'] ?? '';
                $item['job_type']      = $user_info['job_type'] ?? '';
                $item['department_id'] = $user_info['department_id'] ?? '';

                $item['entry_date'] = isset($user_info['entry_date']) && !empty($user_info['entry_date']) ? intval($user_info['entry_date']) : '';
                if ($item['entry_date']) {  // 计算时间差值
                    list($year, $month, $days) = $this->DiffDate(date('Y-m-d', $item['entry_date']), $time);
                    $item['entry_date_limit'] = $year . '年' . $month . '月' . $days . '日';
                } else {
                    $item['entry_date_limit'] = '';
                }
                $item['position_name'] = '';
            }
            $department_ids  = array_unique(array_column($this->return_data['data']['data'], 'department_id'));
            $department_list = Structure::whereIn('id', $department_ids)->select(['id', 'name'])->get();
            $department_list = array_column(\Common::laravel_to_array($department_list), 'name', 'id');
            foreach ($this->return_data['data']['data'] as &$item) {
                // 获取用户缓存信息
                $item['department_name'] = $department_list[$item['department_id']] ?? '';
            }
        } catch (\Exception $e) {
            $code = $e->getCode();
            var_dump($e->getLine());
            var_dump($e->getMessage());die();
            if (in_array($code, array_keys(StatusConstants::STATUS_TO_CODE_MAPS))) {
                $this->return_data['code'] = $code;
            } else {
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        }
        return $this->return_data;
    }

    /**
     * +----------------------------------------------------------
     * 功能：计算两个日期相差 年 月 日
     * +----------------------------------------------------------
     * @param date $date1 起始日期
     * @param date $date2 截止日期日期
     * +----------------------------------------------------------
     * @return array
     * +----------------------------------------------------------
     */
    public function DiffDate($date1, $date2)
    {
        if (strtotime($date1) > strtotime($date2)) {
            $ymd   = $date2;
            $date2 = $date1;
            $date1 = $ymd;
        }
        list($y1, $m1, $d1) = explode('-', $date1);
        list($y2, $m2, $d2) = explode('-', $date2);
        $y    = $m = $d = $_m = 0;
        $math = ($y2 - $y1) * 12 + $m2 - $m1;
        $y    = round($math / 12);
        $m    = intval($math % 12);
        $d    = (mktime(0, 0, 0, $m2, $d2, $y2) - mktime(0, 0, 0, $m2, $d1, $y2)) / 86400;
        if ($d < 0) {
            $m -= 1;
            $d += date('j', mktime(0, 0, 0, $m2, 0, $y2));
        }
        $m < 0 && $y -= 1;
        return array($y, $m, $d);
    }

    // 获取全部用户信息并更新
    public function reset_user_all()
    {
        try {
            $where       = [
                'status' => ModelConstants::COMMON_STATUS_NORMAL
            ];
            $user_result = User::where($where)->get();
            $user_result = \Common::laravel_to_array($user_result);
            $user_ids    = array_column($user_result, 'id');
            $this->user_reset($user_ids);
        } catch (\Exception $e) {
            Log::NewInfo([
                'line' => $e->getLine(),
                'msg'  => $e->getMessage(),
            ], __FUNCTION__ . ' ');
        } finally {
            return true;
        }
    }


    /**
     * 获取民族列表
     */
    public function get_nation_list()
    {
        $this->return_data['data'] = Nation::select(['id', 'name'])->get();
        return $this->return_data;
    }

    /**
     * 获取新员工介绍(最近一月的)
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_new_staff_list($limit, $offset)
    {
        try {
            $now_time                           = time();
            $last_month_time                    = $now_time - \CacheConstants::CACHE_EXPIRE_TIME_HALF_ONE_WEEK;
            $start_time                         = date("Y-m-d H:i:s", $now_time);
            $end_time                           = date("Y-m-d H:i:s", $last_month_time);
            $where                              = [
                'is_admin' => 0,
                ['created_at', '>=', $end_time],
                ['created_at', '<=', $start_time],
                ['position_id', '!=', 0]  // todo 获取有岗位的信息(演示用) 后续需要删掉这一条限制
            ];
            $this->return_data['data']['data']  = User::where($where)->select(['id'])->orderBy('created_at', 'desc')->limit($limit)->offset($offset)->get();
            $this->return_data['data']['total'] = User::where($where)->count();
            foreach ($this->return_data['data']['data'] as &$item) {
                $user_info = $this->get_user_info_by_id($item['id']);
                if ($user_info['code'] == 200) {
                    $item['name']            = $user_info['data']['name'];
                    $item['avatar']          = $user_info['data']['avatar'];
                    $item['education_name']  = $user_info['data']['education_name'];
                    $item['department_name'] = $user_info['data']['department_name'];
                    $item['position_name']   = $user_info['data']['position_name'];
                }
            }
        } catch (\Exception $e) {
        } finally {
            return $this->return_data;
        }
    }


    /**
     * 获取员工分布
     * @return array
     */
    public function get_staff_found_info()
    {
        try {
            // 查询一级组织
            $result_structure     = Structure::where(['pid' => 1])->get();
            $result_structure     = \Common::laravel_to_array($result_structure);
            $result_structure_ids = array_column($result_structure, 'id');
            $result_structure_ids = implode(',', $result_structure_ids);
//            $sql = "select a.name,b.gender,count(b.gender) total from common_structure a left join common_user b on a.id = b.department_id
//                where b.structure_id in (".$result_structure_ids.") or b.department_id in (".$result_structure_ids.")
//                GROUP BY a.id,b.gender";
//            $sql = "select a.name,b.gender,count(b.gender) total from common_structure a left join common_user b on a.id = b.department_id GROUP BY a.id,b.gender";

            $sql     = "SELECT b.name,a.gender,count(a.gender) total  FROM common_user a LEFT JOIN common_structure b ON a.structure_id = b.id
            WHERE a.structure_id IN (" . $result_structure_ids . ") or a.department_id in (" . $result_structure_ids . ")
            GROUP BY a.structure_id,a.department_id,a.gender";
            $result  = DB::connection('mysql_common')->select($sql);
            $result  = array_map('get_object_vars', $result);
            $tmp_arr = [];
            foreach ($result as $item) {
                $arr = [
                    'body_total' => 0,
                    'girl_total' => 0
                ];

                if ($item['gender'] == UserConstants::GENDER_BODY) {
                    $arr['body_total'] = $item['total'];
                } elseif ($item['gender'] == UserConstants::GENDER_GIRL) {
                    $arr['girl_total'] = $item['total'];
                }

                if (!empty($item['gender'])) {
                    if (isset($tmp_arr[$item['name']])) {
                        if ($item['gender'] == UserConstants::GENDER_BODY) {
                            $tmp_arr[$item['name']]['body_total'] = $item['total'];
                        } elseif ($item['gender'] == UserConstants::GENDER_GIRL) {
                            $tmp_arr[$item['name']]['girl_total'] = $item['total'];
                        }
                    } else {
                        $tmp_arr[$item['name']] = $arr;
                    }
                }
            }

            $department   = ['部门'];
            $gender_body  = ['男'];
            $gender_girls = ['女'];
            foreach ($tmp_arr as $key => $val) {
                $department[]   = $key;
                $gender_body[]  = $val['body_total'];
                $gender_girls[] = $val['girl_total'];
            }
            $data                              = [
                $department,
                $gender_body,
                $gender_girls
            ];
            $this->return_data['data']['data'] = $data;
        } catch (\Exception $e) {
            var_dump($e->getLine());
            var_dump($e->getMessage());
            die();
        } finally {
            return $this->return_data;
        }
    }


    /**
     * 获取员工分布
     * @return array
     */
    public function update_structure_data()
    {
        try {
            // 需手动建一个顶级组织 百林科医药科技(上海)有限公司  组织-部门
//            $sql = "select * from common_user_ding_dept";  update common_structure set group_type=2 where pid !=0 or pid != 1
//            $result = DB::connection('mysql_common')->select($sql);
//            $result = array_map('get_object_vars', $result);
//            $data = [];
//            foreach ($result as $item){
//                $arr = [];
//                $arr['id'] = $item['dept_id'];
//                $arr['pid'] = $item['pid'];
//                $arr['name'] = $item['name'];
//                $arr['manager_id'] = $item['manager_id'];
//                $arr['order'] = $item['order'];
//                $arr['group_type'] = 1;
//                $data[] = $arr;
//            }
//
//            $res = Structure::insert($data);


            // 需手动建一个顶级组织 百林科医药科技(上海)有限公司  组织-部门
//            $sql = "select * from common_user_ding_user limit 0,50";
//            $sql = "select * from common_user_ding_user";
//            $result = DB::connection('mysql_common')->select($sql);
//            $result = array_map('get_object_vars', $result);
//            $data = [];
//            $data_postion = [];
//            $my_config = \Common::get_config();
////            DB::connection('mysql_common')->beginTransaction();
//            foreach ($result as $item){
//                $salt = \Common::get_random_str(4);
//                $arr = [];
//                $tag = 0;
//                if (!empty($item['title'])){
//                    $position = [
//                        'name' => $item['title'],
//                        'structure_id' => $item['dept_id_list'],
//                    ];
//                    $create_user_result = Position::create($position);
//                    $arr['position_id'] = $create_user_result->id;
//                    $tag = 1;
//                }
//                $arr['orgin_id'] = $item['userid'];
//                $arr['account'] = $item['name'];
//                $arr['name'] = $item['name'];
//                $arr['email'] = $item['email'];
//                $arr['phone'] = $item['mobile'];
//                $arr['job_number'] = $item['job_number'];
//                $arr['uuid'] = \Common::guid();
//                $arr['salt'] = $salt;
//                $arr['pwd'] = sha1($salt.sha1($my_config['user_safe']['default_password'])); // 初始密码为默认设置;
//
//                if ($tag){
//                    $data_postion[] = $arr;
//                }else{
//                    $data[] = $arr;
//                }
//            }
//            User::insert($data);
//            User::insert($data_postion);
//            DB::connection('mysql_common')->commit();
            // 处理员工部门关系
//            $sql = "select * from common_user";
//            $result = DB::connection('mysql_common')->select($sql);
//            $result = array_map('get_object_vars', $result);
//            foreach ($result as $item){
//                // 根据orgin_id查询部门信息
//                $sql = "select * from common_user_ding_user where userid = '".$item['orgin_id']."'";
//                $result = DB::connection('mysql_common')->select($sql);
//                $result_user = array_map('get_object_vars', $result);
//                if (isset($result_user[0]['dept_id_list'])){
//                    $dept_id_list = explode(',',$result_user[0]['dept_id_list']);
//
//                    $structure_id = 0;
//                    $department_id = 0;
//                    foreach ($dept_id_list as $val){
//                        $find = Structure::find($val)->toArray();
//                        if ($find['group_type'] == 1){
//                            $structure_id = $val;
//                        }else{
//                            $department_id = $val;
//                        }
//                        $arr = [
//                            'user_id' => $item['id'],
//                            'structure_id' => $val,
//                        ];
//                        StructureUser::insert($arr);
//                    }
//                    $tmp_arr = [
//                        'structure_id' => $structure_id,
//                        'department_id' => $department_id,
//                    ];
//                    User::where(['id' => $item['id']])->update($tmp_arr);
//                }
//            }

            // 更新部门主管id对应关系
//            $sql = "select * from common_structure";
//            $result = DB::connection('mysql_common')->select($sql);
//            $result = array_map('get_object_vars', $result);
//            foreach ($result as $item){
//                if ($item['name']){
//                    // 根据name查询部门信息
//                    $sql = "select * from common_user_ding_dept where name = '".$item['name']."'";
//                    $result = DB::connection('mysql_common')->select($sql);
//                    $result = array_map('get_object_vars', $result);
//                    if ($result){
////                        var_dump($result[0]['manager_id']);die();
//                        if (isset($result[0]['manager_id'])){
//                            // 根据orgin_id查询用户信息
//                            $sql = "select * from common_user where orgin_id = '".$result[0]['manager_id']."'";
//                            $result_user = DB::connection('mysql_common')->select($sql);
//                            $result_user = array_map('get_object_vars', $result_user);
//                            if (isset($result_user[0]['id'])){
//                                $arr = [
//                                    'manager_id' => $result_user[0]['id']
//                                ];
//                                Structure::where(['id' => $item['id']])->update($arr);
//                            }
//                        }
//                    }
//                }
//
//            }
            // 更新user_info表入职时间
//            $sql = "select * from common_user_ding_user";
//            $result = DB::connection('mysql_common')->select($sql);
//            $result = array_map('get_object_vars', $result);
//            foreach ($result as $item){
//                if ($item['hired_date']){
//                    $entry_date = substr($item['hired_date'],0,10);
//                    // 根据name查询部门信息
//                    $sql = "select * from common_user where orgin_id = '".$item['userid']."'";
//                    $result = DB::connection('mysql_common')->select($sql);
//                    $result = array_map('get_object_vars', $result);
//                    if ($result){
////                        var_dump($result[0]['manager_id']);die();
//                        if (isset($result[0]['id'])){
//                            // 根据orgin_id查询用户信息
//                            $sql = "select * from common_user where orgin_id = '".$result[0]['manager_id']."'";
//                            $result_user = DB::connection('mysql_common')->select($sql);
//                            $temp_arr = [
//                                'user_id' => $result[0]['id'],
//                                'entry_date' => $entry_date
//                            ];
//                            UserInfo::insert($temp_arr);
//                        }
//                    }
//                }
//            }

        } catch (\Exception $e) {
//            DB::connection('mysql_common')->rollBack();
            var_dump($e->getLine());
            var_dump($e->getMessage());
            die();
        } finally {
            return $this->return_data;
        }
    }


    public function reset_password($interface, $data)
    {
        // 1 发送验证码接口
        // 2 核实验证码接口
        // 3 重置密码接口 需要再一起提供验证码
        if ($interface == 1) {
            // 查询用户邮箱
            $has_account = User::where('account', $data['username'])->count();
            if ($has_account == 0) {
                $this->return_data['code'] = StatusConstants::ERROR_ACCOUNT_CHECK_FAIL;
                return $this->return_data;
            }

            $email = User::where('account', $data['username'])->select("email")->first();

            if (empty($email['email'])) {

                $this->return_data['code'] = StatusConstants::ERROR_INVALID_USER_EMAIL;
                $this->return_data['data'] = $email;
                return $this->return_data;
            }
            $this->return_data['data'] = ['email' => $email];
            return $this->return_data;
        }

        if ($interface == 2) {

            $username = $data['username'];
            // $code     = Redis::get("remote:email_code:$username");
            $code = rand(100000, 999999);
            // $email_result = $this->_redis->setex("remote:email_code:$username", \CacheConstants::CACHE_EXPIRE_TIME_ONE_DAY, $code);
            // var_dump($email_result);exit();

            if ($code == NULL) {
                // 验证码为空，生成验证码
                $code = rand(100000, 999999);
                // $this->_redis->select(1);
                // $this->_redis->setex("remote:email_code:toUsername", \CacheConstants::CACHE_EXPIRE_TIME_ONE_DAY, $code);

                // $res = $this->_redis->hGetAll('user:info:10');
                // var_dump($this->_redis );exit();
                // Redis::setex("remote:email_code:$username", 86400, $code);
            }

            // 写入数据库
            $email_code = User::where('account', $data['username'])->update(
                ['email_code' => $code]
            );

            $email_info = User::where('account', $data['username'])->select("email")->first();
            // 发送找回密码邮件验证码
            $send = new AssessmentService();

            $content['name']  = $data['username'];
            $content['email'] = $email_info['email'];
            $content['code']  = $code;
            $value['content'] = $content;
            $value['view']    = "email_code";
            $value['to']      = $email_info['email'];
            $value['name']    = $email_info['name'];
            $value['user_id'] = $email_info['id'];
            $value['subject'] = "用户[" . $data['username'] . "]BIO-CLOUD系统账户密码重置";


            $email_result     = $send->send_email($value);

            $this->return_data['data'] = $email_result;
            return $this->return_data;
        }

        if ($interface == 3) {

            // 验证邮箱验证码
            $has_account = User::where('account', $data['username'])
                ->where('email_code', $data['email_code'])
                ->count();
            if ($has_account == 0) {
                $this->return_data['code'] = StatusConstants::ERROR_INVALID_USER_EMAIL_CODE;
                return $this->return_data;
            }


            $this->return_data['data'] = $has_account;
            return $this->return_data;

        }
        if ($interface == 4) {

            // 验证邮箱验证码,开始改密码
            $has_account = User::where('account', $data['username'])
                ->where('email_code', $data['email_code'])
                ->count();

            if ($has_account == 0) {
                $this->return_data['code'] = StatusConstants::ERROR_INVALID_USER_EMAIL_CODE;
                return $this->return_data;
            }

            $email_info = User::where('account', $data['username'])->select("email", "salt","id")->first();
            $salt       = $email_info['salt'];

            $pwd     = $data['password'];
            $new_pwd = sha1($salt . sha1($pwd));

            // 新密码，写入数据库
            $set_new_pwd = User::where('account', $data['username'])->update(
                ['pwd' => $new_pwd]
            );
            // $set_new_pwd = User::where('account', $data['username'])->update(
            //     ['pwd' => $new_pwd, 'email_code' => '']
            // );
            $username    = $data['username'];
            $this->_redis->setex("remote:email_code:$username", 0, 0);

            $this->user_reset([$email_info['id']]);

            $this->return_data['data'] = $set_new_pwd;
            return $this->return_data;
        }
        return 0;
    }


    /**
     * 去除数组空值
     * @return mixed
     */
    public function test_redis()
    {
        $res = $this->_redis->hGetAll('user:info:10');
        var_dump($res);
        var_dump($this->_redis);die();
    }


}
